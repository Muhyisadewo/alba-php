<?php
// generate_excel_simple.php
include __DIR__ . '/../../config.php';

if (!isset($_GET['sales_id']) || !is_numeric($_GET['sales_id'])) {
    die("Sales tidak ditemukan.");
}

$sales_id = (int)$_GET['sales_id'];

// Ambil data sales
$stmtSales = $conn->prepare("SELECT nama_sales FROM sales WHERE id = ?");
$stmtSales->bind_param("i", $sales_id);
$stmtSales->execute();
$sales = $stmtSales->get_result()->fetch_assoc();
$stmtSales->close();

// Ambil contoh barang
$sql = "SELECT nama_barang, harga_ambil FROM daftar_barang WHERE sales_id = ? LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $sales_id);
$stmt->execute();
$barang = $stmt->get_result();

// Nama file
$filename = "Template_Barang_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $sales['nama_sales']) . ".csv";

// Output sebagai CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, ['nama_barang', 'harga']);

// Data contoh dari database (jika ada)
while ($row = $barang->fetch_assoc()) {
    fputcsv($output, [
        $row['nama_barang'],
        number_format($row['harga_ambil'], 0, '', '') // format tanpa titik
    ]);
}

// Tambahkan contoh lain
fputcsv($output, ['Contoh Barang 1', '150000']);
fputcsv($output, ['Contoh Barang 2', '75000']);
fputcsv($output, ['Contoh Barang 3', '250000']);

fclose($output);
exit();
?>