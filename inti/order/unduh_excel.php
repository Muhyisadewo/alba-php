<?php
include __DIR__ . '/../../config.php';

$id = $_GET['id'];

// Query data order dari riwayat_order_detail
$sql = "
SELECT nama_barang, qty, harga, subtotal
FROM riwayat_order_detail
WHERE riwayat_order_id = '$id'
";
$result = $conn->query($sql);

// Header file excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=order_$id.xls");

echo "<table border='1'>
<tr>
    <th>Nama Barang</th>
    <th>Qty</th>
    <th>Harga</th>
    <th>Subtotal</th>
</tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>".$row['nama_barang']."</td>
        <td>".$row['qty']."</td>
        <td>Rp ".number_format($row['harga'], 0, ',', '.')."</td>
        <td>Rp ".number_format($row['subtotal'], 0, ',', '.')."</td>
    </tr>";
}

echo "</table>";
