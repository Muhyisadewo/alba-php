<?php
include __DIR__ . '/../../config.php';

/* =========================
   1. CEK KONEKSI
========================= */
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

/* =========================
   2. VALIDASI PARAMETER
========================= */
if (!isset($_GET['nama']) || trim($_GET['nama']) === '') {
    die("Supplier tidak ditemukan.");
}

/* =========================
   3. NORMALISASI INPUT
========================= */
$nama_supplier = trim($_GET['nama']);

/* =========================
   4. QUERY DATA SALES
========================= */
$sql = "
    SELECT
        s.id,
        s.nama_sales,
        s.kontak,
        s.interval_kunjungan,
        jk.nama_jenis AS kunjungan
    FROM sales s
    LEFT JOIN jenis_kunjungan jk
        ON s.jenis_kunjungan_id = jk.id
    WHERE LOWER(TRIM(s.perusahaan)) = LOWER(TRIM(?))
    ORDER BY s.nama_sales ASC
";

/* =========================
   5. PREPARE STATEMENT
========================= */
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare gagal: " . $conn->error);
}

/* =========================
   6. EKSEKUSI QUERY
========================= */
$stmt->bind_param("s", $nama_supplier);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Supplier</title>
    <link rel="stylesheet" href="inti/order/supplier_detail.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="supplier-name">Supplier: <?= htmlspecialchars($nama_supplier) ?></h1>
        </div>

        <div class="btn-container">
            <a class="btn btn-back" href="?path=order">
                <span>&larr;</span> Kembali
            </a>
            <a class="btn btn-add" href="?path=tambah_sales&supplier=<?= urlencode($nama_supplier) ?>">
                <span>+</span> Tambah Sales
            </a>
        </div>

        <div class="grid-container">
            <?php
            if ($result->num_rows > 0) {
                $no = 1;
                while ($row = $result->fetch_assoc()) { ?>
                    <div class="card">
                        <a class="edit-icon" href="?path=edit_sales&id=<?= $row['id'] ?>" title="Edit">✏️</a>

                        <div class="card-header">
                            <h3 class="card-title">Sales #<?= $no++ ?></h3>
                            <div class="card-subtitle"><?= htmlspecialchars($row['nama_sales']); ?></div>
                        </div>

                        <div class="card-content">
                            <div class="card-item">
                                <span class="card-label">No HP</span>
                                <span class="card-value"><?= htmlspecialchars($row['kontak']); ?></span>
                            </div>

                            <div class="card-item">
                                <span class="card-label">Kunjungan</span>
                                <span class="card-value">
                                    <?= !empty($row['interval_kunjungan']) ? " {$row['interval_kunjungan']}" : "" ?>
                                    <?= htmlspecialchars($row['kunjungan']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a class="action-btn" href="?path=riwayat_order&sales_id=<?= $row['id'] ?>">Riwayat</a>
                            <a class="action-btn" href="?path=daftar_barang&sales_id=<?= $row['id'] ?>">Barang</a>
                            <a class="action-btn" href="?path=monitor_sales&sales_id=<?= $row['id'] ?>">Monitor</a>
                            <a class="action-btn btn-order-now" href="?path=order_now&sales_id=<?= $row['id'] ?>">Order</a>
                        </div>
                    </div>
                <?php }
            } else {
                echo "<div class='no-data'>Belum ada sales untuk supplier ini.</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>