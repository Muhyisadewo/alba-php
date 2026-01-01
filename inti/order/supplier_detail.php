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
    <style>
        /* Tambahkan style untuk WhatsApp icon */
        .wa-icon {
            display: inline-block;
            margin-left: 8px;
            color: #25D366;
            text-decoration: none;
            vertical-align: middle;
            transition: transform 0.2s;
        }
        .wa-icon:hover {
            transform: scale(1.2);
        }
        .wa-icon svg {
            width: 20px;
            height: 20px;
            fill: #25D366;
        }
        .card-value {
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
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
            <a class="btn btn-excel" href="?path=generate_excel_simple&sales_id=<?= $sales_id ?>" target="_blank">
                📥 Excel
            </a>
            <a class="btn btn-add" href="?path=tambah_sales&supplier=<?= urlencode($nama_supplier) ?>">
                <span>+</span> Tambah Sales
            </a>
        </div>

        <div class="grid-container">
            <?php
            if ($result->num_rows > 0) {
                $no = 1;
                while ($row = $result->fetch_assoc()) { 
                    // Format nomor WhatsApp
                    $whatsapp_number = '';
                    if (!empty($row['kontak'])) {
                        // Hapus semua karakter non-digit
                        $digits = preg_replace('/\D/', '', $row['kontak']);
                        // Jika digit pertama adalah 0, ganti dengan 62 (kode Indonesia)
                        if (substr($digits, 0, 1) == '0') {
                            $whatsapp_number = '62' . substr($digits, 1);
                        } else {
                            $whatsapp_number = $digits;
                        }
                    }
            ?>
                    <div class="card">
                        <a class="edit-icon" href="?path=edit_sales&id=<?= $row['id'] ?>" title="Edit">✏️</a>

                        <div class="card-header">
                            <h3 class="card-title">Sales #<?= $no++ ?></h3>
                            <div class="card-subtitle"><?= htmlspecialchars($row['nama_sales']); ?></div>
                        </div>

                        <div class="card-content">
                            <div class="card-item">
                                <span class="card-label">No HP</span>
                                <span class="card-value">
                                    <?= htmlspecialchars($row['kontak']); ?>
                                    <?php if (!empty($whatsapp_number)): ?>
                                        <a class="wa-icon" href="https://wa.me/<?= $whatsapp_number ?>" target="_blank" title="Chat WhatsApp">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                                <path fill="#25D366" d="M19.05 4.91A9.816 9.816 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01zm-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.264 8.264 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.82 2.42a8.183 8.183 0 0 1 2.41 5.83c.02 4.54-3.68 8.23-8.22 8.23zm4.52-6.16c-.25-.12-1.47-.74-1.69-.82-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43s.17-.25.22-.41c.05-.16.02-.31-.03-.46s-.56-1.35-.76-1.85c-.2-.5-.41-.43-.56-.43h-.48c-.17 0-.43.06-.66.31-.22.25-.86.85-.86 2.07 0 1.22.89 2.39 1.01 2.56.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.46-.6 1.67-1.18.21-.58.21-1.07.14-1.18s-.24-.16-.5-.28z"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </span>
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