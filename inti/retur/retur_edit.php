<?php
include __DIR__ . '/../../config.php';

// Pastikan ID retur ada
if (!isset($_GET['id'])) {
    die("ID retur tidak ditemukan.");
}

$retur_id = intval($_GET['id']);

// Ambil data retur berdasarkan ID
$query = "SELECT * FROM returs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $retur_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Data retur tidak ditemukan.");
}

$data = $result->fetch_assoc();

// Ambil daftar supplier
$supplierQuery = "SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
$supplierResult = $conn->query($supplierQuery);

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = intval($_POST['supplier_id']);
    $qty_retur   = intval($_POST['qty_retur']);
    $alasan      = $_POST['alasan'];

    // Update data
    $updateQuery = "
        UPDATE returs 
        SET supplier_id = ?, qty_retur = ?, alasan = ?
        WHERE id = ?
    ";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("iisi", $supplier_id, $qty_retur, $alasan, $retur_id);

    if ($updateStmt->execute()) {
        echo "<script>alert('Retur berhasil diperbarui!'); window.location='index.php';</script>";
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Retur</title>
<style>
body { font-family: Arial; background: #f5f5f5; padding: 20px; }
.container { max-width: 700px; margin:auto; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.2);}
input, select, textarea { width:100%; padding:10px; margin-bottom:12px; border:1px solid #ddd; border-radius:6px;}
button { background:#0069d9; color:white; padding:10px 15px; border:none; border-radius:6px; cursor:pointer;}
button:hover { background:#0053b3; }
</style>
</head>
<body>

<div class="container">
    <h2>Edit Retur Barang</h2>

    <form method="POST">
        <label>Supplier</label>
        <select name="supplier_id" required>
            <option value="">-- Pilih Supplier --</option>
            <?php while ($sup = $supplierResult->fetch_assoc()) { ?>
                <option value="<?= $sup['id'] ?>" <?= $sup['id'] == $data['supplier_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sup['nama_supplier']) ?>
                </option>
            <?php } ?>
        </select>

        <label>Qty Retur</label>
        <input type="number" name="qty_retur" value="<?= intval($data['qty_retur']) ?>" required>

        <label>Alasan / Keterangan</label>
        <textarea name="alasan" rows="3"><?= htmlspecialchars($data['alasan']) ?></textarea>

        <button type="submit">Update Retur</button>
    </form>
</div>

</body>
</html>
