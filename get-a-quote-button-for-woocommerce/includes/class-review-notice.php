<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Review / rating notice class
 *
 * Shows a notice after 1 week of installation and respects user feedback:
 *  - "You deserve it!" → opens review page, permanently dismissed
 *  - "Maybe Later"     → snoozed for 1 month
 *  - "I Already Did"   → permanently dismissed
 */
class WPB_GQB_Review_Notice
{
    // Set to true during development to always show the notice regardless of timing.
    const DEV_MODE = false;

    const INSTALL_DATE_OPTION = 'wpb_gqb_installed';
    const META_DISMISSED      = 'wpb_gqb_review_dismissed';
    const META_LATER          = 'wpb_gqb_review_later';
    const REVIEW_URL          = 'https://wordpress.org/support/plugin/get-a-quote-button-for-woocommerce/reviews/?rate=5#new-post';
    const NONCE_ACTION        = 'wpb_gqb_review_notice_action';

    public function __construct()
    {
        add_action('admin_notices', array($this, 'maybe_show_notice'));
        add_action('admin_init', array($this, 'handle_notice_action'));
    }

    /**
     * Decide whether to render the notice.
     */
    public function maybe_show_notice()
    {
        $user_id = get_current_user_id();

        if (! self::DEV_MODE) {
            if (get_user_meta($user_id, self::META_DISMISSED, true)) {
                return;
            }

            $install_date = get_option(self::INSTALL_DATE_OPTION);
            if (! $install_date || (time() - (int) $install_date) < WEEK_IN_SECONDS) {
                return;
            }

            $later_time = get_user_meta($user_id, self::META_LATER, true);
            if ($later_time && (time() - (int) $later_time) < MONTH_IN_SECONDS) {
                return;
            }
        }

        $this->render_notice();
    }

    /**
     * Output the notice HTML.
     */
    private function render_notice()
    {
        $nonce = wp_create_nonce(self::NONCE_ACTION);

        $rate_url    = esc_url(add_query_arg(array('wpb_gqb_review_action' => 'rate',    '_wpnonce' => $nonce)));
        $later_url   = esc_url(add_query_arg(array('wpb_gqb_review_action' => 'later',   '_wpnonce' => $nonce)));
        $dismiss_url = esc_url(add_query_arg(array('wpb_gqb_review_action' => 'dismiss', '_wpnonce' => $nonce)));
        $review_url  = esc_url(self::REVIEW_URL);
?>
        <div class="wpb-gqb-review-notice notice" style="border-left: 4px solid #f0b429; padding: 0; margin: 15px 0; border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,.06);">
            <div style="display: flex; align-items: center; padding: 14px 18px; gap: 14px;">
                <div style="font-size: 32px; line-height: 1; flex-shrink: 0; opacity: .9;">&#11088;</div>
                <div style="flex: 1; min-width: 0;">
                    <p style="margin: 0 0 4px; font-size: 13.5px; font-weight: 600; color: #1d2327;">
                        <?php esc_html_e('Enjoying Get a Quote Button for WooCommerce?', 'get-a-quote-button-for-woocommerce'); ?>
                    </p>
                    <p style="margin: 0 0 11px; font-size: 13px; color: #50575e; line-height: 1.55;">
                        <?php esc_html_e("You've been using the plugin for a week — thank you! If it's been helpful, a quick 5-star review would mean the world to us and help others discover it.", 'get-a-quote-button-for-woocommerce'); ?>
                    </p>
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <a href="<?php echo $review_url; ?>"
                            target="_blank"
                            style="display:inline-flex;align-items:center;gap:4px;background:#f0b429;color:#1d2327;text-decoration:none;padding:6px 13px;border-radius:3px;font-size:12.5px;font-weight:600;line-height:1.4;">
                            &#9733; <?php esc_html_e('You deserve it!', 'get-a-quote-button-for-woocommerce'); ?>
                        </a>
                        <a href="<?php echo $later_url; ?>"
                            style="display:inline-flex;align-items:center;text-decoration:none;padding:6px 13px;font-size:12.5px;color:#50575e;background:#fff;border:1px solid #c3c4c7;border-radius:3px;line-height:1.4;">
                            <?php esc_html_e('Maybe Later', 'get-a-quote-button-for-woocommerce'); ?>
                        </a>
                        <a href="<?php echo $dismiss_url; ?>"
                            style="display:inline-flex;align-items:center;text-decoration:none;padding:6px 10px;font-size:12.5px;color:#787c82;line-height:1.4;">
                            <?php esc_html_e('I Already Did', 'get-a-quote-button-for-woocommerce'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Process the action from query string and redirect to a clean URL.
     */
    public function handle_notice_action()
    {
        if (empty($_GET['wpb_gqb_review_action'])) {
            return;
        }

        $nonce  = ! empty($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        $action = sanitize_text_field(wp_unslash($_GET['wpb_gqb_review_action']));

        if (! wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            die(esc_html__('Nonce Error!!!', 'get-a-quote-button-for-woocommerce'));
        }

        $user_id = get_current_user_id();

        switch ($action) {
            case 'rate':
            case 'dismiss':
                update_user_meta($user_id, self::META_DISMISSED, 'true');
                break;

            case 'later':
                update_user_meta($user_id, self::META_LATER, time());
                break;
        }

        wp_safe_redirect(remove_query_arg(array('wpb_gqb_review_action', '_wpnonce')));
        exit;
    }
}
