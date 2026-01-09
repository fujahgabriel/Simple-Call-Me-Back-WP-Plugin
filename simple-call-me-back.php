<?php
/**
 * Plugin Name: Simple Call Me Back
 * Description: A plugin to allow visitors to request a callback via a modal form.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Fujah Gabriel
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-call-me-back
 * Domain Path: /languages
 * github: https://github.com/fujahgabriel/simple-call-me-back-wp-plugin
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CallMeBackPlugin {

    const VERSION = '1.0.2';
    const PLUGIN_NAME = 'Simple Call Me Back';
    const SPONSOR_URL = 'https://fujahgabriel.xyz/sponsor';
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cmb_requests';

        // Hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_frontend'));
        
        // AJAX
        add_action('wp_ajax_cmb_submit_request', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_cmb_submit_request', array($this, 'handle_submission'));

        // Export
        add_action('admin_init', array($this, 'handle_csv_export'));

        // Shortcode
        add_shortcode('call_me_back', array($this, 'shortcode_button'));
    }

    /**
     * Create Database Table on Activation
     */
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            name tinytext NOT NULL,
            phone varchar(20) NOT NULL,
            position tinytext NOT NULL,
            company tinytext NOT NULL,
            status varchar(20) DEFAULT 'new' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Add Admin Menu
     */
    public function add_admin_menu() {
        add_menu_page(
            self::PLUGIN_NAME,
            self::PLUGIN_NAME,
            'manage_options',
            'simple-call-me-back',
            array($this, 'requests_page'),
            'dashicons-phone',
            26
        );

        add_submenu_page(
            'simple-call-me-back',
            'Requests',
            'Requests',
            'manage_options',
            'simple-call-me-back',
            array($this, 'requests_page')
        );

        add_submenu_page(
            'simple-call-me-back',
            'Settings',
            'Settings',
            'manage_options',
            'simple-call-me-back-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Register Settings
     */
    public function register_settings() {
        register_setting('cmb_settings_group', 'cmb_button_text', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('cmb_settings_group', 'cmb_button_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('cmb_settings_group', 'cmb_button_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('cmb_settings_group', 'cmb_modal_title', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('cmb_settings_group', 'cmb_modal_subtext', array('sanitize_callback' => 'sanitize_textarea_field'));
        register_setting('cmb_settings_group', 'cmb_submit_button_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('cmb_settings_group', 'cmb_submit_button_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('cmb_settings_group', 'cmb_modal_footer_text', array('sanitize_callback' => 'wp_kses_post'));
        register_setting('cmb_settings_group', 'cmb_modal_footer_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
        register_setting('cmb_settings_group', 'cmb_modal_size', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('cmb_settings_group', 'cmb_show_floating_button', array('sanitize_callback' => 'absint'));
        register_setting('cmb_settings_group', 'cmb_float_position', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('cmb_settings_group', 'cmb_float_margin_x', array('sanitize_callback' => 'absint'));
        register_setting('cmb_settings_group', 'cmb_float_margin_y', array('sanitize_callback' => 'absint'));
        // HubSpot Settings
        register_setting('cmb_settings_group', 'cmb_enable_hubspot_sync', array('sanitize_callback' => 'absint'));
        register_setting('cmb_settings_group', 'cmb_hubspot_api_key', array('sanitize_callback' => 'sanitize_text_field'));

        // Email Settings
        register_setting('cmb_settings_group', 'cmb_enable_email_notification', array('sanitize_callback' => 'absint'));
        register_setting('cmb_settings_group', 'cmb_notification_email', array('sanitize_callback' => 'sanitize_email'));

        // Slack Settings
        register_setting('cmb_settings_group', 'cmb_enable_slack_notification', array('sanitize_callback' => 'absint'));
        register_setting('cmb_settings_group', 'cmb_slack_webhook_url', array('sanitize_callback' => 'esc_url_raw'));
    }

    /**
     * Enqueue Scripts and Styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('cmb-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
        wp_enqueue_style('intl-tel-input', plugin_dir_url(__FILE__) . 'assets/vendor/intl-tel-input/css/intlTelInput.css');
        wp_enqueue_style('dashicons');
        
        // Dynamic styles from settings
        $bg_color = get_option('cmb_button_color', '#0073aa');
        $text_color = get_option('cmb_button_text_color', '#ffffff');
        
        $submit_bg_color = get_option('cmb_submit_button_color', '#0073aa');
        $submit_text_color = get_option('cmb_submit_button_text_color', '#ffffff');

        // Handle Button Position
        $position = get_option('cmb_float_position', 'bottom-right');
        $margin_x = intval(get_option('cmb_float_margin_x', '30'));
        $margin_y = intval(get_option('cmb_float_margin_y', '30'));
        $pos_css = '';
        
        switch ($position) {
            case 'top-left':
                $pos_css = "top: {$margin_y}px; left: {$margin_x}px; bottom: auto; right: auto;";
                break;
            case 'top-right':
                $pos_css = "top: {$margin_y}px; right: {$margin_x}px; bottom: auto; left: auto;";
                break;
            case 'bottom-left':
                $pos_css = "bottom: {$margin_y}px; left: {$margin_x}px; top: auto; right: auto;";
                break;
            case 'bottom-right':
            default:
                $pos_css = "bottom: {$margin_y}px; right: {$margin_x}px; top: auto; left: auto;";
                break;
        }

        $custom_css = "
            .cmb-floating-btn {
                background-color: {$bg_color};
                color: {$text_color};
                {$pos_css}
            }
            .cmb-submit-btn {
                background-color: {$submit_bg_color};
                color: {$submit_text_color};
            }
            .cmb-details { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
            .cmb-summary { cursor: pointer; font-size: 0.9em; color: #555; margin-bottom: 5px; font-weight: 500; list-style: none; display: flex; align-items: center; justify-content: space-between; }
            .cmb-summary::-webkit-details-marker { display: none; }
            .cmb-summary .dashicons { transition: transform 0.2s; font-size: 1.2em; width: 20px; height: 20px; }
            .cmb-details[open] .cmb-summary .dashicons { transform: rotate(180deg); }
            .cmb-details[open] .cmb-summary { margin-bottom: 15px; color: #333; }
            /* Override intl-tel-input to match form style */
            .iti { width: 100%; }
            .iti__flag { background-image: url('assets/vendor/intl-tel-input/img/flags.png'); }
            @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
              .iti__flag { background-image: url('assets/vendor/intl-tel-input/img/flags@2x.png'); }
            }
        ";
        wp_add_inline_style('cmb-style', $custom_css);

        wp_enqueue_script('intl-tel-input', plugin_dir_url(__FILE__) . 'assets/vendor/intl-tel-input/js/intlTelInput.min.js', array(), '17.0.8', true);
        wp_enqueue_script('cmb-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('intl-tel-input'), self::VERSION, true);
        
        wp_localize_script('cmb-script', 'cmb_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cmb_submit_request_nonce'),
            'utils_url' => plugin_dir_url(__FILE__) . 'assets/vendor/intl-tel-input/js/utils.js'
        ));
    }

    /**
     * Render Frontend HTML (Floating Button)
     */
    public function render_frontend() {
        if (!get_option('cmb_show_floating_button', '1')) {
            $this->render_modal_markup(); // Still need markup if shortcode is used
            return;
        }

        $btn_text = get_option('cmb_button_text', 'Request Callback');
        ?>
        <button id="cmb-floating-btn" class="cmb-floating-btn">
            <span class="cmb-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg>
            </span>
            <?php echo esc_html($btn_text); ?>
        </button>
        <?php
        
        $this->render_modal_markup();
    }

    /**
     * Shortcode Button
     */
    public function shortcode_button($atts) {
        $atts = shortcode_atts(array(
            'text' => get_option('cmb_button_text', 'Request Callback'),
            'class' => 'button'
        ), $atts);

        // Ensure scripts are enqueued even if shortcode is used
        wp_enqueue_script('cmb-script');
        wp_enqueue_style('cmb-style');
        
        // We ensure modal markup is present in footer (handled by wp_footer hook logic, 
        // but if wp_footer isn't called or floating is disabled, we must ensure it's there. 
        // The wp_footer hook `render_frontend` handles the modal markup even if floating is disabled.)

        return '<button class="cmb-trigger-btn ' . esc_attr($atts['class']) . '">' . esc_html($atts['text']) . '</button>';
    }

    /**
     * Helper to render modal markup once
     */
    private function render_modal_markup() {
        // Prevent duplicate rendering
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;

        $modal_title = get_option('cmb_modal_title', 'Request a Call Back');
        $modal_subtext = get_option('cmb_modal_subtext', 'Please fill out the form below and we will get back to you shortly.');
        $modal_size = get_option('cmb_modal_size', 'medium');
        ?>
        <div id="cmb-modal-overlay" class="cmb-modal-overlay">
            <div class="cmb-modal cmb-size-<?php echo esc_attr($modal_size); ?>">
                <span class="cmb-modal-close">&times;</span>
                <h3 class="cmb-modal-title"><?php echo esc_html($modal_title); ?></h3>
                <?php if (!empty($modal_subtext)): ?>
                    <p class="cmb-modal-subtext"><?php echo esc_html($modal_subtext); ?></p>
                <?php endif; ?>
                
                <form id="cmb-callback-form">
                    <div class="cmb-form-group">
                        <label for="cmb-name">Name *</label>
                        <input type="text" id="cmb-name" name="cmb_name" placeholder="e.g. John Doe" required>
                    </div>
                    
                    <div class="cmb-form-group">
                        <label for="cmb-phone">Phone Number (International) *</label>
                        <input type="tel" id="cmb-phone" name="cmb_phone" placeholder="" required>
                    </div>

                    <details class="cmb-details">
                        <summary class="cmb-summary">Additional Info (Position, Company) <span class="dashicons dashicons-arrow-down-alt2"></span></summary>
                        <div class="cmb-form-group">
                            <label for="cmb-position">Position</label>
                            <input type="text" id="cmb-position" name="cmb_position" placeholder="e.g. Marketing Manager">
                        </div>

                        <div class="cmb-form-group">
                            <label for="cmb-company">Company Name</label>
                            <input type="text" id="cmb-company" name="cmb_company" placeholder="e.g. Acme Corp">
                        </div>
                    </details>

                    <button type="submit" class="cmb-submit-btn">Submit Request</button>
                    <div id="cmb-message" class="cmb-message"></div>
                    
                    <?php 
                    $footer_text = get_option('cmb_modal_footer_text');
                    $footer_color = get_option('cmb_modal_footer_text_color', '#666666');
                    if (!empty($footer_text)): ?>
                        <p class="cmb-modal-footer-text" style="text-align: center; margin-top: 15px; font-size: 0.9em; color: <?php echo esc_attr($footer_color); ?>;">
                            <?php echo wp_kses_post($footer_text); ?>
                        </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX Submission
     */
    public function handle_submission() {
        check_ajax_referer('cmb_submit_request_nonce', 'nonce');

        $name = sanitize_text_field(wp_unslash($_POST['cmb_name']));
        $phone = sanitize_text_field(wp_unslash($_POST['cmb_phone']));
        $position = sanitize_text_field(wp_unslash($_POST['cmb_position']));
        $company = sanitize_text_field(wp_unslash($_POST['cmb_company']));

        if (empty($name) || empty($phone)) {
            wp_send_json_error(array('message' => 'Name and Phone are required.'));
        }

        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'time' => current_time('mysql'),
                'name' => $name,
                'phone' => $phone,
                'position' => $position,
                'company' => $company
            )
        );

        if ($result) {
            // Trigger HubSpot Sync
            if (get_option('cmb_enable_hubspot_sync') && get_option('cmb_hubspot_api_key')) {
                $this->sync_to_hubspot($name, $phone, $position, $company);
            }

            // Trigger Email Notification
            if (get_option('cmb_enable_email_notification')) {
                $this->send_email_notification($name, $phone, $position, $company);
            }

            // Trigger Slack Notification
            if (get_option('cmb_enable_slack_notification') && get_option('cmb_slack_webhook_url')) {
                $this->send_slack_notification($name, $phone, $position, $company);
            }

            wp_send_json_success(array('message' => 'Thank you! We will call you back soon.'));
        } else {
            wp_send_json_error(array('message' => 'Could not save request. Please try again.'));
        }
    }

    /**
     * Send data to HubSpot
     */
    private function sync_to_hubspot($name, $phone, $position, $company) {
        $token = get_option('cmb_hubspot_api_key');
        if (empty($token)) return;

        // Split name into First/Last for better CRM data
        $parts = explode(' ', trim($name), 2);
        $firstname = $parts[0];
        $lastname = isset($parts[1]) ? $parts[1] : '';

        $data = array(
            'properties' => array(
                'firstname' => $firstname,
                'lastname' => $lastname,
                'phone' => $phone,
                'jobtitle' => $position,
                'company' => $company,
                'lifecyclestage' => 'lead'
            )
        );

        $response = wp_remote_post('https://api.hubapi.com/crm/v3/objects/contacts', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'method' => 'POST',
            'data_format' => 'body'
        ));

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(self::PLUGIN_NAME . ' - HubSpot Sync Error: ' . $response->get_error_message());
            }
        }
    }

    /**
     * Send Email Notification
     */
    private function send_email_notification($name, $phone, $position, $company) {
        $to = get_option('cmb_notification_email', get_option('admin_email'));
        if (!is_email($to)) return;

        $subject = 'New Message: ' . self::PLUGIN_NAME;
        
        $message  = "You have received a new callback request.\n\n";
        $message .= "Name: " . $name . "\n";
        $message .= "Phone: " . $phone . "\n";
        if ($position) $message .= "Position: " . $position . "\n";
        if ($company) $message .= "Company: " . $company . "\n";
        $message .= "\nLogin to your dashboard to manage requests.";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Send Slack Notification
     */
    private function send_slack_notification($name, $phone, $position, $company) {
        $webhook_url = get_option('cmb_slack_webhook_url');
        if (empty($webhook_url)) return;

        $text = "*New Callback Request from " . self::PLUGIN_NAME . "*\n";
        $text .= "*Name:* $name\n";
        $text .= "*Phone:* $phone\n";
        if ($position) $text .= "*Position:* $position\n";
        if ($company) $text .= "*Company:* $company\n";

        $payload = array('text' => $text);

        $response = wp_remote_post($webhook_url, array(
            'body' => json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'method' => 'POST',
            'data_format' => 'body'
        ));

        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(self::PLUGIN_NAME . ' - Slack Notification Error: ' . $response->get_error_message());
            }
        }
    }

    /**
     * Requests Page (Admin)
     */
    public function requests_page() {
        global $wpdb;
        
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Handle Delete
        if ($action == 'delete' && $id > 0) {
            check_admin_referer('cmb_delete_request_' . $id);
            $wpdb->delete($this->table_name, array('id' => $id));
            echo '<div class="notice notice-success is-dismissible"><p>Request deleted.</p></div>';
            $action = 'list';
        }

        // Handle Edit Save
        if (isset($_POST['cmb_action']) && $_POST['cmb_action'] == 'update_request') {
             $id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
             check_admin_referer('cmb_edit_request_' . $id);
             
             $wpdb->update(
                $this->table_name,
                array(
                    'name' => sanitize_text_field(wp_unslash($_POST['cmb_name'])),
                    'phone' => sanitize_text_field(wp_unslash($_POST['cmb_phone'])),
                    'position' => sanitize_text_field(wp_unslash($_POST['cmb_position'])),
                    'company' => sanitize_text_field(wp_unslash($_POST['cmb_company'])),
                    'status' => sanitize_text_field(wp_unslash($_POST['cmb_status']))
                ),
                array('id' => $id)
             );
             
             echo '<div class="notice notice-success is-dismissible"><p>Request updated.</p></div>';
             $this->render_list_page();
             return;
        }

        if ($action == 'edit' && $id > 0) {
            $this->render_edit_page($id);
        } else {
            $this->render_list_page();
        }
    }

    /**
     * Render List Page
     */
    private function render_list_page() {
        global $wpdb;

        // Custom CSS for Status Badges
        echo '<style>
            .cmb-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; color: #fff; text-transform: uppercase; }
            .cmb-status-new { background-color: #2271b1; }
            .cmb-status-contacted { background-color: #dba617; }
            .cmb-status-closed { background-color: #787c82; }
        </style>';

        // Handle pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $this->table_name");
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $this->table_name ORDER BY time DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        // Calculate total pages
        $total_pages = ceil($total_items / $per_page);

        ?>
        <div class="wrap">
            <h1>Call Back Requests</h1>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin.php?action=cmb_export_csv')); ?>" style="float: right; margin-bottom: 10px;">
                <?php wp_nonce_field('cmb_export_csv_nonce', 'cmb_export_nonce'); ?>
                <button type="submit" class="button button-primary">Export to CSV</button>
            </form>
            <div style="clear: both;"></div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Position</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results) : ?>
                        <?php foreach ($results as $row) : ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($row->time))); ?></td>
                                <td><?php echo esc_html($row->name); ?></td>
                                <td><a href="tel:<?php echo esc_attr($row->phone); ?>"><?php echo esc_html($row->phone); ?></a></td>
                                <td><?php echo esc_html($row->position); ?></td>
                                <td><?php echo esc_html($row->company); ?></td>
                                <td>
                                    <?php 
                                    $status_class = 'cmb-status-' . sanitize_html_class($row->status); 
                                    $status_label = ucfirst($row->status);
                                    ?>
                                    <span class="cmb-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $edit_url = add_query_arg(array('page' => 'simple-call-me-back', 'action' => 'edit', 'id' => $row->id), admin_url('admin.php'));
                                    $delete_url = wp_nonce_url(add_query_arg(array('page' => 'simple-call-me-back', 'action' => 'delete', 'id' => $row->id), admin_url('admin.php')), 'cmb_delete_request_' . $row->id);
                                    ?>
                                    <a href="<?php echo esc_url($edit_url); ?>">Edit</a> | 
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Are you sure you want to delete this request?');" style="color: #a00;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">No requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo wp_kses_post(paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;', 'simple-call-me-back'),
                            'next_text' => __('&raquo;', 'simple-call-me-back'),
                            'total' => $total_pages,
                            'current' => $current_page
                        )));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Edit Page
     */
    private function render_edit_page($id) {
        global $wpdb;
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));

        if (!$item) {
            echo '<div class="notice notice-error"><p>Request not found.</p></div>';
            $this->render_list_page();
            return;
        }
        ?>
        <div class="wrap">
            <h1>Edit Request</h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=simple-call-me-back')); ?>" class="page-title-action">Back to List</a>
            
            <form method="post">
                <input type="hidden" name="cmb_action" value="update_request">
                <input type="hidden" name="request_id" value="<?php echo esc_attr($id); ?>">
                <?php wp_nonce_field('cmb_edit_request_' . $id); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="cmb_name">Name</label></th>
                        <td><input type="text" name="cmb_name" id="cmb_name" value="<?php echo esc_attr($item->name); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="cmb_phone">Phone</label></th>
                        <td><input type="text" name="cmb_phone" id="cmb_phone" value="<?php echo esc_attr($item->phone); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="cmb_position">Position</label></th>
                        <td><input type="text" name="cmb_position" id="cmb_position" value="<?php echo esc_attr($item->position); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="cmb_company">Company</label></th>
                        <td><input type="text" name="cmb_company" id="cmb_company" value="<?php echo esc_attr($item->company); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="cmb_status">Status</label></th>
                        <td>
                            <select name="cmb_status" id="cmb_status">
                                <option value="new" <?php selected($item->status, 'new'); ?>>New</option>
                                <option value="contacted" <?php selected($item->status, 'contacted'); ?>>Contacted</option>
                                <option value="closed" <?php selected($item->status, 'closed'); ?>>Closed</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Date Submitted</th>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->time))); ?></td>
                    </tr>
                </table>

                <?php submit_button('Update Request'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Settings Page (Admin)
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(self::PLUGIN_NAME); ?> Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cmb_settings_group'); ?>
                <?php do_settings_sections('cmb_settings_group'); ?>
                
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Button Text</th>
                        <td><input type="text" name="cmb_button_text" value="<?php echo esc_attr(get_option('cmb_button_text', 'Request Callback')); ?>" /></td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Button Background Color</th>
                        <td><input type="color" name="cmb_button_color" value="<?php echo esc_attr(get_option('cmb_button_color', '#0073aa')); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Button Text Color</th>
                        <td><input type="color" name="cmb_button_text_color" value="<?php echo esc_attr(get_option('cmb_button_text_color', '#ffffff')); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Modal Title</th>
                        <td><input type="text" name="cmb_modal_title" value="<?php echo esc_attr(get_option('cmb_modal_title', 'Request a Call Back')); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Modal Subtext</th>
                        <td><textarea name="cmb_modal_subtext" rows="3" cols="50"><?php echo esc_textarea(get_option('cmb_modal_subtext', 'Please fill out the form below and we will get back to you shortly.')); ?></textarea></td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row">Submit Button Color</th>
                        <td><input type="color" name="cmb_submit_button_color" value="<?php echo esc_attr(get_option('cmb_submit_button_color', '#0073aa')); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Submit Button Text Color</th>
                        <td><input type="color" name="cmb_submit_button_text_color" value="<?php echo esc_attr(get_option('cmb_submit_button_text_color', '#ffffff')); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Text Below Submit Button</th>
                        <td>
                            <input type="text" name="cmb_modal_footer_text" value="<?php echo esc_attr(get_option('cmb_modal_footer_text')); ?>" class="regular-text" />
                            <p class="description">e.g. Call us anytime on <a href="tel:+123456789">+1 234 567 89</a></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Footer Text Color</th>
                        <td><input type="color" name="cmb_modal_footer_text_color" value="<?php echo esc_attr(get_option('cmb_modal_footer_text_color', '#666666')); ?>" /></td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"></th>Modal Size</th>
                        <td>
                            <select name="cmb_modal_size">
                                <option value="small" <?php selected(get_option('cmb_modal_size', 'medium'), 'small'); ?>>Small</option>
                                <option value="medium" <?php selected(get_option('cmb_modal_size', 'medium'), 'medium'); ?>>Medium</option>
                                <option value="large" <?php selected(get_option('cmb_modal_size', 'medium'), 'large'); ?>>Large</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Floating Button Position</th>
                        <td>
                            <select name="cmb_float_position">
                                <option value="bottom-right" <?php selected(get_option('cmb_float_position', 'bottom-right'), 'bottom-right'); ?>>Bottom Right</option>
                                <option value="bottom-left" <?php selected(get_option('cmb_float_position', 'bottom-right'), 'bottom-left'); ?>>Bottom Left</option>
                                <option value="top-right" <?php selected(get_option('cmb_float_position', 'bottom-right'), 'top-right'); ?>>Top Right</option>
                                <option value="top-left" <?php selected(get_option('cmb_float_position', 'bottom-right'), 'top-left'); ?>>Top Left</option>
                            </select>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Margin X (px)</th>
                        <td>
                            <input type="number" name="cmb_float_margin_x" value="<?php echo esc_attr(get_option('cmb_float_margin_x', '30')); ?>" style="width: 80px;" />
                            <p class="description">Horizontal distance from the edge (left or right).</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Margin Y (px)</th>
                        <td>
                            <input type="number" name="cmb_float_margin_y" value="<?php echo esc_attr(get_option('cmb_float_margin_y', '30')); ?>" style="width: 80px;" />
                            <p class="description">Vertical distance from the edge (top or bottom).</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Enable Floating Button</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cmb_show_floating_button" value="1" <?php checked(get_option('cmb_show_floating_button', '1'), '1'); ?> />
                                Show floating button on all pages
                            </label>
                        </td>
                    </tr>
                </table>

                <hr>
                <h3>HubSpot Integration</h3>
                <p class="description">Automatically create a contact in HubSpot when a callback is requested.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable HubSpot Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cmb_enable_hubspot_sync" value="1" <?php checked(get_option('cmb_enable_hubspot_sync'), '1'); ?> />
                                Sync requests to HubSpot CRM
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">HubSpot Access Token</th>
                        <td>
                            <input type="password" name="cmb_hubspot_api_key" value="<?php echo esc_attr(get_option('cmb_hubspot_api_key')); ?>" class="regular-text" />
                            <p class="description">Enter your Private App Access Token (from HubSpot Settings > Integrations > Private Apps).</p>
                        </td>
                    </tr>
                </table>

                <hr>
                <h3>Email Notifications</h3>
                <p class="description">Receive an email when a new request is submitted.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Email</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cmb_enable_email_notification" value="1" <?php checked(get_option('cmb_enable_email_notification'), '1'); ?> />
                                Send email notifications
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Recipient Email</th>
                        <td>
                            <input type="email" name="cmb_notification_email" value="<?php echo esc_attr(get_option('cmb_notification_email', get_option('admin_email'))); ?>" class="regular-text" />
                            <p class="description">Enter the email address locally (Defaults to site admin email).</p>
                        </td>
                    </tr>
                </table>

                <hr>
                <h3>Slack Integration</h3>
                <p class="description">Send a notification to a Slack channel via Webhook.</p>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Slack</th>
                        <td>
                            <label>
                                <input type="checkbox" name="cmb_enable_slack_notification" value="1" <?php checked(get_option('cmb_enable_slack_notification'), '1'); ?> />
                                Send Slack notifications
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Slack Webhook URL</th>
                        <td>
                            <input type="url" name="cmb_slack_webhook_url" value="<?php echo esc_attr(get_option('cmb_slack_webhook_url')); ?>" class="regular-text" />
                            <p class="description">Paste your <a href="https://api.slack.com/messaging/webhooks" target="_blank">Incoming Webhook URL</a> here.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>

                <hr>
                <div class="card" style="margin-top: 20px; max-width: 600px; background: #fff; padding: 15px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <h2 style="margin-top: 0;">❤️ Support the Development</h2>
                    <p>Your support helps keep <strong><?php echo esc_html(self::PLUGIN_NAME); ?></strong> free and ad-free for everyone.</p>
                    <p>
                        <a href="<?php echo esc_url(self::SPONSOR_URL); ?>" target="_blank" class="button button-secondary">
                            Sponsor the Plugin
                        </a>
                    </p>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Handle CSV Export
     */
    public function handle_csv_export() {
        if (isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) == 'cmb_export_csv') {
            
            // Check permissions first
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-call-me-back'));
            }

            // Check nonce
            if (!isset($_POST['cmb_export_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cmb_export_nonce'])), 'cmb_export_csv_nonce')) {
                wp_die(esc_html__('Security check failed', 'simple-call-me-back'));
            }

            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY time DESC", ARRAY_A);

            $filename = 'callback_requests_' . gmdate('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w'); // WP_Filesystem is not suitable for output streams
            
            // Header row
            fputcsv($output, array('ID', 'Date', 'Name', 'Phone', 'Position', 'Company'));

            // Data rows
            foreach ($results as $row) {
                fputcsv($output, array(
                    $row['id'],
                    $row['time'],
                    $row['name'],
                    $row['phone'],
                    $row['position'],
                    $row['company']
                ));
            }

            exit;
        }
    }
}

new CallMeBackPlugin();
