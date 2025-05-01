<?php
/**
 * Template Name: Event Details Template
 */
get_header();

if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    global $wpdb;
    
    // Main event table
    $event_table = $wpdb->prefix . 'event';
    
    // Fetch event details
    $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $event_table WHERE event_id = %d",
    $event_id));

    if ($event) {
        ?>
        <div class="wrap">
            <h1>Event Details</h1>
            <div class="container-form">
                <table class="form-table">
                    <tr><th>Event ID</th><td><?php echo esc_html($event->event_id); ?></td></tr>
                    <tr><th>Company Name</th><td><?php echo esc_html($event->company_name); ?></td></tr>
                    <tr><th>Event Name</th><td><?php echo esc_html($event->event_name); ?></td></tr>
                    <tr><th>Event Date</th><td><?php echo esc_html($event->event_date); ?></td></tr>
                    <tr><th>Event Location</th><td><?php echo esc_html($event->event_location); ?></td></tr>
                    <tr><th>Event Time</th><td><?php echo esc_html($event->event_time); ?></td></tr>
                    <tr><th>Contact Name</th><td><?php echo esc_html($event->contact_name); ?></td></tr>
                    <tr><th>Contact Email</th><td><?php echo esc_html($event->contact_email); ?></td></tr>
                    <tr><th>Contact Phone</th><td><?php echo esc_html($event->contact_phone); ?></td></tr>
                </table>
                
                <h2>Additional Details</h2>
                <?php
                // Fetch and display data from related tables
                $related_tables = [
                    'event_objective' => 'Objectives',
                    'event_logistics' => 'Logistics',
                    'event_restauration' => 'Catering',
                    
                ];
                
                foreach ($related_tables as $table => $title) {
                    $data = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}$table WHERE event_id = $event_id");
                    if ($data) {
                        echo "<h3>$title</h3>";
                        echo "<table class='form-table'>";
                        foreach ($data as $key => $value) {
                            if (!is_numeric($key) && $value !== null) {
                                echo "<tr><th>" . esc_html(str_replace('_', ' ', $key)) . "</th><td>" . esc_html($value) . "</td></tr>";
                            }
                        }
                        echo "</table>";
                    }
                }
                ?>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="wrap"><p>Event not found.</p></div>';
    }
} else {
    echo '<div class="wrap"><p>No event specified.</p></div>';
}

get_footer();