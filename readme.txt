Nepal Metal Price Tracker
A WordPress plugin that tracks and displays current gold and silver prices in Nepal with historical data visualization.

Description
Nepal Metal Price Tracker scrapes real-time gold and silver prices from Hamro Patro's website and displays them on your WordPress site. The plugin includes features for historical price tracking, data visualization, and price refresh functionality.

Features

🔍 Real-time Price Tracking: Displays current gold and silver prices in NPR per tola
📊 Historical Data: Tracks and stores price history for the last 30 days
📈 Price Visualization: Shows beautiful charts for gold and silver price trends
🔄 Manual Refresh: Allows users to refresh prices with a button click
⏱️ Automatic Updates: Daily scheduled price updates
🧩 Widget Support: Easy display in widget areas

Installation

Upload the nepal-metal-price-tracker folder to the /wp-content/plugins/ directory
Activate the plugin through the 'Plugins' menu in WordPress
Use shortcodes to display prices on your pages or posts

Shortcodes

Display current prices:
Copy[nepal_gold_price]

Show gold price history table:
Copy[gold_price_history_table]

Display gold price chart:
Copy[gold_price_history_graph]

Show silver price history table:
Copy[silver_price_history_table]

Display silver price chart:
Copy[silver_price_history_graph]

Add manual refresh buttons:
Copy
[gold_price_manual_refresh]
[silver_price_manual_refresh]


Technical Details

Web scraping uses PHP's DOMDocument to parse HTML from Hamro Patro
Data is cached using WordPress transients for 1 hour
Historical data is stored in WordPress options table
Charts are rendered using Chart.js
Responsive design works on all devices

License
GPL v2 or later versions
