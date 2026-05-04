<?php
/**
 * Plugin Name: WooCommerce GHL Booking Sync (v5.0)
 * Description: Sync WooCommerce bookings to GoHighLevel, manage services/packages, and optionally sync to Google Calendar.
 * Version: 5.0
 * Author: Vishav Kaith
 */

if (!defined('ABSPATH')) exit;

// Define plugin base path
define('KVW_PLUGIN_DIR', plugin_dir_path(__FILE__));
if (!defined('KVW_GHL_PLUGIN_DIR')) {
    define('KVW_GHL_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
/**
 * Load core plugin components
 */
add_action('plugins_loaded', function() {
//  require_once KVW_GHL_PLUGIN_DIR . 'admin/functions.php';
require_once KVW_GHL_PLUGIN_DIR . 'admin/admin-menu.php';
require_once KVW_GHL_PLUGIN_DIR . 'admin/product-meta.php';
require_once KVW_GHL_PLUGIN_DIR . 'includes/admin-pages.php';
require_once KVW_GHL_PLUGIN_DIR . 'includes/frontend-booking.php';
require_once KVW_GHL_PLUGIN_DIR . 'includes/sync-ghl.php';
require_once KVW_GHL_PLUGIN_DIR . 'includes/calendar-sync.php';
});

/**
 * Trigger GHL sync and Google Calendar sync on order status
 */
//add_action('woocommerce_order_status_processing', 'kvw_handle_ghl_contact_sync');
/*add_action('woocommerce_order_status_completed', 'kvw_handle_ghl_contact_sync');
function kvw_handle_ghl_contact_sync($order_id) {
    $booking_data = get_post_meta($order_id, 'kvw_booking_data', true);
    if (!$booking_data) return;

    kvw_sync_contact_to_ghl($order_id, $booking_data);
    kvw_sync_to_google_calendar($order_id, $booking_data);
}
*/
/**
 * Booking form logic: cart, order, display
 */
add_action('woocommerce_before_calculate_totals', 'kvw_adjust_cart_item_price', 20, 1);
function kvw_adjust_cart_item_price($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item) {
        if (!empty($cart_item['kvw_booking_data']['price'])) {
            $price = floatval($cart_item['kvw_booking_data']['price']);
            error_log("Setting price: $price for item: " . $cart_item['data']->get_name());
            $cart_item['data']->set_price($price);
        }
    }
}

add_filter('woocommerce_add_cart_item_data', 'kvw_save_booking_fields_to_cart', 10, 2);
function kvw_save_booking_fields_to_cart($cart_item_data, $product_id) {
    $services = isset($_POST['kvw_selected_services']) ? array_map('sanitize_text_field', $_POST['kvw_selected_services']) : [];
    $package  = sanitize_text_field($_POST['kvw_selected_package'] ?? '');

    $cart_item_data['kvw_booking_data'] = [
        'package'         => $package,
        'services'        => empty($package) ? $services : [], // main services only if no package
        'extra_services'  => !empty($package) ? $services : [], // move services to extra if package selected
        'staff'           => sanitize_text_field($_POST['kvw_selected_staff'] ?? ''),
        'staff_id'        => sanitize_text_field($_POST['kvw_selected_staff_id'] ?? ''),
        'duration'     => sanitize_text_field($_POST['kvw_duration'] ?? ''),
        'calendar_id'     => sanitize_text_field($_POST['kvw_selected_calendar_id'] ?? ''),

        'datetime'        => sanitize_text_field($_POST['kvw_selected_datetime'] ?? ''),
        'price'           => floatval($_POST['kvw_selected_price'] ?? 0),
    ];

    kvw_log_to_file('post data', $_POST);
    return $cart_item_data;
}
add_action('woocommerce_checkout_create_order_line_item', 'kvw_save_booking_data_to_order_item', 10, 4);
function kvw_save_booking_data_to_order_item($item, $cart_item_key, $values, $order) {
    if (!empty($values['kvw_booking_data'])) {
        $item->add_meta_data('Booking Info', json_encode($values['kvw_booking_data']), true);
    }
}

add_action('woocommerce_checkout_create_order', 'kvw_save_booking_meta_to_order_fixed', 20, 2);
function kvw_save_booking_meta_to_order_fixed($order, $data) {
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!empty($cart_item['kvw_booking_data'])) {
            foreach ($cart_item['kvw_booking_data'] as $key => $value) {
                if (is_array($value)) $value = implode(",", $value);
                            kvw_log_to_file("Values",$value);

                $order->add_meta_data("{$key}", sanitize_text_field($value));
            }
        }
    }
}

