<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['sales_id'])) {
    die("Sales ID tidak ditemukan.");
}

$sales_id = $_GET['sales_id'];

// Ambil data sales dengan join ke jenis_kunjungan untuk ambil nama_jenis
$sales_query = "
    SELECT s.*, jk.nama_jenis AS kunjungan 
    FROM sales s 
    LEFT JOIN jenis_kunjungan jk ON s.jenis_kunjungan_id = jk.id 
    WHERE s.id = ?
";
$sales_stmt = $conn->prepare($sales_query);
$sales_stmt->bind_param("i", $sales_id);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();

if ($sales_result->num_rows == 0) {
    die("Sales tidak ditemukan.");
}

$sales = $sales_result->fetch_assoc();
$sales_stmt->close();

// Ambil data monitoring untuk sales ini dengan perhitungan next_visit berdasarkan jenis kunjungan
$monitoring_query = "
SELECT
    r.last_visit,
    s.interval_kunjungan,
    jk.nama_jenis,
    CASE
        WHEN jk.nama_jenis LIKE '%Minggu%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan WEEK)
        WHEN jk.nama_jenis LIKE '%Bulan%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan MONTH)
        ELSE DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan DAY)
    END AS next_visit
FROM sales s
LEFT JOIN jenis_kunjungan jk ON s.jenis_kunjungan_id = jk.id
LEFT JOIN (
    SELECT sales_id, MAX(tanggal_kunjungan) AS last_visit
    FROM riwayat_kunjungan
    GROUP BY sales_id
) r ON r.sales_id = s.id
WHERE s.id = ?
";

$monitoring_stmt = $conn->prepare($monitoring_query);
$monitoring_stmt->bind_param("i", $sales_id);
$monitoring_stmt->execute();
$monitoring_result = $monitoring_stmt->get_result();
$monitoring = $monitoring_result->fetch_assoc();
$monitoring_stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kunjungan Sales - ALBA</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #437057;      /* Hijau Emerald Alba */
            --primary-dark: #2d4d3b;
            --gold-accent: #c5a059;        /* Emas Mewah */
            --text-dark: #2c3e50;
            --bg-light: #f4f7f6;
            --white: #ffffff;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --radius: 20px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-light);
            background-image: radial-gradient(circle at top right, #e8edea, transparent);
            color: var(--text-dark);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* Card Utama */
        .card-monitor {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        /* Dekorasi Garis Emas di Atas */
        .card-monitor::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--gold-accent));
        }

        h3.section-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 20px;
            border-left: 4px solid var(--gold-accent);
            padding-left: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Info Sales Grid (Tetap 2 Kolom) */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .info-item strong {
            color: var(--primary-color);
            font-size: 0.85rem;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }

        .info-item span {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Status Visit Grid (Tetap 3 Kolom) */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 40px;
        }

        .status-box {
            text-align: center;
            padding: 20px 10px;
            background: rgba(67, 112, 87, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(67, 112, 87, 0.1);
            transition: all 0.3s ease;
        }

        .status-box strong {
            display: block;
            font-size: 0.75rem;
            color: var(--primary-color);
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .status-box .date-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.7rem;
            margin-top: 8px;
            text-transform: uppercase;
        }

        .badge.hari-ini { background: #e3f2fd; color: #1976d2; border: 1px solid #1976d2; }
        .badge.telat { background: #ffebee; color: #c62828; border: 1px solid #c62828; animation: pulse 2s infinite; }
        .badge.belum { background: #f5f5f5; color: #616161; border: 1px solid #bdbdbd; }

        /* Buttons Area */
        .action-area {
            text-align: center;
            margin-top: 20px;
        }

        .btn-checkin {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white !important;
            text-decoration: none;
            border-radius: 50px;
            padding: 18px 40px;
            font-weight: 700;
            font-size: 1rem;
            box-shadow: 0 10px 20px rgba(67, 112, 87, 0.2);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-checkin:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 25px rgba(67, 112, 87, 0.3);
        }

        .btn-back {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: var(--gold-accent);
        }

        /* Animations */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* --- HP/MOBILE VIEW (Tetap Menyamping, Elemen Diperkecil) --- */
        @media (max-width: 600px) {
            body { padding: 10px; }
            .card-monitor { padding: 20px 15px; }
            h2 { font-size: 1.4rem; }
            
            .info-item span { font-size: 0.9rem; }
            .info-item strong { font-size: 0.7rem; }
            
            .status-box .date-text { font-size: 0.9rem; }
            .status-box strong { font-size: 0.65rem; }
            .badge { font-size: 0.6rem; padding: 4px 8px; }
            
            .btn-checkin { padding: 14px 25px; font-size: 0.85rem; }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Monitoring Sales</h2>

        <div class="card-monitor">
            <div class="sales-info">
                <h3 class="section-title">Informasi Sales</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Nama Sales</strong>
                        <span><?= htmlspecialchars($sales['nama_sales']) ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Supplier</strong>
                        <span><?= htmlspecialchars($sales['perusahaan']) ?></span>
                    </div>
                    <div class="info-item">
                        <strong>No HP</strong>
                        <span><?= htmlspecialchars($sales['kontak']) ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Kunjungan</strong>
                        <span>
                            <?= $sales['interval_kunjungan'] ?> 
                            <?= htmlspecialchars($sales['kunjungan'] ?? 'Tidak ada') ?>
                        </span>
                    </div>
                </div>
            </div>

            <h3 class="section-title">Status Kunjungan</h3>
            <div class="status-grid">
                <div class="status-box">
                    <strong>Terakhir</strong>
                    <span class="date-text">
                        <?= $monitoring['last_visit'] ? date('d/m/y', strtotime($monitoring['last_visit'])) : '-' ?>
                    </span>
                </div>

                <div class="status-box">
                    <strong>Jadwal</strong>
                    <span class="date-text">
                        <?= $monitoring['next_visit'] ? date('d/m/y', strtotime($monitoring['next_visit'])) : '-' ?>
                    </span>
                </div>

                <div class="status-box">
                    <strong>Status</strong>
                    <?php
                    $tanggalHariIni = date("Y-m-d");
                    $nextVisit = $monitoring['next_visit'];

                    if ($nextVisit == $tanggalHariIni) {
                        echo "<span class='badge hari-ini'>Harus Datang</span>";
                    } elseif ($nextVisit < $tanggalHariIni && $nextVisit != null) {
                        echo "<span class='badge telat'>Telat</span>";
                    } else {
                        echo "<span class='badge belum'>Belum Jadwal</span>";
                    }
                    ?>
                </div>
            </div>

            <div class="action-area">
                <a class="btn-checkin" href="?path=proses_kunjungan.php?sales_id=<?= $sales_id ?>">
                    Checkin Kunjungan
                </a>
                <br>
                <a class="btn-back" href="../../index.php?path=supplier_detail/<?= urlencode($sales['perusahaan']) ?>">
                    ← Kembali ke Detail Supplier
                </a>
            </div>
        </div>
    </div>

</body>
</html>