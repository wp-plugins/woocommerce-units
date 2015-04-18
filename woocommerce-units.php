<?php
/*
  Plugin Name: WooCommerce Units
  Plugin URI: http://milentijevic.com/wordpress-plugins/
  Version: 0.1.0
  Description: Sell WooCommerce Products per Unit.
  Author: Mladjo
  Author URI: http://milentijevic.com
  Text Domain: wcu
  Domain Path: /languages/

  License: GNU General Public License v3.0
  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
// If this file is called directly, abort.
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
/**
 * Check if WooCommerce is active
 * */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    /**
     * New class
     * */
    if (!class_exists('WC_Units')) {

        class WC_Units {

            public static function getInstance() {
                static $_instance;
                if (!$_instance) {
                    $_instance = new WC_Units();
                }
                return $_instance;
            }

            /**
             * Construct the plugin.
             */
            public function __construct() {

                add_action('plugins_loaded', array(&$this, 'load_localisation'));
                add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

                // Include required files
                $this->includes();
                // display the pricing calculator price per unit on the frontend (catalog and product page)
                $this->add_price_html_filters();

                add_action('woocommerce_before_add_to_cart_form', array($this, 'wcu_get_unit_quantity'));
                //add_action('woocommerce_before_single_variation', array($this, 'get_unit_variation_quantity'));
                // add the product measurements area into the variation JSON object
                //add_filter('woocommerce_available_variation', array($this, 'available_variation'), 10, 3);

                add_action('woocommerce_before_add_to_cart_button', array($this, 'wcu_render_price_calculator'), 5);

                // Change stock message display
                add_filter('woocommerce_get_availability', array($this, 'wcu_get_availability'), 1, 2);

                add_filter('woocommerce_get_price', array($this, 'wcu_custom_price'), 10, 2);
                add_filter('woocommerce_get_regular_price', array($this, 'wcu_custom_price'), 10, 2);
                add_filter('woocommerce_get_sale_price', array($this, 'wcu_custom_price'), 10, 2);
            }

            /**
             * Add all price_html product filters
             */
            private function add_price_html_filters() {
                add_filter('woocommerce_sale_price_html', array($this, 'wcu_price_per_unit_html'), 10, 2);
                add_filter('woocommerce_price_html', array($this, 'wcu_price_per_unit_html'), 10, 2);
                add_filter('woocommerce_empty_price_html', array($this, 'wcu_price_per_unit_html'), 10, 2);

//                add_filter('woocommerce_variable_sale_price_html', array($this, 'variable_price_per_unit_html'), 10, 2);
//                add_filter('woocommerce_variable_price_html', array($this, 'variable_price_per_unit_html'), 10, 2);
//                add_filter('woocommerce_variable_empty_price_html', array($this, 'variable_price_per_unit_html'), 10, 2);
//
//                add_filter('woocommerce_variation_sale_price_html', array($this, 'variable_price_per_unit_html'), 10, 2);
//                add_filter('woocommerce_variation_price_html', array($this, 'variable_price_per_unit_html'), 10, 2);
//		remove_filter( 'woocommerce_get_variation_regular_price', array( $this, 'get_variation_regular_price' ), 10, 4 );
//		remove_filter( 'woocommerce_get_variation_sale_price',    array( $this, 'get_variation_sale_price' ), 10, 4 );
//		remove_filter( 'woocommerce_get_variation_price',         array( $this, 'get_variation_price' ), 10, 4 );
            }

            function wcu_get_availability($availability, $product) {
                //change in stock display
                global $product;
                if ($product->is_in_stock() && $product->managing_stock()) {
                    if (get_post_meta($product->id, '_area', true) > 0) :
                        $area = get_post_meta($product->id, '_area', true);
                        $quantity = $product->get_stock_quantity();
                        $availability['availability'] = $quantity . ' ' . __('units in stock', 'wcu') . ' = ' . $quantity * $area . get_option('woocommerce_dimension_unit') .'&sup2;'. __(' in stock', 'wcu');
                        return $availability;
                    endif;
                } else {
                    return $availability;
                }
            }

            // this modifies price when available for both Simple & Variable product type
            function wcu_custom_price($price, $product) {

                if (
                        $product->is_type(array('simple', 'variable')) && get_post_meta($product->id, '_area', true) > 0
                ) {

                    return $price * get_post_meta($product->id, '_area', true);
                } elseif (
                        $product->is_type('variation') && get_post_meta($product->variation_id, 'attribute_area', true) > 0
                ) {
                    return $price * get_post_meta($product->variation_id, 'attribute_area', true);
                } else {
                    return $price;
                }
            }

//            function available_variation($variation_data, $product, $variation) {
//
//                // var_dump($variation_data);
//
//                $variation_data['attribute_area'] = '<div class="product_meta"><span class="unit-quantity">' . get_post_meta($variation_data[variation_id], 'attribute_area', true) . get_option('woocommerce_dimension_unit').'&sup2; </span>' . __('Per unit', 'wcu') . '</div>';
//
//                // $variation_data['price_html'] = $variation->get_price_html();
//
//                $variation_data['price_html'] = $variation_data['attribute_area'] . $variation_data['price_html'];
//
//                return $variation_data;
//            }

            /**
             * Register/queue frontend scripts.
             */
            public function enqueue_frontend_scripts() {
                wp_enqueue_script('wcu-script', plugins_url('/assets/js/wcu.js', __FILE__), false, false, true);
            }

            /**
             * load_localisation function.
             *
             * @access public
             * @since 1.0.0
             * @return void
             */
            public function load_localisation() {
                load_plugin_textdomain('wcu', false, dirname(plugin_basename(__FILE__)) . '/languages');
            }

            /**
             * Include required core files used in admin and on the frontend.
             */
            private function includes() {
                include_once( 'admin/writepanel-product_data.php' );
            }

            // Extra product info unit quantity
            public function wcu_get_unit_quantity($product) {
                global $product;
                if ($product->is_type('simple'))
                    if (get_post_meta($product->id, '_area', true) > 0) :
                        echo '<div class="wcu-product_meta">';
                        echo '<span class="unit-quantity">' . get_post_meta($product->id, '_area', true) . get_option('woocommerce_dimension_unit'). '&sup2; </span>' . __('Per unit ', 'wcu');
                        echo '<span class="unit-cost">' . sprintf(get_woocommerce_price_format(), get_woocommerce_currency_symbol(), get_post_meta($product->id, '_area', true) * $product->price) . __(' Unit cost', 'wcu') . '</span>';
                        echo '</div>';
                endif;
            }

            // Modifies price display
            public function wcu_price_per_unit_html($price, $product) {
                //global $product;
                if ($product->is_type('simple'))
                    if (get_post_meta($product->id, '_area', true) > 0) :
                        if ($product->sale_price) {
                            return '<del><span class="amount">' . sprintf(get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $product->regular_price) . get_option('woocommerce_dimension_unit').'&sup2; </span></del> <ins><span class="amount">' . sprintf(get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $product->sale_price) . get_option('woocommerce_dimension_unit').'&sup2;</span></ins>';
                        } else {
                            return '<span class="amount">' . sprintf(get_woocommerce_price_format(), get_woocommerce_currency_symbol(), $product->regular_price) . get_option('woocommerce_dimension_unit').'&sup2; </span>';
                        }
                endif;
                return $price . ' m&sup2;';
            }

            // Modifies variable price display
//            public function variable_price_per_unit_html($price, $product) {
//                global $product;
//                if (get_post_meta($product->id, '_area', true) > 0) :
//                    return '<p class="price"><del><span class="amount">' . $product->max_variation_price . get_woocommerce_currency_symbol() . '</span></del> <ins><span class="amount">' . $product->min_variation_price . get_woocommerce_currency_symbol() . '</span></ins>m²</p>';
//                endif;
//
//                return $price . get_option('woocommerce_dimension_unit').'&sup2;';
//            }

            /**
             * Render the price calculator on the product page
             */
            public function wcu_render_price_calculator($product) {
                global $product;
                if (
                        $product->is_type(array('simple')) && get_post_meta($product->id, '_area', true) > 0) {
                    ?>

                    <table class="area_calculator" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="label"><label for="area_needed"><?php _e('Area needed', 'wcu'); ?> <?php echo get_option('woocommerce_dimension_unit') ?>&sup2;</label></td>
                                <td class="value">
                                    <input type="hidden" value="<?php echo get_post_meta($product->id, '_area', true); ?>" name="area_actual" data-unit="<?php echo get_option('woocommerce_dimension_unit') ?>">
                                    <input type="text" autocomplete="off" class="area_needed" id="area_needed" name="area_needed" value="<?php echo get_post_meta($product->id, '_area', true); ?>">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php
                }
            }

        }

        WC_Units::getInstance();
    }
}
                         