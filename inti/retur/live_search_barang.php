<?php
include __DIR__ . '/../../config.php';

$search = $_GET['search'] ?? '';

$query = "
    SELECT db.*, o.sales_id, s.nama_sales, s.perusahaan AS nama_supplier
    FROM daftar_barang db
    LEFT JOIN orders o ON db.order_id = o.id
    LEFT JOIN sales s ON o.sales_id = s.id
    WHERE 1
";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (
        db.nama_barang LIKE ? 
        OR s.nama_sales LIKE ? 
        OR s.perusahaan LIKE ?
    )";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
    $types = "sss";
}

$query .= " ORDER BY db.created_at DESC LIMIT 50";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<p style='grid-column:1/-1;text-align:center;'>Data tidak ditemukan</p>";
    exit;
}

while ($row = $result->fetch_assoc()):
?>
<div class="card">
    <img src="../../uploads/barang/<?= htmlspecialchars($row['gambar']) ?>" alt="">
    <h3><?= htmlspecialchars($row['nama_barang']) ?></h3>
    <p>Supplier: <?= htmlspecialchars($row['nama_supplier'] ?? '-') ?></p>
    <p>Sales: <?= htmlspecialchars($row['nama_sales'] ?? '-') ?></p>

    <a class="btn" href="tambah_retur_proses.php?dbid=<?= $row['id'] ?>&order_id=<?= $row['order_id'] ?>&sales_id=<?= $row['sales_id'] ?>">
        Tambahkan ke Retur
    </a>
</div>
<?php endwhile; ?>
