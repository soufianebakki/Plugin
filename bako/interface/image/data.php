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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Start a transaction
    $conn->begin_transaction();

    try {
        // Retrieve and sanitize form data for wp_event_promotion
        $promotion_necessaire = isset($_POST['promotion']) ? htmlspecialchars($_POST['promotion']) : "";
        $campagnes = isset($_POST['Campagnes']) ? 1 : 0;
        $campagne = isset($_POST['Campagne']) ? json_encode($_POST['Campagne']) : NULL;
        $promdig = isset($_POST['promdig']) ? 1 : 0;
        $promdigs = isset($_POST['promdigs']) ? json_encode($_POST['promdigs']) : NULL;
        $shooting = isset($_POST['Shooting']) ? 1 : 0;
        $production = isset($_POST['production']) ? json_encode($_POST['production']) : NULL;
        $imp = isset($_POST['imp']) ? 1 : 0;
        $design = isset($_POST['Design']) ? json_encode($_POST['Design']) : NULL;
        $besoin_publicite_autre = isset($_POST['besoin_publicite_autre']) ? htmlspecialchars($_POST['besoin_publicite_autre']) : "";

        // Check if event_id is set (you might want to get this from session or another source)
        if (!isset($event_id)) {
            throw new Exception("Event ID is not set");
        }

        // Insert into wp_event_promotion
        $stmt_promotion = $conn->prepare("INSERT INTO wp_event_promotion (event_id, promotion_necessaire, campagnes, campagne, promdig, promdigs, shooting, production, imp, design, besoin_publicite_autre) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_promotion === false) {
            throw new Exception("Error preparing promotion statement: " . $conn->error);
        }
        
        $stmt_promotion->bind_param("isissssssss", $event_id, $promotion_necessaire, $campagnes, $campagne, $promdig, $promdigs, $shooting, $production, $imp, $design, $besoin_publicite_autre);

        if (!$stmt_promotion->execute()) {
            throw new Exception("Error inserting into wp_event_promotion: " . $stmt_promotion->error);
        }

        // Commit the transaction if everything is successful
        $conn->commit();
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        die("Error: " . $e->getMessage());
    } finally {
        // Close the statement if it exists
        if (isset($stmt_promotion)) {
            $stmt_promotion->close();
        }
    }
}

// Close the connection
$conn->close();
?>