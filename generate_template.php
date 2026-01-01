<?php
// generate_template.php - Template Excel untuk import barang
// Output sebagai CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="template_barang.csv"');

$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, ['nama_barang', 'harga']);

// Contoh data
fputcsv($output, ['Contoh Barang 1', '150000']);
fputcsv($output, ['Contoh Barang 2', '75000']);
fputcsv($output, ['Contoh Barang 3', '250000']);
fputcsv($output, ['Contoh Barang 4', '100000']);
fputcsv($output, ['Contoh Barang 5', '200000']);

fclose($output);
exit();
?>
