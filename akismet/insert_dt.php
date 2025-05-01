<?php
/**
 * Script Name: Insert Event Data
 * Description: Inserts sample data into the event management tables.
 * Version: 1.0.0
 * Author: soo
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include WordPress core to use $wpdb
require_once('wp-load.php');

// Function to insert sample data
function insert_sample_event_data() {
    global $wpdb;

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
    $event_element_supplementaires = $wpdb->prefix . 'event_elements';
    $event_suggestions_table = $wpdb->prefix . 'event_suggestions';

    // Insert data into wp_event table
    $wpdb->insert(
        $event_table,
        [
            'company_name' => 'Example Company',
            'event_name' => 'Example Event',
            'event_date' => '2025-04-01',
            'event_location' => 'Example Location',
            'event_time' => '10:00 AM',
            'contact_name' => 'John Doe',
            'contact_email' => 'john.doe@example.com',
            'contact_phone' => '123-456-7890'
        ]
    );
    $event_id = $wpdb->insert_id; // Get the last inserted event ID

    // Insert data into wp_event_objectives table
    $wpdb->insert(
        $event_objective_table,
        [
            'event_id' => $event_id,
            'objective_type' => 'notoriete',
            'objective_autre' => 'Increase brand awareness',
            'public_cible' => 'Young professionals',
            'participants' => '100'
        ]
    );

    // Insert data into wp_event_formats table
    $wpdb->insert(
        $event_format_table,
        [
            'event_id' => $event_id,
            'format_type' => 'Workshop, Conference',
            'format_autre' => 'Hybrid event'
        ]
    );

    // Insert data into wp_event_programs table
    $wpdb->insert(
        $event_program_table,
        [
            'event_id' => $event_id,
            'themes' => 'Technology and Innovation',
            'intervenants' => '1', // Boolean (1 = true, 0 = false)
            'intervenants_details' => 'Keynote speakers and panelists'
        ]
    );

    // Insert data into wp_event_logistics table
    $wpdb->insert(
        $event_logistics_table,
        [
            'event_id' => $event_id,
            'location_salle' => 1,
            'salle_type' => 'Conference Hall',
            'salle_capacite' => '500',
            'ambiance_souhaitee' => 'Professional',
            'installations_specifiques' => 'Projector, Microphones',
            'equipement_audiovisuel' => 1,
            'type_equipement' => 'Projector, Sound System',
            'configuration_specifique' => 'Theater-style seating',
            'besoin_assistance' => 'oui',
            'transports' => 1,
            'navettes' => 1,
            'stationnement' => 1,
            'transports_autre' => 'Shuttle service',
            'decoration' => 1,
            'decoration_souhaitee' => 'Modern and minimalistic',
            'matiere_signaletique' => 'oui',
            'cabine_photo' => 1,
            'photobooth' => 1,
            'animations' => 1,
            'animations_dj' => 1,
            'animateur' => 1,
            'spectacle' => 1,
            'animations_autre' => 'Live band',
            'securite' => 1,
            'gardes_securite' => 1,
            'controle_acces' => 1,
            'securite_autre' => 'Metal detectors',
            'logistiques_autre' => 'Additional staff'
        ]
    );

    // Insert data into wp_event_hygiene table
    $wpdb->insert(
        $event_hygiene_table,
        [
            'event_id' => $event_id,
            'nettoyage' => 1,
            'sanitaires' => 1,
            'desinfectants' => 1,
            'fourniture' => 1,
            'hygiene_autre' => 'Hand sanitizers at every entrance'
        ]
    );

    // Insert data into wp_event_hebergement table
    $wpdb->insert(
        $event_hebergement_table,
        [
            'event_id' => $event_id,
            'hebergement_necessaire' => 'oui',
            'hebergement_nuits' => 2,
            'hebergement_type' => 'hotels',
            'hebergement_autre' => 'Nearby hotels',
            'hebergement_budget' => 5000.00,
            'hebergement_localisation' => 'Downtown area',
            'hebergement_besoins' => 'Special dietary requirements'
        ]
    );

    // Insert data into wp_event_promotion table
    $wpdb->insert(
        $event_promotion_table,
        [
            'event_id' => $event_id,
            'promotion_necessaire' => 'oui',
            'campagnes' => 1,
            'campagne' => 'publicitaires',
            'promdig' => 1,
            'promdigs' => 'reseaux_sociaux',
            'shooting' => 1,
            'production' => 'photos',
            'imp' => 1,
            'design' => 'roll_up',
            'besoin_publicite_autre' => 'Social media ads'
        ]
    );

    // Insert data into wp_event_accueil table
    $wpdb->insert(
        $event_accueil_table,
        [
            'event_id' => $event_id,
            'accueil_necessaire' => 'oui',
            'accueil_type' => 'enregistrement',
            'accueil_autre' => 'Welcome desk',
            'accueil_materiel' => 'badges_nominaux',
            'accueil_materiel_autre' => 'Tablets for check-in',
            'accueil_personnes' => 5,
            'accueil_considerations' => 'Multilingual staff'
        ]
    );

    // Insert data into wp_event_restauration table
    $wpdb->insert(
        $event_restauration_table,
        [
            'event_id' => $event_id,
            'restauration_necessaire' => 'oui',
            'restauration_type' => 'buffets',
            'restauration_autre' => 'Coffee breaks',
            'restauration_duree' => '2 hours',
            'restauration_format' => 'buffet',
            'restauration_format_autre' => 'Food stations',
            'souhait_type' => 'vegetariennes',
            'allergies_alimentaires' => 'Gluten-free options',
            'repas_speciaux' => 'Vegan meals',
            'restauration_remarques' => 'Label all food items'
        ]
    );

    // Insert data into wp_event_gadgets table
    $wpdb->insert(
        $event_gadgets_table,
        [
            'event_id' => $event_id,
            'gadgets_necessaire' => 'oui',
            'gadgets_type' => 't_shirts',
            'gadgets_autre' => 'Custom notebooks',
            'combien_pieces' => '200',
            'branding_gadgets' => 'Company logo on all items'
        ]
    );

    // Insert data into wp_event_budget table
    $wpdb->insert(
        $event_budget_table,
        [
            'event_id' => $event_id,
            'budget' => 20000.00,
            'budget_gadgets' => 1000.00,
            'budget_promotion' => 3000.00,
            'budget_restauration' => 5000.00,
            'budget_autre' => 'Miscellaneous expenses'
        ]
    );

    // Insert data into wp_event_suivi table
    $wpdb->insert(
        $event_suivi_table,
        [
            'event_id' => $event_id,
            'suivi_type' => 'enquetes',
            'suivi_autre' => 'Post-event survey'
        ]
    );

    // Insert data into wp_event_elements table
    $wpdb->insert(
        $event_element_supplementaires,
        [
            'event_id' => $event_id,
            'exigences_specifiques' => 'Special lighting requirements'
        ]
    );

    // Insert data into wp_event_suggestions table
    $wpdb->insert(
        $event_suggestions_table,
        [
            'event_id' => $event_id,
            'commentaires' => 'Great event! More networking opportunities next time.'
        ]
    );

    echo "Sample data inserted successfully!";
}

// Run the function to insert data
insert_sample_event_data();