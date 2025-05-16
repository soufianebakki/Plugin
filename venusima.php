<?php
/**
 * Plugin Name: Multi_Step_Form DATA
 * Description: Creates database tables for event management and provides a multi-step form.
 * Version:     1.0.0
 * Author:      Soufiane Bakki
 * Text Domain: Multi_task_plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}



// Add this to your plugin's activation hook (where you have create_multistep_form_page())
function create_event_details_page() {
    if (!get_page_by_path('event-details')) {
        $page_id = wp_insert_post([
            'post_title'   => 'Event Details',
            'post_name'    => 'event-details',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[event_details]',
        ]);
        
        // Assign template - now using correct path
        update_post_meta($page_id, '_wp_page_template', 'event-details-template.php');
    }
}
register_activation_hook(__FILE__, 'create_event_details_page');
// Add menu and submenu pages into dashboard
function custom_add_menu_page() {
    // Main menu for Event Management
    add_menu_page(
        __('Database', 'textdomain'), // Page title
        'Database mangment',                  // Menu title
        'manage_options',            // Capability
        'event-management',          // Menu slug
        'event_management_callback', // Callback function
        'dashicons-database-view',   // Icon
        6                           // Position
    );

    // Submenu for Multistep Form
    add_submenu_page(
        'event-management',          // Parent slug
        __('Multistep Form', 'textdomain'),  // Page title
        'MultistepForm',            // Menu title
        'manage_options',            // Capability
        'multistep-form',            // Menu slug
        'multistep_form_callback'    // Callback function
    );
}
add_action('admin_menu', 'custom_add_menu_page');

// Create Multistep Form page on plugin activation
function create_multistep_form_page() {
    // Check if the page already exists
    $page = get_page_by_path('multistep-form');

    if (!$page) {
        // Create the page
        $page_data = array(
            'post_title'   => 'MultistepForm',
            'post_content' => '[multistepform]', // Add the shortcode
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'multistep-form', // Slug
        );

        // Insert the page
        wp_insert_post($page_data);
    }
}
register_activation_hook(__FILE__, 'create_multistep_form_page');


// Handle delete action
function handle_delete_event() {
    global $wpdb;

    // Check if the delete action is triggered
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['event_id'])) {
        $event_id = intval($_GET['event_id']);

        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_event_' . $event_id)) {
            wp_die('Security check failed.');
        }

        // Define all related table names
        $tables = array(
            'wp_event',
            'wp_event_accueil',
            'wp_event_budget',
            'wp_event_formats',
            'wp_event_gadgets',
            'wp_event_hebergement',
            'wp_event_hygiene',
            'wp_event_logistics',
            'wp_event_objectives',
            'wp_event_programs',
            'wp_event_promotion',
            'wp_event_restauration',
            'wp_event_suggestions',
            'wp_event_suivi',
            'wp_event_supplementaires'
        );

        // Loop through each table and delete rows with the matching event_id
        foreach ($tables as $table) {
            $wpdb->delete($table, array('event_id' => $event_id), array('%d'));
        }

        // Redirect to the same page after deletion
        wp_redirect(esc_url(remove_query_arg(array('action', 'event_id', '_wpnonce'))));
        exit;
    }
}
add_action('admin_init', 'handle_delete_event');

// Callback for Event Management page
function event_management_callback() {
    global $wpdb;
    $event_table = $wpdb->prefix . 'event'; // Define the main event table name

    // Handle delete action
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['event_id'])) {
        $event_id = intval($_GET['event_id']);

        // Verify the nonce for security
        if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_event_' . $event_id)) {
            // Delete the event from the database
            $wpdb->delete($event_table, array('event_id' => $event_id), array('%d'));

            // Display a success message
            echo '<div class="notice notice-success"><p>Event deleted successfully.</p></div>';
        } else {
            // Display an error message if the nonce is invalid
            echo '<div class="notice notice-error"><p>Invalid request. Please try again.</p></div>';
        }
    }

    // Fetch filter values from the form
    $filter_event_id = isset($_POST['filter_event_id']) ? intval($_POST['filter_event_id']) : '';
    $filter_date = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : '';

    // Build the SQL query based on filters
    $sql = "SELECT * FROM $event_table WHERE 1=1";
    if (!empty($filter_event_id)) {
        $sql .= $wpdb->prepare(" AND event_id = %d", $filter_event_id);
    }
    if (!empty($filter_date)) {
        $sql .= $wpdb->prepare(" AND event_date = %s", $filter_date);
    }

    // Fetch filtered events from the database
    $events = $wpdb->get_results($sql);

    // Get the Event Details page ID (only once for better performance)
    $event_details_page = get_pages([
        'meta_key' => '_wp_page_template',
        'meta_value' => 'event-details-template.php'
    ]);
    $details_page_id = !empty($event_details_page) ? $event_details_page[0]->ID : 0;

    

    ?>
    <div class="wrap">
        <h1>Event Management</h1>
        <p>Database Management For Events</p>

        <!-- Filter Form -->
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="filter_event_id">Event ID</label></th>
                    <td>
                        <input name="filter_event_id" type="number" id="filter_event_id" value="<?php echo esc_attr($filter_event_id); ?>" class="regular-text" />
                        <p class="description">Enter the Event ID to filter.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="filter_date">Date</label></th>
                    <td>
                        <input name="filter_date" type="date" id="filter_date" value="<?php echo esc_attr($filter_date); ?>" class="regular-text" />
                        <p class="description">Enter the event date to filter.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="filter" class="button button-primary" value="Filter" />
                <a href="<?php echo esc_url(remove_query_arg(array('action', 'event_id'))); ?>" class="button button-secondary">Clear Filters</a>
            </p>
        </form>

        <!-- Display all events in a table -->
        <h2>All Events</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Event ID</th>
                    <th>Company Name</th>
                    <th>Event Name</th>
                    <th>Event Date</th>
                    <th>Event Location</th>
                    <th>Event Time</th>
                    <th>Contact Name</th>
                    <th>Contact Email</th>
                    <th>Contact Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($events)) : ?>
                    <?php foreach ($events as $event) : ?>
                        <tr>
                            <td><?php echo esc_html($event->event_id); ?></td>
                            <td><?php echo esc_html($event->company_name); ?></td>
                            <td><?php echo esc_html($event->event_name); ?></td>
                            <td><?php echo esc_html($event->event_date); ?></td>
                            <td><?php echo esc_html($event->event_location); ?></td>
                            <td><?php echo esc_html($event->event_time); ?></td>
                            <td><?php echo esc_html($event->contact_name); ?></td>
                            <td><?php echo esc_html($event->contact_email); ?></td>
                            <td><?php echo esc_html($event->contact_phone); ?></td>
                            <td>
                                <!-- Delete Button (unchanged) -->
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'delete', 'event_id' => $event->event_id)), 'delete_event_' . $event->event_id)); ?>" class="button button-primary" onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                                
                                
  <!-- View Button -->
  <!-- View Button -->
  <?php
    $details_page = get_page_by_path('event-details');
    if ($details_page) {
    $view_url = add_query_arg('event_id', $event->event_id, get_permalink($details_page->ID));
    echo '<a href="' . esc_url($view_url) . '" class="button button-secondary" target="_blank">View</a>';
} else {
        echo '<button class="button button-secondary" disabled title="Please create an \'Event Details\' page">View</button>';
        if (current_user_can('manage_options')) {
            echo '<span style="color:#ff0000;margin-left:5px;font-size:12px;">';
            echo '(Missing <a href="' . admin_url('post-new.php?post_type=page') . '" style="color:#ff0000;text-decoration:underline;">Event Details Page</a>)';
            echo '</span>';
        }
    }
    ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="10">No events found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
    </div>
    <?php
}

/**
 * Override the theme's template with the plugin's template.
 */
