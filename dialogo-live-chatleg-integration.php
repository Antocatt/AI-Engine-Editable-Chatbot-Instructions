<?php
/**
 * Plugin Name: Dialogo.Live - ChatLeg Integration
 * Plugin URI: https://chatleg.pro/dialogo
 * Description: Allows users to customize their AI chatbot prompts with admin controls and GDPR compliance
 * Version: 1.0.0
 * Author: Dialogo.Live
 * Author URI: https://chatleg.pro/dialogo
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dialogo-live-chatleg
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dialogo.Live - ChatLeg Integration Plugin
 * 
 * Allows each logged-in user to customize their AI chatbot prompts 
 * with admin controls and GDPR compliance for legal assistance.
 */

class DialogoLiveChatLegIntegration {
    
    /**
     * Plugin constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Add shortcode
        add_shortcode('dialogo_prompt_customizer', array($this, 'prompt_customizer_shortcode'));
        
        // Add custom rewrite rule for the route
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_custom_route'));
        
        // Admin menu and settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_save_dialogo_prompt', array($this, 'save_prompt_ajax'));
        add_action('wp_ajax_get_prompt_history', array($this, 'get_prompt_history_ajax'));
        add_action('wp_ajax_accept_dialogo_terms', array($this, 'accept_terms_ajax'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_custom_styles'));
        
        // Filter AI Engine chatbot prompts
        add_filter('mwai_chatbot_params', array($this, 'inject_user_prompt'), 10, 2);
    }
    
    /**
     * Add custom rewrite rules for /personalizza-assistente route
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^personalizza-assistente/?$', 'index.php?dialogo_page=customizer', 'top');
        add_rewrite_tag('%dialogo_page%', '([^&]+)');
    }
    
    /**
     * Handle custom route
     */
    public function handle_custom_route() {
        $dialogo_page = get_query_var('dialogo_page');
        if ($dialogo_page === 'customizer') {
            // Ensure user is logged in
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(get_home_url() . '/personalizza-assistente'));
                exit;
            }
            
