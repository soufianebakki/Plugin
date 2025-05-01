<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include WordPress core to use $wpdb
require_once('../../../wp-load.php');

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    global $wpdb;

    // Start a transaction
    $wpdb->query('START TRANSACTION');

    try {
        // Retrieve and sanitize form data for wp_event
        $company_name = sanitize_text_field($_POST['company_name']);
        $event_name = sanitize_text_field($_POST['event_name']);
        $event_date = sanitize_text_field($_POST['event_date']);
        $event_location = sanitize_text_field($_POST['event_location']);
        $event_time = sanitize_text_field($_POST['event_time']);
        $contact_name = sanitize_text_field($_POST['contact_name']);
        $contact_email = sanitize_email($_POST['contact_email']);
        $contact_phone = sanitize_text_field($_POST['contact_phone']);

        // Insert into wp_event
        $wpdb->insert(
            $wpdb->prefix . 'event',
            array(
                'company_name' => $company_name,
                'event_name' => $event_name,
                'event_date' => $event_date,
                'event_location' => $event_location,
                'event_time' => $event_time,
                'contact_name' => $contact_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // Get the auto-incremented event_id
        $event_id = $wpdb->insert_id;

        if (!$event_id) {
            throw new Exception("Error inserting into wp_event: " . $wpdb->last_error);
        }

        // Insert into wp_event_objectives
        $objective_type = isset($_POST['objectif']) ? implode(", ", $_POST['objectif']) : "";
        $objective_autre = isset($_POST['objectif_autre']) ? sanitize_text_field($_POST['objectif_autre']) : "";
        $public_cible = isset($_POST['public_cible']) ? sanitize_text_field($_POST['public_cible']) : "";
        $participants = isset($_POST['participants']) ? intval($_POST['participants']) : 0;

        $wpdb->insert(
            $wpdb->prefix . 'event_objectives',
            array(
                'event_id' => $event_id,
                'objective_type' => $objective_type,
                'objective_autre' => $objective_autre,
                'public_cible' => $public_cible,
                'participants' => $participants
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );

        // Insert into wp_event_formats
        $format_type = isset($_POST['format']) ? implode(", ", $_POST['format']) : "";
        $format_autre = isset($_POST['format_autre']) ? sanitize_text_field($_POST['format_autre']) : "";

        $wpdb->insert(
            $wpdb->prefix . 'event_formats',
            array(
                'event_id' => $event_id,
                'format_type' => $format_type,
                'format_autre' => $format_autre
            ),
            array('%d', '%s', '%s')
        );

        // Insert into wp_event_programs
        $themes = isset($_POST['themes']) ? sanitize_text_field($_POST['themes']) : "";
        $intervenants = isset($_POST['intervenants']) ? sanitize_text_field($_POST['intervenants']) : "";
        $intervenants_details = isset($_POST['intervenants_details']) ? sanitize_text_field($_POST['intervenants_details']) : "";

        $wpdb->insert(
            $wpdb->prefix . 'event_programs',
            array(
                'event_id' => $event_id,
                'themes' => $themes,
                'intervenants' => $intervenants,
                'intervenants_details' => $intervenants_details
            ),
            array('%d', '%s', '%s', '%s')
        );

        // Insert into wp_event_logistics
        $wpdb->insert(
            $wpdb->prefix . 'event_logistics',
            array(
                'event_id' => $event_id,
                'location_salle' => isset($_POST['location_salle']) ? 1 : 0,
                'salle_type' => sanitize_text_field($_POST['salle_type'] ?? ''),
                'salle_capacite' => sanitize_text_field($_POST['salle_capacite'] ?? ''),
                'ambiance_souhaitee' => sanitize_text_field($_POST['Ambiance_souhaitee'] ?? ''),
                'installations_specifiques' => sanitize_text_field($_POST['installations_specifiques'] ?? ''),
                'equipement_audiovisuel' => isset($_POST['equipement_audiovisuel']) ? 1 : 0,
                'type_equipement' => sanitize_text_field($_POST['type_equipement'] ?? ''),
                'configuration_specifique' => sanitize_text_field($_POST['configuration_specifique'] ?? ''),
                'besoin_assistance' => sanitize_text_field($_POST['besoin_assistance'] ?? ''),
                'transports' => isset($_POST['transports']) ? 1 : 0,
                'navettes' => isset($_POST['Navettes']) ? 1 : 0,
                'stationnement' => isset($_POST['Stationnement']) ? 1 : 0,
                'transports_autre' => sanitize_text_field($_POST['transports_autre'] ?? ''),
                'decoration' => isset($_POST['decoration']) ? 1 : 0,
                'decoration_souhaitee' => sanitize_text_field($_POST['decoration_souhaitee'] ?? ''),
                'matiere_signaletique' => sanitize_text_field($_POST['matiere_signaletique'] ?? ''),
                'cabine_photo' => isset($_POST['Cabine_Photo']) ? 1 : 0,
                'photobooth' => isset($_POST['Photobooth']) ? 1 : 0,
                'animations' => isset($_POST['Animations']) ? 1 : 0,
                'animations_dj' => isset($_POST['Animations_DJ']) ? 1 : 0,
                'animateur' => isset($_POST['Animateur']) ? 1 : 0,
                'spectacle' => isset($_POST['Spectacle']) ? 1 : 0,
                'animations_autre' => sanitize_text_field($_POST['Animations_autre'] ?? ''),
                'securite' => isset($_POST['securite']) ? 1 : 0,
                'gardes_securite' => isset($_POST['Gardes_securite']) ? 1 : 0,
                'controle_acces' => isset($_POST['Controle_acces']) ? 1 : 0,
                'securite_autre' => sanitize_text_field($_POST['securite_autre'] ?? ''),
                'logistiques_autre' => sanitize_text_field($_POST['logistiques_autre'] ?? '')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%d', '%s', '%s')
        );

        // Insert into wp_event_hygiene
        $wpdb->insert(
            $wpdb->prefix . 'event_hygiene',
            array(
                'event_id' => $event_id,
                'nettoyage' => isset($_POST['hygiene']) && in_array('nettoyage', $_POST['hygiene']) ? 1 : 0,
                'sanitaires' => isset($_POST['hygiene']) && in_array('sanitaires', $_POST['hygiene']) ? 1 : 0,
                'desinfectants' => isset($_POST['hygiene']) && in_array('desinfectants', $_POST['hygiene']) ? 1 : 0,
                'fourniture' => isset($_POST['hygiene']) && in_array('Fourniture', $_POST['hygiene']) ? 1 : 0,
                'hygiene_autre' => sanitize_text_field($_POST['hygiene_autre'] ?? '')
            ),
            array('%d', '%d', '%d', '%d', '%d', '%s')
        );

        // Insert into wp_event_hebergement
        $wpdb->insert(
            $wpdb->prefix . 'event_hebergement',
            array(
                'event_id' => $event_id,
                'hebergement_necessaire' => sanitize_text_field($_POST['hebergement'] ?? ''),
                'hebergement_nuits' => intval($_POST['hebergement_nuits'] ?? 0),
                'hebergement_type' => isset($_POST['hebergement_type']) ? implode(", ", $_POST['hebergement_type']) : "",
                'hebergement_autre' => sanitize_text_field($_POST['hebergement_autre'] ?? ''),
                'hebergement_budget' => floatval($_POST['hebergement_budget'] ?? 0),
                'hebergement_localisation' => sanitize_text_field($_POST['hebergement_localisation'] ?? ''),
                'hebergement_besoins' => sanitize_text_field($_POST['hebergement_besoins'] ?? '')
            ),
            array('%d', '%s', '%d', '%s', '%s', '%f', '%s', '%s')
        );

        // Insert into wp_event_promotion
        $wpdb->insert(
            $wpdb->prefix . 'event_promotion',
            array(
                'event_id' => $event_id,
                'promotion_necessaire' => sanitize_text_field($_POST['promotion'] ?? ''),
                'campagnes' => isset($_POST['Campagnes']) ? 1 : 0,
                'campagne' => isset($_POST['Campagne']) ? json_encode($_POST['Campagne']) : NULL,
                'promdig' => isset($_POST['promdig']) ? 1 : 0,
                'promdigs' => isset($_POST['promdigs']) ? json_encode($_POST['promdigs']) : NULL,
                'shooting' => isset($_POST['Shooting']) ? 1 : 0,
                'production' => isset($_POST['production']) ? json_encode($_POST['production']) : NULL,
                'imp' => isset($_POST['imp']) ? 1 : 0,
                'design' => isset($_POST['Design']) ? json_encode($_POST['Design']) : NULL,
                'besoin_publicite_autre' => sanitize_text_field($_POST['besoin_publicite_autre'] ?? '')
            ),
            array('%d', '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s')
        );

        // Insert into wp_event_accueil
        $wpdb->insert(
            $wpdb->prefix . 'event_accueil',
            array(
                'event_id' => $event_id,
                'accueil_necessaire' => sanitize_text_field($_POST['accueil'] ?? ''),
                'accueil_type' => sanitize_text_field($_POST['accueil_type'] ?? ''),
                'accueil_autre' => sanitize_text_field($_POST['accueil_autre'] ?? ''),
                'accueil_materiel' => sanitize_text_field($_POST['accueil_materiel'] ?? ''),
                'accueil_materiel_autre' => sanitize_text_field($_POST['accueil_materiel_autre'] ?? ''),
                'accueil_personnes' => intval($_POST['accueil_personnes'] ?? 0),
                'accueil_considerations' => sanitize_text_field($_POST['accueil_considerations'] ?? '')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s')
        );

        // Insert into wp_event_restauration
        $wpdb->insert(
            $wpdb->prefix . 'event_restauration',
            array(
                'event_id' => $event_id,
                'restauration_necessaire' => sanitize_text_field($_POST['restauration'] ?? ''),
                'restauration_type' => isset($_POST['restauration_type']) ? implode(", ", $_POST['restauration_type']) : "",
                'restauration_autre' => sanitize_text_field($_POST['restauration_autre'] ?? ''),
                'restauration_duree' => sanitize_text_field($_POST['restauration_duree'] ?? ''),
                'restauration_format' => isset($_POST['restauration_format']) ? implode(", ", $_POST['restauration_format']) : "",
                'restauration_format_autre' => sanitize_text_field($_POST['restauration_format_autre'] ?? ''),
                'souhait_type' => isset($_POST['souhait_type']) ? implode(", ", $_POST['souhait_type']) : "",
                'allergies_alimentaires' => sanitize_text_field($_POST['allergies_alimentaires'] ?? ''),
                'repas_speciaux' => sanitize_text_field($_POST['repas_speciaux'] ?? ''),
                'restauration_remarques' => sanitize_text_field($_POST['restauration_remarques'] ?? '')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // Insert into wp_event_gadgets
        $wpdb->insert(
            $wpdb->prefix . 'event_gadgets',
            array(
                'event_id' => $event_id,
                'gadgets_necessaire' => sanitize_text_field($_POST['gadgets'] ?? ''),
                'gadgets_type' => isset($_POST['gadgets_type']) ? implode(", ", $_POST['gadgets_type']) : "",
                'gadgets_autre' => sanitize_text_field($_POST['gadgets_autre'] ?? ''),
                'combien_pieces' => sanitize_text_field($_POST['combien_pieces'] ?? ''),
                'branding_gadgets' => sanitize_text_field($_POST['branding_gadgets'] ?? '')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );

        // Insert into wp_event_budget
        $wpdb->insert(
            $wpdb->prefix . 'event_budget',
            array(
                'event_id' => $event_id,
                'budget_total' => floatval($_POST['budget'] ?? 0),
                'budget_logistique' => floatval($_POST['budget-Logistique'] ?? 0),
                'budget_promotion' => floatval($_POST['budget-Promotion'] ?? 0),
                'budget_restauration' => floatval($_POST['budget-Restauration'] ?? 0),
                'budget_gadgets' => floatval($_POST['budget-Gadgets'] ?? 0),
                'budget_autre' => sanitize_text_field($_POST['Budget_autre'] ?? '')
            ),
            array('%d', '%f', '%f', '%f', '%f', '%f', '%s')
        );

        // Insert into wp_event_suivi
        $wpdb->insert(
            $wpdb->prefix . 'event_suivi',
            array(
                'event_id' => $event_id,
                'suivi_type' => isset($_POST['suivi']) ? implode(", ", $_POST['suivi']) : "",
                'suivi_autre' => sanitize_text_field($_POST['suivi_autre'] ?? '')
            ),
            array('%d', '%s', '%s')
        );

        // Insert into wp_event_supplementaires
        $wpdb->insert(
            $wpdb->prefix . 'event_supplementaires',
            array(
                'event_id' => $event_id,
                'exigences_specifiques' => sanitize_text_field($_POST['exigences_specifiques'] ?? '')
            ),
            array('%d', '%s')
        );

        // Insert into wp_event_suggestions
        $wpdb->insert(
            $wpdb->prefix . 'event_suggestions',
            array(
                'event_id' => $event_id,
                'commentaires' => sanitize_text_field($_POST['commentaires'] ?? '')
            ),
            array('%d', '%s')
        );

        // Commit the transaction
        $wpdb->query('COMMIT');
        echo "New records created successfully. Event ID: " . $event_id;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $wpdb->query('ROLLBACK');
        echo "Error: " . $e->getMessage();
    }
}
?>