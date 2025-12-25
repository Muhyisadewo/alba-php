<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['id'])) {
    die("Order tidak ditemukan.");
}

$order_id = $_GET['id'];

header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=daftar_barang_order_" . $order_id . ".xls");

$sql = "SELECT * FROM daftar_barang WHERE order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<table border="1">
<tr>
    <th>Nama Barang</th>
    <th>Harga Ambil</th>
    <th>Qty</th>
    <th>Subtotal</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>
<tr>
    <td><?= $row['nama_barang'] ?></td>
    <td><?= $row['harga_ambil'] ?></td>
    <td><?= $row['qty'] ?></td>
    <td><?= $row['subtotal'] ?></td>
</tr>
<?php } ?>
</table>
