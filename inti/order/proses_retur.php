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

    // Validasi supplier_id ada di database
    if ($supplier_id > 0) {
        $check_supplier = $conn->prepare("SELECT id FROM supplier WHERE id = ?");
        $check_supplier->bind_param("i", $supplier_id);
        $check_supplier->execute();
        $supplier_result = $check_supplier->get_result();
        if ($supplier_result->num_rows == 0) {
            echo "Supplier tidak ditemukan.";
            exit;
        }
        $check_supplier->close();
    } else {
        echo "Supplier harus dipilih.";
        exit;
    }

    // Validasi daftar_barang_id ada di database
    $check_barang = $conn->prepare("SELECT id FROM daftar_barang WHERE id = ?");
    $check_barang->bind_param("i", $daftar_barang_id);
    $check_barang->execute();
    $barang_result = $check_barang->get_result();
    if ($barang_result->num_rows == 0) {
        echo "Barang tidak ditemukan.";
        exit;
    }
    $check_barang->close();

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