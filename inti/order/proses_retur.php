<?php
include __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $daftar_barang_id = intval($_POST['daftar_barang_id']);
    $order_id = intval($_POST['order_id']);
    $supplier_id = intval($_POST['supplier_id']);
    $qty = intval($_POST['qty']);
    $alasan = trim($_POST['alasan']);

    if ($qty <= 0 || empty($alasan)) {
        echo "Data tidak valid.";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO returs (daftar_barang_id, order_id, supplier_id, qty, alasan, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiiis", $daftar_barang_id, $order_id, $supplier_id, $qty, $alasan);

    if ($stmt->execute()) {
        echo "Retur berhasil ditambahkan.";
    } else {
        echo "Gagal menambahkan retur.";
    }

    $stmt->close();
    $conn->close();
}
?>