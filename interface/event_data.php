<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

// Include the database connection file
include('cnx.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start a transaction
    $conn->begin_transaction();

    try {
        // Retrieve and sanitize form data for wp_event
        $company_name = htmlspecialchars($_POST['company_name']);
        $event_name = htmlspecialchars($_POST['event_name']);
        $event_date = htmlspecialchars($_POST['event_date']);
        $event_location = htmlspecialchars($_POST['event_location']);
        $event_time = htmlspecialchars($_POST['event_time']);
        $contact_name = htmlspecialchars($_POST['contact_name']);
        $contact_email = filter_var($_POST['contact_email'], FILTER_SANITIZE_EMAIL);
        $contact_phone = htmlspecialchars($_POST['contact_phone']);

        // Insert into wp_event
        $stmt_event = $conn->prepare("INSERT INTO wp_event (company_name, event_name, event_date, event_location, event_time, contact_name, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_event === false) {
            throw new Exception("Error preparing event statement: " . $conn->error);
        }
        $stmt_event->bind_param("ssssssss", $company_name, $event_name, $event_date, $event_location, $event_time, $contact_name, $contact_email, $contact_phone);

        if (!$stmt_event->execute()) {
            throw new Exception("Error inserting into wp_event: " . $stmt_event->error);
        }

        // Get the auto-incremented event_id
        $event_id = $stmt_event->insert_id;

        // Retrieve and sanitize form data for wp_event_objectives
        $objective_type = isset($_POST['objectif']) ? implode(", ", $_POST['objectif']) : "";
        $objective_autre = isset($_POST['objectif_autre']) ? htmlspecialchars($_POST['objectif_autre']) : "";
        $public_cible = isset($_POST['public_cible']) ? htmlspecialchars($_POST['public_cible']) : "";
        $participants = isset($_POST['participants']) ? intval($_POST['participants']) : 0;

        // Insert into wp_event_objectives
        $stmt_objectives = $conn->prepare("INSERT INTO wp_event_objectives (event_id, objective_type, objective_autre, public_cible, participants) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_objectives === false) {
            throw new Exception("Error preparing objectives statement: " . $conn->error);
        }
        $stmt_objectives->bind_param("isssi", $event_id, $objective_type, $objective_autre, $public_cible, $participants);

        if (!$stmt_objectives->execute()) {
            throw new Exception("Error inserting into wp_event_objectives: " . $stmt_objectives->error);
        }

        // Retrieve and sanitize form data for wp_event_formats
        $format_type = isset($_POST['format']) ? implode(", ", $_POST['format']) : "";
        $format_autre = isset($_POST['format_autre']) ? htmlspecialchars($_POST['format_autre']) : "";

        // Insert into wp_event_formats
        $stmt_formats = $conn->prepare("INSERT INTO wp_event_formats (event_id, format_type, format_autre) VALUES (?, ?, ?)");
        if ($stmt_formats === false) {
            throw new Exception("Error preparing formats statement: " . $conn->error);
        }
        $stmt_formats->bind_param("iss", $event_id, $format_type, $format_autre);

        if (!$stmt_formats->execute()) {
            throw new Exception("Error inserting into wp_event_formats: " . $stmt_formats->error);
        }

        // Retrieve and sanitize form data for wp_event_programs
        $themes = isset($_POST['themes']) ? htmlspecialchars($_POST['themes']) : "";
        $intervenants = isset($_POST['intervenants']) ? htmlspecialchars($_POST['intervenants']) : "";
        $intervenants_details = isset($_POST['intervenants_details']) ? htmlspecialchars($_POST['intervenants_details']) : "";

        // Insert into wp_event_programs
        $stmt_programs = $conn->prepare("INSERT INTO wp_event_programs (event_id, themes, intervenants, intervenants_details) VALUES (?, ?, ?, ?)");
        if ($stmt_programs === false) {
            throw new Exception("Error preparing programs statement: " . $conn->error);
        }
        $stmt_programs->bind_param("isss", $event_id, $themes, $intervenants, $intervenants_details);

        if (!$stmt_programs->execute()) {
            throw new Exception("Error inserting into wp_event_programs: " . $stmt_programs->error);
        }

        // Retrieve and sanitize form data for wp_event_logistics
        $location_salle = isset($_POST['location_salle']) ? 1 : 0;
        $salle_type = $_POST['salle_type'] ?? null;
        $salle_capacite = $_POST['salle_capacite'] ?? null;
        $ambiance_souhaitee = $_POST['Ambiance_souhaitee'] ?? null;
        $installations_specifiques = $_POST['installations_specifiques'] ?? null;
        $equipement_audiovisuel = isset($_POST['equipement_audiovisuel']) ? 1 : 0;
        $type_equipement = $_POST['type_equipement'] ?? null;
        $configuration_specifique = $_POST['configuration_specifique'] ?? null;
        $besoin_assistance = $_POST['besoin_assistance'] ?? null;
        $transports = isset($_POST['transports']) ? 1 : 0;
        $navettes = isset($_POST['Navettes']) ? 1 : 0;
        $stationnement = isset($_POST['Stationnement']) ? 1 : 0;
        $transports_autre = $_POST['transports_autre'] ?? null;
        $decoration = isset($_POST['decoration']) ? 1 : 0;
        $decoration_souhaitee = $_POST['decoration_souhaitee'] ?? null;
        $matiere_signaletique = $_POST['matiere_signaletique'] ?? null;
        $cabine_photo = isset($_POST['Cabine_Photo']) ? 1 : 0;
        $photobooth = isset($_POST['Photobooth']) ? 1 : 0;
        $animations = isset($_POST['Animations']) ? 1 : 0;
        $animations_dj = isset($_POST['Animations_DJ']) ? 1 : 0;
        $animateur = isset($_POST['Animateur']) ? 1 : 0;
        $spectacle = isset($_POST['Spectacle']) ? 1 : 0;
        $animations_autre = $_POST['Animations_autre'] ?? null;
        $securite = isset($_POST['securite']) ? 1 : 0;
        $gardes_securite = isset($_POST['Gardes_securite']) ? 1 : 0;
        $controle_acces = isset($_POST['Controle_acces']) ? 1 : 0;
        $securite_autre = $_POST['securite_autre'] ?? null;
        $logistiques_autre = $_POST['logistiques_autre'] ?? null;

        // Insert into wp_event_logistics
        $stmt_logistics = $conn->prepare("INSERT INTO wp_event_logistics (
            event_id, 
            location_salle, 
            salle_type, 
            salle_capacite, 
            ambiance_souhaitee, 
            installations_specifiques, 
            equipement_audiovisuel, 
            type_equipement, 
            configuration_specifique, 
            besoin_assistance, 
            transports, 
            navettes, 
            stationnement, 
            transports_autre, 
            decoration, 
            decoration_souhaitee, 
            matiere_signaletique, 
            cabine_photo, 
            photobooth, 
            animations, 
            animations_dj, 
            animateur, 
            spectacle, 
            animations_autre, 
            securite, 
            gardes_securite, 
            controle_acces, 
            securite_autre, 
            logistiques_autre
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_logistics === false) {
            throw new Exception("Error preparing logistics statement: " . $conn->error);
        }
        $stmt_logistics->bind_param(
            "iisssssisssssssssssssssssssss", // Data types: i = integer, s = string
            $event_id,
            $location_salle,
            $salle_type,
            $salle_capacite,
            $ambiance_souhaitee,
            $installations_specifiques,
            $equipement_audiovisuel,
            $type_equipement,
            $configuration_specifique,
            $besoin_assistance,
            $transports,
            $navettes,
            $stationnement,
            $transports_autre,
            $decoration,
            $decoration_souhaitee,
            $matiere_signaletique,
            $cabine_photo,
            $photobooth,
            $animations,
            $animations_dj,
            $animateur,
            $spectacle,
            $animations_autre,
            $securite,
            $gardes_securite,
            $controle_acces,
            $securite_autre,
            $logistiques_autre
        );

        if (!$stmt_logistics->execute()) {
            throw new Exception("Error inserting into wp_event_logistics: " . $stmt_logistics->error);
        }

        // Retrieve and sanitize form data for wp_event_hygiene
        $nettoyage = isset($_POST['hygiene']) && in_array('nettoyage', $_POST['hygiene']) ? 1 : 0;
        $sanitaires = isset($_POST['hygiene']) && in_array('sanitaires', $_POST['hygiene']) ? 1 : 0;
        $desinfectants = isset($_POST['hygiene']) && in_array('desinfectants', $_POST['hygiene']) ? 1 : 0;
        $fourniture = isset($_POST['hygiene']) && in_array('Fourniture', $_POST['hygiene']) ? 1 : 0;
        $hygiene_autre = isset($_POST['hygiene_autre']) ? htmlspecialchars($_POST['hygiene_autre']) : "";

        // Insert into wp_event_hygiene
        $stmt_hygiene = $conn->prepare("INSERT INTO wp_event_hygiene (event_id, nettoyage, sanitaires, desinfectants, fourniture, hygiene_autre) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_hygiene === false) {
            throw new Exception("Error preparing hygiene statement: " . $conn->error);
        }
        $stmt_hygiene->bind_param("iiiiis", $event_id, $nettoyage, $sanitaires, $desinfectants, $fourniture, $hygiene_autre);

        if (!$stmt_hygiene->execute()) {
            throw new Exception("Error inserting into wp_event_hygiene: " . $stmt_hygiene->error);
        }

        // Retrieve and sanitize form data for wp_event_hebergement
        $hebergement_necessaire = isset($_POST['hebergement']) ? htmlspecialchars($_POST['hebergement']) : "";
        $hebergement_nuits = isset($_POST['hebergement_nuits']) ? intval($_POST['hebergement_nuits']) : 0;
        $hebergement_type = isset($_POST['hebergement_type']) ? implode(", ", $_POST['hebergement_type']) : "";
        $hebergement_autre = isset($_POST['hebergement_autre']) ? htmlspecialchars($_POST['hebergement_autre']) : "";
        $hebergement_budget = !empty($_POST['hebergement_budget']) ? floatval($_POST['hebergement_budget']) : NULL; // Handle empty budget
        $hebergement_localisation = isset($_POST['hebergement_localisation']) ? htmlspecialchars($_POST['hebergement_localisation']) : "";
        $hebergement_besoins = isset($_POST['hebergement_besoins']) ? htmlspecialchars($_POST['hebergement_besoins']) : "";

        // Insert into wp_event_hebergement
        $stmt_hebergement = $conn->prepare("INSERT INTO wp_event_hebergement (event_id, hebergement_necessaire, hebergement_nuits, hebergement_type, hebergement_autre, hebergement_budget, hebergement_localisation, hebergement_besoins) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_hebergement === false) {
            throw new Exception("Error preparing hebergement statement: " . $conn->error);
        }
        $stmt_hebergement->bind_param("isisssss", $event_id, $hebergement_necessaire, $hebergement_nuits, $hebergement_type, $hebergement_autre, $hebergement_budget, $hebergement_localisation, $hebergement_besoins);

        if (!$stmt_hebergement->execute()) {
            throw new Exception("Error inserting into wp_event_hebergement: " . $stmt_hebergement->error);
        }

        // Retrieve and sanitize form data for wp_event_promotion
        $promotion_necessaire = isset($_POST['promotion']) ? $_POST['promotion'] : "";
        $campagnes = isset($_POST['Campagnes']) ? 1 : 0;
        $campagne = isset($_POST['Campagne']) ? json_encode($_POST['Campagne']) : NULL;
        $promdig = isset($_POST['promdig']) ? 1 : 0;
        $promdigs = isset($_POST['promdigs']) ? json_encode($_POST['promdigs']) : NULL;
        $shooting = isset($_POST['Shooting']) ? 1 : 0;
        $production = isset($_POST['production']) ? json_encode($_POST['production']) : NULL;
        $imp = isset($_POST['imp']) ? 1 : 0;
        $design = isset($_POST['design']) ? json_encode($_POST['design']) : NULL;
        $besoin_publicite_autre = isset($_POST['besoin_publicite_autre']) ? $_POST['besoin_publicite_autre'] : "";
      
        // Insert into wp_event_promotion
        $stmt_promotion = $conn->prepare("INSERT INTO wp_event_promotion (event_id, promotion_necessaire, campagnes, campagne, promdig, promdigs, shooting, production, imp, design, besoin_publicite_autre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_promotion === false) {
            throw new Exception("Error preparing promotion statement: " . $conn->error);
        }
        $stmt_promotion->bind_param("isissssssss", $event_id, $promotion_necessaire, $campagnes, $campagne, $promdig, $promdigs, $shooting, $production, $imp, $design, $besoin_publicite_autre);
      
        if (!$stmt_promotion->execute()) {
            throw new Exception("Error inserting into wp_event_promotion: " . $stmt_promotion->error);
        }

        // Retrieve and sanitize form data for wp_event_accueil
        $accueil_necessaire = $_POST['accueil']; // "oui" or "non"
        $accueil_type = isset($_POST['accueil_type']) ? $_POST['accueil_type'] : null; // Selected type
        $accueil_autre = $_POST['accueil_autre'] ?? null; // Optional "autre" field
        $accueil_materiel = isset($_POST['accueil_materiel']) ? $_POST['accueil_materiel'] : null; // Selected material
        $accueil_materiel_autre = $_POST['accueil_materiel_autre'] ?? null; // Optional "autre" field
        $accueil_personnes = $_POST['accueil_personnes'] ?? null; // Number of people
        $accueil_considerations = $_POST['accueil_considerations'] ?? null; // Considerations

        // Insert into wp_event_accueil
        $stmt_accueil = $conn->prepare("INSERT INTO wp_event_accueil (
            event_id, 
            accueil_necessaire, 
            accueil_type, 
            accueil_autre, 
            accueil_materiel, 
            accueil_materiel_autre, 
            accueil_personnes, 
            accueil_considerations
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_accueil === false) {
            throw new Exception("Error preparing accueil statement: " . $conn->error);
        }
        $stmt_accueil->bind_param("isssssis", $event_id, $accueil_necessaire, $accueil_type, $accueil_autre, $accueil_materiel, $accueil_materiel_autre, $accueil_personnes, $accueil_considerations);

        if (!$stmt_accueil->execute()) {
            throw new Exception("Error inserting into wp_event_accueil: " . $stmt_accueil->error);
        }

        // Retrieve and sanitize form data for wp_event_restauration
        $restauration_necessaire = in_array($_POST['restauration'], ['oui', 'non']) ? $_POST['restauration'] : null;
        $restauration_type = isset($_POST['restauration_type']) ? implode(", ", $_POST['restauration_type']) : null;
        $restauration_autre = $_POST['restauration_autre'] ?? null;
        $restauration_duree = $_POST['restauration_duree'] ?? null;
        $restauration_format = isset($_POST['restauration_format']) ? implode(", ", $_POST['restauration_format']) : null;
        $restauration_format_autre = $_POST['restauration_format_autre'] ?? null;
        $souhait_type = isset($_POST['souhait_type']) ? implode(", ", $_POST['souhait_type']) : null;
        $allergies_alimentaires = $_POST['allergies_alimentaires'] ?? null;
        $repas_speciaux = $_POST['repas_speciaux'] ?? null;
        $restauration_remarques = $_POST['restauration_remarques'] ?? null;

        // Insert into wp_event_restauration
        $stmt_restauration = $conn->prepare("INSERT INTO wp_event_restauration (
            event_id, 
            restauration_necessaire, 
            restauration_type, 
            restauration_autre, 
            restauration_duree, 
            restauration_format, 
            restauration_format_autre, 
            souhait_type, 
            allergies_alimentaires, 
            repas_speciaux, 
            restauration_remarques
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_restauration === false) {
            throw new Exception("Error preparing restauration statement: " . $conn->error);
        }
        $stmt_restauration->bind_param("issssssssss", $event_id, $restauration_necessaire, $restauration_type, $restauration_autre, $restauration_duree, $restauration_format, $restauration_format_autre, $souhait_type, $allergies_alimentaires, $repas_speciaux, $restauration_remarques);

        if (!$stmt_restauration->execute()) {
            throw new Exception("Error inserting into wp_event_restauration: " . $stmt_restauration->error);
        }

        // Retrieve and sanitize form data for wp_event_gadgets
        $gadgets_necessaire = isset($_POST['gadgets']) && in_array($_POST['gadgets'], ['oui', 'non']) ? $_POST['gadgets'] : null;
        $gadgets_type = isset($_POST['gadgets_type']) ? implode(", ", $_POST['gadgets_type']) : null;
        $gadgets_autre = $_POST['gadgets_autre'] ?? null;
        $combien_pieces = $_POST['combien_pieces'] ?? null;
        $branding_gadgets = $_POST['branding_gadgets'] ?? null;

        // Insert into wp_event_gadgets
        $stmt_gadgets = $conn->prepare("INSERT INTO wp_event_gadgets (
            event_id, 
            gadgets_necessaire, 
            gadgets_type, 
            gadgets_autre, 
            combien_pieces, 
            branding_gadgets
        ) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt_gadgets === false) {
            throw new Exception("Error preparing gadgets statement: " . $conn->error);
        }
        $stmt_gadgets->bind_param("isssss", $event_id, $gadgets_necessaire, $gadgets_type, $gadgets_autre, $combien_pieces, $branding_gadgets);

        if (!$stmt_gadgets->execute()) {
            throw new Exception("Error inserting into wp_event_gadgets: " . $stmt_gadgets->error);
        }

        // Retrieve and sanitize form data for wp_event_budget
        $budget_total = $_POST['budget'] ?? null;
        $budget_logistique = $_POST['budget-Logistique'] ?? null;
        $budget_promotion = $_POST['budget-Promotion'] ?? null;
        $budget_restauration = $_POST['budget-Restauration'] ?? null;
        $budget_gadgets = $_POST['budget-Gadgets'] ?? null;
        $budget_autre = $_POST['Budget_autre'] ?? null;

        // Insert into wp_event_budget
        $stmt_budget = $conn->prepare("INSERT INTO wp_event_budget (
            event_id, 
            budget_total, 
            budget_logistique, 
            budget_promotion, 
            budget_restauration, 
            budget_gadgets, 
            budget_autre
        ) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_budget === false) {
            throw new Exception("Error preparing budget statement: " . $conn->error);
        }
        $stmt_budget->bind_param("idddddd", $event_id, $budget_total, $budget_logistique, $budget_promotion, $budget_restauration, $budget_gadgets, $budget_autre);

        if (!$stmt_budget->execute()) {
            throw new Exception("Error inserting into wp_event_budget: " . $stmt_budget->error);
        }

        // Retrieve and sanitize form data for wp_event_suivi
        $suivi_type = isset($_POST['suivi']) ? implode(", ", $_POST['suivi']) : null; // Checkbox values as comma-separated string
        $suivi_autre = $_POST['suivi_autre'] ?? null; // Optional "autre" field

        // Insert into wp_event_suivi
        $stmt_suivi = $conn->prepare("INSERT INTO wp_event_suivi (
            event_id, 
            suivi_type, 
            suivi_autre
        ) VALUES (?, ?, ?)");
        if ($stmt_suivi === false) {
            throw new Exception("Error preparing suivi statement: " . $conn->error);
        }
        $stmt_suivi->bind_param("iss", $event_id, $suivi_type, $suivi_autre);

        if (!$stmt_suivi->execute()) {
            throw new Exception("Error inserting into wp_event_suivi: " . $stmt_suivi->error);
        }

        // Retrieve and sanitize form data for wp_event_supplementaires
        $exigences_specifiques = $_POST['exigences_specifiques'] ?? null; // Specific requirements

        // Insert into wp_event_supplementaires
        $stmt_supplementaires = $conn->prepare("INSERT INTO wp_event_supplementaires (
            event_id, 
            exigences_specifiques
        ) VALUES (?, ?)");
        if ($stmt_supplementaires === false) {
            throw new Exception("Error preparing supplementaires statement: " . $conn->error);
        }
        $stmt_supplementaires->bind_param("is", $event_id, $exigences_specifiques);

        if (!$stmt_supplementaires->execute()) {
            throw new Exception("Error inserting into wp_event_supplementaires: " . $stmt_supplementaires->error);
        }

        // Retrieve and sanitize form data for wp_event_suggestions
        $commentaires = $_POST['commentaires'] ?? null; // Comments or suggestions

        // Insert into wp_event_suggestions
        $stmt_suggestions = $conn->prepare("INSERT INTO wp_event_suggestions (
            event_id, 
            commentaires
        ) VALUES (?, ?)");
        if ($stmt_suggestions === false) {
            throw new Exception("Error preparing suggestions statement: " . $conn->error);
        }
        $stmt_suggestions->bind_param("is", $event_id, $commentaires);

        if (!$stmt_suggestions->execute()) {
            throw new Exception("Error inserting into wp_event_suggestions: " . $stmt_suggestions->error);
        }

        // Commit the transaction
        $conn->commit();
        echo "New records created successfully. Event ID: " . $event_id;
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    } finally {
        // Close the statements
        if (isset($stmt_event)) {
            $stmt_event->close();
        }
        if (isset($stmt_objectives)) {
            $stmt_objectives->close();
        }
        if (isset($stmt_formats)) {
            $stmt_formats->close();
        }
        if (isset($stmt_programs)) {
            $stmt_programs->close();
        }
        if (isset($stmt_logistics)) {
            $stmt_logistics->close();
        }
        if (isset($stmt_hygiene)) {
            $stmt_hygiene->close();
        }
        if (isset($stmt_hebergement)) {
            $stmt_hebergement->close();
        }
        if (isset($stmt_promotion)) {
            $stmt_promotion->close();
        }
        if (isset($stmt_accueil)) {
            $stmt_accueil->close();
        }
        if (isset($stmt_restauration)) {
            $stmt_restauration->close();
        }
        if (isset($stmt_gadgets)) {
            $stmt_gadgets->close();
        }
        if (isset($stmt_budget)) {
            $stmt_budget->close();
        }
        if (isset($stmt_suivi)) {
            $stmt_suivi->close();
        }
        if (isset($stmt_supplementaires)) {
            $stmt_supplementaires->close();
        }
        if (isset($stmt_suggestions)) {
            $stmt_suggestions->close();
        }
    }
}

// Close the connection
$conn->close();
?>