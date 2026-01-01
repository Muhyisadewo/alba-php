<?php
include __DIR__ . '/../../config.php';

$search        = $_GET['search'] ?? '';
$supplier_id  = (int)($_GET['supplier_id'] ?? 0);
$sales_id     = (int)($_GET['sales_id'] ?? 0);

$sql = "
    SELECT
        r.id AS retur_id,
        r.qty AS qty_retur,
        db.nama_barang,
        db.gambar,
        s.nama_supplier,
        sa.nama_sales
    FROM returs r
    LEFT JOIN daftar_barang db ON r.daftar_barang_id = db.id
    LEFT JOIN supplier s ON r.supplier_id = s.id
    LEFT JOIN sales sa ON db.sales_id = sa.id
    WHERE 1
";

$params = [];
$types  = '';

if ($search !== '') {
    $sql .= " AND db.nama_barang LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($supplier_id > 0) {
    $sql .= " AND r.supplier_id = ?";
    $params[] = $supplier_id;
    $types .= 'i';
}
if ($sales_id > 0) {
    $sql .= " AND db.sales_id = ?";
    $params[] = $sales_id;
    $types .= 'i';
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<p class="empty">Tidak ada data retur.</p>';
    exit;
}

while ($row = $result->fetch_assoc()):
?>
<div class="card">
    <img src="<?= !empty($row['gambar'])
        ? '../../uploads/barang/' . htmlspecialchars($row['gambar'])
        : 'https://via.placeholder.com/120' ?>">

    <h3><?= htmlspecialchars($row['nama_barang']) ?></h3>
    <p><strong>Supplier:</strong> <?= htmlspecialchars($row['nama_supplier']) ?></p>
    <p><strong>Sales:</strong> <?= htmlspecialchars($row['nama_sales']) ?></p>
    <p><strong>Qty Retur:</strong> <?= (int)$row['qty_retur'] ?></p>

    <div class="actions">
        <a href="?path=retur_edit&id=<?= $row['retur_id'] ?>" class="btn-edit">Edit</a>
        <a href="?path=retur_delete&id=<?= $row['retur_id'] ?>"
           class="btn-delete"
           onclick="return confirm('Yakin ingin menghapus retur ini?')">
           Hapus
        </a>
    </div>
</div>
<?php endwhile; ?>
