<?php
/**
 * Plugin Name: Nepal Metal Price Tracker
 * Plugin URI: https://sohanmehta.com.np
 * Description: Track and display current gold and silver prices in Nepal with historical data and charts (Web Scrapping From Hamro Patro).
 * Version: 1.0
 * Author: Sohan Mehta
 * Author URI: https://sohanmehta.com.np
 * Text Domain: nepal-metal-price-tracker
 * License: GPL v2 or later 
 */


if (!defined('ABSPATH')) {
    exit;
}

// Function to get gold and silver prices for Nepal
function get_gold_silver_prices_nepal() {
    // Check if prices are cached in a transient
    $cached_prices = get_transient('nepal_gold_silver_prices');
    if ($cached_prices !== false) {
        return $cached_prices;
    }

    // Fetch prices
    $prices = scrape_hamropatro_prices();

    // If prices are successfully fetched, cache them for 1 hour
    if ($prices) {
        set_transient('nepal_gold_silver_prices', $prices, 1 * HOUR_IN_SECONDS);
        return $prices;
    }

    // If scraping fails, return false
    return false;
}

function scrape_hamropatro_prices() {
    $url = 'https://www.hamropatro.com/gold';
    
    // Initialize cURL with more detailed options
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // Add comprehensive user agent
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Add headers to mimic browser request
    $headers = [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    // Execute the request
    $html = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        error_log('cURL Error in Hamropatro Scraper: ' . $error);
        curl_close($ch);
        return false;
    }
    
    // Close cURL
    curl_close($ch);
    
    // Use DOMDocument to parse HTML
    $dom = new DOMDocument();
    @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);
    
    // Find all <ul> elements
    $lists = $dom->getElementsByTagName('ul');
    
    $goldHallmarkTola = 0;
    $silverTola = 0;
    
    // Iterate through all <ul> elements
    foreach ($lists as $list) {
        // Check if it's the gold-silver list
        if ($list->getAttribute('class') === 'gold-silver') {
            $items = $list->getElementsByTagName('li');
            
            for ($i = 0; $i < $items->length; $i++) {
                $itemText = trim($items->item($i)->textContent);
                
                // Extract Gold Hallmark (tola)
                if (strpos($itemText, 'Gold Hallmark - tola') !== false && $i + 1 < $items->length) {
                    $priceText = trim($items->item($i + 1)->textContent);
                    // Remove 'Nrs.' and any commas, then convert to float
                    $priceText = str_replace(['Nrs.', ','], '', $priceText);
                    $goldHallmarkTola = floatval($priceText);
                    error_log('Gold Hallmark Tola Price: ' . $goldHallmarkTola);
                }
                
                // Extract Silver (tola)
                if (strpos($itemText, 'Silver - tola') !== false && $i + 1 < $items->length) {
                    $priceText = trim($items->item($i + 1)->textContent);
                    // Remove 'Nrs.' and any commas, then convert to float
                    $priceText = str_replace(['Nrs.', ','], '', $priceText);
                    $silverTola = floatval($priceText);
                    error_log('Silver Tola Price: ' . $silverTola);
                }
            }
            
            break; // Stop after finding the gold-silver list
        }
    }
    
    // Prepare prices array
    $prices = array(
        'gold' => array(
            'price_per_tola' => $goldHallmarkTola,
            'price_npr' => $goldHallmarkTola,
            'change_percentage' => 0
        ),
        'silver' => array(
            'price_per_tola' => $silverTola,
            'price_npr' => $silverTola,
            'change_percentage' => 0
        ),
        'last_updated' => current_time('mysql')
    );
    
    // Final debug log
    error_log('Final Prices: ' . print_r($prices, true));
    
    return $prices;
}

// Add a shortcode to display prices
add_shortcode('nepal_gold_price', 'display_nepal_gold_price');

function display_nepal_gold_price($atts) {
    $prices = get_gold_silver_prices_nepal();
    
    if (!$prices) {
        return '<p>Unable to fetch current prices. Please try again later.</p>';
    }
    
    // Format prices with commas and 2 decimal places
    $gold_price = number_format($prices['gold']['price_per_tola'], 2);
    $silver_price = number_format($prices['silver']['price_per_tola'], 2);
    
    // Get price change indicators
    $gold_change = $prices['gold']['change_percentage'];
    $gold_arrow = $gold_change >= 0 ? '↑' : '↓';
    $gold_class = $gold_change >= 0 ? 'price-up' : 'price-down';
    
    $silver_change = $prices['silver']['change_percentage'];
    $silver_arrow = $silver_change >= 0 ? '↑' : '↓';
    $silver_class = $silver_change >= 0 ? 'price-up' : 'price-down';
    
    // Start building the HTML output
    $output = '
    <div class="metal-prices-container">
        <h3>Today\'s Metal Prices in Nepal</h3>
        
        <div class="metal-price gold">
            <h4>Gold Hallmark</h4>
            <div class="price-value">NPR ' . $gold_price . '<span>per tola</span></div>
            <div class="price-change ' . $gold_class . '">' . $gold_arrow . ' ' . abs($gold_change) . '%</div>
        </div>
        
        <div class="metal-price silver">
            <h4>Silver</h4>
            <div class="price-value">NPR ' . $silver_price . '<span>per tola</span></div>
            <div class="price-change ' . $silver_class . '">' . $silver_arrow . ' ' . abs($silver_change) . '%</div>
        </div>
        
        <div class="price-footer">
            <div class="update-time">Last Updated: ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($prices['last_updated'])) . '</div>
            <button id="refresh-prices" class="refresh-btn">Refresh Prices</button>
        </div>
    </div>';
    
    return $output;
}

