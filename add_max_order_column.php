<?php
include 'config.php';

try {
    // Check if max_order column already exists
    $result = $conn->query("SHOW COLUMNS FROM gudang_pecahon LIKE 'max_order'");
    if ($result->num_rows == 0) {
        // Add max_order column
        $sql = "ALTER TABLE gudang_pecahon ADD COLUMN max_order INT(11) DEFAULT 0";
        if ($conn->query($sql) === TRUE) {
            echo "Column max_order added successfully to gudang_pecahon table.\n";
        } else {
            echo "Error adding column: " . $conn->error . "\n";
        }
    } else {
        echo "Column max_order already exists in gudang_pecahon table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