            // Load custom template or display shortcode content
            get_header();
            echo '<div class="dialogo-page-content">';
            echo do_shortcode('[dialogo_prompt_customizer]');
            echo '</div>';
            get_footer();
            exit;
        }
    }
    
    /**
     * Main shortcode function
     */
    public function prompt_customizer_shortcode($atts) {
        // Must be logged in to use
        if (!is_user_logged_in()) {
            return '<div class="dialogo-error">Devi essere loggato per personalizzare il tuo assistente.</div>';
        }
        
        $current_user = wp_get_current_user();
        $username = $current_user->display_name;
        
        // Check if user has accepted terms
        $consent_accepted = get_user_meta($current_user->ID, 'dialogo_consent_accepted', true);
        
        // Get user's current settings
        $prompt_string = get_user_meta($current_user->ID, 'dialogo_prompt_string', true);
        $field_of_law = get_user_meta($current_user->ID, 'dialogo_field_of_law', true);
        $prompt_history = get_user_meta($current_user->ID, 'dialogo_prompt_history', true);
        
        if (empty($prompt_history)) {
            $prompt_history = array();
        }
        
        // Get admin settings
        $max_chars = get_option('dialogo_max_chars', 1000);
        $show_hardcoded = get_option('dialogo_show_hardcoded_prompt', true);
        $hardcoded_prompt = get_option('dialogo_hardcoded_prompt', 'Sei un assistente legale specializzato.');
        
        ob_start();
        ?>
        
        <div id="dialogo-customizer-container">
            <!-- GDPR/ToS Overlay -->
            <?php if (!$consent_accepted): ?>
            <div id="dialogo-consent-overlay" class="dialogo-overlay">
                <div class="dialogo-consent-modal">
                    <h3>Termini di Servizio e Privacy</h3>
                    <div class="dialogo-consent-content">
                        <p>Prima di personalizzare il tuo assistente legale, devi accettare i nostri termini di servizio e la politica sulla privacy.</p>
                        <p>I tuoi dati personali e le personalizzazioni dell'assistente saranno trattati in conformità al GDPR.</p>
                        <ul>
                            <li>Le tue personalizzazioni saranno salvate in modo sicuro</li>
                            <li>Puoi modificare o cancellare i tuoi dati in qualsiasi momento</li>
                            <li>I dati non saranno condivisi con terze parti senza consenso</li>
                        </ul>
                    </div>
                    <div class="dialogo-consent-actions">
                        <button id="dialogo-accept-terms" class="dialogo-btn-primary">Accetto i Termini</button>
                        <button id="dialogo-decline-terms" class="dialogo-btn-secondary">Rifiuto</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Interface -->
            <div class="dialogo-main-interface" <?php echo !$consent_accepted ? 'style="display:none;"' : ''; ?>>
                
                <!-- User Greeting -->
                <div class="dialogo-greeting">
                    <h2>Ciao <?php echo esc_html($username); ?>, queste sono le tue istruzioni custom per il modello Omnia.</h2>
                </div>
                
                <!-- Legal Area Selection -->
                <div class="dialogo-section">
                    <label for="dialogo-legal-area">Area Legale:</label>
                    <select id="dialogo-legal-area" name="legal_area">
                        <option value="">Seleziona area legale</option>
                        <option value="penale" <?php selected($field_of_law, 'penale'); ?>>Penale</option>
                        <option value="civile" <?php selected($field_of_law, 'civile'); ?>>Civile</option>
                        <option value="tributario" <?php selected($field_of_law, 'tributario'); ?>>Tributario</option>
                        <option value="lavoro" <?php selected($field_of_law, 'lavoro'); ?>>Lavoro</option>
                        <option value="altro" <?php selected($field_of_law, 'altro'); ?>>Altro</option>
                    </select>
                </div>
                
                <!-- Preset Buttons -->
                <div class="dialogo-section">
                    <h3>Template Predefiniti:</h3>
                    <div class="dialogo-presets">
                        <button class="dialogo-preset-btn" data-preset="penale">Assistente Penale</button>
                        <button class="dialogo-preset-btn" data-preset="civile">Assistente Civile</button>
                        <button class="dialogo-preset-btn" data-preset="tributario">Assistente Tributario</button>
                        <button class="dialogo-preset-btn" data-preset="lavoro">Assistente Lavoro</button>
                        <button class="dialogo-preset-btn" data-preset="generale">Assistente Generale</button>
                    </div>
                </div>
                
                <!-- Hardcoded Prompt Display (if enabled) -->
                <?php if ($show_hardcoded && !empty($hardcoded_prompt)): ?>
                <div class="dialogo-section">
                    <h3>Istruzioni Base del Sistema:</h3>
                    <div class="dialogo-hardcoded-prompt">
                        <?php echo nl2br(esc_html($hardcoded_prompt)); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Custom Prompt Input -->
                <div class="dialogo-section">
                    <h3>Le Tue Istruzioni Personalizzate:</h3>
                    <textarea 
                        id="dialogo-prompt-input" 
                        placeholder="Inserisci qui le tue istruzioni personalizzate per l'assistente legale..."
                        maxlength="<?php echo esc_attr($max_chars); ?>"
                    ><?php echo esc_textarea($prompt_string); ?></textarea>
                    
                    <!-- Character Counter -->
                    <div class="dialogo-char-counter">
                        <span id="dialogo-char-count"><?php echo strlen($prompt_string); ?></span> / <?php echo $max_chars; ?> caratteri
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="dialogo-actions">
                    <button id="dialogo-save-btn" class="dialogo-btn-primary">Salva</button>
                    <button id="dialogo-reset-btn" class="dialogo-btn-secondary">Reset</button>
                    <button id="dialogo-history-btn" class="dialogo-btn-secondary">Cronologia</button>
                </div>
                
                <!-- Status Messages -->
                <div id="dialogo-status" class="dialogo-status"></div>
                
                <!-- History Modal -->
                <div id="dialogo-history-modal" class="dialogo-modal" style="display:none;">
                    <div class="dialogo-modal-content">
                        <span class="dialogo-close">&times;</span>
                        <h3>Cronologia delle Modifiche</h3>
                        <div id="dialogo-history-list"></div>
                    </div>
                </div>
                
                <!-- Live Chatbot Preview -->
                <div class="dialogo-section">
                    <h3>Anteprima Chatbot:</h3>
                    <div class="dialogo-chatbot-preview">
                        <?php echo do_shortcode('[mwai_chatbot]'); ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Branding -->
            <div class="dialogo-branding">
                Powered by <a href="https://chatleg.pro/dialogo" target="_blank" rel="noopener">Dialogo.Live</a>
            </div>
            
        </div>
        
        <?php
        return ob_get_clean();
    }

    
    /**
     * AJAX handler to save user's custom prompt
     */
    public function save_prompt_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dialogo_save_prompt')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        $user_id = get_current_user_id();
        $prompt_string = sanitize_textarea_field($_POST['prompt_string']);
        $field_of_law = sanitize_text_field($_POST['field_of_law']);
        
        // Get character limit from settings
        $max_chars = get_option('dialogo_max_chars', 1000);
        if (strlen($prompt_string) > $max_chars) {
            wp_send_json_error('Prompt exceeds character limit');
            return;
        }
        
        // Save current prompt to history before updating
        $current_prompt = get_user_meta($user_id, 'dialogo_prompt_string', true);
        if (!empty($current_prompt) && $current_prompt !== $prompt_string) {
            $history = get_user_meta($user_id, 'dialogo_prompt_history', true);
            if (!is_array($history)) {
                $history = array();
            }
            
            // Add to history with timestamp
            $history[] = array(
                'prompt' => $current_prompt,
                'field_of_law' => get_user_meta($user_id, 'dialogo_field_of_law', true),
                'timestamp' => current_time('mysql')
            );
            
            // Keep only last 10 entries
            $history = array_slice($history, -10);
            update_user_meta($user_id, 'dialogo_prompt_history', $history);
        }
        
        // Update user meta
        update_user_meta($user_id, 'dialogo_prompt_string', $prompt_string);
        update_user_meta($user_id, 'dialogo_field_of_law', $field_of_law);
        
        wp_send_json_success('Prompt salvato con successo');
    }
    
    /**
     * AJAX handler to get prompt history
     */
    public function get_prompt_history_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'dialogo_get_history')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        $user_id = get_current_user_id();
        $history = get_user_meta($user_id, 'dialogo_prompt_history', true);
        
        if (!is_array($history)) {
            $history = array();
        }
        
        wp_send_json_success($history);
    }
    
    /**
     * AJAX handler to accept terms
     */
    public function accept_terms_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'dialogo_accept_terms')) {
            wp_die('Security check failed');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        $user_id = get_current_user_id();
        update_user_meta($user_id, 'dialogo_consent_accepted', true);
        update_user_meta($user_id, 'dialogo_consent_date', current_time('mysql'));
        
        wp_send_json_success('Consent recorded');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Dialogo.Live Settings',
            'Dialogo.Live Settings',
            'manage_options',
            'dialogo-live-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register admin settings
     */
    public function register_settings() {
        register_setting('dialogo_settings', 'dialogo_hardcoded_prompt');
        register_setting('dialogo_settings', 'dialogo_max_chars');
        register_setting('dialogo_settings', 'dialogo_show_hardcoded_prompt');
        register_setting('dialogo_settings', 'dialogo_moderation_enabled');
        register_setting('dialogo_settings', 'dialogo_preset_penale');
        register_setting('dialogo_settings', 'dialogo_preset_civile');
        register_setting('dialogo_settings', 'dialogo_preset_tributario');
        register_setting('dialogo_settings', 'dialogo_preset_lavoro');
        register_setting('dialogo_settings', 'dialogo_preset_generale');
    }
    
    /**
     * Admin settings page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Dialogo.Live Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('dialogo_settings'); ?>
                <?php do_settings_sections('dialogo_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Hardcoded Prompt</th>
                        <td>
                            <textarea name="dialogo_hardcoded_prompt" rows="5" cols="60" class="large-text"><?php echo esc_textarea(get_option('dialogo_hardcoded_prompt', 'Sei un assistente legale specializzato.')); ?></textarea>
                            <p class="description">Base system prompt that will be combined with user's custom prompt</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Max Characters</th>
                        <td>
                            <input type="number" name="dialogo_max_chars" value="<?php echo esc_attr(get_option('dialogo_max_chars', 1000)); ?>" min="100" max="5000" />
                            <p class="description">Maximum characters allowed for user input</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Show Hardcoded Prompt</th>
                        <td>
                            <input type="checkbox" name="dialogo_show_hardcoded_prompt" value="1" <?php checked(get_option('dialogo_show_hardcoded_prompt', true)); ?> />
                            <p class="description">Display the hardcoded prompt to users</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Enable Moderation</th>
                        <td>
                            <input type="checkbox" name="dialogo_moderation_enabled" value="1" <?php checked(get_option('dialogo_moderation_enabled', false)); ?> />
                            <p class="description">Enable content moderation filter for user prompts</p>
                        </td>
                    </tr>
                </table>
                
                <h2>Legal Presets</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Preset Penale</th>
                        <td>
                            <textarea name="dialogo_preset_penale" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('dialogo_preset_penale', 'Specializzati in diritto penale. Fornisci consulenza su reati, procedimenti penali e difesa legale.')); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Preset Civile</th>
                        <td>
                            <textarea name="dialogo_preset_civile" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('dialogo_preset_civile', 'Specializzati in diritto civile. Aiuta con contratti, proprietà, responsabilità civile e controversie private.')); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Preset Tributario</th>
                        <td>
                            <textarea name="dialogo_preset_tributario" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('dialogo_preset_tributario', 'Specializzati in diritto tributario. Consulenza su tasse, imposte, detrazioni e controversie fiscali.')); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Preset Lavoro</th>
                        <td>
                            <textarea name="dialogo_preset_lavoro" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('dialogo_preset_lavoro', 'Specializzati in diritto del lavoro. Assistenza su contratti di lavoro, licenziamenti, diritti dei lavoratori.')); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Preset Generale</th>
                        <td>
                            <textarea name="dialogo_preset_generale" rows="3" cols="60" class="large-text"><?php echo esc_textarea(get_option('dialogo_preset_generale', 'Assistente legale generale. Fornisci consulenza di base su varie aree del diritto italiano.')); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <div style="margin-top: 40px; padding: 20px; background: #f1f1f1; border-radius: 5px;">
                <h3>Powered by Dialogo.Live</h3>
                <p>Visit <a href="https://chatleg.pro/dialogo" target="_blank">https://chatleg.pro/dialogo</a> for more information.</p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Filter AI Engine chatbot params to inject user prompt
     */
    public function inject_user_prompt($params, $chatbot_id) {
        if (!is_user_logged_in()) {
            return $params;
        }
        
        $user_id = get_current_user_id();
        $user_prompt = get_user_meta($user_id, 'dialogo_prompt_string', true);
        $hardcoded_prompt = get_option('dialogo_hardcoded_prompt', '');
        
        if (!empty($user_prompt) || !empty($hardcoded_prompt)) {
            $combined_prompt = trim($hardcoded_prompt . "\n\n" . $user_prompt);
            if (!empty($combined_prompt)) {
                $params['instructions'] = $combined_prompt;
            }
        }
        
        return $params;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (is_admin()) return;
        
        // Only load on pages that contain our shortcode or custom route
        global $post;
        if ((!is_object($post) || !has_shortcode($post->post_content, 'dialogo_prompt_customizer')) && 
            get_query_var('dialogo_page') !== 'customizer') {
            return;
        }
        
        // Enqueue wp-util for AJAX functionality
        wp_enqueue_script('wp-util');
        
        // Add inline JavaScript since external file might have path issues
        wp_add_inline_script('wp-util', $this->get_inline_javascript());
        
        // Localize script for AJAX
        wp_localize_script('wp-util', 'dialogo_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'save_nonce' => wp_create_nonce('dialogo_save_prompt'),
            'history_nonce' => wp_create_nonce('dialogo_get_history'),
            'terms_nonce' => wp_create_nonce('dialogo_accept_terms'),
            'presets' => array(
                'penale' => get_option('dialogo_preset_penale', 'Specializzati in diritto penale. Fornisci consulenza su reati, procedimenti penali e difesa legale.'),
                'civile' => get_option('dialogo_preset_civile', 'Specializzati in diritto civile. Aiuta con contratti, proprietà, responsabilità civile e controversie private.'),
                'tributario' => get_option('dialogo_preset_tributario', 'Specializzati in diritto tributario. Consulenza su tasse, imposte, detrazioni e controversie fiscali.'),
                'lavoro' => get_option('dialogo_preset_lavoro', 'Specializzati in diritto del lavoro. Assistenza su contratti di lavoro, licenziamenti, diritti dei lavoratori.'),
                'generale' => get_option('dialogo_preset_generale', 'Assistente legale generale. Fornisci consulenza di base su varie aree del diritto italiano.')
            )
        ));
    }
    
    /**
     * Get inline JavaScript content
     */
    private function get_inline_javascript() {
        $js_file = plugin_dir_path(__FILE__) . 'dialogo-customizer.js';
        if (file_exists($js_file)) {
            return file_get_contents($js_file);
        }
        return '// JavaScript file not found';
    }

    
    /**
     * Add custom CSS styles
     */
    public function add_custom_styles() {
        // Only load on pages that contain our shortcode or custom route
        global $post;
        if ((!is_object($post) || !has_shortcode($post->post_content, 'dialogo_prompt_customizer')) && 
            get_query_var('dialogo_page') !== 'customizer') {
            return;
        }
        
        ?>
        <style>
        /* Dialogo.Live ChatLeg Integration Styles */
        #dialogo-customizer-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        
        .dialogo-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dialogo-consent-modal {
            background: white;
            border-radius: 8px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .dialogo-consent-modal h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.5em;
        }
        
        .dialogo-consent-content {
            margin: 20px 0;
            line-height: 1.6;
        }
        
        .dialogo-consent-content ul {
            padding-left: 20px;
        }
        
        .dialogo-consent-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .dialogo-greeting h2 {
            color: #2c5aa0;
            font-size: 1.8em;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .dialogo-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .dialogo-section h3 {
            margin-top: 0;
            color: #333;
            font-size: 1.2em;
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 10px;
        }
        
        .dialogo-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        #dialogo-legal-area {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .dialogo-presets {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .dialogo-preset-btn {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .dialogo-preset-btn:hover {
            background: #1e3f73;
        }
        
        .dialogo-hardcoded-prompt {
            background: #e8f4f8;
            border: 1px solid #b3d9e8;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            color: #333;
        }
        
        #dialogo-prompt-input {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            resize: vertical;
            box-sizing: border-box;
        }
        
        #dialogo-prompt-input:focus {
            border-color: #2c5aa0;
            outline: none;
        }
        
        .dialogo-char-counter {
            text-align: right;
            margin-top: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .dialogo-char-counter.warning {
            color: #ff6600;
            font-weight: bold;
        }
        
        .dialogo-char-counter.error {
            color: #cc0000;
            font-weight: bold;
        }
        
        .dialogo-actions {
            text-align: center;
            margin: 30px 0;
        }
        
        .dialogo-btn-primary, .dialogo-btn-secondary {
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        .dialogo-btn-primary {
            background: #2c5aa0;
            color: white;
        }
        
        .dialogo-btn-primary:hover:not(:disabled) {
            background: #1e3f73;
        }
        
        .dialogo-btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .dialogo-btn-secondary {
            background: #666;
            color: white;
        }
        
        .dialogo-btn-secondary:hover {
            background: #555;
        }
        
        .dialogo-status {
            margin: 20px 0;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            display: none;
        }
        
        .dialogo-status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            display: block;
        }
        
        .dialogo-status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            display: block;
        }
        
        .dialogo-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dialogo-modal-content {
            background: white;
            border-radius: 8px;
            padding: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .dialogo-close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }
        
        .dialogo-close:hover {
            color: #000;
        }
        
        .dialogo-history-item {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .dialogo-history-item .timestamp {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .dialogo-history-item .field-of-law {
            font-size: 12px;
            color: #2c5aa0;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .dialogo-history-item .prompt-text {
            font-family: monospace;
            background: white;
            padding: 10px;
            border-radius: 3px;
            white-space: pre-wrap;
        }
        
        .dialogo-chatbot-preview {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }
        
        .dialogo-branding {
            text-align: center;
            margin-top: 40px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 4px;
            font-size: 14px;
            color: #666;
        }
        
        .dialogo-branding a {
            color: #2c5aa0;
            text-decoration: none;
            font-weight: bold;
        }
        
        .dialogo-branding a:hover {
            text-decoration: underline;
        }
        
        .dialogo-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            #dialogo-customizer-container {
                padding: 10px;
            }
            
            .dialogo-presets {
                flex-direction: column;
            }
            
            .dialogo-preset-btn {
                width: 100%;
                margin-bottom: 5px;
            }
            
            .dialogo-btn-primary, .dialogo-btn-secondary {
                display: block;
                width: 100%;
                margin: 5px 0;
            }
            
            .dialogo-consent-modal {
                padding: 20px;
            }
        }
        </style>
        <?php
    }
}

// Initialize the plugin
new DialogoLiveChatLegIntegration();