add_filter('page_template', 'venuux_load_event_details_template');

function venuux_load_event_details_template($template) {
    global $post;

    // Check if this is the 'event-details' page
    if ($post && $post->post_name === 'event-details') {
        $plugin_template = plugin_dir_path(__FILE__) . 'event-details-template.php';
        
        // Check if the file exists in the plugin
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return $template;
}


// Callback for Multistep Form new tab
function multistep_form_callback() {
    // Get the URL to the interface folder
    $form_url = plugin_dir_url(__FILE__) . 'interface/index.html';

    // Output a link with JavaScript to force opening in a new tab
    echo '<div class="wrap">';
    echo '<h1>Multistep Form</h1>';
    echo '<a href="' . esc_url($form_url) . '" onclick="window.open(\'' . esc_url($form_url) . '\', \'_blank\'); return false;" style="display: inline-block; padding: 10px 20px; background-color: #1890A0; color: #fff; text-decoration: none; border-radius: 5px;">Open Multistep Form in New Tab</a>';
    echo '</div>';
}

// Multi-step form shortcode
function multistepform_shortcode() {
    // Get the URL to the interface folder
    $interface_url = plugin_dir_url(__FILE__) . 'interface/index.html';

    // Base URL for the "View" button
    $view_button_base_url = 'http://localhost/bako/41-2/';

    // Output the interface button and a dynamic "View" button
    return '
        <a href="' . esc_url($interface_url) . '" onclick="window.open(\'' . esc_url($interface_url) . '\', \'_blank\'); return false;" 
            style="display: inline-block; padding: 10px 20px; background-color: #1890A0; color: #fff; text-decoration: none; border-radius: 5px; margin-right: 10px;">
            Open Multistep Form
        </a>

        <button id="dynamicViewButton" class="button button-secondary"
            style="display: inline-block; padding: 10px 20px; background-color: #ff5733; color: #fff; text-decoration: none; border-radius: 5px;">
            View Event
        </button>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                document.getElementById("dynamicViewButton").addEventListener("click", function() {
                    var selectedRow = document.querySelector("table tr.selected"); // Find the selected row
                    if (selectedRow) {
                        var eventId = selectedRow.getAttribute("data-event_id"); // Get event_id from the row
                        if (eventId) {
                            window.open("' . esc_url($view_button_base_url) . '?event_id=" + eventId, "_blank");
                        } else {
                            alert("No event ID found!");
                        }
                    } else {
                        alert("Please select an event first.");
                    }
                });
            });
        </script>
    ';
}
add_shortcode('multistepform', 'multistepform_shortcode');

