<?php
// Routing system for obfuscated URLs
if (isset($_GET['path'])) {
    $path = $_GET['path'];
    // Remove query string from path if present
    if (strpos($path, '?') !== false) {
        list($path, $query_string) = explode('?', $path, 2);
        parse_str($query_string, $query_params);
        $_GET = array_merge($_GET, $query_params);
    }

    // Define mappings from obfuscated paths to actual files
    $routes = [
        'retur' => 'inti/retur/index.php',
        'retur.php' => 'inti/retur/index.php',
        'gudang' => 'inti/gudang/index.php',
        'gudang.php' => 'inti/gudang/index.php',
        'order' => 'inti/order/index.php',
        'order.php' => 'inti/order/index.php',
        'sektor_detail.php' => 'inti/gudang/sektor_detail.php',
        'supplier_detail.php' => 'inti/order/supplier_detail.php',
        'monitor_sales.php' => 'inti/order/monitor_sales.php',
        'tambah_supplier.php' => 'inti/order/tambah_supplier.php',
        'tambah_sales.php' => 'inti/order/tambah_sales.php',
        'edit_sales.php' => 'inti/order/edit_sales.php',
        'riwayat_order.php' => 'inti/order/riwayat_order.php',
        'daftar_barang.php' => 'inti/order/daftar_barang.php',
        'tambah_barang.php' => 'inti/order/tambah_barang.php',
        'edit_barang.php' => 'inti/order/edit_barang.php',
        'order_now.php' => 'inti/order/order_now.php',
        'print_order.php' => 'inti/order/print_order.php',
        'unduh_excel.php' => 'inti/order/unduh_excel.php',
        'hapus_order.php' => 'inti/order/hapus_order.php',
        'delete_order.php' => 'inti/order/delete_order.php',
        'delet_riwayat.php' => 'inti/order/delet_riwayat.php',
        'export_barang.php' => 'inti/order/export_barang.php',
        'hapus_order_barang.php' => 'inti/order/hapus_order_barang.php',
        'proses_tambah_barang.php' => 'inti/order/proses_tambah_barang.php',
        'proses_kunjungan.php' => 'inti/order/proses_kunjungan.php',
        'proses_retur.php' => 'inti/order/proses_retur.php',
        'edit_order.php' => 'inti/order/edit_order.php',
        // Gudang routes
        'barang_sektor.php' => 'inti/gudang/barang_sektor.php',
        'hapus_barang.php' => 'inti/gudang/hapus_barang.php',
        'index.php' => 'inti/gudang/index.php',
        'gudang_pecahon.php' => 'inti/gudang/gudang_pecahon.php',
        'edit_barang_pecahon.php' => 'inti/gudang/edit_barang_pecahon.php',
        'tambah_barang_pecahon.php' => 'inti/gudang/tambah_barang_pecahon.php',
        'ambil_barang_pecahon.php' => 'inti/gudang/ambil_barang_pecahon.php',
        'hapus_barang_pecahon.php' => 'inti/gudang/hapus_barang_pecahon.php',
        'ambil_barang.php' => 'inti/gudang/ambil_barang.php',
        'edit_barang_gdg.php' => 'inti/gudang/edit_barang_gdg.php',
        'get_barang.php' => 'inti/gudang/get_barang.php',
        'get_barang_pecahon.php' => 'inti/gudang/get_barang_pecahon.php',
        'get_sales.php' => 'inti/gudang/get_sales.php',
        'tambah_sektor.php' => 'inti/gudang/tambah_sektor.php',
        'tambah_barang_sektor.php' => 'inti/gudang/tambah_barang_sektor.php',
        'tambah_sales_ajax.php' => 'inti/gudang/tambah_sales_ajax.php',
        'tambah_supplier_ajax.php' => 'inti/gudang/tambah_supplier_ajax.php',
        // Retur routes
        'retur_add.php' => 'inti/retur/retur_add.php',
        'retur_edit.php' => 'inti/retur/retur_edit.php',
        'retur_delete.php' => 'inti/retur/retur_delete.php',
        'tambah_retur_proses.php' => 'inti/retur/tambah_retur_proses.php',
        'live_search_barang.php' => 'inti/retur/live_search_barang.php',
        'hapus_retur.php' => 'inti/retur/hapus_barang.php',
        // Add more routes as needed
    ];

    if (array_key_exists($path, $routes)) {
        include $routes[$path];
        exit;
    } else {
        // Handle dynamic paths with parameters
        if (strpos($path, 'sektor_detail/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['id'] = $parts[1];
                include 'inti/gudang/sektor_detail.php';
                exit;
            }
        } elseif (strpos($path, 'supplier_detail/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['nama'] = $parts[1];
                include 'inti/order/supplier_detail.php';
                exit;
            }
        } elseif (strpos($path, 'monitor_sales/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['sales_id'] = $parts[1];
                include 'inti/order/monitor_sales.php';
                exit;
            }
        } elseif (strpos($path, 'riwayat_order/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['sales_id'] = $parts[1];
                include 'inti/order/riwayat_order.php';
                exit;
            }
        } elseif (strpos($path, 'daftar_barang/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['sales_id'] = $parts[1];
                include 'inti/order/daftar_barang.php';
                exit;
            }
        } elseif (strpos($path, 'order_now/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['sales_id'] = $parts[1];
                include 'inti/order/order_now.php';
                exit;
            }
        } elseif (strpos($path, 'print_order/') === 0) {
            $parts = explode('/', $path);
            if (count($parts) >= 2) {
                $_GET['order_id'] = $parts[1];
                include 'inti/gudang/index.php';
                exit;
            }
        }
    }

    // If no route matches, show 404 or redirect to home
    header("HTTP/1.0 404 Not Found");
    echo "Halaman tidak ditemukan.";
    exit;
}

