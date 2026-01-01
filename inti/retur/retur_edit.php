<?php
include __DIR__ . '/../../config.php';

/* ===============================
   1. VALIDASI ID RETUR
================================ */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID retur tidak valid.");
}
$retur_id = (int) $_GET['id'];

/* ===============================
   2. AMBIL DATA RETUR
================================ */
$stmt = $conn->prepare("SELECT * FROM returs WHERE id = ?");
$stmt->bind_param("i", $retur_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Data retur tidak ditemukan.");
}

$data = $result->fetch_assoc();
$stmt->close();

/* ===============================
   3. AMBIL DAFTAR SUPPLIER
================================ */
$supplierResult = $conn->query(
    "SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC"
);

/* ===============================
   4. PROSES UPDATE
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
    $qty         = (int) ($_POST['qty'] ?? 0);
    $alasan      = trim($_POST['alasan'] ?? '');

    if ($supplier_id <= 0 || $qty <= 0) {
        die("Supplier dan Qty wajib diisi dengan benar.");
    }

    $updateStmt = $conn->prepare("
        UPDATE returs 
        SET supplier_id = ?, qty = ?, alasan = ?
        WHERE id = ?
    ");
    $updateStmt->bind_param(
        "iisi",
        $supplier_id,
        $qty,
        $alasan,
        $retur_id
    );

    if ($updateStmt->execute()) {
        echo "<script>
            alert('Retur berhasil diperbarui');
            window.location='?path=retur';
        </script>";
        exit;
    } else {
        die("Gagal update: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Retur</title>
<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.container {
    max-width:700px;
    margin:auto;
    background:white;
    padding:20px;
    border-radius:8px;
    box-shadow:0 2px 5px rgba(0,0,0,.2);
}
input, select, textarea {
    width:100%;
    padding:10px;
    margin-bottom:12px;
    border:1px solid #ddd;
    border-radius:6px;
}
button {
    background:#0069d9;
    color:white;
    padding:10px 15px;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
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
                <option value="<?= $sup['id']; ?>"
                    <?= $sup['id'] == $data['supplier_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($sup['nama_supplier']); ?>
                </option>
            <?php } ?>
        </select>

        <label>Qty Retur</label>
        <input type="number" name="qty" min="1"
               value="<?= (int) $data['qty']; ?>" required>

        <label>Alasan / Keterangan</label>
        <textarea name="alasan" rows="3"><?= htmlspecialchars($data['alasan']); ?></textarea>

        <button type="submit">Update Retur</button>
    </form>
</div>

</body>
</html>
