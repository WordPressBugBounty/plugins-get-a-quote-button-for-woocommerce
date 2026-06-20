<?php

if (! defined('ABSPATH')) exit; // Exit if accessed directly 

/* -------------------------------------------------------------------------- */
/* Get settings option 
/* -------------------------------------------------------------------------- */

if (!function_exists('wpb_gqb_get_option')) {
    function wpb_gqb_get_option($option, $section, $default = '')
    {

        $options = get_option($section);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return $default;
    }
}

/**
 * Searches for a contact form ID by a hash string.
 *
 * @param string $hash Part of a hash string.
 * @return Contact form ID.
 */
if (! function_exists('wpb_gqb_wpcf7_get_contact_form_id_by_hash')) {
    function wpb_gqb_wpcf7_get_contact_form_id_by_hash($hash)
    {
        global $wpdb;

        $hash = trim($hash);

        if (strlen($hash) < 7) {
            return null;
        }

        $like = $wpdb->esc_like($hash) . '%';

        // Properly prepared SQL query, ignoring PHPCS false positive
        return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_hash' AND meta_value LIKE %s",
                $like
            )
        );
    }
}


/**
 * Show or hide the product info in the form
 */

add_action('init', function () {
    add_filter('wpcf7_form_class_attr', function ($class) {
        $product_info       = wpb_gqb_get_option('wpb_gqb_form_product_info', 'woo_settings', 'hide');

        if ($product_info) {
            $class .= ' wpb_gqb_form_product_info_' . esc_attr($product_info);
        }

        return $class;
    });
});

/**
 * CF7 Post Shortcode
 */

add_action('wpcf7_init', 'wpb_gqb_cf7_add_form_tag_for_post_title');

function wpb_gqb_cf7_add_form_tag_for_post_title()
{
    wpcf7_add_form_tag('post_title', 'wpb_gqb_cf7_post_title_tag_handler');
    wpcf7_add_form_tag('gqb_product_title', 'wpb_gqb_cf7_send_post_title_tag_handler');
}

function wpb_gqb_cf7_post_title_tag_handler($tag)
{
    if (isset($_POST['wpb_post_id'])) {
        $id = intval(wp_unslash($_POST['wpb_post_id']));
        return '<input type="hidden" name="post-title" value="' . esc_attr(get_the_title($id)) . '">';
    }
}

/**
 * Send the product title
 */

function wpb_gqb_cf7_send_post_title_tag_handler($tag)
{
    if (isset($_POST['wpb_post_id'])) {
        $id = intval(wp_unslash($_POST['wpb_post_id']));
        return '<input class="gqb_hidden_field gqb_product_title" type="text" name="gqb_product_title" value="' . esc_attr(get_the_title($id)) . '">';
    }
}


/**
 * Premium Links
 */

add_action('wpb_gqb_after_settings_page', function () {
    $pro_url = add_query_arg(
        array(
            'utm_content'  => 'Get+A+Quote+Pro',
            'utm_campaign' => 'adminlink',
            'utm_medium'   => 'settings-sidebar',
            'utm_source'   => 'FreeVersion',
        ),
        'https://wpbean.com/downloads/get-a-quote-button-pro-for-woocommerce-and-elementor/'
    );
?>
    <div class="wpb-gqb-pro-sidebar-card">
        <div class="wpb-gqb-pro-card-header">
            <span class="wpb-gqb-pro-card-crown dashicons dashicons-star-filled"></span>
            <h3><?php esc_html_e('Upgrade to Pro', 'get-a-quote-button-for-woocommerce'); ?></h3>
            <p><?php esc_html_e('Unlock powerful features for your store', 'get-a-quote-button-for-woocommerce'); ?></p>
        </div>
        <div class="wpb-gqb-pro-card-body">
            <ul class="wpb-gqb-pro-features-list">
                <li><?php esc_html_e('Product title, price, quantity & variations in the form', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('CF7 & WPForms smart tags for product data in emails', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Hide price & cart button for selected products', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Advanced shortcode builder with multiple buttons', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Different forms for different products', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Filter by category, tag, user role & more', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Button & popup typography controls', 'get-a-quote-button-for-woocommerce'); ?></li>
                <li><?php esc_html_e('Elementor widget support', 'get-a-quote-button-for-woocommerce'); ?></li>
            </ul>
            <a href="<?php echo esc_url($pro_url); ?>" target="_blank" rel="noopener noreferrer" class="wpb-gqb-pro-card-cta">
                <span class="dashicons dashicons-cart"></span>
                <?php esc_html_e('Upgrade to Pro', 'get-a-quote-button-for-woocommerce'); ?>
            </a>
        </div>
    </div>
<?php
});
