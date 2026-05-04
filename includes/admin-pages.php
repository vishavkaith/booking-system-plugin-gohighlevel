<?php

if (!defined('ABSPATH')) exit;

// SETTINGS PAGE
function kvw_render_settings() {
    echo '<div class="wrap"><h1>Woo GHL Booking Settings</h1><p>Settings page under construction.</p></div>';
}

// SYNC NOW PAGE
function kvw_render_sync_now() {
    echo '<div class="wrap"><h1>Manual Sync</h1><p>This page will allow manual sync with GoHighLevel and Google Calendar.</p></div>';
}

// SERVICES PAGE
function kvw_render_manage_services_page() {
    $services = get_option('kvw_manual_services', []);
    $packages = get_option('kvw_manual_packages', []);
    $product_categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ]);

    if (isset($_POST['kvw_save_services'])) {
        // Save services
        $services = [];
        if (!empty($_POST['service_names'])) {
            foreach ($_POST['service_names'] as $i => $name) {
                if (trim($name) !== '') {
                    $services[] = [
                        'name' => sanitize_text_field($name),
                        'price' => isset($_POST['service_price'][$i]) ? sanitize_text_field($_POST['service_price'][$i]) : '0',
                        'duration' => isset($_POST['service_duration'][$i]) ? sanitize_text_field($_POST['service_duration'][$i]) : '0',

                        'description' => sanitize_text_field($_POST['service_descriptions'][$i] ?? ''),
                        'categories' => array_map('sanitize_text_field', $_POST['service_categories'][$i] ?? [])
                    ];
                }
            }
        }

        // Save packages
        $packages = [];
        if (!empty($_POST['package_names'])) {
            foreach ($_POST['package_names'] as $i => $name) {
                if (trim($name) !== '') {
                    $packages[] = [
                        'name' => sanitize_text_field($name),
                        'price' => isset($_POST['package_price'][$i]) ? sanitize_text_field($_POST['package_price'][$i]) : '0',
                        'duration' => isset($_POST['package_duration'][$i]) ? sanitize_text_field($_POST['package_duration'][$i]) : '0',

                        'description' => sanitize_text_field($_POST['package_descriptions'][$i] ?? ''),
                        'categories' => array_map('sanitize_text_field', $_POST['package_categories'][$i] ?? [])
                    ];
                }
            }
        }

        update_option('kvw_manual_services', $services);
        update_option('kvw_manual_packages', $packages);

        echo '<div class="notice notice-success"><p>Services and packages saved.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Manage Services & Packages</h1>
        <form method="post">
            <h2>Services</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>Name</th><th>Price</th><th>Duration</th><th>Description</th><th>Categories</th><th>Action</th></tr>
                </thead>
                <tbody id="services-table">
                    <?php foreach ($services as $i => $s): ?>
                        <tr>
                            <td><input type="text" name="service_names[]" value="<?= esc_attr($s['name']) ?>"></td>
                            <td><input type="text" name="service_price[]" value="<?= esc_attr($s['price'] ?? '0') ?>"></td>
                            
                            <td><input type="text" name="service_duration[]" value="<?= esc_attr($s['duration'] ?? '0') ?>"></td>

                            <td><input type="text" name="service_descriptions[]" value="<?= esc_attr($s['description']) ?>"></td>
                            <td>
                                <select name="service_categories[<?= $i ?>][]" multiple style="min-width:150px;">
                                    <?php foreach ($product_categories as $cat): ?>
                                        <option value="<?= esc_attr($cat->slug) ?>" <?= in_array($cat->slug, $s['categories'] ?? []) ? 'selected' : '' ?>>
                                            <?= esc_html($cat->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="add-service">Add Service</button></p>

            <h2>Packages</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr><th>Name</th><th>Price</th><th>Duration</th><th>Description</th><th>Categories</th><th>Action</th></tr>
                </thead>
                <tbody id="packages-table">
                    <?php foreach ($packages as $i => $p): ?>
                        <tr>
                            <td><input type="text" name="package_names[]" value="<?= esc_attr($p['name']) ?>"></td>
                            <td><input type="text" name="package_price[]" value="<?= esc_attr($p['price'] ?? '0') ?>"></td>
                            <td><input type="text" name="package_duration[]" value="<?= esc_attr($s['duration'] ?? '0') ?>"></td>

                            <td><input type="text" name="package_descriptions[]" value="<?= esc_attr($p['description']) ?>"></td>
                            <td>
                                <select name="package_categories[<?= $i ?>][]" multiple style="min-width:150px;">
                                    <?php foreach ($product_categories as $cat): ?>
                                        <option value="<?= esc_attr($cat->slug) ?>" <?= in_array($cat->slug, $p['categories'] ?? []) ? 'selected' : '' ?>>
                                            <?= esc_html($cat->name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><button type="button" class="button remove-row">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="add-package">Add Package</button></p>

            <p><input type="submit" name="kvw_save_services" class="button-primary" value="Save All"></p>
        </form>
    </div>

    <script>
        const categoryOptions = `<?php foreach ($product_categories as $cat): ?>
            <option value="<?= esc_attr($cat->slug) ?>"><?= esc_html($cat->name) ?></option>
        <?php endforeach; ?>`;

        document.getElementById('add-service').addEventListener('click', function () {
            const index = document.querySelectorAll('#services-table tr').length;
            const row = `<tr>
                <td><input type="text" name="service_names[]" value=""></td>
                <td><input type="text" name="service_price[]" value=""></td>
                <td><input type="text" name="service_duration[]" value=""></td>

                <td><input type="text" name="service_descriptions[]" value=""></td>
                <td><select name="service_categories[${index}][]" multiple style="min-width:150px;">${categoryOptions}</select></td>
                <td><button type="button" class="button remove-row">Remove</button></td>
            </tr>`;
            document.getElementById('services-table').insertAdjacentHTML('beforeend', row);
        });

        document.getElementById('add-package').addEventListener('click', function () {
            const index = document.querySelectorAll('#packages-table tr').length;
            const row = `<tr>
                <td><input type="text" name="package_names[]" value=""></td>
                <td><input type="text" name="package_price[]" value=""></td>
                <td><input type="text" name="package_duration[]" value=""></td>

                <td><input type="text" name="package_descriptions[]" value=""></td>
                <td><select name="package_categories[${index}][]" multiple style="min-width:150px;">${categoryOptions}</select></td>
                <td><button type="button" class="button remove-row">Remove</button></td>
            </tr>`;
            document.getElementById('packages-table').insertAdjacentHTML('beforeend', row);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-row')) {
                e.target.closest('tr').remove();
            }
        });
    </script>
<?php
}
/*function kvw_render_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>Woo GHL Booking Settings</h1>';
    // Your settings UI here
    echo '</div>';
}*/
function kvw_render_settings_page(){
    if (isset($_POST['kvw_save_settings'])) {
        update_option('kvw_api_key', sanitize_text_field($_POST['kvw_api_key']));
        update_option('kvw_location_id', sanitize_text_field($_POST['kvw_location_id']));
        //update_option('kvw_google_sync', isset($_POST['kvw_google_sync']) ? 1 : 0);
        //update_option('kvw_google_calendar_id', sanitize_text_field($_POST['kvw_google_calendar_id']));
        //update_option('kvw_google_credentials', wp_kses_post($_POST['kvw_google_credentials']));
        update_option('kvw_default_duration', intval($_POST['kvw_default_duration']));
        update_option('kvw_buffer_time', intval($_POST['kvw_buffer_time']));
        update_option('kvw_push_services', isset($_POST['kvw_push_services']) ? 1 : 0);

        echo '<div class="updated"><p><strong>Settings saved successfully.</strong></p></div>';
    }

    // Get saved options with defaults
    $api_key = get_option('kvw_api_key', '');
    $location_id = get_option('kvw_location_id', '');
    $google_sync = get_option('kvw_google_sync', 0);
    $google_calendar_id = get_option('kvw_google_calendar_id', '');
    $google_credentials = get_option('kvw_google_credentials', '');
    $default_duration = get_option('kvw_default_duration', 60);
    $buffer_time = get_option('kvw_buffer_time', 15);
    $push_services = get_option('kvw_push_services', 0);

    echo '<div class="wrap">';
    echo '<h1>Woo GHL Booking - Settings</h1>';
    echo '<form method="post">';
    echo '<table class="form-table">';
    
    echo '<tr><th scope="row">GHL API Key</th><td><input type="text" name="kvw_api_key" value="' . esc_attr($api_key) . '" size="50" /></td></tr>';
    echo '<tr><th scope="row">GHL Location ID</th><td><input type="text" name="kvw_location_id" value="' . esc_attr($location_id) . '" size="30" /></td></tr>';
    //echo '<tr><th scope="row">Sync to Google Calendar</th><td><label><input type="checkbox" name="kvw_google_sync" value="1" ' . checked($google_sync, 1, false) . ' /> Enable</label></td></tr>';
    //echo '<tr><th scope="row">Google Calendar ID</th><td><input type="text" name="kvw_google_calendar_id" value="' . esc_attr($google_calendar_id) . '" size="40" /></td></tr>';
    //echo '<tr><th scope="row">Google Service Account JSON</th><td><textarea name="kvw_google_credentials" rows="10" cols="60">' . esc_textarea($google_credentials) . '</textarea><br><small>Paste the entire JSON credentials (as plain text).</small></td></tr>';
    echo '<tr><th scope="row">Default Appointment Duration (in minutes)</th><td><input type="number" name="kvw_default_duration" value="' . esc_attr($default_duration) . '" /></td></tr>';
    echo '<tr><th scope="row">Buffer Time Between Appointments (in minutes)</th><td><input type="number" name="kvw_buffer_time" value="' . esc_attr($buffer_time) . '" /></td></tr>';
    echo '<tr><th scope="row">Push Services to GHL Custom Value</th><td><label><input type="checkbox" name="kvw_push_services" value="1" ' . checked($push_services, 1, false) . ' /> Yes</label></td></tr>';
    
    echo '</table>';
    echo '<p><input type="submit" name="kvw_save_settings" class="button-primary" value="Save Settings" /></p>';
    echo '</form>';
    echo '</div>';
}
function kvw_render_staff_calendars() {
    // SYNC staff & calendars on POST
    error_log('kvw_render_staff_calendars called');

    if (isset($_POST['kvw_sync_staff_ghl'])) {
        $staff_data = kvw_fetch_ghl_team_members_with_calendars(); // ← function we defined earlier
        update_option('kvw_synced_staff', $staff_data);
        echo '<div class="updated"><p><strong>Staff fetched successfully from GHL.</strong></p></div>';
    }

    // Get saved synced list
    $synced_staff = get_option('kvw_synced_staff', []);

    echo '<div class="wrap"><h2>Woo GHL Booking - Staff Calendars</h2>';

    echo '<form method="post">';
    submit_button('Sync Now from GHL', 'secondary', 'kvw_sync_staff_ghl');
    echo '</form>';
error_log('synced_staff count: ' . count($synced_staff));

    if (empty($synced_staff)) {
        echo '<p>No staff synced yet. Click the Sync button above.</p>';
        echo '</div>';
        return;
    }

    // Save calendar ID overrides (optional if editable)
    if (isset($_POST['kvw_save_staff_calendars'])) {
        $new_ids = [];
        foreach ($synced_staff as $staff) {
            $field = 'calendar_id_' . sanitize_title($staff['name']);
            $new_ids[$staff['name']] = sanitize_text_field($_POST[$field] ?? $staff['calendar_id']);
        }
        update_option('kvw_staff_calendars_override', $new_ids);
        echo '<div class="updated"><p>Calendar IDs saved.</p></div>';
    }

    // Show table of staff + calendar IDs
    $saved_ids = get_option('kvw_staff_calendars_override', []);

    echo '<form method="post">';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Staff Name</th><th>Calendar ID</th></tr></thead><tbody>';

    foreach ($synced_staff as $staff) {
        error_log('Staff member: ' . print_r($staff, true));

        $name = $staff['name'];
        $default_id = $staff['calendar_id'];
        $saved_value = $saved_ids[$name] ?? $default_id;
        $field_name = 'calendar_id_' . sanitize_title($name);
        echo "<tr><td>" . esc_html($name) . "</td>";
        echo "<td><input type='text' name='$field_name' value='" . esc_attr($saved_value) . "' style='width:100%;' /></td></tr>";
    }

    echo '</tbody></table>';
    submit_button('Save Mappings', 'primary', 'kvw_save_staff_calendars');
    echo '</form></div>';
}
function kvw_render_api_logs_page() {
     echo '<div class="wrap"><h1>GHL API Logs</h1>';
    echo '<p>Below are recent logs of API calls to GoHighLevel.</p>';
$staff = kvw_fetch_ghl_team_members_with_calendars();

    echo '<pre>';
    print_r($staff);
    echo '</pre>';

    echo '</div>';
    $logs = get_option('kvw_ghl_api_logs', []);

    if (empty($logs)) {
        echo '<p>No logs found.</p>';
    } else {
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Timestamp</th><th>Message</th><th>Data</th></tr></thead><tbody>';

        foreach (array_reverse($logs) as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log['timestamp']) . '</td>';
            echo '<td>' . esc_html($log['message']) . '</td>';
            echo '<td><pre>' . esc_html(print_r($log['data'], true)) . '</pre></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
function kvw_fetch_ghl_team_members_with_calendars() {
    $api_key = get_option('kvw_api_key');
    if (!$api_key) return [];

    // Fetch all users
    $users_response = wp_remote_get('https://rest.gohighlevel.com/v1/users', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
    ]);
    if (is_wp_error($users_response)) {
        kvw_log_ghl_api('Error fetching GHL users', $users_response->get_error_message());
        return [];
    }
    $users_body = json_decode(wp_remote_retrieve_body($users_response), true);
    $users = $users_body['users'] ?? [];

    // Fetch teams/calendars
    $teams_response = wp_remote_get('https://rest.gohighlevel.com/v1/calendars/teams', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
    ]);
    if (is_wp_error($teams_response)) {
        kvw_log_ghl_api('Error fetching GHL calendars/teams', $teams_response->get_error_message());
        return [];
    }
    $teams_body = json_decode(wp_remote_retrieve_body($teams_response), true);
    $teams = $teams_body['teams'] ?? [];

    $staff = [];

    // Create a map of user IDs to user info
    $users_map = [];
    foreach ($users as $user) {
        $users_map[$user['id']] = $user;
    }

    // Loop through each team (calendar group)
    foreach ($teams as $team) {
        $calendarName = $team['calendarConfig']['calendarName'] ?? '';
        $calendarSlug = $team['calendarConfig']['slug'] ?? '';
        $team_id = $team['id'] ?? '';

        // If team members info is missing or empty, assign all users to all groups
        if (empty($team['members'])) {
            // Assign ALL users to this team as fallback
            foreach ($users as $user) {
                $staff[] = [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? '',
                    'email' => $user['email'] ?? '',
                    'phone' => $user['phone'] ?? '',
                    'calendar_id' => $calendarName,
                    'calendar_slug' => $calendarSlug,
                    'team_id' => $team_id,
                ];
            }
        } else {
            // If members exist, assign only listed members
            foreach ($team['members'] as $member) {
                $user_id = $member['id'] ?? '';
                if (isset($users_map[$user_id])) {
                    $user = $users_map[$user_id];
                    $staff[] = [
                        'id' => $user_id,
                        'name' => $user['name'] ?? '',
                        'email' => $user['email'] ?? '',
                        'phone' => $user['phone'] ?? '',
                        'calendar_id' => $calendarName,
                        'calendar_slug' => $calendarSlug,
                        'team_id' => $team_id,
                    ];
                }
            }
        }
    }

    // Remove duplicates (in case of multiple groups assigned)
    $staff = array_unique($staff, SORT_REGULAR);

    // Save to option (DO NOT REMOVE THIS)
    update_option('kvw_synced_staff', $staff, false);

    return $staff;
}
function kvw_log_ghl_api($message, $data = []) {
    $logs = get_option('kvw_ghl_api_logs', []);
    $logs[] = [
        'timestamp' => current_time('mysql'),
        'message'   => $message,
        'data'      => $data,
    ];
    update_option('kvw_ghl_api_logs', $logs);
}
function kvw_log_to_file($label, $data = null) {
    $log_file = plugin_dir_path(__FILE__) . 'ghl-sync.log';
    $timestamp = date('Y-m-d H:i:s');
    $output = "\n[{$timestamp}] {$label}";

    if ($data !== null) {
        $output .= "\n" . print_r($data, true);
    }

    $output .= "\n" . str_repeat('-', 80);
    file_put_contents($log_file, $output, FILE_APPEND);
}
function kvw_sync_contact_to_ghl($booking_data,$order_id) {
    $api_key     = get_option('kvw_api_key');
    $location_id = get_option('kvw_location_id');
    if (!$api_key || !$location_id) {
        kvw_log_to_file("Missing API key or Location ID for order $order_id");
        return;
    }
        kvw_log_to_file('[GHL] contact id: ',$order_id);

    $order = wc_get_order($order_id);
        kvw_log_to_file('[GHL] order details: '.$order);

    $payload = [
        'locationId'  => $location_id,
        'firstName'   => $order->get_billing_first_name(),
        'lastName'    => $order->get_billing_last_name(),
        'email'       => $order->get_billing_email(),
        'phone'       => $order->get_billing_phone(),
        'customField' => [
            'notes' => "Service: {$booking_data['service']}\nStaff: {$booking_data['staff']}",
        ],
    ];

    kvw_log_to_file("Sending contact to GHL for order---> ".$order_id);
    //kvw_log_to_file($payload);

    $response = wp_remote_post('https://rest.gohighlevel.com/v1/contacts/', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode($payload),
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        kvw_log_to_file("Error syncing contact for order $order_id: " , $response->get_error_message());
    } else {
        kvw_log_to_file("GHL contact sync response for order $order_id: " , wp_remote_retrieve_body($response));
    }
        $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    kvw_log_to_file('[GHL] Appointment API Response (' . $code . '): ' . $body);

}function kvw_create_ghl_appointment($booking_data, $contact_email) {
    $calendar_id = $booking_data['calendar_id'] ?? '';
    $location_id = get_option('kvw_location_id', '');
    $start_time  = $booking_data['start_time'] ?? '';
    $end_time    = $booking_data['end_time'] ?? '';
    $name        = $booking_data['name'] ?? '';
    $phone       = $booking_data['phone'] ?? '';

    // Validate required fields
    if (empty($calendar_id) || empty($location_id) || empty($start_time) || empty($end_time) || empty($contact_email)) {
        kvw_log_to_file('[GHL] Appointment skipped: Missing required fields.');
        return;
    }

    $payload = [
        'calendarId' => $calendar_id,
        'locationId' => $location_id,
        'startTime'  => $start_time,
        'endTime'    => $end_time,
        'email'      => $contact_email,
        'name'       => $name,
        'phone'      => $phone,
        'source'     => 'WooCommerce Plugin',
    'description'  => kvw_generate_ghl_description($booking_data),
    ];

    // Log final payload
    kvw_log_to_file('[GHL] Sending appointment payload: ' . print_r($payload, true));

    $api_key = get_option('kvw_ghl_api_key');
    $url = "https://rest.gohighlevel.com/v1/appointments";

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ],
        'body' => json_encode($payload),
    ]);

    // Handle errors
    if (is_wp_error($response)) {
        kvw_log_to_file('[GHL] Appointment API error: ' . $response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    kvw_log_to_file('[GHL] Appointment API Response (' . $code . '): ' . $body);
}
function kvw_generate_ghl_description($booking_data) {
    $desc = [];

    if (!empty($booking_data['package'])) {
        $desc[] = "Package: " . $booking_data['package'];
    }

    if (!empty($booking_data['extra'])) {
        $desc[] = "Extras: " . (is_array($booking_data['extra']) ? implode(", ", $booking_data['extra']) : $booking_data['extra']);
    }

    if (!empty($booking_data['service'])) {
        $desc[] = "Service: " . $booking_data['service'];
    }

    if (!empty($booking_data['staff'])) {
        $desc[] = "Staff: " . $booking_data['staff'];
    }

    return implode("\n", $desc); // line-separated
}