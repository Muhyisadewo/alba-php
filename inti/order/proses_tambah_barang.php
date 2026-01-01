<?php
include __DIR__ . '/../../config.php';
require_once __DIR__ . '/helpers/image_helper.php';

/* ===============================
   VALIDASI REQUEST
================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak valid.");
}

/* ===============================
   INPUT
================================ */
$sales_id    = (int) ($_POST['sales_id'] ?? 0);
$nama_barang = trim($_POST['nama_barang'] ?? '');
$harga_ambil = (int) ($_POST['harga_ambil'] ?? 0);
$qty         = (int) ($_POST['qty'] ?? 0);
$subtotal    = $harga_ambil * $qty;

if ($sales_id <= 0 || $nama_barang === '') {
    die("Data tidak lengkap.");
}

/* ===============================
   SUPPLIER
================================ */
$stmt = $conn->prepare("SELECT supplier_id FROM sales WHERE id=?");
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$sales = $stmt->get_result()->fetch_assoc();
$stmt->close();

$supplier_id = (int) ($sales['supplier_id'] ?? 0);
if ($supplier_id <= 0) {
    die("Supplier tidak valid.");
}

/* ===============================
   ORDER
================================ */
$stmt = $conn->prepare("SELECT id FROM orders WHERE sales_id=? ORDER BY tanggal_order DESC LIMIT 1");
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $stmt = $conn->prepare("INSERT INTO orders (sales_id, tanggal_order, total_harga) VALUES (?, NOW(), 0)");
    $stmt->bind_param("i", $sales_id);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();
} else {
    $order_id = (int) $order['id'];
}

/* ===============================
   GAMBAR (WAJIB LEWAT HELPER)
================================ */
$gambar = '';
if (!empty($_FILES['gambar']['name'])) {
    $gambar = uploadImageWebp(
        $_FILES['gambar'],
        __DIR__ . '/../../uploads/barang'
    );
}

/* ===============================
   INSERT BARANG
================================ */
$stmt = $conn->prepare("
    INSERT INTO daftar_barang
    (order_id, supplier_id, sales_id, nama_barang, harga_ambil, qty, subtotal, gambar)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iiisiiis",
    $order_id,
    $supplier_id,
    $sales_id,
    $nama_barang,
    $harga_ambil,
    $qty,
    $subtotal,
    $gambar
);
$stmt->execute();
$stmt->close();

/* ===============================
   UPDATE TOTAL ORDER
================================ */
$stmt = $conn->prepare("UPDATE orders SET total_harga = total_harga + ? WHERE id=?");
$stmt->bind_param("ii", $subtotal, $order_id);
$stmt->execute();
$stmt->close();

header("Location: /index.php?path=daftar_barang&sales_id=" . $sales_id);
exit;