add_action('woocommerce_new_order_item', 'kvw_add_custom_order_item_meta_new', 10, 3);
function kvw_add_custom_order_item_meta_new($item_id, $item, $order_id) {
    if ($item->is_type('line_item')) {
        $booking = json_decode($item->get_meta('Booking Info'), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($booking)) {

            // Save each field individually so it can be retrieved in kvw_handle_new_order_sync
            if (!empty($booking['service'])) {
                wc_add_order_item_meta($item_id, 'kvw_service', sanitize_text_field($booking['service']));
            }
            if (!empty($booking['staff'])) {
                wc_add_order_item_meta($item_id, 'kvw_staff', sanitize_text_field($booking['staff']));
            }
            if (!empty($booking['staff_id'])) {
                wc_add_order_item_meta($item_id, 'kvw_staff_id', sanitize_text_field($booking['staff_id']));
            }
            if (!empty($booking['calendar_id'])) {
                wc_add_order_item_meta($item_id, 'kvw_calendar_id', sanitize_text_field($booking['calendar_id']));
            }
            if (!empty($booking['duration'])) {
                wc_add_order_item_meta($item_id, 'kvw_duration', sanitize_text_field($booking['duration']));
            }

            if (!empty($booking['datetime'])) {
                wc_add_order_item_meta($item_id, 'kvw_datetime', sanitize_text_field($booking['datetime']));
            }
            if (!empty($booking['package'])) {
                wc_add_order_item_meta($item_id, 'kvw_package', sanitize_text_field($booking['package']));
            }
            if (!empty($booking['extra_services'])) {
                wc_add_order_item_meta($item_id, 'kvw_extra_services', implode(', ', array_map('sanitize_text_field', $booking['extra_services'])));
            }
            if (!empty($booking['price'])) {
                wc_add_order_item_meta($item_id, 'kvw_total_price', floatval($booking['price']));
            }

            // Optional: keep the summary
            $label = !empty($booking['package']) ? 'Package' : 'Service';
            $name = $booking['package'] ?? $booking['extra_services'][0] ?? $booking['services'][0] ?? 'N/A';
            $summary = "$label: " . esc_html($name);
            $summary .= ', Staff: ' . esc_html($booking['staff'] ?? 'N/A');
            $summary .= ', Date: ' . esc_html($booking['datetime'] ?? 'N/A');
            wc_add_order_item_meta($item_id, '_custom_meta', $summary);
        }
    }
}
/**
 * Cart display enhancements
 */
add_filter('woocommerce_cart_item_name', 'kvw_cart_item_name_extra_info', 20, 3);
function kvw_cart_item_name_extra_info($product_name, $cart_item, $cart_item_key) {
    if (!empty($cart_item['kvw_booking_data'])) {
        $d = $cart_item['kvw_booking_data'];
        $extra = '';
        if (!empty($d['package'])) $extra .= 'Package: ' . esc_html($d['package']) . '<br>';
        if (!empty($d['extra_services']) && is_array($d['extra_services'])) {
            $extra .= 'Extra Services: ' . implode(', ', array_map('esc_html', $d['extra_services'])) . '<br>';
        }
        if (!empty($d['services']) && is_array($d['services']) && empty($d['package'])) {
            $extra .= 'Services: ' . implode(', ', array_map('esc_html', $d['services'])) . '<br>';
        }
        $extra .= 'Staff: ' . esc_html($d['staff']) . '<br>';
        $extra .= 'Date: ' . esc_html($d['datetime']);
        return $product_name . '<br><small>' . $extra . '</small>';
    }
    return $product_name;
}

/**
 * Show booking form on specific product category
 */
add_action('woocommerce_before_add_to_cart_button', 'kvw_show_booking_form_for_category');
function kvw_show_booking_form_for_category() {
    global $product;
    if (!$product || !is_product()) return;

    if (has_term('massage', 'product_cat', $product->get_id())) {
        $booking_form_path = KVW_PLUGIN_DIR . 'includes/frontend-booking.php';
        if (file_exists($booking_form_path)) {
            require_once $booking_form_path;
        } else {
            echo "<strong>Booking form file not found.</strong>";
        }
    }
}
// Hook to WooCommerce order creation
//add_action('woocommerce_checkout_order_processed', 'kvw_handle_new_order_sync', 20, 1);

/**
 * Handle WooCommerce order and sync to GHL.
 */
function kvw_handle_new_order_sync1($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    // Fetch booking data from order meta
    $booking_data = [
        'service'     => get_post_meta($order_id, 'kvw_service', true),
        'staff'       => get_post_meta($order_id, 'kvw_staff', true),
        'staff_id'    => get_post_meta($order_id, 'kvw_staff_id', true),
        'calendar_id' => get_post_meta($order_id, 'kvw_calendarId', true),
        'duration' => get_post_meta($order_id, 'kvw_duration', true),

        'datetime'    => get_post_meta($order_id, 'kvw_datetime', true),
        'package'     => get_post_meta($order_id, 'kvw_package', true),
        'extra'       => get_post_meta($order_id, 'kvw_extra_services', true),
        'price'       => get_post_meta($order_id, 'kvw_total_price', true),
        'name'        => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'email'       => $order->get_billing_email(),
        'phone'       => $order->get_billing_phone(),
    ];

    // Log final payload
    kvw_log_to_file('[GHL] Final Booking Payload: ' ,$booking_data);

    // Sync contact
    $contact_id = kvw_sync_contact_to_ghl($booking_data, $order_id);

    if ($contact_id && !empty($booking_data['calendar_id'])) {
        // Convert datetime to ISO and calculate duration
        $start_time = date('c', strtotime($booking_data['datetime']));
        $duration_minutes = $booking_data['duration'];
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

        kvw_log_to_file('[GHL] Appointment Payload: ' . print_r($appointment_payload, true));
        kvw_sync_appointment_to_ghl($appointment_payload, $order_id);
    } else {
        kvw_log_to_file("[GHL] Skipped appointment sync: No calendar ID.");
    }
}