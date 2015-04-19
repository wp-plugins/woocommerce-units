<?php
/**
 * Product Data Panel - Inventory Tab
 *
 * Functions to modify the Inventory Data Panel 
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

add_action('woocommerce_product_options_inventory_product_data', 'wc_units_product_options');

/**
 * Display our custom product meta fields in the product edit page
 */
function wc_units_product_options() {
    global $woocommerce, $post;
    echo '<div class="options_group">';
    woocommerce_wp_text_input(array(
        'id' => '_area',
        'label' => __('Area', 'wcu'),
        'type' => 'number',
        'placeholder' => '',
        'desc_tip' => 'true',
        'description' => __('Enter the package area here.', 'wcu'),
        'custom_attributes' => array(
            'step' => 'any',
            'min' => '0'
        )
    ));
    echo '</div>';
}

add_action('woocommerce_process_product_meta', 'wc_units_process_product_meta');

/**
 * Save our custom product meta fields
 */
function wc_units_process_product_meta($post_id) {
    // Area
    $woocommerce_number_field = $_POST['_area'];
    //if (!empty($woocommerce_number_field))
    update_post_meta($post_id, '_area', esc_attr($woocommerce_number_field));
}

/**
 * Show options for the variable product type
 */
//add_action('woocommerce_product_after_variable_attributes', 'wc_units_variable_fields', 10, 2);

function wc_units_variable_fields($loop, $variation_data) {
    global $post;
    // will use the parent area/volume (if set) as the placeholder
    $parent_data = array(
        'area' => $post ? get_post_meta($post->ID, '_area', true) : null,
    );
    // default placeholders
    if (!$parent_data['area']) $parent_data['area'] = '0.00';
    ?>
    <tr>
        <td class="hide_if_variation_virtual">
            <label>
    <?php _e('Area', 'wcu'); ?> <a class="tips" data-tip="<?php _e('Enter the package area here', 'wcu'); ?>" href="#">[?]</a></label>
            <input type="number" size="5" name="variable_area[<?php echo $loop; ?>]" value="<?php if (isset($variation_data['attribute_area'][0])) echo esc_attr($variation_data['attribute_area'][0]); ?>" placeholder="<?php echo $parent_data['area']; ?>" step="any" min="0" />
        </td>
    </tr>
    <?php
}

//add_action('woocommerce_process_product_meta_variable', 'wc_units_process_product_meta_variable');

/**
 * Save the variable product options.
 *
 * @param mixed $post_id the post identifier
 */
function wc_units_process_product_meta_variable($post_id) {

    if (isset($_POST['variable_sku'])) {

        $variable_post_id = $_POST['variable_post_id'];
        $variable_area = $_POST['variable_area'];

        $max_loop = max(array_keys($_POST['variable_post_id']));

        for ($i = 0; $i <= $max_loop; $i++) {

            if (!isset($variable_post_id[$i]))
                continue;

            $variation_id = (int) $variable_post_id[$i];

            // Update post meta
            update_post_meta($variation_id, 'attribute_area', $variable_area[$i]);
        }
    }
}
