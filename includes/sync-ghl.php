<?php
if (!defined('ABSPATH')) exit;

// Hook into WooCommerce order complete
add_action('woocommerce_thankyou', 'kvw_handle_new_order_sync', 10, 1);

function kvw_handle_new_order_sync($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    foreach ($order->get_items() as $item_id => $item) {
        $booking_data = [
            'service'     => wc_get_order_item_meta($item_id, 'kvw_service', true),
            'staff'       => wc_get_order_item_meta($item_id, 'kvw_staff', true),
            'staff_id'    => wc_get_order_item_meta($item_id, 'kvw_staff_id', true),
            'calendar_id' => wc_get_order_item_meta($item_id, 'kvw_calendar_id', true),
            'datetime'    => wc_get_order_item_meta($item_id, 'kvw_datetime', true),
            'package'     => wc_get_order_item_meta($item_id, 'kvw_package', true),
            'extra'       => wc_get_order_item_meta($item_id, 'kvw_extra_services', true),
            'price'       => wc_get_order_item_meta($item_id, 'kvw_total_price', true),
            'name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email'       => $order->get_billing_email(),
            'phone'       => $order->get_billing_phone(),
        ];

        kvw_log_to_file('[GHL] Final Booking Payload new: ', $booking_data);

        $contact_id = kvw_sync_contact_to_ghl($booking_data, $order_id);
        kvw_log_to_file('[GHL] contact id: ', $contact_id);

        if ($contact_id && !empty($booking_data['calendar_id'])) {
            $start_time = date('c', strtotime($booking_data['datetime']));
            $duration_minutes = 30;
            $end_time = date('c', strtotime("+{$duration_minutes} minutes", strtotime($booking_data['datetime'])));

            $appointment_payload = [
                'calendarId'   => $booking_data['calendar_id'],
                'locationId'   => get_option('kvw_ghl_location_id'),
                'startTime'    => $start_time,
                'endTime'      => $end_time,
                'email'        => $booking_data['email'],
                'name'         => $booking_data['name'],
                'phone'        => $booking_data['phone'],
                'source'       => 'WooCommerce Plugin',
            ];

            kvw_log_to_file('[GHL] Appointment Payload: ' , $appointment_payload);
            kvw_create_ghl_appointment($appointment_payload, $order_id);
        }
    }
}