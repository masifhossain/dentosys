<?php

// Include the database configuration and connection logic
require_once __DIR__ . '/includes/db.php';

// The $conn variable is created in db.php
if ($conn) {
    echo "<h1>✅ Success!</h1>";
    echo "<p>Successfully connected to the '<strong>" . $DB_NAME . "</strong>' database.</p>";
} else {
    echo "<h1>❌ Error!</h1>";
    // mysqli_connect_error() will return the specific error message
    echo "<p>Failed to connect to the database: " . mysqli_connect_error() . "</p>";
}

// Close the connection
mysqli_close($conn);

?>