include 'config.php';

// Query untuk mendapatkan sales yang kunjungannya kurang dari 2 hari
$notification_query = "
SELECT
    s.nama_sales,
    s.perusahaan,
    CASE
        WHEN jk.nama_jenis LIKE '%Minggu%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan WEEK)
        WHEN jk.nama_jenis LIKE '%Bulan%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan MONTH)
        ELSE DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan DAY)
    END AS next_visit,
    COALESCE((SELECT SUM(ro.total_harga) FROM riwayat_order ro JOIN orders o ON ro.order_id = o.id WHERE o.sales_id = s.id), 0) AS total_subtotal
FROM sales s
LEFT JOIN jenis_kunjungan jk ON s.jenis_kunjungan_id = jk.id
LEFT JOIN (
    SELECT sales_id, MAX(tanggal_kunjungan) AS last_visit
    FROM riwayat_kunjungan
    GROUP BY sales_id
) r ON r.sales_id = s.id
WHERE
    CASE
        WHEN jk.nama_jenis LIKE '%Minggu%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan WEEK)
        WHEN jk.nama_jenis LIKE '%Bulan%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan MONTH)
        ELSE DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan DAY)
    END <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
    AND CASE
        WHEN jk.nama_jenis LIKE '%Minggu%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan WEEK)
        WHEN jk.nama_jenis LIKE '%Bulan%' THEN DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan MONTH)
        ELSE DATE_ADD(r.last_visit, INTERVAL s.interval_kunjungan DAY)
    END >= CURDATE()
";

$notification_result = $conn->query($notification_query);
$upcoming_visits = [];
if ($notification_result->num_rows > 0) {
    while ($row = $notification_result->fetch_assoc()) {
        $upcoming_visits[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Utama</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background:  #21633eff;
            color: #437057;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }
        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(67, 112, 87, 0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(67, 112, 87, 0.2);
        }
        h1 {
            font-size: 3em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(67, 112, 87, 0.5);
            color: #437057;
        }
        .nav-links {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        .nav-link {
            display: inline-block;
            padding: 20px 40px;
            background: #437057;
            color: #fff;
            text-decoration: none;
            border-radius: 15px;
            font-size: 1.2em;
            font-weight: bold;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px rgba(67, 112, 87, 0.2);
        }
        .nav-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(67, 112, 87, 0.3);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            animation: fadeIn 0.3s;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .modal-button {
            background-color: #437057;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .modal-button:hover {
            background-color: #5a8f6b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Selamat Datang di ALBA</h1>
        <p>Mau kemana?</p>

        <?php if (!empty($upcoming_visits)): ?>
        <div style="background: rgba(255, 0, 0, 0.1); border: 1px solid #ff0000; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
            <h3 style="color: #ff0000; margin-top: 0;">Pemberitahuan Kunjungan</h3>
            <p>Ada kunjungan sales yang akan datang dalam 2 hari ke depan:</p>
            <ul style="text-align: left; color: #437057;">
                <?php foreach ($upcoming_visits as $visit): ?>
                <li>
                    <strong><?= htmlspecialchars($visit['nama_sales']) ?></strong> dari <strong><?= htmlspecialchars($visit['perusahaan']) ?></strong><br>
                    Jadwal kunjungan: <?= date('d/m/Y', strtotime($visit['next_visit'])) ?><br>
                    Tagihan: Rp <?= number_format($visit['total_subtotal'], 0, ',', '.') ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Total Tagihan: Rp <?= number_format(array_sum(array_column($upcoming_visits, 'total_subtotal')), 0, ',', '.') ?></strong></p>
        </div>
        <?php endif; ?>

        <div class="nav-links">
            <a href="?path=gudang" class="nav-link gudang-link" id="gudangLink">Gudang</a>
            <a href="?path=order" class="nav-link">Order</a>
            <a href="?path=retur" class="nav-link">Retur</a>
        </div>
    </div>
 
</body>
</html>