function enqueue_metal_price_assets() {
    // Register and enqueue the style
    wp_register_style('metal-prices-style', false);
    wp_enqueue_style('metal-prices-style');
    
    // Add the CSS as inline style
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
    ";
    
    wp_add_inline_style('metal-prices-style', $custom_css);
    
    // Register and enqueue the script
    wp_enqueue_script('jquery');
    wp_register_script('metal-prices-script', '', array('jquery'), '1.0', true);
    wp_enqueue_script('metal-prices-script');
    
    // Add the JavaScript as inline script
    $custom_js = "
    jQuery(document).ready(function($) {
        $('#refresh-prices').on('click', function() {
            var button = $(this);
            button.prop('disabled', true).text('Refreshing...');
            
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
                        alert('Error refreshing prices');
                        button.prop('disabled', false).text('Refresh Prices');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                    button.prop('disabled', false).text('Refresh Prices');
                }
            });
        });
    });
    ";
    
    wp_add_inline_script('metal-prices-script', $custom_js);
}
add_action('wp_enqueue_scripts', 'enqueue_metal_price_assets');

// Add AJAX handler for refreshing prices
add_action('wp_ajax_refresh_metal_prices', 'refresh_metal_prices');
add_action('wp_ajax_nopriv_refresh_metal_prices', 'refresh_metal_prices');

function refresh_metal_prices() {
    // Clear the transient to force a refresh
    delete_transient('nepal_gold_silver_prices');
    wp_send_json_success();
}

// Optional: Add a widget for displaying prices
class Nepal_Metal_Prices_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'nepal_metal_prices_widget', // Base ID
            'Nepal Metal Prices', // Name
            array('description' => 'Display current gold and silver prices in Nepal')
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo do_shortcode('[nepal_gold_price]');
        echo $args['after_widget'];
    }
}

function register_nepal_metal_prices_widget() {
    register_widget('Nepal_Metal_Prices_Widget');
}
add_action('widgets_init', 'register_nepal_metal_prices_widget');




// Function to track gold prices from web scraping
function track_gold_price_history() {
    // Get current gold price from web scraping
    $current_prices = scrape_hamropatro_prices();
    
    if (!$current_prices) {
        return false;
    }
    
    // Retrieve existing historical data
    $historical_data = get_option('gold_price_history', []);
    
    // Add current price to historical data
    $today = date('Y-m-d');
    $current_gold_price = $current_prices['gold']['price_per_tola'];
    
    // Store price for today
    $historical_data[$today] = $current_gold_price;
    
    // Keep only last 30 days of data
    $historical_data = array_slice($historical_data, -30, 30, true);
    
    // Save updated historical data
    update_option('gold_price_history', $historical_data);
    
    return $historical_data;
}

// Schedule daily tracking
function schedule_gold_price_tracking() {
    if (!wp_next_scheduled('daily_gold_price_tracking')) {
        wp_schedule_event(time(), 'daily', 'daily_gold_price_tracking');
    }
}
add_action('wp_loaded', 'schedule_gold_price_tracking');
add_action('daily_gold_price_tracking', 'track_gold_price_history');

