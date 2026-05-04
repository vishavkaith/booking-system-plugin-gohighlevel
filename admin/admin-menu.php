<?php
if (!defined('ABSPATH')) exit;
// Avoid redeclaring menu
if (!function_exists('kvw_register_admin_menu')) {

    add_action('admin_menu', function() {
        add_menu_page(
            'Woo GHL Booking',
            'Woo GHL Booking',
            'manage_options',
            'woo-ghl-booking',
            function() {
                echo '<h2>Woo GHL Booking</h2><p>Use the submenu items to manage settings and sync options.</p>';
            },
            'dashicons-calendar-alt',
            56
        );

        add_submenu_page(
            'woo-ghl-booking',
            'Settings',
            'Settings',
            'manage_options',
            'woo-ghl-booking-settings',
            'kvw_render_settings_page'
        );

        add_submenu_page(
            'woo-ghl-booking',
            'Manage Services',
            'Manage Services',
            'manage_options',
            'woo-ghl-booking-services',
            'kvw_render_manage_services_page'
        );

        add_submenu_page(
            'woo-ghl-booking',
            'Staff Calendars',
            'Staff Calendars',
            'manage_options',
            'woo-ghl-booking-staff-calendars',
            'kvw_render_staff_calendars'
        );

        add_submenu_page(
            'woo-ghl-booking',
            'Sync Now',
            'Sync Now',
            'manage_options',
            'woo-ghl-booking-sync',
            'kvw_render_sync_now'
        );

        // 🆕 New submenu example: GHL API Logs
        add_submenu_page(
            'woo-ghl-booking',
            'API Logs',
            'API Logs',
            'manage_options',
            'woo-ghl-booking-api-logs',
            'kvw_render_api_logs_page'
        );
    });
}
// REMOVE the auto submenu added by WordPress
/*add_action('admin_head', function() {
    remove_submenu_page('woo-ghl-booking', 'woo-ghl-booking');
});*/
// --- Services Page ---
function kvw_render_services1() {
    $services = get_option('kvw_manual_services', []);
    $packages = get_option('kvw_manual_packages', []);

    echo '<h2>Services</h2>';
    echo '<form method="post">';
    wp_nonce_field('kvw_save_services');
    echo '<table id="services-table" class="widefat fixed striped">';
    echo '<thead><tr><th>Name</th><th>Price</th><th>Description</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    if (!empty($services)) {
        foreach ($services as $service) {
            echo '<tr>';
            echo '<td><input type="text" name="kvw_services[name][]" value="' . esc_attr($service['name']) . '" class="regular-text" /></td>';
            echo '<td><input type="number" name="kvw_services[price][]" value="' . esc_attr($service['price']) . '" step="0.01" class="small-text" /></td>';
            echo '<td><textarea name="service_description[]" class="large-text">' . esc_textarea($service['description']) . '</textarea></td>';
            echo '<td><button type="button" class="button remove-service">Delete</button></td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '<button type="button" class="button" id="add-service">Add Service</button>';

    echo '<hr><h2>Packages</h2>';
    echo '<table id="packages-table" class="widefat fixed striped">';
    echo '<thead><tr><th>Name</th><th>Price</th><th>Included Services<br><small>(comma separated)</small></th><th>Extra Services<br><small>(comma separated)</small></th><th>Description</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    if (!empty($packages)) {
        foreach ($packages as $p) {
            $p['extras'] = isset($p['extras']) && is_array($p['extras']) ? $p['extras'] : [];
            $p['services'] = isset($p['services']) && is_array($p['services']) ? $p['services'] : [];

            echo '<tr>';
            echo '<td><input type="text" name="kvw_packages[name][]" value="' . esc_attr($p['name']) . '" class="regular-text" /></td>';
            echo '<td><input type="number" name="kvw_packages[price][]" value="' . esc_attr($p['price']) . '" step="0.01" class="small-text" /></td>';
            echo '<td><input type="text" name="kvw_packages[services][]" value="' . esc_attr(implode(', ', $p['services'])) . '" class="regular-text" /></td>';
            echo '<td><input type="text" name="package_extra_services[]" value="' . esc_attr(implode(', ', $p['extras'])) . '" class="regular-text" /></td>';
            echo '<td><textarea name="package_description[]" class="large-text">' . esc_textarea($p['description']) . '</textarea></td>';
            echo '<td><button type="button" class="button remove-package">Delete</button></td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';
    echo '<button type="button" class="button" id="add-package">Add Package</button>';
    echo '<p><input type="submit" name="kvw_save_services" class="button-primary" value="Save Services & Packages"></p>';
    echo '</form>';

    // JS
    ?>
    <script>
        document.getElementById('add-service').addEventListener('click', function () {
            const table = document.querySelector('#services-table tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="kvw_services[name][]" class="regular-text" /></td>
                <td><input type="number" name="kvw_services[price][]" step="0.01" class="small-text" /></td>
                <td><textarea name="service_description[]" class="large-text"></textarea></td>
                <td><button type="button" class="button remove-service">Delete</button></td>`;
            table.appendChild(row);
        });

        document.getElementById('add-package').addEventListener('click', function () {
            const table = document.querySelector('#packages-table tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="kvw_packages[name][]" class="regular-text" /></td>
                <td><input type="number" name="kvw_packages[price][]" step="0.01" class="small-text" /></td>
                <td><input type="text" name="kvw_packages[services][]" class="regular-text" /></td>
                <td><input type="text" name="package_extra_services[]" class="regular-text" /></td>
                <td><textarea name="package_description[]" class="large-text"></textarea></td>
                <td><button type="button" class="button remove-package">Delete</button></td>`;
            table.appendChild(row);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-service')) {
                e.target.closest('tr').remove();
            }
            if (e.target.classList.contains('remove-package')) {
                e.target.closest('tr').remove();
            }
        });
    </script>
    <?php
}

// --- Sync Now Page ---
function kvw_render_sync_now_page
() {
    if (isset($_POST['kvw_sync_now'])) {
        // Simulate sync logic
        set_transient('kvw_services_cache', ['Massage', 'Spa', 'Reflexology'], 12 * HOUR_IN_SECONDS);
        echo '<div class="updated"><p>Synced services from GHL (simulated).</p></div>';
    }

    echo '<div class="wrap"><h2>Woo GHL Booking - Sync Now</h2>
    <form method="post">
        <p><input type="submit" name="kvw_sync_now" class="button-secondary" value="Sync Now"></p>
 
   </form></div>';
}
function kvw_render_calendar_test_page() {
    if (isset($_POST['kvw_test_calendar'])) {
        $calendar_id = sanitize_text_field($_POST['calendar_id']);
        $slots = kvw_get_unavailable_time_slots($calendar_id);
        echo '<h3>Booked Time Slots:</h3><pre>';
        print_r($slots);
        echo '</pre>';
    }

    ?>
    <div class="wrap">
        <h2>Test GHL Calendar Bookings</h2>
        <form method="POST">
            <label>Calendar ID:</label><br>
            <input type="text" name="calendar_id" style="width: 300px;" required>
            <br><br>
            <button class="button button-primary" name="kvw_test_calendar">Fetch Booked Slots</button>
        </form>
    </div>
    <?php
}