<?php
/*
Plugin Name: PriceCraft for WooCommerce
Plugin URI: https://github.com/aa-seesh/PriceCraft-for-WooCommerce
Description: Enhances WooCommerce with advanced price management features, including category-based pricing and product weight customization.
Version: 1.0
Author: Aashish Shah
Author URI: https://aashishshah.com.np
Text Domain: pricecraft-for-woocommerce
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PriceCraft_For_WooCommerce {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        add_action('product_cat_add_form_fields', array($this, 'add_category_price_field'));
        add_action('product_cat_edit_form_fields', array($this, 'add_category_price_field'), 10, 2);
        add_action('created_product_cat', array($this, 'save_category_price'), 10, 2);
        add_action('edited_product_cat', array($this, 'save_category_price'), 10, 2);
        add_filter('woocommerce_get_price_html', array($this, 'custom_product_price_html'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'custom_cart_item_price'), 10, 3);
    }

    /**
     * Add product weight and making charge fields.
     */
    public function add_product_fields() {
        global $post;
        $product = wc_get_product($post->ID);
        ?>
        <div class="options_group">
            <p class="form-field">
                <label for="product_weight"><?php esc_html_e('Product Weight', 'pricecraft-for-woocommerce'); ?></label>
                <input type="number" class="short" style="width: 100px;" name="product_weight" id="product_weight"
                       value="<?php echo esc_attr($product->get_weight()); ?>" step="0.01">
            </p>
            <p class="form-field">
                <label for="making_charge"><?php esc_html_e('Making Charge', 'pricecraft-for-woocommerce'); ?></label>
                <input type="number" class="short" style="width: 100px;" name="making_charge" id="making_charge"
                       value="<?php echo esc_attr($product->get_meta('making_charge')); ?>" step="0.01">
            </p>
        </div>
        <?php
    }

    /**
     * Save product data.
     */
    public function save_product_data($post_id) {
        if (isset($_POST['product_weight'])) {
            update_post_meta($post_id, '_weight', wc_format_decimal($_POST['product_weight']));
        } else {
            delete_post_meta($post_id, '_weight');
        }
        if (isset($_POST['making_charge'])) {
            update_post_meta($post_id, 'making_charge', wc_format_decimal($_POST['making_charge']));
        } else {
            delete_post_meta($post_id, 'making_charge');
        }
    }

    /**
     * Add category price field.
     */
    public function add_category_price_field($term) {
        $category_price = get_term_meta($term->term_id, 'category_price', true);
        ?>
        <div class="form-field term-display-type-wrap">
            <label for="category_price"><?php esc_html_e('Category Price', 'pricecraft-for-woocommerce'); ?></label>
            <input type="number" name="category_price" id="category_price"
                   value="<?php echo esc_attr($category_price); ?>" step="0.01">
            <p class="description"><?php esc_html_e('Enter the base price for products in this category.', 'pricecraft-for-woocommerce'); ?></p>
        </div>
        <?php
    }

    /**
     * Save category price.
     */
    public function save_category_price($term_id, $tt_id) {
        if (isset($_POST['category_price'])) {
            update_term_meta($term_id, 'category_price', wc_format_decimal($_POST['category_price']));
        }
    }

    /**
     * Display calculated price on product single page.
     */
    public function custom_product_price_html($price_html, $product) {
        if ($product->is_type('simple')) {
            $regular_price = $this->calculate_regular_price($product);
            if ($regular_price !== '') {
                $price_html = wc_price($regular_price);
            }
        }
        return $price_html;
    }

    /**
     * Display calculated price in cart.
     */
    public function custom_cart_item_price($price_html, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        if ($product->is_type('simple')) {
            $regular_price = $this->calculate_regular_price($product);
            if ($regular_price !== '') {
                $price_html = wc_price($regular_price);
            }
        }
        return $price_html;
    }

    /**
     * Calculate regular price based on category price, making charge, and product weight.
     */
    private function calculate_regular_price($product) {
        $category_ids = $product->get_category_ids();
        if (!empty($category_ids)) {
            $category_price = get_term_meta($category_ids[0], 'category_price', true);
            $making_charge = $product->get_meta('making_charge', true);
            $product_weight = $product->get_weight();
            if ($category_price !== '' && $making_charge !== '' && $product_weight !== '') {
                return ($category_price + $making_charge) * $product_weight;
            }
        }
        return '';
    }
}

new PriceCraft_For_WooCommerce();
?>
