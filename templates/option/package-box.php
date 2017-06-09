<tr valign="top">
    <th scope="row" class="titledesc"><?php _e('Package Box', FLAGSHIP_SHIPPING_TEXT_DOMAIN);
?>:</th>
    <td class="forminp" id="package_box_collection">
        <table class="widefat wc_input_table sortable" cellspacing="0">
            <thead>
                <tr>
                    <th class="sort">&nbsp;</th>
                    <th><?php _e('Model Name', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Length (in)', FLAGSHIP_SHIPPING_TEXT_DOMAIN);?></th>
                    <th><?php _e('Width (in)', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Height (in)', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Weight (LB)', FLAGSHIP_SHIPPING_TEXT_DOMAIN);?></th>
                    <th><?php _e('Max. Supported (LB)', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?></th>
                    <th><?php _e('Selected', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody class="accounts">
            <?php
            $i = -1;
            if ($packageBoxes) {
                foreach ($packageBoxes as $box) {
                    ++$i;

                    echo '<tr class="package_box">
                                        <td class="sort"></td>
                                        <td><input type="text" value="'.esc_attr(wp_unslash($box['model_name'])).'" name="package_box_model_name['.$i.']" /></td>
                                        <td><input type="number" value="'.esc_attr($box['length']).'" name="package_box_length['.$i.']" style="min-width: 80px" /></td>
                                        <td><input type="number" value="'.esc_attr($box['width']).'" name="package_box_width['.$i.']" style="min-width: 80px" /></td>
                                        <td><input type="number" value="'.esc_attr($box['height']).'" name="package_box_height['.$i.']" style="min-width: 80px" /></td>
                                        <td><input type="number" value="'.esc_attr($box['weight']).'" name="package_box_weight['.$i.']" style="min-width: 80px" /></td>
                                        <td><input type="number" value="'.esc_attr($box['max_weight']).'" name="package_box_max_weight['.$i.']" style="min-width: 80px" /></td>
                                        <td><input type="checkbox" name="package_box_selected['.$i.']" class="package_box_selected" /></td>
                                    </tr>';
                }
            }
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="7">
                        <a href="#" class="add button">
                            <?php _e('+ Add package box', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?>
                        </a>&nbsp;
                        <a href="#" class="remove_rows button">
                            <?php _e('Remove selected package box(es)', FLAGSHIP_SHIPPING_TEXT_DOMAIN); ?>
                        </a>
                    </th>
                </tr>
            </tfoot>
        </table>
        <script type="text/javascript">
            (function($, window){
                $(function(){
                    $('#package_box_collection').on('click', 'a.add', function(){
                        var size = $('#package_box_collection').find('tbody .package_box').length;

                        $('<tr class="package_box">\
                                <td class="sort"></td>\
                                <td><input type="text" name="package_box_model_name[' + size + ']" /></td>\
                                <td><input type="number" name="package_box_length[' + size + ']" style="min-width: 80px" /></td>\
                                <td><input type="number" name="package_box_width[' + size + ']" style="min-width: 80px" /></td>\
                                <td><input type="number" name="package_box_height[' + size + ']" style="min-width: 80px" /></td>\
                                <td><input type="number" name="package_box_weight[' + size + ']" style="min-width: 80px" /></td>\
                                <td><input type="number" name="package_box_max_weight[' + size + ']" style="min-width: 80px" /></td>\
                                <td><input type="checkbox" name="package_box_selected[' + size + ']" class="package_box_selected" /></td>\
                            </tr>').appendTo('#package_box_collection table tbody');

                        return false;
                    });

                    $('#package_box_collection').on('click', 'a.remove_rows', function(e){
                        $('#package_box_collection').find('.package_box_selected:checked').each(function() {
                            $(this).closest('tr.package_box').remove();
                        });

                        return false;
                    });
                });
            })(jQuery, window);
        </script>
    </td>
</tr>