// Shortcode to display gold price tracking table
function gold_price_history_table_shortcode() {
    // Fetch historical data
    $historical_data = get_option('gold_price_history', []);
    
    // If no data, try to track prices
    if (empty($historical_data)) {
        track_gold_price_history();
        $historical_data = get_option('gold_price_history', []);
    }
    
    // Start output buffering
    ob_start();
    ?>
    <div class="gold-price-history-container">
        <h3>Gold Price History (Last 30 Days)</h3>
        <table class="gold-price-history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Price (NPR per tola)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($historical_data, true) as $date => $price): ?>
                    <tr>
                        <td><?php echo esc_html($date); ?></td>
                        <td><?php echo number_format($price, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <style>
    .gold-price-history-container {
        max-width: 600px;
        margin: 20px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .gold-price-history-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .gold-price-history-table th,
    .gold-price-history-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .gold-price-history-table thead {
        background-color: #f2f2f2;
    }
    
    .gold-price-history-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('gold_price_history_table', 'gold_price_history_table_shortcode');

// Shortcode to display gold price graph
function gold_price_history_graph_shortcode() {
    // Enqueue Chart.js
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.1', true);
    
    // Fetch historical data
    $historical_data = get_option('gold_price_history', []);
    
    // If no data, try to track prices
    if (empty($historical_data)) {
        track_gold_price_history();
        $historical_data = get_option('gold_price_history', []);
    }
    
    // Prepare data for JavaScript
    $dates = array_keys($historical_data);
    $prices = array_values($historical_data);
    
    // Start output buffering
    ob_start();
    ?>
    <div class="gold-price-graph-container">
        <h3>Gold Price Trend</h3>
        <canvas id="goldPriceChart" width="400" height="200"></canvas>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('goldPriceChart').getContext('2d');

        const goldData = {
            labels: <?php echo json_encode($dates); ?>,
            prices: <?php echo json_encode($prices); ?>
        };

        function createGradient(ctx) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(255,215,0,0.5)');
            gradient.addColorStop(1, 'rgba(255,215,0,0.1)');
            return gradient;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: goldData.labels,
                datasets: [{
                    label: 'Gold Price (NPR per tola)',
                    data: goldData.prices,
                    borderColor: '#FFD700',
                    backgroundColor: createGradient(ctx),
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#FFD700',
                    pointBorderColor: '#FFD700',
                    pointHoverRadius: 8,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.7)',
                        titleColor: 'white',
                        bodyColor: 'white'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Price (NPR per tola)'
                        }
                    }
                }
            }
        });
    });
    </script>

    <style>
    .gold-price-graph-container {
        max-width: 800px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('gold_price_history_graph', 'gold_price_history_graph_shortcode');

// Manual refresh function for AJAX
function manual_gold_price_tracking_refresh() {
    // Check nonce for security
    check_ajax_referer('gold_price_tracking_refresh', 'nonce');
    
    // Attempt to track prices
    $result = track_gold_price_history();
    
    if ($result) {
        wp_send_json_success('Gold prices updated successfully');
    } else {
        wp_send_json_error('Failed to update gold prices');
    }
}
add_action('wp_ajax_gold_price_tracking_refresh', 'manual_gold_price_tracking_refresh');
add_action('wp_ajax_nopriv_gold_price_tracking_refresh', 'manual_gold_price_tracking_refresh');

// Enqueue scripts for manual refresh
function enqueue_gold_price_tracking_scripts() {
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', '
    jQuery(document).ready(function($) {
        $("#refresh-gold-prices").on("click", function() {
            var button = $(this);
            button.prop("disabled", true).text("Refreshing...");
            
            $.ajax({
                url: "' . admin_url('admin-ajax.php') . '",
                type: "POST",
                data: {
                    action: "gold_price_tracking_refresh",
                    nonce: "' . wp_create_nonce('gold_price_tracking_refresh') . '"
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert("Error refreshing prices");
                    }
                    button.prop("disabled", false).text("Refresh Prices");
                },
                error: function() {
                    alert("Error connecting to server");
                    button.prop("disabled", false).text("Refresh Prices");
                }
            });
        });
    });
    ');
}
add_action('wp_enqueue_scripts', 'enqueue_gold_price_tracking_scripts');

// Add a manual refresh button shortcode
function gold_price_manual_refresh_shortcode() {
    return '<button id="refresh-gold-prices" style="display: block; margin: 0 auto;" class="btn btn-primary">Refresh Gold Prices</button>';
}
add_shortcode('gold_price_manual_refresh', 'gold_price_manual_refresh_shortcode');




// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Function to track silver prices from web scraping
function track_silver_price_history() {
    // Get current silver price from web scraping
    $current_prices = scrape_hamropatro_prices();
    
    if (!$current_prices) {
        return false;
    }
    
    // Retrieve existing historical data
    $historical_data = get_option('silver_price_history', []);
    
    // Add current price to historical data
    $today = date('Y-m-d');
    $current_silver_price = $current_prices['silver']['price_per_tola'];
    
    // Store price for today
    $historical_data[$today] = $current_silver_price;
    
    // Keep only last 30 days of data
    $historical_data = array_slice($historical_data, -30, 30, true);
    
    // Save updated historical data
    update_option('silver_price_history', $historical_data);
    
    return $historical_data;
}

// Schedule daily tracking for silver
function schedule_silver_price_tracking() {
    if (!wp_next_scheduled('daily_silver_price_tracking')) {
        wp_schedule_event(time(), 'daily', 'daily_silver_price_tracking');
    }
}
add_action('wp_loaded', 'schedule_silver_price_tracking');
add_action('daily_silver_price_tracking', 'track_silver_price_history');

