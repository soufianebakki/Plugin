<?php
// interface/data.php
require_once('../../../wp-load.php'); // Load WordPress
global $wpdb;
$event_table = $wpdb->prefix . 'event';

header('Content-Type: application/json');

// Fetch event data
if ($_GET['action'] === 'get_event') {
    $event_id = intval($_GET['event_id']);
    $event = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $event_table WHERE event_id = %d", 
        $event_id
    ));

    echo json_encode([
        'success' => !!$event,
        'event' => $event
    ]);
}

// Save event data (handle form submission)
else if ($_POST['action'] === 'save_event') {
    $data = [
        'company_name' => sanitize_text_field($_POST['company_name']),
        'event_name' => sanitize_text_field($_POST['event_name']),
        // ... all other fields ...
    ];

    if (!empty($_POST['event_id'])) {
        // Update existing
        $wpdb->update($event_table, $data, ['event_id' => intval($_POST['event_id'])]);
    } else {
        // Create new
        $wpdb->insert($event_table, $data);
    }

    echo json_encode(['success' => true]);
}