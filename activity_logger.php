<?php
/**
 * activity_logger.php
 * Reusable function for inserting activity logs into the database.
 * * FIX: Explicitly set autocommit to TRUE for the log insertion to ensure
 * the entry is permanently saved, even if the passed connection is in
 * an autocommit(FALSE) state due to a surrounding transaction.
 */
function log_activity($mysqli, $description) {
    // Save the current autocommit state
    $original_autocommit_state = $mysqli->autocommit(TRUE);

    // Sanitize the input
    $safe_description = $mysqli->real_escape_string($description);
    
    $success = false;
    
    // Use prepared statement for safer insertion
    $stmt = $mysqli->prepare("INSERT INTO coordinator_log (description) VALUES (?)");
    
    if ($stmt) {
        $stmt->bind_param("s", $safe_description);
        
        if ($stmt->execute()) {
            // Log successful, transaction is implicitly committed due to autocommit(TRUE)
            $success = true;
        } else {
            error_log("Failed to insert activity log: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare activity log statement: " . $mysqli->error);
    }
    
    // Restore the original autocommit state for the main script
    $mysqli->autocommit($original_autocommit_state);
    
    return $success;
}
?>