register_activation_hook(__FILE__, function() {
    // Create the details page if it doesn't exist
    if (!get_page_by_path('event-details')) {
        wp_insert_post([
            'post_title'   => 'Event Details',
            'post_name'    => 'event-details',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '[event_details]', // If using shortcode
            'page_template' => 'event-details-template.php'
        ]);
    }
});



// Add this with your other shortcode functions
function event_details_shortcode($atts) {
    if (!isset($_GET['event_id'])) {
        return '<p>No event ID provided.</p>';
    }
    
    // Let the template handle the display
    ob_start();
    get_template_part('indexx');
    return ob_get_clean();
}
add_shortcode('event_details', 'event_details_shortcode');



// Shortcode and database
function view_event_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'event_id' => '',
    ), $atts);

    if (empty($atts['event_id'])) {
        return '<p>Please provide an event_id.</p>';
    }

    return '<a href="http://localhost/bako/wp-content/plugins/modification.php?event_id=' . esc_attr($atts['event_id']) . '" target="_blank" style="display: inline-block; padding: 10px 20px; background-color: #1890A0; color: #fff; text-decoration: none; border-radius: 5px;">View Event Form</a>';
}
add_shortcode('view_event_form', 'view_event_form_shortcode');

// Function to create the database tables
function create_event_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table names
    $event_table = $wpdb->prefix . 'event';
    $event_objective_table = $wpdb->prefix . 'event_objectives';
    $event_format_table = $wpdb->prefix . 'event_formats';
    $event_program_table = $wpdb->prefix . 'event_programs';
    $event_logistics_table = $wpdb->prefix . 'event_logistics';
    $event_hygiene_table = $wpdb->prefix . 'event_hygiene';
    $event_hebergement_table = $wpdb->prefix . 'event_hebergement';
    $event_promotion_table = $wpdb->prefix . 'event_promotion';
    $event_accueil_table = $wpdb->prefix . 'event_accueil';
    $event_restauration_table = $wpdb->prefix . 'event_restauration';
    $event_gadgets_table = $wpdb->prefix . 'event_gadgets';
    $event_budget_table = $wpdb->prefix . 'event_budget';
    $event_suivi_table = $wpdb->prefix . 'event_suivi';
    $event_event_supplementaires = $wpdb->prefix . 'event_supplementaires';
    $event_suggestions_table = $wpdb->prefix . 'event_suggestions';

    // SQL queries
    $sql1 = "CREATE TABLE IF NOT EXISTS $event_table (
        event_id INT AUTO_INCREMENT PRIMARY KEY,
        company_name TEXT NOT NULL,
        event_name TEXT NOT NULL,
        event_date DATE NOT NULL,
        event_location TEXT,
        event_time TEXT,
        contact_name TEXT,
        contact_email TEXT,
        contact_phone TEXT
    ) $charset_collate;";

    $sql2 = "CREATE TABLE IF NOT EXISTS $event_objective_table (
        objective_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        objective_type TEXT,
        objective_autre TEXT,
        public_cible TEXT,
        participants INT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql3 = "CREATE TABLE IF NOT EXISTS $event_format_table (
        format_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        format_type TEXT,
        format_autre TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql4 = "CREATE TABLE IF NOT EXISTS $event_program_table (
        program_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        themes TEXT,
        intervenants TEXT,
        intervenants_details TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql5 = "CREATE TABLE IF NOT EXISTS $event_logistics_table (
        logistics_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        location_salle BOOLEAN,
        salle_type TEXT,
        salle_capacite TEXT,
        ambiance_souhaitee TEXT,
        installations_specifiques TEXT,
        equipement_audiovisuel BOOLEAN,
        type_equipement TEXT,
        configuration_specifique TEXT,
        besoin_assistance TEXT,
        transports BOOLEAN,
        navettes BOOLEAN,
        stationnement BOOLEAN,
        transports_autre TEXT,
        decoration BOOLEAN,
        decoration_souhaitee TEXT,
        matiere_signaletique TEXT,
        cabine_photo BOOLEAN,
        photobooth BOOLEAN,
        animations BOOLEAN,
        animations_dj BOOLEAN,
        animateur BOOLEAN,
        spectacle BOOLEAN,
        animations_autre TEXT,
        securite BOOLEAN,
        gardes_securite BOOLEAN,
        controle_acces BOOLEAN,
        securite_autre TEXT,
        logistiques_autre TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql6 = "CREATE TABLE IF NOT EXISTS $event_hygiene_table (
        hygiene_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        nettoyage BOOLEAN,
        sanitaires BOOLEAN,
        desinfectants BOOLEAN,
        fourniture BOOLEAN,
        hygiene_autre TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql7 = "CREATE TABLE IF NOT EXISTS $event_hebergement_table (
        hebergement_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        hebergement_necessaire TEXT,
        hebergement_nuits INT,
        hebergement_type TEXT,
        hebergement_autre TEXT,
        hebergement_budget DECIMAL(10,2),
        hebergement_localisation TEXT,
        hebergement_besoins TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql8 = "CREATE TABLE IF NOT EXISTS $event_promotion_table (
        promotion_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        promotion_necessaire TEXT,
        campagnes BOOLEAN,
        campagne TEXT,
        promdig BOOLEAN,
        promdigs TEXT,
        shooting BOOLEAN,
        production TEXT,
        imp BOOLEAN,
        design TEXT,
        besoin_publicite_autre TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql9 = "CREATE TABLE IF NOT EXISTS $event_accueil_table (
        accueil_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        accueil_necessaire TEXT,
        accueil_type TEXT,
        accueil_autre TEXT,
        accueil_materiel TEXT,
        accueil_materiel_autre TEXT,
        accueil_personnes INT,
        accueil_considerations TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql10 = "CREATE TABLE IF NOT EXISTS $event_restauration_table (
        restauration_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        restauration_necessaire TEXT,
        restauration_type TEXT,
        restauration_autre TEXT,
        restauration_duree TEXT,
        restauration_format TEXT,
        restauration_format_autre TEXT,
        souhait_type TEXT,
        allergies_alimentaires TEXT,
        repas_speciaux TEXT,
        restauration_remarques TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql11 = "CREATE TABLE IF NOT EXISTS $event_gadgets_table (
        gadgets_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        gadgets_necessaire TEXT,
        gadgets_type TEXT,
        gadgets_autre TEXT,
        combien_pieces TEXT,
        branding_gadgets TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql12 = "CREATE TABLE IF NOT EXISTS $event_budget_table (
        budget_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        budget_total DECIMAL(10,2),
        budget_logistique DECIMAL(10,2),
        budget_promotion DECIMAL(10,2),
        budget_restauration DECIMAL(10,2),
        budget_gadgets DECIMAL(10,2),
        budget_autre TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql13 = "CREATE TABLE IF NOT EXISTS $event_suivi_table (
        suivi_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        suivi_type TEXT,
        suivi_autre TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql14 = "CREATE TABLE IF NOT EXISTS $event_event_supplementaires (
        supplementaires_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        exigences_specifiques TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    $sql15 = "CREATE TABLE IF NOT EXISTS $event_suggestions_table (
        suggestions_id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT,
        commentaires TEXT,
        FOREIGN KEY (event_id) REFERENCES $event_table(event_id) ON DELETE CASCADE
    ) $charset_collate;";

    // Include WordPress database upgrade functions
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Execute SQL queries
    dbDelta($sql1);
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);
    dbDelta($sql5);
    dbDelta($sql6);
    dbDelta($sql7);
    dbDelta($sql8);
    dbDelta($sql9);
    dbDelta($sql10);
    dbDelta($sql11);
    dbDelta($sql12);
    dbDelta($sql13);
    dbDelta($sql14);
    dbDelta($sql15);
}

// Hook the function to the plugin activation
register_activation_hook(__FILE__, 'create_event_tables');