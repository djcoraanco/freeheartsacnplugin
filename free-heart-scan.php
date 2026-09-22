<?php
/**
 * Plugin Name:       Free Heart Scan - Intake & CRM Lead Generator
 * Plugin URI:        https://lifeimagingfla.com
 * Description:       Complete standalone preventive evaluation and lead capture system for Life Imaging. Includes intelligent income calculation, integration with Life Imaging CRM API v1, local database with CSV export, Elementor widget, and shortcode [free_heart_scan].
 * Version:           2.1.2
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * Author:            Free Heart Scan Team & Life Imaging
 * Author URI:        https://lifeimagingfla.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       free-heart-scan
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('FHS_PLUGIN_VERSION', '2.1.2');
define('FHS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FHS_PLUGIN_URL', plugin_dir_url(__FILE__));

class FreeHeartScanCompletePlugin {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        
        add_action('init', array($this, 'init_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_fhs_export_csv', array($this, 'export_leads_csv'));

        // Shortcodes
        add_shortcode('free_heart_scan', array($this, 'render_form_shortcode'));
        add_shortcode('free_heart_scan_form', array($this, 'render_form_shortcode'));

        // REST API Endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // AJAX Fallback
        add_action('wp_ajax_fhs_submit_lead', array($this, 'ajax_submit_lead'));
        add_action('wp_ajax_nopriv_fhs_submit_lead', array($this, 'ajax_submit_lead'));

        // Elementor Support
        add_action('elementor/widgets/register', array($this, 'register_elementor_widget'));
    }

    /**
     * Create local leads database table upon plugin activation
     */
    public function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fhs_leads';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            lead_id varchar(100) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(150) NOT NULL,
            phone varchar(50) NOT NULL,
            zip_code varchar(20) NOT NULL,
            age_range varchar(50) NOT NULL,
            job_title varchar(150) NOT NULL,
            years_in_position varchar(50) NOT NULL,
            stress_level varchar(50) NOT NULL,
            employment_status varchar(50) DEFAULT '',
            industry varchar(50) DEFAULT '',
            estimated_income decimal(12,2) DEFAULT NULL,
            income_range varchar(50) NOT NULL,
            assigned_clinic varchar(150) NOT NULL,
            crm_http_status int(5) DEFAULT 0,
            crm_synced tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function init_plugin() {
        // Register frontend assets
        wp_register_style(
            'fhs-form-css',
            FHS_PLUGIN_URL . 'assets/css/free-heart-scan.css',
            array(),
            FHS_PLUGIN_VERSION
        );

        wp_register_script(
            'fhs-form-js',
            FHS_PLUGIN_URL . 'assets/js/free-heart-scan.js',
            array('jquery'),
            FHS_PLUGIN_VERSION,
            true
        );

        wp_localize_script('fhs-form-js', 'fhsConfig', array(
            'apiUrl'     => esc_url_raw(rest_url('free-heart-scan/v1/submit')),
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('wp_rest'),
            'ajaxNonce'  => wp_create_nonce('fhs_ajax_nonce'),
            'clinics'    => $this->get_clinics_list()
        ));
    }

    public function register_settings() {
        register_setting('fhs_settings_group', 'fhs_crm_base_url');
        register_setting('fhs_settings_group', 'fhs_crm_email');
        register_setting('fhs_settings_group', 'fhs_crm_password');
        register_setting('fhs_settings_group', 'fhs_gemini_api_key');
        register_setting('fhs_settings_group', 'fhs_openai_api_key');
        register_setting('fhs_settings_group', 'fhs_redirect_url');
        register_setting('fhs_settings_group', 'fhs_enable_local_logging');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Free Heart Scan',
            'Heart Scan CRM',
            'manage_options',
            'free-heart-scan-dashboard',
            array($this, 'render_admin_dashboard_page'),
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'free-heart-scan-dashboard',
            'Captured Leads',
            'Captured Leads',
            'manage_options',
            'free-heart-scan-leads',
            array($this, 'render_admin_leads_page')
        );

        add_submenu_page(
            'free-heart-scan-dashboard',
            'API & CRM Settings',
            'Settings',
            'manage_options',
            'free-heart-scan-settings',
            array($this, 'render_admin_settings_page')
        );
    }

    public function render_admin_dashboard_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fhs_leads';
        $total_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today_leads = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()");
        $crm_url = get_option('fhs_crm_base_url', 'https://crm-api.lifeimagingfla.com');
        $has_gemini = !empty(get_option('fhs_gemini_api_key'));
        ?>
        <div class="wrap">
            <h1 style="display:flex; align-items:center; gap:10px;">
                <span style="color:#c8102e;">❤️</span> Free Heart Scan - Dashboard & CRM
            </h1>
            <p>Standalone native system integrated with <strong>Life Imaging CRM</strong> and <strong>Gemini AI</strong> income calculation engine.</p>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap:20px; margin:25px 0;">
                <div style="background:#fff; border-left:4px solid #c8102e; padding:20px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0; font-size:14px; color:#64748b;">TOTAL CAPTURED LEADS</h3>
                    <p style="font-size:32px; font-weight:bold; color:#262c60; margin:10px 0 0 0;"><?php echo intval($total_leads); ?></p>
                </div>
                <div style="background:#fff; border-left:4px solid #0284c7; padding:20px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0; font-size:14px; color:#64748b;">LEADS CAPTURED TODAY</h3>
                    <p style="font-size:32px; font-weight:bold; color:#0284c7; margin:10px 0 0 0;"><?php echo intval($today_leads); ?></p>
                </div>
                <div style="background:#fff; border-left:4px solid <?php echo $has_gemini ? '#15803d' : '#f59e0b'; ?>; padding:20px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0; font-size:14px; color:#64748b;">SALARY CALCULATION ENGINE</h3>
                    <p style="font-size:16px; font-weight:bold; color:#1e293b; margin:10px 0 0 0;">
                        <?php echo $has_gemini ? '✅ Gemini AI + Active Heuristics' : '⚡ Local Resilient Heuristic Engine Active'; ?>
                    </p>
                </div>
            </div>

            <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom:25px;">
                <h2 style="margin-top:0;">How to display the form on your website:</h2>
                <ol style="line-height:2; font-size:15px;">
                    <li><strong>On any page or post:</strong> Paste the shortcode <code style="background:#f1f5f9; padding:4px 8px; font-size:16px; color:#c8102e;">[free_heart_scan]</code></li>
                    <li><strong>In Elementor:</strong> Insert the <em>"Free Heart Scan Form"</em> widget or add a Shortcode block with <code>[free_heart_scan]</code>.</li>
                    <li><strong>In Gutenberg / Blocks:</strong> Add a Shortcode block and type <code>[free_heart_scan]</code>.</li>
                </ol>
                <div style="margin-top:15px;">
                    <a href="<?php echo admin_url('admin.php?page=free-heart-scan-leads'); ?>" class="button button-primary">View Captured Leads</a>
                    <a href="<?php echo admin_url('admin.php?page=free-heart-scan-settings'); ?>" class="button">API / CRM Settings</a>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_admin_settings_page() {
        $crm_url = get_option('fhs_crm_base_url', 'https://crm-api.lifeimagingfla.com');
        $crm_email = get_option('fhs_crm_email', '');
        $crm_pass = get_option('fhs_crm_password', '');
        $gemini_key = get_option('fhs_gemini_api_key', '');
        $openai_key = get_option('fhs_openai_api_key', '');
        $redirect_url = get_option('fhs_redirect_url', '');
        $enable_logging = get_option('fhs_enable_local_logging', false);
        ?>
        <div class="wrap">
            <h1>Free Heart Scan & Life Imaging CRM Settings</h1>
            <p>Configure API credentials for Life Imaging CRM and optional Google Gemini AI salary calculation.</p>

            <form method="post" action="options.php">
                <?php settings_fields('fhs_settings_group'); ?>
                <?php do_settings_sections('fhs_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="fhs_crm_base_url">CRM Base URL</label></th>
                        <td>
                            <input name="fhs_crm_base_url" type="url" id="fhs_crm_base_url" value="<?php echo esc_attr($crm_url); ?>" class="regular-text" />
                            <p class="description">CRM API Endpoint (Default: <code>https://crm-api.lifeimagingfla.com</code>)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fhs_crm_email">CRM API Email / User</label></th>
                        <td>
                            <input name="fhs_crm_email" type="text" id="fhs_crm_email" value="<?php echo esc_attr($crm_email); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fhs_crm_password">CRM API Password</label></th>
                        <td>
                            <input name="fhs_crm_password" type="password" id="fhs_crm_password" value="<?php echo esc_attr($crm_pass); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fhs_gemini_api_key">Google Gemini API Key (Optional)</label></th>
                        <td>
                            <input name="fhs_gemini_api_key" type="password" id="fhs_gemini_api_key" value="<?php echo esc_attr($gemini_key); ?>" class="regular-text" placeholder="Optional: Gemini API key" />
                            <p class="description">If configured, Gemini will estimate salary and income range automatically. If left blank, the local heuristic engine is used.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fhs_openai_api_key">OpenAI API Key (Optional)</label></th>
                        <td>
                            <input name="fhs_openai_api_key" type="password" id="fhs_openai_api_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" placeholder="Optional: OpenAI API key" />
                            <p class="description">If configured, OpenAI will estimate salary and income range automatically. This is preferred over Gemini. Do not commit your API key into source control.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fhs_enable_local_logging">Enable Local Logging</label></th>
                        <td>
                            <label><input name="fhs_enable_local_logging" type="checkbox" id="fhs_enable_local_logging" value="1" <?php checked(1, $enable_logging); ?> /> Log CRM/Gemini/OpenAI responses locally for debugging (sensitive data may be stored).</label>
                            <p class="description">Enable only for debugging and remove when not needed. Logs may include partial API responses; take care with sensitive data.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="fhs_redirect_url">Redirect URL on Success (Optional)</label></th>
                        <td>
                            <input name="fhs_redirect_url" type="url" id="fhs_redirect_url" value="<?php echo esc_attr($redirect_url); ?>" class="regular-text" placeholder="https://mysite.com/thank-you/" />
                            <p class="description">If left blank, the form displays the confirmation card with assigned clinic in-place.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function render_admin_leads_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fhs_leads';
        $leads = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 200");
        ?>
        <div class="wrap">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h1>Captured Leads (Free Heart Scan)</h1>
                <a href="<?php echo admin_url('admin-post.php?action=fhs_export_csv'); ?>" class="button button-primary">📥 Export to CSV / Excel</a>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Lead ID</th>
                        <th>Name</th>
                        <th>Email / Phone</th>
                        <th>ZIP</th>
                        <th>Job Title / Experience</th>
                        <th>Income Range</th>
                        <th>Assigned Clinic</th>
                        <th>CRM Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leads)) : ?>
                        <tr>
                            <td colspan="9" style="text-align:center; padding:20px;">No leads recorded yet.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($leads as $lead) : ?>
                            <tr>
                                <td><?php echo esc_html(date('M d, Y H:i', strtotime($lead->created_at))); ?></td>
                                <td><code><?php echo esc_html($lead->lead_id); ?></code></td>
                                <td><strong><?php echo esc_html($lead->first_name . ' ' . $lead->last_name); ?></strong></td>
                                <td>
                                    <?php echo esc_html($lead->email); ?><br/>
                                    <small><?php echo esc_html($lead->phone); ?></small>
                                </td>
                                <td><?php echo esc_html($lead->zip_code); ?></td>
                                <td>
                                    <?php echo esc_html($lead->job_title); ?><br/>
                                    <small><?php echo esc_html($lead->years_in_position); ?> yrs | Stress: <?php echo esc_html($lead->stress_level); ?></small>
                                </td>
                                <td>
                                    <span style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:4px; font-weight:bold; font-size:11px;">
                                        <?php echo esc_html($lead->income_range); ?>
                                    </span>
                                    <?php if ($lead->estimated_income > 0) : ?>
                                        <br/><small style="color:#059669;">$<?php echo number_format($lead->estimated_income); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($lead->assigned_clinic); ?></td>
                                <td>
                                    <?php if ($lead->crm_synced) : ?>
                                        <span style="color:#15803d; font-weight:bold;">✅ Synced</span>
                                    <?php else : ?>
                                        <span style="color:#f59e0b; font-weight:bold;">⚡ Local Stored</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function export_leads_csv() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'fhs_leads';
        $leads = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=free_heart_scan_leads_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Lead ID', 'Date', 'First Name', 'Last Name', 'Email', 'Phone', 'ZIP Code', 'Age Range', 'Job Title', 'Years in Position', 'Stress Level', 'Estimated Income', 'Income Range', 'Assigned Clinic', 'CRM Synced'));

        foreach ($leads as $row) {
            fputcsv($output, array(
                $row['id'],
                $row['lead_id'],
                $row['created_at'],
                $row['first_name'],
                $row['last_name'],
                $row['email'],
                $row['phone'],
                $row['zip_code'],
                $row['age_range'],
                $row['job_title'],
                $row['years_in_position'],
                $row['stress_level'],
                $row['estimated_income'],
                $row['income_range'],
                $row['assigned_clinic'],
                $row['crm_synced'] ? 'YES' : 'NO'
            ));
        }

        fclose($output);
        exit;
    }

    public function register_rest_routes() {
        register_rest_route('free-heart-scan/v1', '/submit', array(
            'methods'  => 'POST',
            'callback' => array($this, 'handle_lead_submission'),
            'permission_callback' => '__return_true',
        ));
    }

    public function ajax_submit_lead() {
        check_ajax_referer('fhs_ajax_nonce', 'nonce');
        $params = $_POST;
        $request = new WP_REST_Request('POST');
        $request->set_body_params($params);
        $response = $this->handle_lead_submission($request);
        wp_send_json($response->get_data(), $response->get_status());
    }

    /**
     * Process and submit lead to CRM + Gemini + Local DB
     */
    public function handle_lead_submission($request) {
        $params = $request->get_json_params();
        if (empty($params)) {
            $params = $request->get_body_params();
        }

        $first_name = sanitize_text_field($params['first_name'] ?? '');
        $last_name  = sanitize_text_field($params['last_name'] ?? '');
        $email      = sanitize_email($params['email'] ?? '');
        $phone      = preg_replace('/\D/', '', $params['phone'] ?? '');
        $zip_code   = sanitize_text_field($params['zip_code'] ?? '');
        $age_range  = sanitize_text_field($params['age_range'] ?? '');
        $job_title  = sanitize_text_field($params['job_title'] ?? '');
        $years_in_position = sanitize_text_field($params['years_in_position'] ?? '3_5');
        $stress_level = sanitize_text_field($params['current_or_prior_work_related_stress_level'] ?? 'moderate');
        $employment_status = sanitize_text_field($params['employment_status'] ?? 'employed');
        $industry   = sanitize_text_field($params['industry'] ?? '');
        $manual_income_range = sanitize_text_field($params['income_range'] ?? '');

        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || strlen($phone) < 10 || strlen($zip_code) !== 5) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => 'Please fill in all required fields properly.'
            ), 400);
        }

        // 1. Calculate closest clinic
        $closest_location = $this->find_closest_clinic($zip_code);

        // 2. Estimate Income & Range via Gemini or Heuristic
        $income_calc = $this->calculate_income_and_range($job_title, $zip_code, $years_in_position, $employment_status, $industry, $manual_income_range);
        $final_income_range = $income_calc['income_range'];
        $final_salary = $income_calc['estimated_income'];

        // 3. Prepare payload for Life Imaging CRM
        $crm_payload = array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => strtolower($email),
            'phone'      => $phone,
            'zip_code'   => $zip_code,
            'age_range'  => $age_range,
            'income_range' => $final_income_range,
            'job_title'  => $job_title,
            'current_or_prior_work_related_stress_level' => $stress_level,
            'years_in_position' => $years_in_position,
            'use_ai_estimate' => true,
            'employment_status' => $employment_status,
            'industry'   => $industry,
            'estimated_income' => $final_salary,
        );

        // 4. Submit to Life Imaging CRM API
        $crm_result = $this->send_to_life_imaging_crm($crm_payload);
        $lead_id = $crm_result['lead_id'] ?? ('LEAD-' . substr(time(), -6));
        $crm_synced = !empty($crm_result['success']);

        // 5. Store in local WordPress database
        global $wpdb;
        $table_name = $wpdb->prefix . 'fhs_leads';
        $wpdb->insert($table_name, array(
            'lead_id'           => $lead_id,
            'first_name'        => $first_name,
            'last_name'         => $last_name,
            'email'             => $email,
            'phone'             => $phone,
            'zip_code'          => $zip_code,
            'age_range'         => $age_range,
            'job_title'         => $job_title,
            'years_in_position' => $years_in_position,
            'stress_level'      => $stress_level,
            'employment_status' => $employment_status,
            'industry'          => $industry,
            'estimated_income'  => $final_salary,
            'income_range'      => $final_income_range,
            'assigned_clinic'   => $closest_location['name'] . ' (' . $closest_location['city'] . ')',
            'crm_http_status'   => $crm_result['http_code'] ?? 200,
            'crm_synced'        => $crm_synced ? 1 : 0,
            'created_at'        => current_time('mysql'),
        ));

        $redirect_url = get_option('fhs_redirect_url', '');

        $is_approved = ($final_salary !== null && $final_salary >= 100000) || in_array($final_income_range, array('100000_124999', '125000_149999', '150000_199999', '200000_249999', '250000_plus'));

        return new WP_REST_Response(array(
            'success'          => true,
            'message'          => 'Lead successfully created.',
            'lead_id'          => $lead_id,
            'is_approved'      => $is_approved,
            'closest_location' => $closest_location,
            'estimated_income' => $final_salary,
            'income_range_used'=> $final_income_range,
            'submitted_payload'=> $crm_payload,
            'redirect_url'     => $redirect_url,
            'crm_status'       => $crm_result,
        ), 201);
    }

    private function calculate_income_and_range($job_title, $zip, $years, $employment, $industry, $manual_range) {
        if (!empty($manual_range)) {
            return array('income_range' => $manual_range, 'estimated_income' => null);
        }

        // Prefer OpenAI if configured
        $openai_key = get_option('fhs_openai_api_key');
        if (!empty($openai_key)) {
            $system = "You are a compensation validation specialist. Estimate the average annual salary in USD for the given user data. Respond ONLY with valid JSON containing the numeric field 'estimated_income', e.g. {\"estimated_income\": 85000}. Do not include any explanatory text.";
            $user_prompt = "Job: {$job_title}, ZIP: {$zip}, Experience: {$years}, Employment: {$employment}, Industry: {$industry}.";

            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $openai_key,
                ),
                'body' => json_encode(array(
                    'model' => 'gpt-3.5-turbo',
                    'messages' => array(
                        array('role' => 'system', 'content' => $system),
                        array('role' => 'user', 'content' => $user_prompt),
                    ),
                    'max_tokens' => 60,
                    'temperature' => 0.0,
                )),
                'timeout' => 8,
            ));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $text = $body['choices'][0]['message']['content'] ?? '';
                $parsed = json_decode($text, true);
                if (!empty($parsed['estimated_income']) && is_numeric($parsed['estimated_income'])) {
                    $salary = intval($parsed['estimated_income']);
                    return array('income_range' => $this->map_salary_to_range($salary), 'estimated_income' => $salary);
                }
            }
            // If OpenAI failed to produce a usable result, fall through to Gemini/heuristic
        }

        // Legacy Gemini support if OpenAI not configured
        $gemini_key = get_option('fhs_gemini_api_key');
        if (!empty($gemini_key)) {
            $prompt = "You are a compensation validation specialist. Estimate the average annual salary in USD for: Job: {$job_title}, ZIP: {$zip}, Experience: {$years}, Employment: {$employment}, Industry: {$industry}. Return JSON with 'estimated_income' (number).";
            $response = wp_remote_post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$gemini_key}", array(
                'headers' => array('Content-Type' => 'application/json'),
                'body'    => json_encode(array(
                    'contents' => array(
                        array('parts' => array(array('text' => $prompt)))
                    ),
                    'generationConfig' => array('responseMimeType' => 'application/json')
                )),
                'timeout' => 5,
            ));

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($text, true);
                if (!empty($parsed['estimated_income']) && is_numeric($parsed['estimated_income'])) {
                    $salary = intval($parsed['estimated_income']);
                    return array('income_range' => $this->map_salary_to_range($salary), 'estimated_income' => $salary);
                }
            }
        }

        // Heuristic fallback
        $salary = $this->heuristic_estimate_salary($job_title, $years);
        return array('income_range' => $this->map_salary_to_range($salary), 'estimated_income' => $salary);
    }

    private function map_salary_to_range($salary) {
        if ($salary < 50000) return 'under_50000';
        if ($salary < 75000) return '50000_74999';
        if ($salary < 100000) return '75000_99999';
        if ($salary < 125000) return '100000_124999';
        if ($salary < 150000) return '125000_149999';
        if ($salary < 200000) return '150000_199999';
        if ($salary < 250000) return '200000_249999';
        return '250000_plus';
    }

    private function heuristic_estimate_salary($job_title, $years) {
        $title = strtolower(trim($job_title));
        $base = 55000;

        $map = array(
            'doctor' => 220000, 'physician' => 230000, 'surgeon' => 290000, 'cardiologist' => 320000,
            'dentist' => 185000, 'nurse' => 82000, 'rn' => 85000, 'pilot' => 140000, 'engineer' => 105000,
            'software' => 125000, 'developer' => 115000, 'lawyer' => 145000, 'attorney' => 150000,
            'manager' => 92000, 'director' => 140000, 'executive' => 180000, 'vp' => 210000,
            'ceo' => 320000, 'cfo' => 280000, 'accountant' => 75000, 'realtor' => 80000, 'teacher' => 56000,
            'sales' => 70000, 'mechanic' => 50000, 'electrician' => 68000, 'plumber' => 65000,
        );

        foreach ($map as $key => $val) {
            if (strpos($title, $key) !== false) {
                $base = $val;
                break;
            }
        }

        $mult = 1.0;
        if ($years === 'less_than_1') $mult = 0.95;
        elseif ($years === '3_5') $mult = 1.08;
        elseif ($years === '6_10') $mult = 1.18;
        elseif ($years === '11_15' || $years === '16_20' || $years === '20_plus') $mult = 1.30;

        return round($base * $mult);
    }

    private function send_to_life_imaging_crm($payload) {
        $crm_base_url = get_option('fhs_crm_base_url', 'https://crm-api.lifeimagingfla.com');
        $crm_email    = get_option('fhs_crm_email', '');
        $crm_password = get_option('fhs_crm_password', '');
        $enable_logging = get_option('fhs_enable_local_logging', false);

        // 1. Get Token
        $token_res = wp_remote_post("{$crm_base_url}/v1/token/", array(
            'headers' => array('Content-Type' => 'application/json', 'Accept' => 'application/json'),
            'body'    => json_encode(array('email' => $crm_email, 'password' => $crm_password)),
            'timeout' => 8,
        ));

        if (is_wp_error($token_res)) {
            if ($enable_logging) error_log('[FHS] CRM token request error: ' . $token_res->get_error_message());
            return array('success' => false, 'error' => $token_res->get_error_message(), 'http_code' => 500);
        }

        $token_body = wp_remote_retrieve_body($token_res);
        $token_data = json_decode($token_body, true);

        // Support several possible token field names
        $access_token = null;
        if (!empty($token_data)) {
            if (!empty($token_data['access'])) $access_token = $token_data['access'];
            elseif (!empty($token_data['access_token'])) $access_token = $token_data['access_token'];
            elseif (!empty($token_data['token'])) $access_token = $token_data['token'];
            elseif (!empty($token_data['data']['access'])) $access_token = $token_data['data']['access'];
            elseif (!empty($token_data['data']['access_token'])) $access_token = $token_data['data']['access_token'];
            elseif (!empty($token_data['data']['token'])) $access_token = $token_data['data']['token'];
        }

        if (!$access_token) {
            if ($enable_logging) {
                error_log('[FHS] No access token received from CRM token endpoint. HTTP code: ' . wp_remote_retrieve_response_code($token_res) . ' Body: ' . substr($token_body, 0, 2000));
            }
            return array('success' => false, 'error' => 'No access token received', 'http_code' => wp_remote_retrieve_response_code($token_res), 'body' => $token_body);
        }

        // 2. Post Lead using the access token in Authorization header
        $lead_res = wp_remote_post("{$crm_base_url}/api/v1/leads/", array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => "Bearer {$access_token}",
            ),
            'body'    => json_encode($payload),
            'timeout' => 10,
        ));

        $http_code = wp_remote_retrieve_response_code($lead_res);
        $lead_body = wp_remote_retrieve_body($lead_res);
        $lead_data = json_decode($lead_body, true);

        if ($enable_logging) {
            error_log('[FHS] CRM lead post response HTTP ' . $http_code . ' Body: ' . substr($lead_body, 0, 2000));
        }

        if ($http_code === 201 || $http_code === 200) {
            return array('success' => true, 'lead_id' => $lead_data['id'] ?? $lead_data['result']['id'] ?? null, 'http_code' => $http_code, 'data' => $lead_data);
        }

        return array('success' => false, 'http_code' => $http_code, 'data' => $lead_data, 'body' => $lead_body);
    }

    private function get_clinics_list() {
        return array(
            array('name' => 'Deerfield Beach - Life Imaging', 'address' => '1981 W Hillsboro Boulevard', 'city' => 'Deerfield Beach', 'state' => 'FL', 'zip' => '33442', 'phone' => '954-834-6362'),
            array('name' => 'Orlando - Life Imaging', 'address' => '930 South Orange Avenue', 'city' => 'Orlando', 'state' => 'FL', 'zip' => '32806', 'phone' => '954-834-6362'),
            array('name' => 'Miami - Life Imaging', 'address' => '2344 S Douglas Road', 'city' => 'Coral Gables', 'state' => 'FL', 'zip' => '33134', 'phone' => '954-834-6362'),
            array('name' => 'Jupiter - Life Imaging', 'address' => '311 W Indiantown Rd.', 'city' => 'Jupiter', 'state' => 'FL', 'zip' => '33458', 'phone' => '954-834-6362'),
            array('name' => 'Jacksonville - Life Imaging', 'address' => '9965 San Jose Blvd', 'city' => 'Jacksonville', 'state' => 'FL', 'zip' => '32257', 'phone' => '954-834-6362'),
        );
    }

    private function find_closest_clinic($zip) {
        $clinics = $this->get_clinics_list();
        $num_zip = intval(preg_replace('/\D/', '', $zip));
        if ($num_zip === 0) return $clinics[0];

        $best = $clinics[0];
        $min_diff = 9999999;
        foreach ($clinics as $c) {
            $diff = abs($num_zip - intval($c['zip']));
            if ($diff < $min_diff) {
                $min_diff = $diff;
                $best = $c;
            }
        }
        return $best;
    }

    public function render_form_shortcode($atts) {
        wp_enqueue_style('fhs-form-css');
        wp_enqueue_script('fhs-form-js');

        ob_start();
        include FHS_PLUGIN_DIR . 'templates/form-template.php';
        return ob_get_clean();
    }

    public function register_elementor_widget($widgets_manager) {
        if (!class_exists('\Elementor\Widget_Base')) return;
        require_once FHS_PLUGIN_DIR . 'includes/class-elementor-widget.php';
        $widgets_manager->register(new \FreeHeartScan_Elementor_Widget());
    }
}

FreeHeartScanCompletePlugin::get_instance();
