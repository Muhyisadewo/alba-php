<?php
include __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate input
if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id = (int) $_POST['id'];
$status = (int) $_POST['status'];

// Validate status value (should be 0 or 1)
if ($status !== 0 && $status !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    // Update the is_listed status
    $stmt = $conn->prepare("UPDATE daftar_barang SET is_listed = ? WHERE id = ?");
    $stmt->bind_param("ii", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
