<?php
if (!defined('ABSPATH')) exit;

function kvw_get_team_members() {
    $staff = [];
    $args = ['post_type' => 'cpt_team', 'posts_per_page' => -1, 'post_status' => 'publish'];
    $query = new WP_Query($args);
    foreach ($query->posts as $post) {
        $staff[] = $post->post_title;
    }
    return $staff;
}
function kvw_get_services() {
    $ghl_services = get_transient('kvw_services_cache');
    if ($ghl_services && is_array($ghl_services)) return $ghl_services;
    return get_option('kvw_manual_services', ['Massage', 'Spa']);
}
/*function kvw_handle_ghl_contact_sync($order_id) {
    $booking_data = get_post_meta($order_id, 'kvw_booking_data', true);
    if (!$booking_data) return;

    kvw_sync_contact_to_ghl($order_id, $booking_data);
}*/
// Show booking info in WooCommerce order admin
add_action('woocommerce_admin_order_data_after_order_details', 'kvw_display_booking_data_in_admin');

function kvw_display_booking_data_in_admin($order) {
    $booking = get_post_meta($order->get_id(), 'kvw_booking_data', true);
    if (!$booking || !is_array($booking)) return;

    echo '<div class="order_data_column">';
    echo '<h3>🧾 Booking Details</h3>';
    echo '<p><strong>Service:</strong> ' . esc_html($booking['service']) . '</p>';
    echo '<p><strong>Staff:</strong> ' . esc_html($booking['staff']) . '</p>';
    echo '<p><strong>Date/Time:</strong> ' . esc_html($booking['datetime']) . '</p>';
    echo '</div>';
}
function kvw_push_services_to_ghl_custom_value($services = []) {
    $api_key = get_option('kvw_api_key');
    $location_id = get_option('kvw_location_id');
    if (!$api_key || !$location_id) return;

    $value = implode(',', $services);

    $response = wp_remote_post('https://rest.gohighlevel.com/v1/custom-values', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode([
            'name' => 'service_list',
            'key'  => 'service_list',
            'value' => $value,
            'locationId' => $location_id
        ])
    ]);
}
add_action('init', 'kvw_register_team_cpt');
function kvw_register_team_cpt() {
      if (post_type_exists('cpt_team')) {
        return; // Don't register if already exists
    }
    $labels = [
        'name' => 'Team',
        'singular_name' => 'Team Member',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Team Member',
        'edit_item' => 'Edit Team Member',
        'new_item' => 'New Team Member',
        'view_item' => 'View Team Member',
        'search_items' => 'Search Team Members',
        'not_found' => 'No Team Members found',
        'menu_name' => 'Team',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title', 'editor', 'thumbnail'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'team'],
        'show_in_rest' => true,
    ];

    register_post_type('cpt_team', $args);
}
