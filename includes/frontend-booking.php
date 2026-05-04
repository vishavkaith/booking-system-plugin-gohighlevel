<?php
if (!defined('ABSPATH')) exit;
add_action('woocommerce_before_add_to_cart_button', 'kvw_display_booking_form');

function kvw_display_booking_form() {
    $services = get_option('kvw_manual_services', []);
    $packages = get_option('kvw_manual_packages', []);
    $staff_list = get_option('kvw_synced_staff', []);
//print_r($staff_list);
    // Add "type" field to distinguish
    $services = array_map(function($s) {
        $s['type'] = 'service';
        return $s;
    }, $services);
    $packages = array_map(function($p) {
        $p['type'] = 'package';
        return $p;
    }, $packages);

    $services_data = array_merge($services, $packages);
    if (empty($services_data)) return;

    $total_id = 'kvw_total_price_' . uniqid();
?>
    <div class="kvw-booking-wrapper">
        <h3>Select Package (Optional)</h3>
        <ul class="kvw-packages">
            <?php foreach ($services_data as $key => $item):
                if ($item['type'] !== 'package') continue;
            ?>
                <li>
                    <label>
                        <input type="radio" name="kvw_selected_package" value="<?php echo esc_attr($item['name']); ?>" data-price="<?php echo esc_attr($item['price']); ?>" data-duration="<?php echo esc_attr($item['duration']); ?>"class="kvw-package-radio">
                        <strong><?php echo esc_html($item['name']); ?></strong>
                    </label>
                    <div class="kvw-description" style="display:none;">
                        <em><?php echo esc_html($item['description']); ?></em>
                        <ul>
                        <?php if (!empty($item['included'])):
                            foreach ($item['included'] as $included): ?>
                                <li>✔ <?php echo esc_html($included); ?></li>
                        <?php endforeach; endif; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <h3>Select Additional Services</h3>
        <ul class="kvw-services">
            <?php foreach ($services_data as $key => $item):
                if ($item['type'] !== 'service') continue;
            ?>
                <li>
                    <label>
                        <input type="checkbox" name="kvw_selected_services[]" value="<?php echo esc_attr($item['name']); ?>" data-price="<?php echo esc_attr($item['price']); ?>" data-duration="<?php echo esc_attr($item['duration']); ?>" class="kvw-service-checkbox">
                        <?php echo esc_html($item['name']); ?>
                    </label>
                    <div class="kvw-description" style="display:none;">
                        <em><?php echo esc_html($item['description']); ?></em>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <h3>Select Staff</h3>
        <select name="kvw_selected_staff">
            <option value="">-- Select Staff --</option>
            <?php foreach ($staff_list as $staff): ?>
                <option value="<?php echo esc_attr($staff['name']); ?>" data-staff-id="<?php echo esc_attr($staff['id']); ?>" data-calendar-id="<?php echo esc_attr($staff['calendar_id'] ?? ''); ?>">
                    <?php echo esc_html($staff['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="kvw_selected_staff_id" id="kvw_selected_staff_id">
        <input type="hidden" name="kvw_selected_calendar_id" id="kvw_selected_calendar_id">

        <h3>Select Date & Time</h3>
        <input type="datetime-local" name="kvw_selected_datetime" required>
        <input type="hidden" name="kvw_selected_price" id="kvw_selected_price" value="0">
        <input type="hidden" name="kvw_duration" id="kvw_duration" value="0">

duration
        <h3>Total: <span id="<?php echo $total_id; ?>">₹0</span></h3>
    </div>

    <script>
    jQuery(document).ready(function($) {
        function calculateTotal() {
            let total = 0;
            let totald = 0;

            $('.kvw-package-radio:checked').each(function() {
                total += parseFloat($(this).data('price') || 0);
                totald += parseFloat($(this).data('duration') || 0);
                
            });
            $('.kvw-service-checkbox:checked').each(function() {
                total += parseFloat($(this).data('price') || 0);
                totald += parseFloat($(this).data('duration') || 0);
                
            });
            $('#<?php echo $total_id; ?>').text('₹' + total.toFixed(2));
            $('#kvw_selected_price').val(total);
            $('#kvw_duration').val(totald);

            $('.woocommerce-Price-amount').text('₹' + total.toFixed(2));
        }

        $('select[name="kvw_selected_staff"]').on('change', function() {
            const selected = $(this).find(':selected');
            $('#kvw_selected_staff_id').val(selected.data('staff-id'));
            $('#kvw_selected_calendar_id').val(selected.data('calendar-id'));
        });

        $('.kvw-package-radio').on('change', function() {
            $('.kvw-packages .kvw-description').hide();
            $(this).closest('li').find('.kvw-description').slideDown();
            calculateTotal();
        });

        $('.kvw-service-checkbox').on('change', function() {
            const desc = $(this).closest('li').find('.kvw-description');
            this.checked ? desc.slideDown() : desc.slideUp();
            calculateTotal();
        });
    });
    </script>

    <style>
        .kvw-booking-wrapper { border: 1px solid #ccc; padding: 20px; margin-top: 20px; }
        .kvw-booking-wrapper ul { list-style: none; padding: 0; }
        .kvw-booking-wrapper li { margin-bottom: 10px; }
        .kvw-description { font-size: 0.9em; color: #555; margin-left: 15px; }
    </style>
<?php
}
