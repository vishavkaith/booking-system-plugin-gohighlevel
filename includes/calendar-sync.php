<?php
if (!defined('ABSPATH')) exit;

/**
 * Fetch appointments from GoHighLevel Calendar
 */
function kvw_fetch_ghl_appointments($calendar_id) {
    $api_key = get_option('kvw_api_key');
    if (!$api_key || !$calendar_id) return [];

    $url = "https://rest.gohighlevel.com/v1/appointments?calendarId={$calendar_id}";
    
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['appointments'] ?? [];
}

/**
 * Return an array of unavailable time slots
 */
function kvw_get_unavailable_time_slots($calendar_id) {
    $appointments = kvw_fetch_ghl_appointments($calendar_id);
    $unavailable = [];

    foreach ($appointments as $appt) {
        $start = strtotime($appt['startTime']);
        $end   = strtotime($appt['endTime']);

        $unavailable[] = [
            'date' => date('Y-m-d', $start),
            'start_time' => date('H:i', $start),
            'end_time'   => date('H:i', $end),
        ];
    }

    return $unavailable;
}