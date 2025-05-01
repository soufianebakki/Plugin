<?php
if (!defined('ABSPATH')) exit;

$event_id = get_query_var('event_id') ?: (isset($_GET['event_id']) ? intval($_GET['event_id']) : 0);

// Your database connection
global $wpdb;

// Fetch event data
$event = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}event WHERE event_id = %d", 
    $event_id
));

// Start HTML output
?><!DOCTYPE html>
<html>
<head>
    <title>Event Details #<?php echo $event_id; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .event-details { max-width: 800px; margin: 0 auto; }
        /* Add your other styles */
    </style>
</head>
<body>
    <div class="event-details">
        <?php if ($event) : ?>
            <h1><?php echo esc_html($event->event_name); ?></h1>
            <div class="event-meta">
                <p><strong>Company:</strong> <?php echo esc_html($event->company_name); ?></p>
                <!-- Add all your other event fields -->
            </div>
        <?php else : ?>
            <p>Event not found</p>
        <?php endif; ?>
    </div>
</body>
</html>