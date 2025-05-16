<?php
/**
 * Template Name: Venuux Event Details
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

include('cnx.php');

// Enqueue CSS & JS
function enqueue_event_details_assets() {
    $theme_uri = get_stylesheet_directory_uri();
    wp_enqueue_style('event-details-css', $theme_uri . '/css/style.css');
    wp_enqueue_script('event-details-js', $theme_uri . '/js/script.js', array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'enqueue_event_details_assets');

// Get event_id from the URL
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
} else {
    echo '<div class="container error-message"><p>event_id not provided.</p></div>';
    get_footer();
    exit;
}

global $wpdb;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
    
    // Verify nonce for security
    if (!wp_verify_nonce($_POST['event_update_nonce'], 'update_event_' . $event_id)) {
        wp_die('Security check failed');
    }

    // 1. Update main event table
    $main_event_data = [
        'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
        'event_name' => sanitize_text_field($_POST['event_name'] ?? ''),
        'event_date' => sanitize_text_field($_POST['event_date'] ?? ''),
        'event_location' => sanitize_text_field($_POST['event_location'] ?? ''),
        'event_time' => sanitize_text_field($_POST['event_time'] ?? ''),
        'contact_name' => sanitize_text_field($_POST['contact_name'] ?? ''),
        'contact_email' => sanitize_email($_POST['contact_email'] ?? ''),
        'contact_phone' => sanitize_text_field($_POST['contact_phone'] ?? '')
    ];
    
    $main_event_updated = $wpdb->update(
        $wpdb->prefix . 'event',
        $main_event_data,
        ['event_id' => $event_id],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
        ['%d']
    );

    // 2. Update all related tables
    $related_tables = [
        'event_objectives' => ['objective_type'],
        'event_formats' => ['format_type'],
        'event_programs' => ['program_details'],
        'event_logistics' => ['location_salle', 'equipement_audiovisuel', 'transports', 'decoration'],
        'event_hygiene' => ['sanitaires'],
        'event_hebergement' => ['hebergement_type'],
        'event_promotion' => ['promotion_necessaire', 'campagnes', 'campagne',
                             'promdig', 'promdigs', 'shooting', 'production',
                              'imp', 'design', 'besoin_publicite_autre'], 
        'event_accueil' => ['accueil_type', 'accueil_materiel'],
        'event_restauration' => ['restauration_type'],
        'event_gadgets' => ['gadgets_type'],
        'event_budget' => ['budget_details'],
        'event_suivi' => ['suivi_type'],
        'event_supplementaires' => ['supplementaires_details'],
        'event_suggestions' => ['suggestions']
    ];

    foreach ($related_tables as $table_suffix => $fields) {
        $table_name = $wpdb->prefix . $table_suffix;
        $table_data = ['event_id' => $event_id];
        $formats = ['%d']; // Format for event_id (integer)
    
        foreach ($fields as $field) {
            // Initialize with empty string
            $table_data[$field] = '';
            
            // Check if this field was submitted
            if (isset($_POST[$field])) {
                // Handle checkbox arrays
                if (is_array($_POST[$field])) {
                    $selected_values = array_map('sanitize_text_field', $_POST[$field]);
                    
                    // Handle "other" text input if exists
                    $other_field = $field . '_other';
                    if (!empty($_POST[$other_field])) {
                        $selected_values[] = sanitize_text_field($_POST[$other_field]);
                    }
                    
                    // Store as comma-separated string
                    $table_data[$field] = implode(',', array_filter($selected_values));
                } 
                // Handle regular text fields
                else {
                    $table_data[$field] = sanitize_text_field($_POST[$field]);
                }
            }
            
            $formats[] = '%s'; // Add string format for this field
        }

        // Check if record exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE event_id = %d", 
            $event_id
        ));

        // Update or insert
        if ($exists) {
            $result = $wpdb->update(
                $table_name,
                $table_data,
                ['event_id' => $event_id],
                $formats,
                ['%d']
            );
        } else {
            $result = $wpdb->insert(
                $table_name,
                $table_data,
                $formats
            );
        }
    }

    // Display success message
   if (false !== $main_event_updated || $wpdb->rows_affected > 0) {
    echo '
    <div class="notice notice-success is-dismissible" style="border-left: 5px solid #46b450; padding: 15px; background: #f6ffed;">
        <p style="font-weight: bold; color: #2e7d32; font-size: 15px;">
            ✅ Modifications enregistrées avec succès !<br>
            Vos changements ont bien été pris en compte.
        </p>
    </div>';
} else {
    echo '
    <div class="notice notice-error is-dismissible" style="border-left: 5px solid #dc3232; padding: 15px; background: #fff5f5;">
        <p style="font-weight: bold; color: #a00000; font-size: 15px;">
            ❌ Aucune modification détectée ou une erreur est survenue.<br>
            Veuillez réessayer ou vérifier les données saisies.
        </p>
    </div>';
}

}

// Fetch main event
$event = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}event WHERE event_id = %d",
    $event_id
));

if (!$event) {
    echo '<div class="container error-message"><p>Event not found.</p></div>';
    get_footer();
    exit;
}

// Fetch related tables
$related_data = [];
$related_tables = [
    'event_objectives', 'event_formats', 'event_programs', 'event_logistics',
    'event_hygiene', 'event_hebergement', 'event_promotion', 'event_accueil',
    'event_restauration', 'event_gadgets', 'event_budget', 'event_suivi',
    'event_supplementaires', 'event_suggestions'
];

foreach ($related_tables as $table) {
    $data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}{$table} WHERE event_id = %d",
        $event_id
    ));
    if ($data) {
        $related_data[$table] = $data;
    }
}

// Prepare checkbox values
$saved_formats_array = [];
if (!empty($related_data['event_formats']->format_type)) {
    $saved_formats_array = explode(',', $related_data['event_formats']->format_type);
}

$format_autre = '';
foreach ($saved_formats_array as $value) {
    if (!in_array($value, ['conference', 'atelier', 'seminaire', 'forum', 'salon'])) {
        $format_autre = $value;
        break;
    }
}
?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html($event->event_name); ?> - Event Details</title>
        <?php wp_head(); ?>
    </head>
    <body>
        <header class="container">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/image/image-3-scaled.webp" alt="Venusima">
        </header>
        
        <div class="form-container container">
            <form method="POST" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" class="event-update-form">
                <?php wp_nonce_field('update_event_' . $event_id, 'event_update_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <div class="wrap">
                    
                    <!-- General Information Section -->
                    <div class="step step-1 active">
                        <h2>Informations Générales</h2>
                        <div class="form-table">
                            <div class="form-row">
                                <label>Nom de l'entreprise:</label>
                                <input type="text" class="form-value" name="company_name" placeholder="Nom de l'entreprise" value="<?php echo esc_attr($event->company_name ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Nom de l'Événement:</label>
                                <input type="text" class="form-value" name="event_name" value="<?php echo esc_attr($event->event_name ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Date de l'Événement:</label>
                                <input type="date" class="form-value" name="event_date" value="<?php echo esc_attr($event->event_date ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Lieu de l'Événement:</label>
                                <input type="text" class="form-value" name="event_location" value="<?php echo esc_attr($event->event_location ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Heure de début et de fin:</label>
                                <input type="text" class="form-value" name="event_time" placeholder="HH:MM - HH:MM" value="<?php echo esc_attr($event->event_time ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Personne Contact:</label>
                                <input type="text" class="form-value" name="contact_name" value="<?php echo esc_attr($event->contact_name ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Email de Contact:</label>
                                <input type="email" class="form-value" name="contact_email" value="<?php echo esc_attr($event->contact_email ?? ''); ?>">
                            </div>
                            <div class="form-row">
                                <label>Téléphone de Contact:</label>
                                <input type="tel" class="form-value" name="contact_phone" value="<?php echo esc_attr($event->contact_phone ?? ''); ?>">
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button class="next" type="button">Suivant</button>
                        </div>
                    </div>



                    <!-- Event Objectives Section -->
                    <div class="step step-2">
    <h2>Objectifs et Détails de l'Événement</h2>
    <div class="form-table">
        <div class="form-row">
            <label>Objectifs:</label>
            <div class="form-value checktext-container">
                <?php 
                // Get saved objectives from database
                $saved_objectives = [];
                if (!empty($related_data['event_objectives']->objective_type)) {
                    // Trim each value and remove empty entries
                    $saved_objectives = array_filter(
                        array_map('trim', 
                            explode(',', $related_data['event_objectives']->objective_type)
                        ),
                        function($value) { return !empty($value); }
                    );
                }
                
                // Define all possible options
                $objectives_options = [
                    'notoriete' => 'Augmenter la notoriété de la marque',
                    'leads' => 'Générer des leads',
                    'formation' => 'Former les participants',
                    'reseautage' => 'Réseautage',
                    'lancement' => 'Lancer un nouveau produit'
                ];
                
                // Display all checkbox options
                foreach ($objectives_options as $value => $label) {
                    $checked = in_array($value, $saved_objectives) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="objective_type[]" value="' . esc_attr($value) . '" ' . $checked . '>';
                    echo '<label>' . esc_html($label) . '</label>';
                    echo '</div>';
                }
                
                // Find all "other" objectives (not in predefined list)
                $other_objectives = array_diff($saved_objectives, array_keys($objectives_options));
                $objective_autre = implode(', ', $other_objectives);
                ?>
                <div class="autre-item">
                    <label>Autre</label>
                    <input type="text" name="objective_type_other" 
                           value="<?php echo esc_attr($objective_autre); ?>" 
                           placeholder="Autre objectif">
                </div>
            </div>
        </div>
    </div>
    <div class="step-navigation">
        <button class="prev" type="button">Précédent</button>
        <button class="next" type="button">Suivant</button>
    </div>
</div>



                    <!-- Event Formats Section -->
                    <div class="step step-3">
    <h2>Format de l'Événement</h2>
    <div class="form-table">
        <div class="form-row">
            <label>Type d'événement:</label>
            <div class="form-value checktext-container">
                <?php
                // 1. PARSE DATABASE VALUES
                $saved_formats = [];
                if (!empty($related_data['event_formats']->format_type)) {
                    $saved_formats = array_filter(
                        array_map('trim', explode(',', $related_data['event_formats']->format_type)),
                        function($v) { return !empty($v); }
                    );
                }
                
                // 2. DEFINE OPTIONS
                $formats_options = [
                    'conference' => 'Conférence',
                    'atelier' => 'Atelier',
                    'seminaire' => 'Séminaire',
                    'forum' => 'Forum',
                    'salon' => 'Salon professionnel'
                ];
                
                // 3. DISPLAY CHECKBOXES
                foreach ($formats_options as $value => $label) {
                    $checked = in_array($value, $saved_formats) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="format_type[]" value="' . esc_attr($value) . '" ' . $checked . '>';
                    echo '<label>' . esc_html($label) . '</label>';
                    echo '</div>';
                }
                
                // 4. HANDLE "OTHER" OPTIONS
                $other_formats = array_diff($saved_formats, array_keys($formats_options));
                $format_autre = implode(', ', $other_formats);
                ?>
                <div class="autre-item">
                    <label>Autre</label>
                    <input type="text" name="format_type_other" 
                           value="<?php echo esc_attr($format_autre); ?>" 
                           placeholder="Autre format">
                </div>
            </div>
        </div>
    </div>
    <div class="step-navigation">
        <button class="prev" type="button">Précédent</button>
        <button class="next" type="button">Suivant</button>
    </div>
</div>


                    <!-- Event Program Section -->
 <div class="step step-4">
    <h2>Programme de l'Événement</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved program data
    $program_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_programs WHERE event_id = %d", 
        $event_id
    ));

    // Set default values
    $themes = $program_data->themes ?? '';
    $intervenants = $program_data->intervenants ?? 'non';
    $intervenants_details = $program_data->intervenants_details ?? '';
    ?>

    <textarea name="themes" id="themes" placeholder="Thèmes abordés:"><?php echo esc_textarea($themes); ?></textarea><br>

    <label>Intervenants ou experts invités:</label><br>
    <input type="radio" name="intervenants" value="oui" id="intervenants_oui"
        <?php checked($intervenants, 'oui'); ?>>
    <span class="checktext">Oui</span>

    <input type="radio" name="intervenants" value="non" id="intervenants_non"
        <?php checked($intervenants, 'non'); ?>>
    <span class="checktext">Non</span><br>

    <div id="intervenants_details_div" style="display: <?php echo ($intervenants === 'oui') ? 'block' : 'none'; ?>;">
        <textarea name="intervenants_details" id="intervenants_details"
            placeholder="Si oui, précisez"><?php echo esc_textarea($intervenants_details); ?></textarea><br>
    </div>

    <button type="button" class="prev">Précédent</button>
    <button type="button" class="next">Suivant</button>
</div>


                    




<div class="step step-5">
    <h2>Logistique</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

    // Get saved logistics data
    $logistics_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_logistics WHERE event_id = %d", 
        $event_id
    ));

    // Set values from database or defaults
    $logistique = $logistics_data->Logistique ?? 'oui';
    $location_salle = $logistics_data->location_salle ?? 0;
    $salle_type = $logistics_data->salle_type ?? '';
    $salle_capacite = $logistics_data->salle_capacite ?? '';
    $ambiance_souhaitee = $logistics_data->ambiance_souhaitee ?? '';
    $installations_specifiques = $logistics_data->installations_specifiques ?? '';
    $equipement_audiovisuel = $logistics_data->equipement_audiovisuel ?? 0;
    $type_equipement = $logistics_data->type_equipement ?? '';
    $configuration_specifique = $logistics_data->configuration_specifique ?? '';
    $besoin_assistance = $logistics_data->besoin_assistance ?? 'non';
    $transports = $logistics_data->transports ?? 0;
    $navettes = $logistics_data->navettes ?? 0;
    $stationnement = $logistics_data->stationnement ?? 0;
    $transports_autre = $logistics_data->transports_autre ?? '';
    $decoration = $logistics_data->decoration ?? 0;
    $decoration_souhaitee = $logistics_data->decoration_souhaitee ?? '';
    $matiere_signaletique = $logistics_data->matiere_signaletique ?? 'non';
    $cabine_photo = $logistics_data->cabine_photo ?? 0;
    $photobooth = $logistics_data->photobooth ?? 0;
    $animations = $logistics_data->animations ?? 0;
    $animations_dj = $logistics_data->animations_dj ?? 0;
    $animateur = $logistics_data->animateur ?? 0;
    $spectacle = $logistics_data->spectacle ?? 0;
    $animations_autre = $logistics_data->animations_autre ?? '';
    $securite = $logistics_data->securite ?? 0;
    $gardes_securite = $logistics_data->gardes_securite ?? 0;
    $controle_acces = $logistics_data->controle_acces ?? 0;
    $securite_autre = $logistics_data->securite_autre ?? '';
    $logistiques_autre = $logistics_data->logistiques_autre ?? '';
    ?>

   
<p>Souhaitez-vous une logistique particulière ?</p>
<label>
        <input type="radio" name="logistique" value="oui" id="logistique_oui" <?php echo ($logistique == 'oui' && !empty($location_salle) || !empty($equipement_audiovisuel) || !empty($transports) || !empty($decoration) || !empty($animations) || !empty($securite)) ? 'checked' : ''; ?>>
        <span class="checktext">Oui</span>
        </label>
    <label>
        <input type="radio" name="logistique" value="non" id="logistique_non" <?php echo ($logistique == 'non' || (empty($location_salle) && empty($equipement_audiovisuel) && empty($transports) && empty($decoration) && empty($animations) && empty($securite))) ? 'checked' : ''; ?>>
        <span class="checktext">Non</span>
    </label><br>


    <div id="Logistique" style="display: <?php echo ($logistique === 'oui') ? 'block' : 'none'; ?>;">
        <label>Besoins logistiques:</label><br>
        
        <input type="checkbox" name="location_salle" id="location_salle" value="1"
            <?php checked($location_salle, 1); ?>>
        <span class="checktext">Location de salle</span><br>
        
        <div id="Location_salle_div" style="display: <?php echo $location_salle ? 'block' : 'none'; ?>;">
            <input type="text" placeholder="Type de salle : (Auditorium, salle de réunion, espace extérieur, Un amphithéâtre etc.)"
                name="salle_type" value="<?php echo esc_attr($salle_type); ?>"><br>
            <input type="text" placeholder="Capacité requise"
                name="salle_capacite" value="<?php echo esc_attr($salle_capacite); ?>"><br>
            <input type="text" placeholder="Ambiance souhaitée : (formelle, décontractée, etc.)"
                name="ambiance_souhaitee" value="<?php echo esc_attr($ambiance_souhaitee); ?>"><br>
            <input type="text" placeholder="Avez-vous besoin d'un accès à des installations spécifiques ? (ex. : accès aux personnes handicapées)"
                name="installations_specifiques" value="<?php echo esc_attr($installations_specifiques); ?>"><br>
        </div>

        <input type="checkbox" name="equipement_audiovisuel" id="equipement_audiovisuel" value="1"
            <?php checked($equipement_audiovisuel, 1); ?>>
        <span class="checktext">Équipement Audiovisuel</span><br>
        
        <div id="equipement_audiovisuel_div" style="display: <?php echo $equipement_audiovisuel ? 'block' : 'none'; ?>;">
            <input type="text" placeholder="Type d'équipement : (projecteurs, écrans, microphones, haut-parleurs, etc.)"
                name="type_equipement" value="<?php echo esc_attr($type_equipement); ?>"><br>
            <input type="text" placeholder="Configuration spécifique : (présentation, tableau blanc, dispositions de sièges)"
                name="configuration_specifique" value="<?php echo esc_attr($configuration_specifique); ?>"><br>
            <label>Avez-vous besoin d'une assistance technique pendant l'événement ?</label><br>
            <input type="radio" name="besoin_assistance" value="oui"
                <?php checked($besoin_assistance, 'oui'); ?>>
            <span class="checktext">Oui</span>
            <input type="radio" name="besoin_assistance" value="non"
                <?php checked($besoin_assistance, 'non'); ?>>
            <span class="checktext">Non</span><br>
        </div>

        <input type="checkbox" name="transports" id="transports" value="1"
            <?php checked($transports, 1); ?>>
        <span class="checktext">Transports</span><br>
        
        <div id="transports_div" style="display: <?php echo $transports ? 'block' : 'none'; ?>;">
            <label>Besoin de transports pour les participants :</label><br>
            <input type="checkbox" name="navettes" value="1"
                <?php checked($navettes, 1); ?>>
            <span class="checktext">Navettes</span><br>
            <input type="checkbox" name="stationnement" value="1"
                <?php checked($stationnement, 1); ?>>
            <span class="checktext">Stationnement</span><br>
            <input type="text" name="transports_autre" placeholder="Autres besoins transports"
                value="<?php echo esc_attr($transports_autre); ?>"><br>
        </div>

        <input type="checkbox" name="decoration" id="decoration" value="1"
            <?php checked($decoration, 1); ?>>
        <span class="checktext">Décoration</span><br>
        
        <div id="decoration_div" style="display: <?php echo $decoration ? 'block' : 'none'; ?>;">
            <input type="text" placeholder="Type de décoration souhaitée : (thème, couleurs, éléments spécifiques)"
                name="decoration_souhaitee" value="<?php echo esc_attr($decoration_souhaitee); ?>"><br>
            <label>Avez-vous des besoins en matière de signalétique ? (bannières, panneaux d'accueil, etc.)</label><br>
            <input type="radio" name="matiere_signaletique" value="oui"
                <?php checked($matiere_signaletique, 'oui'); ?>>
            <span class="checktext">Oui</span>
            <input type="radio" name="matiere_signaletique" value="non"
                <?php checked($matiere_signaletique, 'non'); ?>>
            <span class="checktext">Non</span><br>
            <input type="checkbox" name="cabine_photo" value="1"
                <?php checked($cabine_photo, 1); ?>>
            <span class="checktext">Cabine Photo</span><br>
            <input type="checkbox" name="photobooth" value="1"
                <?php checked($photobooth, 1); ?>>
            <span class="checktext">Photobooth 360°</span><br>
        </div>

        <input type="checkbox" name="animations" id="Animations" value="1"
            <?php checked($animations, 1); ?>>
        <span class="checktext">Animations</span><br>
        
        <div id="Animations_div" style="display: <?php echo $animations ? 'block' : 'none'; ?>;">
            <input type="checkbox" name="animations_dj" value="1"
                <?php checked($animations_dj, 1); ?>>
            <span class="checktext">DJ ou groupe de musique</span><br>
            <input type="checkbox" name="animateur" value="1"
                <?php checked($animateur, 1); ?>>
            <span class="checktext">Animateur pour activités (quiz, jeux, etc.)</span><br>
            <input type="checkbox" name="spectacle" value="1"
                <?php checked($spectacle, 1); ?>>
            <span class="checktext">Spectacle ou performances (magicien, danseurs, etc.)</span><br>
            <input type="text" name="animations_autre" placeholder="Autres"
                value="<?php echo esc_attr($animations_autre); ?>"><br>
        </div>

        <input type="checkbox" name="securite" id="securite" value="1"
            <?php checked($securite, 1); ?>>
        <span class="checktext">Sécurité</span><br>
        
        <div id="securite_div" style="display: <?php echo $securite ? 'block' : 'none'; ?>;">
            <input type="checkbox" name="gardes_securite" value="1"
                <?php checked($gardes_securite, 1); ?>>
            <span class="checktext">Gardes de sécurité</span><br>
            <input type="checkbox" name="controle_acces" value="1"
                <?php checked($controle_acces, 1); ?>>
            <span class="checktext">Contrôle d'accès</span><br>
            <input type="text" name="securite_autre" placeholder="Autres"
                value="<?php echo esc_attr($securite_autre); ?>"><br>
        </div>

        <input type="text" name="logistiques_autre" placeholder="Autres besoins logistiques"
            value="<?php echo esc_attr($logistiques_autre); ?>"><br>
            <?php  ?>
    </div>

    <button type="button" class="prev">Précédent</button>
    <button type="button" class="next">Suivant</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const logistiqueOui = document.getElementById('logistique_oui');
    const logistiqueNon = document.getElementById('logistique_non');
    const logistiqueDiv = document.getElementById('Logistique');

    function toggleLogistique() {
        // Show the logistic section only if 'Oui' is selected
        logistiqueDiv.style.display = logistiqueOui.checked ? 'block' : 'none';
    }

    toggleLogistique();
    logistiqueOui.addEventListener('change', toggleLogistique);
    logistiqueNon.addEventListener('change', toggleLogistique);

    // Initialize sub-sections based on checkbox status
    function toggleSubSections() {
        // Toggle the visibility of each section based on its checkbox status
        const locationSalleCheckbox = document.getElementById('location_salle');
        const locationSalleDiv = document.getElementById('Location_salle_div');
        locationSalleDiv.style.display = locationSalleCheckbox.checked ? 'block' : 'none';

        // Repeat for other sections...
    }

    // Initialize
    toggleSubSections();

    // Add event listeners for checkboxes
    const checkboxes = [
        'location_salle', 'equipement_audiovisuel', 'transports', 'decoration', 'animations', 'securite'
    ];
    checkboxes.forEach(function(id) {
        document.getElementById(id).addEventListener('change', toggleSubSections);
    });
});
</script>















<div class="step step-6">
    <h2>Services d'Hygiène</h2>
    <label>Besoins en matière de services d'hygiène:</label><br>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved hygiene data for this event
    $hygiene_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_hygiene WHERE event_id = %d", 
        $event_id
    ));

    // Define all possible hygiene services
    $hygiene_services = [
        
        'nettoyage' => 'Nettoyage quotidien des lieux (avant, pendant, après l\'événement)',
        'sanitaires' => 'Sanitaires propres et accessibles',
        'desinfectants' => 'Distribution de désinfectants pour les mains',
        'fourniture' => 'Fourniture de serviettes en papier ou de produits d\'hygiène'
    ];

    // Display checkboxes
    foreach ($hygiene_services as $key => $label) {
        $checked = ($hygiene_data && $hygiene_data->$key) ? 'checked' : '';
        echo '<input type="checkbox" name="hygiene['.$key.']" value="1" '.$checked.'>';
        echo '<span class="checktext">'.$label.'</span><br>';
    }

    // Handle "other" field
    $hygiene_autre = ($hygiene_data && $hygiene_data->hygiene_autre) ? $hygiene_data->hygiene_autre : '';
    ?>

    <input type="text" name="hygiene_autre" placeholder="Autre" 
           value="<?php echo esc_attr($hygiene_autre); ?>"><br>

    <button type="button" class="prev">Précédent</button>
    <button type="button" class="next">Suivant</button>
</div>

                    





<div class="step step-7">
    <h2>Hébergement</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved accommodation data
    $hebergement_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_hebergement WHERE event_id = %d", 
        $event_id
    ));

    // Set default values
    $hebergement_necessaire = $hebergement_data->hebergement_necessaire ?? 'non';
    
    // PROPERLY parse checkbox values - trim each value and remove empty entries
    $hebergement_types = [];
    if (!empty($hebergement_data->hebergement_type)) {
        $hebergement_types = array_filter(
            array_map('trim', explode(',', $hebergement_data->hebergement_type)),
            function($value) { return !empty($value); }
        );
    }
    
    $hebergement_nuits = $hebergement_data->hebergement_nuits ?? '';
    $hebergement_autre = $hebergement_data->hebergement_autre ?? '';
    $hebergement_budget = $hebergement_data->hebergement_budget ?? '';
    $hebergement_localisation = $hebergement_data->hebergement_localisation ?? '';
    $hebergement_besoins = $hebergement_data->hebergement_besoins ?? '';
    ?>

    <div class="form-row">
        <label>Avez-vous besoin d'organiser un hébergement pour les participants ?</label>
        <div class="form-value">
            <label class="radio-option">
                <input type="radio" name="hebergement_necessaire" value="oui" 
                    <?php checked($hebergement_necessaire, 'oui'); ?>>
                <span>Oui</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="hebergement_necessaire" value="non" 
                    <?php checked($hebergement_necessaire, 'non'); ?>>
                <span>Non</span>
            </label>
        </div>
    </div>

    <div id="hebergement" style="display: <?php echo ($hebergement_necessaire === 'oui') ? 'block' : 'none'; ?>;">
        <div class="form-row">
            <label>Nombre de nuits:</label>
            <div class="form-value">
                <input type="number" name="hebergement_nuits"
                    value="<?php echo esc_attr($hebergement_nuits); ?>"
                    placeholder="Combien de nuits ?">
            </div>
        </div>

        <div class="form-row">
            <label>Type d'hébergement souhaité:</label>
            <div class="form-value checktext-container">
                <?php
                $type_options = [
                    'hotels' => 'Hôtels',
                    'auberges' => 'Auberges',
                    'appartements' => 'Appartements ou locations temporaires',
                    'autre' => 'Autre'
                ];
                
                // Display all checkbox options
                foreach ($type_options as $value => $label) {
                    $checked = in_array($value, $hebergement_types) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="hebergement_type[]" value="'.esc_attr($value).'" '.$checked.' class="hebergement-type">';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
                
                <div id="hebergement_autre_div" style="display: <?php echo (in_array('autre', $hebergement_types)) ? 'block' : 'none'; ?>; margin-top: 10px;">
                    <input type="text" name="hebergement_autre" 
                        value="<?php echo esc_attr($hebergement_autre); ?>"
                        placeholder="Précisez autre type">
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>Budget par participant:</label>
            <div class="form-value">
                <input type="number" step="0.01" name="hebergement_budget" 
                    value="<?php echo esc_attr($hebergement_budget); ?>"
                    placeholder="Budget prévu pour l'hébergement par participant">
            </div>
        </div>

        <div class="form-row">
            <label>Localisation:</label>
            <div class="form-value">
                <input type="text" name="hebergement_localisation" 
                    value="<?php echo esc_attr($hebergement_localisation); ?>"
                    placeholder="Préférences de localisation">
            </div>
        </div>

        <div class="form-row">
            <label>Besoins particuliers:</label>
            <div class="form-value">
                <textarea name="hebergement_besoins" placeholder="Besoins particuliers concernant l'hébergement"><?php 
                    echo esc_textarea($hebergement_besoins); 
                ?></textarea>
            </div>
        </div>
    </div>

    <div class="step-navigation">
        <button type="button" class="prev">Précédent</button>
        <button type="button" class="next">Suivant</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle main accommodation section
    $('input[name="hebergement_necessaire"]').change(function() {
        $('#hebergement').toggle($(this).val() === 'oui');
    });
    
    // Toggle "autre" field when "autre" checkbox changes
    $('.hebergement-type').change(function() {
        if ($(this).val() === 'autre') {
            $('#hebergement_autre_div').toggle(this.checked);
        }
    });
});
</script>



























<div class="step step-8">
    <h2>Promotion de l'Événement</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved promotion data with proper defaults
    $promotion_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_promotion WHERE event_id = %d", 
        $event_id
    ));
    
    if (!$promotion_data) {
        $promotion_data = (object)[
            'promotion_necessaire' => 'non',
            'campagnes' => 0,
            'campagne' => '',
            'promdig' => 0,
            'promdigs' => '',
            'shooting' => 0,
            'production' => '',
            'imp' => 0,
            'design' => '',
            'besoin_publicite_autre' => ''
        ];
    }

    // Improved parsing function that handles both JSON arrays and comma-separated values
    function parse_checkbox_values($value) {
        if (empty($value)) return [];
        
        // Try to decode as JSON first
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return array_filter(array_map('trim', $decoded));
        }
        
        // Fall back to explode if not JSON
        return array_filter(array_map('trim', explode(',', $value)));
    }

    // Parse all checkbox values
    $campagne_values = parse_checkbox_values($promotion_data->campagne);
    $promdigs_values = parse_checkbox_values($promotion_data->promdigs);
    $production_values = parse_checkbox_values($promotion_data->production);
    $design_values = parse_checkbox_values($promotion_data->design);
    ?>

    <div class="form-row">
        <label>Promotion nécessaire:</label>
        <div class="form-value">
            <label class="radio-option">
                <input type="radio" name="promotion_necessaire" value="oui" 
                    <?php checked($promotion_data->promotion_necessaire, 'oui'); ?>>
                <span>Oui</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="promotion_necessaire" value="non" 
                    <?php checked($promotion_data->promotion_necessaire, 'non'); ?>>
                <span>Non</span>
            </label>
        </div>
    </div>

    <div id="promotion-section" style="display: <?php echo ($promotion_data->promotion_necessaire === 'oui') ? 'block' : 'none'; ?>;">
        <!-- Publicité & Communication -->
        <div class="nested-checkbox-group">
            <label class="parent-checkbox">
                <input type="checkbox" name="campagnes" value="1"
                    <?php checked($promotion_data->campagnes, 1); ?>
                    class="toggle-parent">
                <span><b>Publicité & Communication</b></span>
            </label>
            
            <div class="nested-options" style="<?php echo $promotion_data->campagnes ? '' : 'display:none'; ?>">
                <?php
                $campagne_options = [
                    'publicitaires' => 'Campagnes publicitaires (Facebook Ads, Google Ads, affichage)',
                    'supports_marketing' => 'Création de supports marketing (flyers, affiches, brochures)',
                    'Collaboration' => 'Collaboration avec des influenceurs (Instagram, TikTok, YouTube)'
                ];
                
                foreach ($campagne_options as $value => $label) {
                    $checked = in_array($value, $campagne_values) ? 'checked' : '';
                    echo '<label class="child-checkbox">';
                    echo '<input type="checkbox" name="campagne[]" value="'.esc_attr($value).'" '.$checked.'>';
                    echo '<span>'.esc_html($label).'</span>';
                    echo '</label><br>';
                }
                ?>
            </div>
        </div>

        <!-- Promotion Digitale -->
        <div class="nested-checkbox-group">
            <label class="parent-checkbox">
                <input type="checkbox" name="promdig" value="1"
                    <?php checked($promotion_data->promdig, 1); ?>
                    class="toggle-parent">
                <span><b>Promotion Digitale</b></span>
            </label>
            
            <div class="nested-options" style="<?php echo $promotion_data->promdig ? '' : 'display:none'; ?>">
                <?php
                $promdigs_options = [
                    'reseaux_sociaux' => 'Réseaux sociaux (Teasers vidéo, publication sponsorisée, live streaming)',
                    'Emailing' => 'Emailing & Invitations (E-flyers, RSVP en ligne, newsletters)',
                    'site_web' => 'Site web & Landing page (Page dédiée à l\'événement, inscriptions en ligne)',
                    'Formulaire' => 'Formulaire de réservation en ligne'
                ];
                
                foreach ($promdigs_options as $value => $label) {
                    $checked = in_array($value, $promdigs_values) ? 'checked' : '';
                    echo '<label class="child-checkbox">';
                    echo '<input type="checkbox" name="promdigs[]" value="'.esc_attr($value).'" '.$checked.'>';
                    echo '<span>'.esc_html($label).'</span>';
                    echo '</label><br>';
                }
                ?>
            </div>
        </div>

        <!-- Captation & Production -->
        <div class="nested-checkbox-group">
            <label class="parent-checkbox">
                <input type="checkbox" name="shooting" value="1"
                    <?php checked($promotion_data->shooting, 1); ?>
                    class="toggle-parent">
                <span><b>Captation & Production de Contenu</b></span>
            </label>
            
            <div class="nested-options" style="<?php echo $promotion_data->shooting ? '' : 'display:none'; ?>">
                <?php
                $production_options = [
                    'Photos' => 'Photos',
                    'videos' => 'Vidéos',
                    'Drone' => 'Drone',
                    'montage_video' => 'Montage vidéo'
                ];
                
                foreach ($production_options as $value => $label) {
                    $checked = in_array($value, $production_values) ? 'checked' : '';
                    echo '<label class="child-checkbox">';
                    echo '<input type="checkbox" name="production[]" value="'.esc_attr($value).'" '.$checked.'>';
                    echo '<span>'.esc_html($label).'</span>';
                    echo '</label><br>';
                }
                ?>
            </div>
        </div>

        <!-- Design & Impression -->
        <div class="nested-checkbox-group">
            <label class="parent-checkbox">
                <input type="checkbox" name="imp" value="1"
                    <?php checked($promotion_data->imp, 1); ?>
                    class="toggle-parent">
                <span><b>Design & Impression</b></span>
            </label>
            
            <div class="nested-options" style="<?php echo $promotion_data->imp ? '' : 'display:none'; ?>">
                <?php
                $design_options = [
                    'roll_up' => 'Roll-up',
                    'Drapeaux' => 'Drapeaux',
                    'Panneaux' => 'Panneaux',
                    'catalogues' => 'Catalogues',
                    'badges_personnalises' => 'Badges personnalisés',
                    'Cartes_invitation' => 'Cartes d\'invitation'
                ];
                
                foreach ($design_options as $value => $label) {
                    $checked = in_array($value, $design_values) ? 'checked' : '';
                    echo '<label class="child-checkbox">';
                    echo '<input type="checkbox" name="design[]" value="'.esc_attr($value).'" '.$checked.'>';
                    echo '<span>'.esc_html($label).'</span>';
                    echo '</label><br>';
                }
                ?>
            </div>
        </div>

        <!-- Autre besoin -->
        <div class="form-row">
            <label>Autre besoin de publicité:</label>
            <div class="form-value">
                <input type="text" name="besoin_publicite_autre" 
                       value="<?php echo esc_attr($promotion_data->besoin_publicite_autre); ?>"
                       placeholder="Précisez votre besoin">
            </div>
        </div>
    </div>

    <div class="step-navigation">
        <button type="button" class="prev">Précédent</button>
        <button type="button" class="next">Suivant</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle promotion section based on radio button
    $('input[name="promotion_necessaire"]').change(function() {
        $('#promotion-section').toggle($(this).val() === 'oui');
    });
    
    // Toggle nested options when parent checkbox changes
    $('.toggle-parent').change(function() {
        $(this).closest('.nested-checkbox-group').find('.nested-options').toggle(this.checked);
    });
});
</script>



<?php
// Handle form submission for accueil services
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accueil_necessaire'])) {
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Prepare data with proper sanitization
    $accueil_necessaire = sanitize_text_field($_POST['accueil_necessaire']);
    
    // Handle checkbox arrays - similar to hebergement example
    $accueil_type = [];
    if (isset($_POST['accueil_type'])) {
        $accueil_type = array_filter(
            array_map('sanitize_text_field', $_POST['accueil_type']),
            function($value) { return !empty($value); }
        );
    }
    $accueil_type_str = implode(',', $accueil_type);
    
    $accueil_materiel = [];
    if (isset($_POST['accueil_materiel'])) {
        $accueil_materiel = array_filter(
            array_map('sanitize_text_field', $_POST['accueil_materiel']),
            function($value) { return !empty($value); }
        );
    }
    $accueil_materiel_str = implode(',', $accueil_materiel);
    
    $accueil_autre = isset($_POST['accueil_autre']) ? sanitize_text_field($_POST['accueil_autre']) : '';
    $accueil_materiel_autre = isset($_POST['accueil_materiel_autre']) ? sanitize_text_field($_POST['accueil_materiel_autre']) : '';
    $accueil_personnes = isset($_POST['accueil_personnes']) ? intval($_POST['accueil_personnes']) : 0;
    $accueil_considerations = isset($_POST['accueil_considerations']) ? sanitize_text_field($_POST['accueil_considerations']) : '';
    
    // Check if record exists - same approach as hebergement
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT accueil_id FROM {$wpdb->prefix}event_accueil WHERE event_id = %d", 
        $event_id
    ));
    
    $data = array(
        'event_id' => $event_id,
        'accueil_necessaire' => $accueil_necessaire,
        'accueil_type' => $accueil_type_str,
        'accueil_autre' => $accueil_autre,
        'accueil_materiel' => $accueil_materiel_str,
        'accueil_materiel_autre' => $accueil_materiel_autre,
        'accueil_personnes' => $accueil_personnes,
        'accueil_considerations' => $accueil_considerations
    );
    
    $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s');
    
    if ($existing) {
        // Update existing record
        $where = array('event_id' => $event_id);
        $where_format = array('%d');
        
        $result = $wpdb->update(
            "{$wpdb->prefix}event_accueil",
            $data,
            $where,
            $format,
            $where_format
        );
    } else {
        // Insert new record
        $result = $wpdb->insert(
            "{$wpdb->prefix}event_accueil",
            $data,
            $format
        );
    }
    
    if ($result === false) {
        error_log('Failed to save accueil data: ' . $wpdb->last_error);
    } else {
        // Optional: Add success message or redirect
    }
}
?>



<div class="step step-9">
    <h2>Services d'Accueil</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved accueil data with proper defaults
    $accueil_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_accueil WHERE event_id = %d", 
        $event_id
    ));
    
    if (!$accueil_data) {
        $accueil_data = (object)[
            'accueil_necessaire' => 'non',
            'accueil_type' => '',
            'accueil_materiel' => '',
            'accueil_materiel_autre' => '',
            'accueil_personnes' => '',
            'accueil_considerations' => '',
            'accueil_autre' => ''
        ];
    }

    // Parse checkbox values with proper filtering
    $accueil_types = !empty($accueil_data->accueil_type) ? 
        array_filter(array_map('trim', explode(',', $accueil_data->accueil_type))) : [];
    
    $accueil_materiels = !empty($accueil_data->accueil_materiel) ? 
        array_filter(array_map('trim', explode(',', $accueil_data->accueil_materiel))) : [];
    ?>

    <div class="form-row">
        <label>Souhaitez-vous organiser un accueil pour les participants ?</label>
        <div class="form-value">
            <label class="radio-option">
                <input type="radio" name="accueil_necessaire" value="oui"
                    <?php checked($accueil_data->accueil_necessaire, 'oui'); ?>>
                <span>Oui</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="accueil_necessaire" value="non"
                    <?php checked($accueil_data->accueil_necessaire, 'non'); ?>>
                <span>Non</span>
            </label>
        </div>
    </div>
    
    <div id="accueil" style="display: <?php echo ($accueil_data->accueil_necessaire === 'oui') ? 'block' : 'none'; ?>;">
        <div class="form-row">
            <label>Type de services d'accueil envisagés:</label>
            <div class="form-value checktext-container">
                <?php
                $accueil_type_options = [
                    'enregistrement' => 'Enregistrement à l\'entrée',
                    'accueil_personnalise' => 'Accueil personnalisé (hôtes/hôtesses)',
                    'distribution_badges' => 'Distribution de badges',
                    'informations_programme' => 'Informations sur le programme',
                    'point_contact' => 'Point de contact pour les questions',
                    'autre' => 'Autre'
                ];
                
                foreach ($accueil_type_options as $value => $label) {
                    $checked = in_array($value, $accueil_types) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="accueil_type[]" value="'.esc_attr($value).'" '.$checked.' class="accueil-type">';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
                
                <div id="accueil_autre_div" style="display: <?php echo (in_array('autre', $accueil_types)) ? 'block' : 'none'; ?>; margin-top: 10px;">
                    <input type="text" name="accueil_autre" 
                           value="<?php echo esc_attr($accueil_data->accueil_autre); ?>"
                           placeholder="Précisez autre service">
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>Matériel spécifique nécessaire:</label>
            <div class="form-value checktext-container">
                <?php
                $accueil_materiel_options = [
                    'badges_nominaux' => 'Badges nominaux',
                    'table_accueil' => 'Table d\'accueil',
                    'tablettes_enregistrement' => 'Tablettes ou ordinateurs pour l\'enregistrement'
                ];
                
                foreach ($accueil_materiel_options as $value => $label) {
                    $checked = in_array($value, $accueil_materiels) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="accueil_materiel[]" value="'.esc_attr($value).'" '.$checked.' class="accueil-materiel">';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
                
                <div style="margin-top: 10px;">
                    <input type="text" name="accueil_materiel_autre" 
                           value="<?php echo esc_attr($accueil_data->accueil_materiel_autre); ?>"
                           placeholder="Autre matériel (précisez)">
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>Nombre de personnes pour l'accueil:</label>
            <div class="form-value">
                <input type="number" name="accueil_personnes" 
                       value="<?php echo esc_attr($accueil_data->accueil_personnes); ?>"
                       placeholder="Nombre estimé de personnes">
            </div>
        </div>

        <div class="form-row">
            <label>Considérations particulières:</label>
            <div class="form-value">
                <input type="text" name="accueil_considerations" 
                       value="<?php echo esc_attr($accueil_data->accueil_considerations); ?>"
                       placeholder="Accessibilité, temps d'attente, etc.">
            </div>
        </div>
    </div>

    <div class="step-navigation">
        <button type="button" class="prev">Précédent</button>
        <button type="button" class="next">Suivant</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle main accueil section
    $('input[name="accueil_necessaire"]').change(function() {
        $('#accueil').toggle($(this).val() === 'oui');
    });
    
    // Toggle "autre" field for accueil type
    $(document).on('change', '.accueil-type', function() {
        if ($(this).val() === 'autre') {
            $('#accueil_autre_div').toggle(this.checked);
        }
    });
});
</script>


<!-- RESTAURATION-->
<div class="step step-10">
    <h2>Services de Restauration</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved restauration data with proper defaults
    $restauration_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_restauration WHERE event_id = %d", 
        $event_id
    ));
    
    if (!$restauration_data) {
        $restauration_data = (object)[
            'restauration_necessaire' => 'non',
            'restauration_type' => '',
            'restauration_autre' => '',
            'restauration_duree' => '',
            'restauration_format' => '',
            'restauration_format_autre' => '',
            'souhait_type' => '',
            'allergies_alimentaires' => '',
            'repas_speciaux' => '',
            'restauration_remarques' => ''
        ];
    }

    // Parse checkbox values with proper filtering
    $restauration_types = !empty($restauration_data->restauration_type) ? 
        array_filter(array_map('trim', explode(',', $restauration_data->restauration_type))) : [];
    
    $restauration_formats = !empty($restauration_data->restauration_format) ? 
        array_filter(array_map('trim', explode(',', $restauration_data->restauration_format))) : [];
    
    $souhait_types = !empty($restauration_data->souhait_type) ? 
        array_filter(array_map('trim', explode(',', $restauration_data->souhait_type))) : [];
    ?>

    <div class="form-row">
    <label>Souhaitez-vous offrir des services de restauration pendant l'événement ?</label>
        <div class="form-value">
            <label class="radio-option">
                <input type="radio" name="restauration_necessaire" value="oui" id="restauration_oui"
                    <?php checked($restauration_data->restauration_necessaire, 'oui'); ?>>
                <span>Oui</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="restauration_necessaire" value="non" id="restauration_non"
                    <?php checked($restauration_data->restauration_necessaire, 'non'); ?>>
                <span>Non</span>
            </label>
        </div>
    </div>

    <div id="restauration_details" style="display: <?php echo ($restauration_data->restauration_necessaire === 'oui') ? 'block' : 'none'; ?>;">
        <div class="form-row">
            <label>Type de services de restauration:</label>
            <div class="form-value checktext-container">
                <?php
                $restauration_type_options = [
                    'petits_dejeuners' => 'Petits déjeuners',
                    'dejeuners' => 'Déjeuners',
                    'dinners' => 'Dîners',
                    'buffets' => 'Buffets',
                    'cocktails' => 'Cocktails apéritifs',
                    'snacks' => 'Snacks ou pauses café',
                    'autre' => 'Autre'
                ];
                
                foreach ($restauration_type_options as $value => $label) {
                    $checked = in_array($value, $restauration_types) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="restauration_type[]" value="'.esc_attr($value).'" '.$checked.' class="restauration-type">';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
                
                <div id="restauration_autre_div" style="display: <?php echo (in_array('autre', $restauration_types)) ? 'block' : 'none'; ?>; margin-top: 10px;">
                    <input type="text" name="restauration_autre" 
                           value="<?php echo esc_attr($restauration_data->restauration_autre); ?>"
                           placeholder="Précisez autre service">
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>Durée du service:</label>
            <div class="form-value">
                <input type="text" name="restauration_duree" 
                       value="<?php echo esc_attr($restauration_data->restauration_duree); ?>"
                       placeholder="Ex: petit-déjeuner de 8h à 10h">
            </div>
        </div>

        <div class="form-row">
            <label>Format souhaité:</label>
            <div class="form-value checktext-container">
                <?php
                $restauration_format_options = [
                    'service_table' => 'Service à table',
                    'buffet' => 'Buffet',
                    'station_nourriture' => 'Station de nourriture',
                    'autre' => 'Autre'
                ];
                
                foreach ($restauration_format_options as $value => $label) {
                    $checked = in_array($value, $restauration_formats) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="restauration_format[]" value="'.esc_attr($value).'" '.$checked.' class="restauration-format">';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
                
                <div id="restauration_format_autre_div" style="display: <?php echo (in_array('autre', $restauration_formats)) ? 'block' : 'none'; ?>; margin-top: 10px;">
                    <input type="text" name="restauration_format_autre" 
                           value="<?php echo esc_attr($restauration_data->restauration_format_autre); ?>"
                           placeholder="Précisez autre format">
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>Préférences alimentaires:</label>
            <div class="form-value checktext-container">
                <?php
                $souhait_type_options = [
                    'vegetariennes' => 'Options végétariennes',
                    'veganes' => 'Options véganes',
                    'gluten' => 'Sans gluten'
                ];
                
                foreach ($souhait_type_options as $value => $label) {
                    $checked = in_array($value, $souhait_types) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="souhait_type[]" value="'.esc_attr($value).'" '.$checked.'>';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <div class="form-row">
            <label>Allergies alimentaires:</label>
            <div class="form-value">
                <input type="text" name="allergies_alimentaires" 
                       value="<?php echo esc_attr($restauration_data->allergies_alimentaires); ?>"
                       placeholder="Liste des allergies connues">
            </div>
        </div>

        <div class="form-row">
            <label>Repas spéciaux:</label>
            <div class="form-value">
                <input type="text" name="repas_speciaux" 
                       value="<?php echo esc_attr($restauration_data->repas_speciaux); ?>"
                       placeholder="Ex: repas halal, casher">
            </div>
        </div>

        <div class="form-row">
            <label>Remarques supplémentaires:</label>
            <div class="form-value">
                <textarea name="restauration_remarques" placeholder="Informations complémentaires"><?php 
                    echo esc_textarea($restauration_data->restauration_remarques); 
                ?></textarea>
            </div>
        </div>
    </div>

    <div class="step-navigation">
        <button type="button" class="prev">Précédent</button>
        <button type="button" class="next">Suivant</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle restauration details - fixed version
    function toggleRestaurationDetails() {
        $('#restauration_details').toggle($('#restauration_oui').is(':checked'));
    }
    
    // Initialize on page load
    toggleRestaurationDetails();
    
    // Update on change
    $('input[name="restauration_necessaire"]').change(function() {
        toggleRestaurationDetails();
    });
    
    // Toggle "autre" fields
    $(document).on('change', '.restauration-type', function() {
        if ($(this).val() === 'autre') {
            $('#restauration_autre_div').toggle(this.checked);
        }
    });
    
    $(document).on('change', '.restauration-format', function() {
        if ($(this).val() === 'autre') {
            $('#restauration_format_autre_div').toggle(this.checked);
        }
    });
});
</script>


<div class="step step-11">
    <h2>Services de Gadgets Promotionnels</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved gadgets data with proper defaults
    $gadgets_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_gadgets WHERE event_id = %d", 
        $event_id
    ));
    
    if (!$gadgets_data) {
        $gadgets_data = (object)[
            'gadgets_necessaire' => 'non',
            'gadgets_type' => '',
            'gadgets_autre' => '',
            'combien_pieces' => '',
            'branding_gadgets' => ''
        ];
    }

    // Parse checkbox values with proper filtering
    $gadgets_types = !empty($gadgets_data->gadgets_type) ? 
        array_filter(array_map('trim', explode(',', $gadgets_data->gadgets_type))) : [];
    ?>

    <div class="form-row">
        <label>Souhaitez-vous offrir des gadgets ou des goodies aux participants ?</label>
        <div class="form-value">
            <label class="radio-option">
                <input type="radio" name="gadgets_necessaire" value="oui" id="gadgets_oui"
                    <?php checked($gadgets_data->gadgets_necessaire, 'oui'); ?>>
                <span>Oui</span>
            </label>
            <label class="radio-option">
                <input type="radio" name="gadgets_necessaire" value="non" id="gadgets_non"
                    <?php checked($gadgets_data->gadgets_necessaire, 'non'); ?>>
                <span>Non</span>
            </label>
        </div>
    </div>

    <div id="gadgets" style="display: <?php echo ($gadgets_data->gadgets_necessaire === 'oui') ? 'block' : 'none'; ?>;">
        <div class="form-row">
            <label>Type de gadgets envisagés:</label>
            <div class="form-value checktext-container">
                <?php
                $gadgets_options = [
                    't_shirts' => 'T-shirts',
                    'stylos' => 'Stylos',
                    'tasses' => 'Tasses',
                    'sacs' => 'Sacs',
                    'cles_usb' => 'Clés USB',
                    'tirages_sort' => 'Tirages au sort',
                    'autre' => 'Autre'
                ];
                
                foreach ($gadgets_options as $value => $label) {
                    $checked = in_array($value, $gadgets_types) ? 'checked' : '';
                    echo '<div class="checkbox-item">';
                    echo '<input type="checkbox" name="gadgets_type[]" value="'.esc_attr($value).'" '.$checked.' class="gadgets-type">';
                    echo '<label>'.esc_html($label).'</label>';
                    echo '</div>';
                }
                ?>
                
                <div id="gadgets_autre_div" style="display: <?php echo (in_array('autre', $gadgets_types)) ? 'block' : 'none'; ?>; margin-top: 10px;">
                    <input type="text" name="gadgets_autre" 
                           value="<?php echo esc_attr($gadgets_data->gadgets_autre); ?>"
                           placeholder="Précisez autre type">
                </div>
            </div>
        </div>

        <div class="form-row">
            <label>Quantité:</label>
            <div class="form-value">
                <input type="text" name="combien_pieces" 
                       value="<?php echo esc_attr($gadgets_data->combien_pieces); ?>"
                       placeholder="Combien de pièces envisagez-vous de commander ?">
            </div>
        </div>

        <div class="form-row">
            <label>Branding:</label>
            <div class="form-value">
                <input type="text" name="branding_gadgets" 
                       value="<?php echo esc_attr($gadgets_data->branding_gadgets); ?>"
                       placeholder="Préférences concernant le design ou le branding">
            </div>
        </div>
    </div>

    <div class="step-navigation">
        <button type="button" class="prev">Précédent</button>
        <button type="button" class="next">Suivant</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle gadgets section
    $('input[name="gadgets_necessaire"]').change(function() {
        $('#gadgets').toggle($(this).val() === 'oui');
    });
    
    // Toggle "autre" field when checkbox changes
    $(document).on('change', '.gadgets-type', function() {
        if ($(this).val() === 'autre') {
            $('#gadgets_autre_div').toggle(this.checked);
        }
    });
});
</script>

<div class="step step-12">
    <h2>Budget</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved budget data
    $budget_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_budget WHERE event_id = %d", 
        $event_id
    ));

    // Set default values
    $budget_total = $budget_data->budget_total ?? '';
    $budget_logistique = $budget_data->budget_logistique ?? '';
    $budget_promotion = $budget_data->budget_promotion ?? '';
    $budget_restauration = $budget_data->budget_restauration ?? '';
    $budget_gadgets = $budget_data->budget_gadgets ?? '';
    $budget_autre = $budget_data->budget_autre ?? '';
    ?>

    <input type="number" step="0.01" placeholder="Quel est le budget total prévu pour l'événement ?" 
        name="budget_total" value="<?php echo esc_attr($budget_total); ?>"><br>

    <h2>Comment le budget est-il réparti ? (Indiquez une estimation pour chaque catégorie si possible)</h2>
    
    <input type="number" step="0.01" placeholder="Logistique" name="budget_logistique"
        value="<?php echo esc_attr($budget_logistique); ?>"><br>
        
    <input type="number" step="0.01" placeholder="Promotion" name="budget_promotion"
        value="<?php echo esc_attr($budget_promotion); ?>"><br>
        
    <input type="number" step="0.01" placeholder="Restauration" name="budget_restauration"
        value="<?php echo esc_attr($budget_restauration); ?>"><br>
        
    <input type="number" step="0.01" placeholder="Gadgets" name="budget_gadgets"
        value="<?php echo esc_attr($budget_gadgets); ?>"><br>

    <input type="number" step="0.01" name="budget_autre" placeholder="Autres"
        value="<?php echo esc_attr($budget_autre); ?>"><br>

    <button type="button" class="prev">Précédent</button>
    <button type="button" class="next">Suivant</button>
</div>


<div class="step step-13">
    <h2>Suivi et Évaluation</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved follow-up data with proper defaults
    $suivi_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_suivi WHERE event_id = %d", 
        $event_id
    ));
    
    if (!$suivi_data) {
        $suivi_data = (object)[
            'suivi_type' => '',
            'suivi_autre' => ''
        ];
    }

    // Improved parsing of checkbox values
    $suivi_types = !empty($suivi_data->suivi_type) ? 
        array_filter(array_map('trim', explode(',', $suivi_data->suivi_type))) : [];
    
    // Check if "autre" should be shown (has value in database)
    $show_autre = !empty($suivi_data->suivi_autre);
    ?>

    <div class="form-row">
        <label>Comment comptez-vous mesurer le succès de l'événement ?</label>
        <div class="form-value checktext-container">
            <?php
            $suivi_options = [
                'enquetes' => 'Enquêtes de satisfaction',
                'analyse_participation' => 'Analyse des données de participation',
                'retours_intervenants' => 'Retours des intervenants'
            ];
            
            foreach ($suivi_options as $value => $label) {
                $checked = in_array($value, $suivi_types) ? 'checked' : '';
                echo '<div class="checkbox-item">';
                echo '<input type="checkbox" name="suivi_type[]" value="'.esc_attr($value).'" '.$checked.'>';
                echo '<label>'.esc_html($label).'</label>';
                echo '</div>';
            }
            ?>
            
            <div class="checkbox-item">
                <label>Autre méthode:</label>
                <input type="text" name="suivi_autre" 
                       value="<?php echo esc_attr($suivi_data->suivi_autre); ?>"
                       placeholder="Précisez votre méthode">
            </div>
        </div>
    </div>

    <div class="step-navigation">
        <button type="button" class="prev">Précédent</button>
        <button type="button" class="next">Suivant</button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle "autre" field when checkbox changes
    $(document).on('change', '.suivi-type', function() {
        if ($(this).val() === 'autre') {
            $('#suivi_autre_div').toggle(this.checked);
        }
    });
});
</script>




<!-- Step 14: Éléments Supplémentaires -->
<div class="step step-14">
    <h2>Éléments Supplémentaires</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved additional requirements
    $supplementaire_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_supplementaires WHERE event_id = %d", 
        $event_id
    ));

    $exigences_specifiques = $supplementaire_data->exigences_specifiques ?? '';
    ?>

    <textarea placeholder="Y a-t-il des exigences spécifiques que vous souhaitez mentionner ?"
        name="exigences_specifiques"><?php echo esc_textarea($exigences_specifiques); ?></textarea><br>

    <button type="button" class="prev">Précédent</button>
    <button type="button" class="next">Suivant</button>
</div>


<div class="step step-15">
    <h2>Commentaires ou Suggestions</h2>

    <?php
    global $wpdb;
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    // Get saved comments
    $suggestions_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}event_suggestions WHERE event_id = %d", 
        $event_id
    ));

    $commentaires = $suggestions_data->commentaires ?? '';
    ?>

    <textarea placeholder="Souhaitez-vous ajouter d'autres commentaires ou suggestions relatifs à l'organisation de cet événement ?"
        name="commentaires"><?php echo esc_textarea($commentaires); ?></textarea><br>

    <button type="button" class="prev">Précédent</button>
</div>

                <?php wp_nonce_field('update_event_' . $event_id, 'event_update_nonce'); ?>
                <button type="submit" name="modify_event" class="submit-btn">Modifier</button>
            </form>
        </div>
        
        <footer class="form-footer container">
            <p class="copyright"><a href="https://techstridesolutions.ma/">Techstridesolutions</a> © <?php echo date('Y'); ?>. Tous droits réservés.</p>
            <div class="footer-logo">
                <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 1080 1080" style="enable-background:new 0 0 1080 1080;" xml:space="preserve">
                    <style type="text/css">.st0{fill:#1890A0;}</style>
                    <g>
                        <polygon class="st0" points="592.2,876.5 527.6,705 387.3,705 527.4,1066 551.1,1066 691,705 658.5,705"/>
                        <g>
                            <path class="st0" d="M897.7,171.8C926.1,97.3,965,54.6,1014.5,43.6V14c-29.8,2.9-66.8,4.3-111,4.3c-57.2,0-113.9-1.4-170.1-4.3 v29.6c43.8,0.9,76.2,8.3,97.3,22c21.1,13.7,31.7,36.1,31.7,67.3c0,29.3-9.8,69.5-29.5,120.4l-62.2,161.2h33L897.7,171.8z"/>
                            <path class="st0" d="M324.9,167.5c-11.5-29.8-17.3-52.8-17.3-69.2c0-19.7,8-33.4,24.1-41.1c16.1-7.7,42.8-12.2,80.3-13.6V14 c-41.4,2.9-104.3,4.3-188.8,4.3c-65.3,0-117.9-1.4-157.8-4.3v29.6c43.7,1.9,77.6,33.4,101.6,94.4l107.3,276.5H418L324.9,167.5z"/>
                        </g>
                    </g>
                </svg>
            </div>
            <div class="social-media container">
                <a target="_blank" href="https://web.facebook.com/people/VENUSIMAagency/61559850735470/" class="social_item">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28.3 28.3">
                        <path d="M18.2,13.1L18,14.9c0,0.3-0.3,0.5-0.6,0.5h-3v7.7c-0.3,0-0.6,0-1,0c-0.7,0-1.4-0.1-2.1-0.2v-7.5H8.7v-3.1 c0,0,0,0,0,0h2.7V9c0-2.1,1.7-3.8,3.8-3.8h3.1c0,0,0,0,0,0v3.1c0,0,0,0,0,0H16c-0.8,0-1.5,0.7-1.5,1.5v2.7h3.2 C18,12.4,18.3,12.7,18.2,13.1z" style="fill:#1890A0"/>
                        <path d="M14.6,0l-0.8,0C6.2,0,0,6.2,0,13.8l0,0.8c0,7.6,6.2,13.8,13.8,13.8h0.8c7.6,0,13.8-6.2,13.8-13.8v-0.8 C28.3,6.2,22.2,0,14.6,0z M27,14.6C27,21.4,21.4,27,14.6,27h-0.8C6.9,27,1.3,21.4,1.3,14.6v-0.8c0-6.9,5.6-12.5,12.5-12.5h0.8 C21.4,1.3,27,6.9,27,13.8V14.6z" style="fill:#1890A0"/>
                    </svg>
                </a>
                <a target="_blank" href="https://www.instagram.com/venusima.agency/" class="social_item">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28.35 28.35">
                        <path d="M18.3,7H10.08A3.09,3.09,0,0,0,7,10.06v8.22a3.1,3.1,0,0,0,3.1,3.1H18.3a3.09,3.09,0,0,0,3.09-3.1V10.06A3.09,3.09,0,0,0,18.3,7ZM20.15,18a2.1,2.1,0,0,1-2.1,2.11H10.33A2.11,2.11,0,0,1,8.22,18V10.31a2.11,2.11,0,0,1,2.11-2.1h7.72a2.1,2.1,0,0,1,2.1,2.1Z" transform="translate(0 0)" style="fill:#1890a0"/>
                        <path d="M15.82,10.79a3.77,3.77,0,0,0-5,5,3.36,3.36,0,0,0,1.74,1.74,3.77,3.77,0,0,0,5-5A3.4,3.4,0,0,0,15.82,10.79Zm-.39,5.56A2.51,2.51,0,0,1,12,12.93,2.14,2.14,0,0,1,13,12a2.52,2.52,0,0,1,3.42,3.42A2.14,2.14,0,0,1,15.43,16.35Z" transform="translate(0 0)" style="fill:#1890a0"/>
                        <ellipse cx="18.16" cy="10.2" rx="0.56" ry="0.77" transform="translate(-1.89 15.83) rotate(-45)" style="fill:#1890a0"/>
                        <path d="M14.55,0h-.76A13.78,13.78,0,0,0,0,13.79v.76a13.79,13.79,0,0,0,13.79,13.8h.76a13.8,13.8,0,0,0,13.8-13.8v-.76A13.79,13.79,0,0,0,14.55,0ZM27,14.55A12.48,12.48,0,0,1,14.55,27h-.76A12.47,12.47,0,0,1,1.32,14.55v-.76A12.47,12.47,0,0,1,13.79,1.32h.76A12.47,12.47,0,0,1,27,13.79Z" transform="translate(0 0)" style="fill:#1890a0"/>
                    </svg>
                </a>
                <a target="_blank" href="http://linkedin.com/in/venusima-agency-8b82a6310/" class="social_item">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 28.3 28.3">
                        <circle cx="9.3" cy="9.2" r="1.8" style="fill:#1890A0"/>
                        <rect x="7.8" y="12.1" width="2.9" height="8.8" style="fill:#1890A0"/>
                        <path d="M20.8,15.4v5.4c0,0.1-0.1,0.2-0.2,0.2h-2.5c-0.1,0-0.2-0.1-0.2-0.2V16c0-0.4-0.2-0.7-0.5-0.9 c-1.2-0.8-2.5,0-2.5,1.1v4.5c0,0.1-0.1,0.2-0.2,0.2h-2.5c-0.1,0-0.2-0.1-0.2-0.2v-8.4c0-0.1,0.1-0.2,0.2-0.2h2.5 c0.1,0,0.2,0.1,0.2,0.2v0.8c0.6-0.8,1.6-1.3,2.7-1.3C19.2,11.8,20.8,13,20.8,15.4z" style="fill:#1890A0"/>
                        <path d="M14.6,0l-0.8,0C6.2,0,0,6.2,0,13.8l0,0.8c0,7.6,6.2,13.8,13.8,13.8h0.8c7.6,0,13.8-6.2,13.8-13.8v-0.8 C28.3,6.2,22.2,0,14.6,0z M27,14.6C27,21.4,21.4,27,14.6,27h-0.8C6.9,27,1.3,21.4,1.3,14.6v-0.8c0-6.9,5.6-12.5,12.5-12.5h0.8 C21.4,1.3,27,6.9,27,13.8V14.6z" style="fill:#1890A0"/>
                    </svg>
                </a>
            </div>
        <?php wp_footer(); ?>
        <script src="js/script.js"></script>
    </body>
</html>
   