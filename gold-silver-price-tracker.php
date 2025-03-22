<?php
/**
 * Plugin Name: Gold & Silver Price Tracker
 
 * Description: Display real-time gold and silver prices in any currency with customizable API key.
 * Version: 1.0.0
 * Author: Sohan Mehta
 * Author URI: https://sohanmehta.com.np
 * Text Domain: gold-silver-price
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

class Gold_Silver_Price_Tracker {
    // Plugin variables
    private $plugin_slug = 'gold-silver-price';
    private $options_name = 'gold_silver_price_options';
    private $cache_key = 'gold_silver_price_data';
    
    // Constructor
    public function __construct() {
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        
        // Add menu and settings
        add_action('admin_menu', array($this, 'add_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Register shortcode
        add_shortcode('metal_prices', array($this, 'display_metal_prices'));
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX handlers for refresh
        add_action('wp_ajax_refresh_metal_prices', array($this, 'refresh_prices'));
        add_action('wp_ajax_nopriv_refresh_metal_prices', array($this, 'refresh_prices'));
    }
    
    /**
     * Plugin activation
     */
    public function activate_plugin() {
        // Set default options
        $default_options = array(
            'api_key' => '',
            'currency' => 'USD',
            'weight_unit' => 'oz', // oz, g, kg, tola
            'cache_time' => 60, // minutes
            'title' => 'Today\'s Metal Prices',
            'layout' => 'standard',
            'custom_css' => ''
        );
        
        add_option($this->options_name, $default_options);
    }
    
    /**
     * Add admin menu
     */
    public function add_settings_menu() {
        add_options_page(
            __('Gold & Silver Price Settings', $this->plugin_slug),
            __('Metal Price Tracker', $this->plugin_slug),
            'manage_options',
            $this->plugin_slug,
            array($this, 'settings_page')
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Gold & Silver Price Tracker Settings', $this->plugin_slug); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->options_name);
                do_settings_sections($this->plugin_slug);
                submit_button();
                ?>
            </form>
            <div class="card" style="max-width: 600px; margin-top: 20px; padding: 10px 20px;">
                <h2><?php echo esc_html__('Shortcode Usage', $this->plugin_slug); ?></h2>
                <p><?php echo esc_html__('Use this shortcode to display metal prices on your site:', $this->plugin_slug); ?></p>
                <code>[metal_prices]</code>
                
                <h3><?php echo esc_html__('Advanced Shortcode Options', $this->plugin_slug); ?></h3>
                <p><?php echo esc_html__('You can customize individual instances with these attributes:', $this->plugin_slug); ?></p>
                <code>[metal_prices title="Custom Title" currency="EUR" weight_unit="g" show_chart="yes" metals="gold,silver"]</code>
            </div>
        </div>
        <?php
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            $this->options_name,
            $this->options_name,
            array($this, 'validate_settings')
        );
        
        // API Settings
        add_settings_section(
            'api_settings',
            __('API Settings', $this->plugin_slug),
            array($this, 'api_section_info'),
            $this->plugin_slug
        );
        
        add_settings_field(
            'api_key',
            __('GoldAPI.io API Key', $this->plugin_slug),
            array($this, 'api_key_field'),
            $this->plugin_slug,
            'api_settings'
        );
        
        add_settings_field(
            'cache_time',
            __('Cache Duration', $this->plugin_slug),
            array($this, 'cache_time_field'),
            $this->plugin_slug,
            'api_settings'
        );
        
        // Display Settings
        add_settings_section(
            'display_settings',
            __('Display Settings', $this->plugin_slug),
            array($this, 'display_section_info'),
            $this->plugin_slug
        );
        
        add_settings_field(
            'title',
            __('Default Title', $this->plugin_slug),
            array($this, 'title_field'),
            $this->plugin_slug,
            'display_settings'
        );
        
        add_settings_field(
            'currency',
            __('Default Currency', $this->plugin_slug),
            array($this, 'currency_field'),
            $this->plugin_slug,
            'display_settings'
        );
        
        add_settings_field(
            'weight_unit',
            __('Weight Unit', $this->plugin_slug),
            array($this, 'weight_unit_field'),
            $this->plugin_slug,
            'display_settings'
        );
        
        add_settings_field(
            'layout',
            __('Layout Style', $this->plugin_slug),
            array($this, 'layout_field'),
            $this->plugin_slug,
            'display_settings'
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', $this->plugin_slug),
            array($this, 'custom_css_field'),
            $this->plugin_slug,
            'display_settings'
        );
    }
    
    /**
     * Section info
     */
    public function api_section_info() {
        echo '<p>' . __('Configure your GoldAPI.io API key and cache settings.', $this->plugin_slug) . '</p>';
        echo '<p>' . __('Get a free API key from <a href="https://www.goldapi.io" target="_blank">GoldAPI.io</a>', $this->plugin_slug) . '</p>';
    }
    
    public function display_section_info() {
        echo '<p>' . __('Customize how the metal prices are displayed on your website.', $this->plugin_slug) . '</p>';
    }
    
    /**
     * Settings fields
     */
    public function api_key_field() {
        $options = get_option($this->options_name);
        echo '<input type="text" id="api_key" name="' . $this->options_name . '[api_key]" value="' . esc_attr($options['api_key']) . '" class="regular-text" />';
    }
    
    public function cache_time_field() {
        $options = get_option($this->options_name);
        echo '<input type="number" id="cache_time" name="' . $this->options_name . '[cache_time]" value="' . esc_attr($options['cache_time']) . '" class="small-text" min="5" max="1440" /> ' . __('minutes', $this->plugin_slug);
    }
    
    public function title_field() {
        $options = get_option($this->options_name);
        echo '<input type="text" id="title" name="' . $this->options_name . '[title]" value="' . esc_attr($options['title']) . '" class="regular-text" />';
    }
    
    public function currency_field() {
        $options = get_option($this->options_name);
        $currencies = array(
            'USD' => __('US Dollar (USD)', $this->plugin_slug),
            'EUR' => __('Euro (EUR)', $this->plugin_slug),
            'GBP' => __('British Pound (GBP)', $this->plugin_slug),
            'INR' => __('Indian Rupee (INR)', $this->plugin_slug),
            'NPR' => __('Nepalese Rupee (NPR)', $this->plugin_slug),
            'AUD' => __('Australian Dollar (AUD)', $this->plugin_slug),
            'CAD' => __('Canadian Dollar (CAD)', $this->plugin_slug),
            'CHF' => __('Swiss Franc (CHF)', $this->plugin_slug),
            'JPY' => __('Japanese Yen (JPY)', $this->plugin_slug),
            'CNY' => __('Chinese Yuan (CNY)', $this->plugin_slug)
        );
        
        echo '<select id="currency" name="' . $this->options_name . '[currency]">';
        foreach ($currencies as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($options['currency'], $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }
    
    public function weight_unit_field() {
        $options = get_option($this->options_name);
        $weight_units = array(
            'oz' => __('Troy Ounce (oz)', $this->plugin_slug),
            'g' => __('Gram (g)', $this->plugin_slug),
            'kg' => __('Kilogram (kg)', $this->plugin_slug),
            'tola' => __('Tola', $this->plugin_slug)
        );
        
        echo '<select id="weight_unit" name="' . $this->options_name . '[weight_unit]">';
        foreach ($weight_units as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($options['weight_unit'], $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }
    
    public function layout_field() {
        $options = get_option($this->options_name);
        $layouts = array(
            'standard' => __('Standard', $this->plugin_slug),
            'compact' => __('Compact', $this->plugin_slug),
            'detailed' => __('Detailed', $this->plugin_slug)
        );
        
        echo '<select id="layout" name="' . $this->options_name . '[layout]">';
        foreach ($layouts as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($options['layout'], $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }
    
    public function custom_css_field() {
        $options = get_option($this->options_name);
        echo '<textarea id="custom_css" name="' . $this->options_name . '[custom_css]" rows="6" cols="50" class="large-text code">' . esc_textarea($options['custom_css']) . '</textarea>';
    }
    
    /**
     * Validate settings
     */
    public function validate_settings($input) {
        $validated = array();
        
        $validated['api_key'] = sanitize_text_field($input['api_key']);
        $validated['currency'] = sanitize_text_field($input['currency']);
        $validated['weight_unit'] = sanitize_text_field($input['weight_unit']);
        $validated['title'] = sanitize_text_field($input['title']);
        $validated['layout'] = sanitize_text_field($input['layout']);
        $validated['custom_css'] = sanitize_textarea_field($input['custom_css']);
        
        $cache_time = intval($input['cache_time']);
        $validated['cache_time'] = ($cache_time < 5) ? 5 : (($cache_time > 1440) ? 1440 : $cache_time);
        
        // Clear the cache when settings are changed
        delete_transient($this->cache_key);
        
        return $validated;
    }
    
    /**
     * Get metal prices
     */
    public function get_metal_prices() {
        $options = get_option($this->options_name);
        
        // Check cache first
        $prices = get_transient($this->cache_key);
        
        if (false === $prices || empty($prices)) {
            // Gold price (XAU)
            $gold_data = $this->fetch_metal_price('XAU', $options['currency']);
            
            // Silver price (XAG)
            $silver_data = $this->fetch_metal_price('XAG', $options['currency']);
            
            if ($gold_data && $silver_data) {
                // Convert to selected weight unit
                $gold_price = $this->convert_weight_unit($gold_data['price'], 'oz', $options['weight_unit']);
                $silver_price = $this->convert_weight_unit($silver_data['price'], 'oz', $options['weight_unit']);
                
                $prices = array(
                    'gold' => array(
                        'price' => $gold_price,
                        'currency' => $options['currency'],
                        'weight_unit' => $options['weight_unit'],
                        'change_percentage' => $gold_data['ch_percent'] 
                    ),
                    'silver' => array(
                        'price' => $silver_price,
                        'currency' => $options['currency'],
                        'weight_unit' => $options['weight_unit'],
                        'change_percentage' => $silver_data['ch_percent']
                    ),
                    'last_updated' => current_time('mysql')
                );
                
                // Cache based on admin setting
                set_transient($this->cache_key, $prices, $options['cache_time'] * 60);
            }
        }
        
        return $prices;
    }
    
    /**
     * Fetch metal price from API
     */
    public function fetch_metal_price($symbol, $currency) {
        $options = get_option($this->options_name);
        $api_key = $options['api_key'];
        
        $url = "https://www.goldapi.io/api/{$symbol}/{$currency}";
        $headers = array(
            'x-access-token: ' . $api_key
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            return json_decode($response, true);
        }
        
        return false;
    }
    
    /**
     * Convert between weight units
     */
    public function convert_weight_unit($price, $from_unit, $to_unit) {
        // Conversion rates to troy ounce
        $to_oz = array(
            'oz' => 1,
            'g' => 0.0321507466,
            'kg' => 32.1507466,
            'tola' => 0.375
        );
        
        // Convert from the source unit to troy ounce first (if needed)
        if ($from_unit != 'oz') {
            $price = $price / $to_oz[$from_unit];
        }
        
        // Convert from troy ounce to target unit
        return $price * $to_oz[$to_unit];
    }
    
    /**
     * Display metal prices shortcode
     */
    public function display_metal_prices($atts) {
        $options = get_option($this->options_name);
        
        // Parse shortcode attributes
        $attributes = shortcode_atts(array(
            'title' => $options['title'],
            'currency' => $options['currency'],
            'weight_unit' => $options['weight_unit'],
            'layout' => $options['layout'],
            'metals' => 'gold,silver'
        ), $atts);
        
        // Get prices data
        $prices = $this->get_metal_prices();
        
        if (!$prices) {
            return '<p>' . __('Unable to fetch current prices. Please check your API key.', $this->plugin_slug) . '</p>';
        }
        
        // Get which metals to display
        $metals_to_show = explode(',', $attributes['metals']);
        
        // Start building output
        $output = '<div class="metal-prices-container layout-' . esc_attr($attributes['layout']) . '">';
        $output .= '<h3>' . esc_html($attributes['title']) . '</h3>';
        
        // Display gold if requested
        if (in_array('gold', $metals_to_show)) {
            $gold_price = number_format($prices['gold']['price'], 2);
            $gold_change = $prices['gold']['change_percentage'];
            $gold_arrow = $gold_change >= 0 ? '↑' : '↓';
            $gold_class = $gold_change >= 0 ? 'price-up' : 'price-down';
            
            $output .= '
            <div class="metal-price gold">
                <h4>' . __('Gold', $this->plugin_slug) . '</h4>
                <div class="price-value">' . esc_html($attributes['currency']) . ' ' . esc_html($gold_price) . 
                    '<span>' . __('per', $this->plugin_slug) . ' ' . esc_html($attributes['weight_unit']) . '</span></div>
                <div class="price-change ' . esc_attr($gold_class) . '">' . esc_html($gold_arrow) . ' ' . 
                    esc_html(abs($gold_change)) . '%</div>
            </div>';
        }
        
        // Display silver if requested
        if (in_array('silver', $metals_to_show)) {
            $silver_price = number_format($prices['silver']['price'], 2);
            $silver_change = $prices['silver']['change_percentage'];
            $silver_arrow = $silver_change >= 0 ? '↑' : '↓';
            $silver_class = $silver_change >= 0 ? 'price-up' : 'price-down';
            
            $output .= '
            <div class="metal-price silver">
                <h4>' . __('Silver', $this->plugin_slug) . '</h4>
                <div class="price-value">' . esc_html($attributes['currency']) . ' ' . esc_html($silver_price) . 
                    '<span>' . __('per', $this->plugin_slug) . ' ' . esc_html($attributes['weight_unit']) . '</span></div>
                <div class="price-change ' . esc_attr($silver_class) . '">' . esc_html($silver_arrow) . ' ' . 
                    esc_html(abs($silver_change)) . '%</div>
            </div>';
        }
        
        // Add footer with timestamp and refresh button
        $output .= '
        <div class="price-footer">
            <div class="update-time">' . __('Last Updated:', $this->plugin_slug) . ' ' . 
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($prices['last_updated'])) . '</div>
            <button id="refresh-prices" class="refresh-btn">' . __('Refresh Prices', $this->plugin_slug) . '</button>
        </div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        $options = get_option($this->options_name);
        
        // Register and enqueue the style
        wp_register_style('metal-prices-style', false);
        wp_enqueue_style('metal-prices-style');
        
        // Add the CSS
        $custom_css = "
        .metal-prices-container {
            max-width: 500px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .metal-prices-container h3 {
            text-align: center;
            margin: 0 0 20px 0;
            font-size: 20px;
            color: #333;
        }
        
        .metal-price {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .metal-price.gold {
            border-left: 4px solid #FFD700;
        }
        
        .metal-price.silver {
            border-left: 4px solid #C0C0C0;
        }
        
        .metal-price h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .price-value {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .price-value span {
            font-size: 14px;
            color: #666;
            font-weight: normal;
            margin-left: 5px;
        }
        
        .price-change {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            width: fit-content;
        }
        
        .price-change.price-up {
            background-color: rgba(0, 255, 0, 0.1);
            color: #008800;
        }
        
        .price-change.price-down {
            background-color: rgba(255, 0, 0, 0.1);
            color: #dd0000;
        }
        
        .price-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        
        .update-time {
            font-size: 13px;
            color: #777;
        }
        
        .refresh-btn {
            background: #0066cc;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .refresh-btn:hover {
            background: #0055aa;
        }
        
        /* Compact layout modifications */
        .layout-compact .metal-price {
            padding: 10px;
        }
        
        .layout-compact .price-value {
            font-size: 18px;
        }
        
        /* Detailed layout modifications */
        .layout-detailed .metal-price {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
        }
        
        .layout-detailed .metal-price h4 {
            grid-column: 1 / 3;
        }
        ";
        
        // Add custom CSS if set
        if (!empty($options['custom_css'])) {
            $custom_css .= "\n/* Custom CSS */\n" . $options['custom_css'];
        }
        
        wp_add_inline_style('metal-prices-style', $custom_css);
        
        // Register and enqueue the script
        wp_enqueue_script('jquery');
        wp_register_script('metal-prices-script', '', array('jquery'), '1.0', true);
        wp_enqueue_script('metal-prices-script');
        
        // Add the JavaScript
        $custom_js = "
        jQuery(document).ready(function($) {
            $('.refresh-btn').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('" . __('Refreshing...', $this->plugin_slug) . "');
                
                // Clear the transient to force refresh
                $.ajax({
                    url: '" . admin_url('admin-ajax.php') . "',
                    type: 'POST',
                    data: {
                        'action': 'refresh_metal_prices'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('" . __('Error refreshing prices', $this->plugin_slug) . "');
                            button.prop('disabled', false).text('" . __('Refresh Prices', $this->plugin_slug) . "');
                        }
                    },
                    error: function() {
                        alert('" . __('Error connecting to server', $this->plugin_slug) . "');
                        button.prop('disabled', false).text('" . __('Refresh Prices', $this->plugin_slug) . "');
                    }
                });
            });
        });
        ";
        
        wp_add_inline_script('metal-prices-script', $custom_js);
    }
    
    /**
     * AJAX handler to refresh prices
     */
    public function refresh_prices() {
        delete_transient($this->cache_key);
        wp_send_json_success();
    }
}

// Initialize the plugin
$gold_silver_tracker = new Gold_Silver_Price_Tracker();