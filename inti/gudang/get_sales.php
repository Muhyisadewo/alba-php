<?php
include __DIR__ .'/../../config.php';

$supplier_id = $_GET['supplier_id'] ?? 0;

if ($supplier_id > 0) {
    $sql = "SELECT id, nama_sales FROM sales WHERE supplier_id = ? ORDER BY nama_sales ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sales = [];
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
    }

    echo json_encode($sales);
} else {
    echo json_encode([]);
}

$conn->close();
?>