// Shortcode to display silver price tracking table
function silver_price_history_table_shortcode() {
    // Fetch historical data
    $historical_data = get_option('silver_price_history', []);
    
    // If no data, try to track prices
    if (empty($historical_data)) {
        track_silver_price_history();
        $historical_data = get_option('silver_price_history', []);
    }
    
    // Start output buffering
    ob_start();
    ?>
    <div class="silver-price-history-container">
        <h3>Silver Price History (Last 30 Days)</h3>
        <table class="silver-price-history-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Price (NPR per tola)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($historical_data, true) as $date => $price): ?>
                    <tr>
                        <td><?php echo esc_html($date); ?></td>
                        <td><?php echo number_format($price, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <style>
    .silver-price-history-container {
        max-width: 600px;
        margin: 20px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
    }
    
    .silver-price-history-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .silver-price-history-table th,
    .silver-price-history-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    
    .silver-price-history-table thead {
        background-color: #f2f2f2;
    }
    
    .silver-price-history-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('silver_price_history_table', 'silver_price_history_table_shortcode');

// Shortcode to display silver price graph
function silver_price_history_graph_shortcode() {
    // Enqueue Chart.js
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.1', true);
    
    // Fetch historical data
    $historical_data = get_option('silver_price_history', []);
    
    // If no data, try to track prices
    if (empty($historical_data)) {
        track_silver_price_history();
        $historical_data = get_option('silver_price_history', []);
    }
    
    // Prepare data for JavaScript
    $dates = array_keys($historical_data);
    $prices = array_values($historical_data);
    
    // Start output buffering
    ob_start();
    ?>
    <div class="silver-price-graph-container">
        <h3 style="display: block; margin: 0 auto;">Silver Price Trend</h3>
        <canvas id="silverPriceChart" width="400" height="200"></canvas>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('silverPriceChart').getContext('2d');

        const silverData = {
            labels: <?php echo json_encode($dates); ?>,
            prices: <?php echo json_encode($prices); ?>
        };

        function createGradient(ctx) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(192,192,192,0.5)');
            gradient.addColorStop(1, 'rgba(192,192,192,0.1)');
            return gradient;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: silverData.labels,
                datasets: [{
                    label: 'Silver Price (NPR per tola)',
                    data: silverData.prices,
                    borderColor: '#C0C0C0',
                    backgroundColor: createGradient(ctx),
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#C0C0C0',
                    pointBorderColor: '#C0C0C0',
                    pointHoverRadius: 8,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.7)',
                        titleColor: 'white',
                        bodyColor: 'white'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Price (NPR per tola)'
                        }
                    }
                }
            }
        });
    });
    </script>

    <style>
    .silver-price-graph-container {
        max-width: 800px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        padding: 20px;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('silver_price_history_graph', 'silver_price_history_graph_shortcode');

// Manual refresh function for silver prices via AJAX
function manual_silver_price_tracking_refresh() {
    // Check nonce for security
    check_ajax_referer('silver_price_tracking_refresh', 'nonce');
    
    // Attempt to track prices
    $result = track_silver_price_history();
    
    if ($result) {
        wp_send_json_success('Silver prices updated successfully');
    } else {
        wp_send_json_error('Failed to update silver prices');
    }
}
add_action('wp_ajax_silver_price_tracking_refresh', 'manual_silver_price_tracking_refresh');
add_action('wp_ajax_nopriv_silver_price_tracking_refresh', 'manual_silver_price_tracking_refresh');

// Enqueue scripts for manual silver price refresh
function enqueue_silver_price_tracking_scripts() {
    wp_enqueue_script('jquery');
    wp_add_inline_script('jquery', '
    jQuery(document).ready(function($) {
        $("#refresh-silver-prices").on("click", function() {
            var button = $(this);
            button.prop("disabled", true).text("Refreshing...");
            
            $.ajax({
                url: "' . admin_url('admin-ajax.php') . '",
                type: "POST",
                data: {
                    action: "silver_price_tracking_refresh",
                    nonce: "' . wp_create_nonce('silver_price_tracking_refresh') . '"
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert("Error refreshing prices");
                    }
                    button.prop("disabled", false).text("Refresh Prices");
                },
                error: function() {
                    alert("Error connecting to server");
                    button.prop("disabled", false).text("Refresh Prices");
                }
            });
        });
    });
    ');
}
add_action('wp_enqueue_scripts', 'enqueue_silver_price_tracking_scripts');

// Add a manual refresh button shortcode for silver
function silver_price_manual_refresh_shortcode() {
    return '<button id="refresh-silver-prices" class="btn btn-primary" style="display: block; margin: 0 auto;">
    Refresh Silver Price Graph
</button>
';
}
add_shortcode('silver_price_manual_refresh', 'silver_price_manual_refresh_shortcode');
