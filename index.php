<?php
session_start();

// Access control - check if user is logged in, except for exempted files
$exempted_files = ['generate_template.php', 'import_barang.php'];
$current_file = basename($_SERVER['PHP_SELF']);

if (!in_array($current_file, $exempted_files)) {
    if (!isset($_SESSION['user_email'])) {
        header('Location: login.php');
        exit;
    }

    // Verify email is still allowed (in case it was removed while session active)
    include 'config.php';
    $stmt = $conn->prepare("SELECT email FROM allowed_emails WHERE email = ?");
    $stmt->execute([$_SESSION['user_email']]);
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $stmt->close();
}

// Routing logic (tetap sama seperti sebelumnya)
if (isset($_GET['path'])) {
    $path = $_GET['path'];

    // Define routes mapping
    $routes = [
        'laporan' => 'inti/laporan/index.php',
        'toggle_listing' => 'inti/order/toggle_listing.php',
        'hapus_barang_pecahon' => 'inti/gudang/hapus_barang_pecahon.php',
        'gudang_pecahon' => 'inti/gudang/gudang_pecahon.php',
        'tambah_barang_sektor' => 'inti/gudang/tambah_barang_sektor.php',
        'tambah_barang_pecahon' => 'inti/gudang/tambah_barang_pecahon.php',
        'ambil_barang' => 'inti/gudang/ambil_barang.php',
        'ambil_barang_pecahon' => 'inti/gudang/ambil_barang_pecahon.php',
        'gudang' => 'inti/gudang/index.php',
        'order' => 'inti/order/index.php',
        'retur' => 'inti/retur/index.php',
        'laporan' => 'inti/laporan/index.php', // Assuming laporan exists
        'daftar_barang' => 'inti/order/daftar_barang.php',
        'riwayat_order' => 'inti/order/riwayat_order.php',
        'monitor_sales' => 'inti/order/monitor_sales.php',
        'supplier_detail' => 'inti/order/supplier_detail.php',
        'sektor_detail' => 'inti/gudang/sektor_detail.php',
        'edit_barang_pecahon' => 'inti/gudang/edit_barang_pecahon.php',
        'edit_barang_gdg' => 'inti/gudang/edit_barang_gdg.php',
        'barang_sektor' => 'inti/gudang/barang_sektor.php',
        // Add more routes as needed for gudang subpages
        'tambah_sektor' => 'inti/gudang/tambah_sektor.php',
        'hapus_sektor' => 'inti/gudang/hapus_sektor.php',
        'hapus_barang' => 'inti/gudang/hapus_barang.php',
        'get_barang' => 'inti/gudang/get_barang.php',
        'tambah_supplier_ajax' => 'inti/gudang/tambah_supplier_ajax.php',
        'tambah_sales_ajax' => 'inti/gudang/tambah_sales_ajax.php',
        'get_sales' => 'inti/gudang/get_sales.php',
        // Order routes
        'edit_order' => 'inti/order/edit_order.php',
        'delete_order' => 'inti/order/delete_order.php',
        'edit_barang' => 'inti/order/edit_barang.php',
        'tambah_barang' => 'inti/order/tambah_barang.php',
        'hapus_order' => 'inti/order/hapus_order.php',
        'hapus_order_barang' => 'inti/order/hapus_order_barang.php',
        'order_now' => 'inti/order/order_now.php',
        'print_order' => 'inti/order/print_order.php',
        'proses_kunjungan' => 'inti/order/proses_kunjungan.php',
        'proses_pembayaran' => 'inti/order/proses_pembayaran.php',
        'proses_retur' => 'inti/order/proses_retur.php',
        'proses_tambah_barang' => 'inti/order/proses_tambah_barang.php',
        'delet_riwayat' => 'inti/order/delet_riwayat.php',
        'tambah_sales' => 'inti/order/tambah_sales.php',
        'edit_sales' => 'inti/order/edit_sales.php',
        'tambah_supplier' => 'inti/order/tambah_supplier.php',
        'export_barang' => 'inti/order/export_barang.php',
        'unduh_excel' => 'inti/order/unduh_excel.php',
        'helpers/image_helper' => 'inti/order/helpers/image_helper.php',
        'index.php/supplier_detail' => 'inti/order/supplier_detail.php',
        'proses_pembayaran_multiple' => 'inti/order/proses_pembayaran_multiple.php',
        'riwayat_pembayaran' => 'inti/order/riwayat_pembayaran.php',
        'generate_excel_simple' => 'inti/order/generate_excel_simple.php',
        // Retur routes
        'retur_add' => 'inti/retur/retur_add.php',
        'retur_delete' => 'inti/retur/retur_delete.php',
        'retur_edit' => 'inti/retur/retur_edit.php',
        'tambah_retur_proses' => 'inti/retur/tambah_retur_proses.php',
        'live_search_barang' => 'inti/retur/live_search_barang.php',
        'retur_search' => 'inti/retur/retur_search.php',
        'pengaturan' => 'inti/pengaturan/index.php',
    ];

    if (isset($routes[$path])) {
        include $routes[$path];
        exit;
    } else {
        http_response_code(404);
        echo "Halaman tidak ditemukan";
        exit;
    }
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
    COALESCE((
        SELECT SUM(ro.total_harga) 
        FROM riwayat_order ro 
        WHERE ro.order_id IN (
            SELECT o.id FROM orders o WHERE o.sales_id = s.id
        )
    ), 0) AS total_subtotal
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
$total_tagihan = 0;
if ($notification_result->num_rows > 0) {
    while ($row = $notification_result->fetch_assoc()) {
        $upcoming_visits[] = $row;
        $total_tagihan += $row['total_subtotal'];
    }
}

// QUERY TAGIHAN YANG BELUM DIBAYAR BERDASARKAN STATUS
// Hanya ambil order dengan status 'belum_dibayar' atau 'pending'
$tagihan_query = "
SELECT
    o.id as order_id,
    CONCAT('ORD-', LPAD(o.id, 5, '0')) as kode_order,
    o.tanggal_order,
    o.total_harga as total_amount,
    o.status,
    s.id as sales_id,
    s.nama_sales,
    s.perusahaan,
    s.kontak,
    CASE 
        WHEN o.status = 'belum_dibayar' THEN 'BELUM LUNAS'
        WHEN o.status = 'pending' THEN 'PENDING'
        ELSE o.status
    END as status_tagihan,
    DATE_ADD(o.tanggal_order, INTERVAL 30 DAY) as tanggal_jatuh_tempo,
    DATEDIFF(DATE_ADD(o.tanggal_order, INTERVAL 30 DAY), CURDATE()) as hari_jatuh_tempo,
    (SELECT COUNT(*) FROM riwayat_order ro WHERE ro.order_id = o.id) as jumlah_riwayat
FROM orders o
JOIN sales s ON o.sales_id = s.id
WHERE o.tanggal_order >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) -- 2 bulan terakhir
    AND o.total_harga > 0
    AND o.status IN ('belum_dibayar', 'pending') -- HANYA YANG BELUM DIBAYAR
ORDER BY 
    CASE 
        WHEN o.status = 'pending' THEN 1
        WHEN o.status = 'belum_dibayar' THEN 2
        ELSE 3
    END,
    o.tanggal_order DESC, 
    o.total_harga DESC
LIMIT 15
";

// Coba query utama
$tagihan_result = $conn->query($tagihan_query);
$tagihan_list = [];
$total_tagihan_pending = 0;

if ($tagihan_result && $tagihan_result->num_rows > 0) {
    while ($row = $tagihan_result->fetch_assoc()) {
        $row['final_amount'] = $row['total_amount'];
        $tagihan_list[] = $row;
        $total_tagihan_pending += $row['final_amount'];
    }
} else {
    // Jika query utama gagal, coba query sederhana
    $simple_query = "
    SELECT 
        o.id as order_id,
        CONCAT('ORD-', LPAD(o.id, 5, '0')) as kode_order,
        o.tanggal_order,
        o.total_harga as total_amount,
        o.status,
        s.id as sales_id,
        s.nama_sales,
        s.perusahaan,
        s.kontak,
        CASE 
            WHEN o.status = 'belum_dibayar' THEN 'BELUM LUNAS'
            WHEN o.status = 'pending' THEN 'PENDING'
            ELSE o.status
        END as status_tagihan,
        DATE_ADD(o.tanggal_order, INTERVAL 30 DAY) as tanggal_jatuh_tempo,
        DATEDIFF(DATE_ADD(o.tanggal_order, INTERVAL 30 DAY), CURDATE()) as hari_jatuh_tempo
    FROM orders o
    JOIN sales s ON o.sales_id = s.id
    WHERE o.tanggal_order >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        AND o.total_harga > 0
        AND o.status IN ('belum_dibayar', 'pending')
    ORDER BY o.tanggal_order DESC
    LIMIT 10
    ";
    
    $simple_result = $conn->query($simple_query);
    if ($simple_result && $simple_result->num_rows > 0) {
        while ($row = $simple_result->fetch_assoc()) {
            $row['final_amount'] = $row['total_amount'];
            $row['jumlah_riwayat'] = 0;
            $tagihan_list[] = $row;
            $total_tagihan_pending += $row['total_amount'];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ALBA - Halaman Utama</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Reset & Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #21633E;
            --primary-light: #437057;
            --secondary: #D4AF37;
            --accent: #8B4513;
            --light: #F5F5F5;
            --dark: #1A2C21;
            --success: #28A745;
            --warning: #FFC107;
            --danger: #DC3545;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --radius: 20px;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #21633eff 0%, #1A2C21 100%);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        /* Top Navigation Bar - Fixed */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 10px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .top-nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .nav-logo-text {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2em;
        }
        
        .quick-nav {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px 0;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        
        .quick-nav::-webkit-scrollbar {
            height: 4px;
        }
        
        .quick-nav::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 2px;
        }
        
        .quick-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            padding: 8px 12px;
            background: rgba(33, 99, 62, 0.1);
            border-radius: 12px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.8em;
            font-weight: 600;
            transition: var(--transition);
            white-space: nowrap;
        }
        
        .quick-nav-item i {
            font-size: 1.2em;
            margin-bottom: 5px;
        }
        
        .quick-nav-item:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Main container dengan padding untuk nav fixed */
        .main-container {
            flex: 1;
            width: 95%;
            max-width: 1200px;
            margin: 90px auto 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .main-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            z-index: -1;
        }
        
        /* Header */
        .header {
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            box-shadow: 0 5px 15px rgba(33, 99, 62, 0.3);
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            background: linear-gradient(to right, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            font-size: 1.2em;
            color: var(--primary-light);
            margin-bottom: 30px;
            font-weight: 500;
        }
        
        /* Notifikasi Kunjungan */
        .notification-container {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 193, 7, 0.1) 100%);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .notification-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--warning), var(--danger));
        }
        
        .notification-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .notification-icon {
            background: var(--warning);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }
        
        .notification-title {
            color: #d35400;
            font-weight: 700;
            font-size: 1.3em;
            margin: 0;
        }
        
        .visit-list {
            list-style: none;
            margin: 15px 0;
        }
        
        .visit-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary);
        }
        
        .visit-item:last-child {
            margin-bottom: 0;
        }
        
        .sales-name {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1em;
        }
        
        .company-name {
            color: var(--primary-light);
            font-size: 0.95em;
        }
        
        .visit-date {
            background: rgba(33, 99, 62, 0.1);
            color: var(--primary);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
            display: inline-block;
            margin: 5px 0;
        }
        
        .visit-amount {
            font-weight: 700;
            color: var(--danger);
            font-size: 1.1em;
        }
        
        .total-amount {
            text-align: right;
            padding-top: 15px;
            border-top: 1px dashed rgba(0, 0, 0, 0.1);
            font-weight: 700;
            font-size: 1.3em;
            color: var(--dark);
        }
        
        /* Tagihan Section */
        .tagihan-container {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 107, 107, 0.1) 100%);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .tagihan-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--danger), #ff6b6b);
        }
        
        .tagihan-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .tagihan-icon {
            background: var(--danger);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }
        
        .tagihan-title {
            color: var(--danger);
            font-weight: 700;
            font-size: 1.3em;
            margin: 0;
        }
        
        .tagihan-list {
            list-style: none;
            margin: 15px 0;
        }
        
        .tagihan-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid var(--danger);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .tagihan-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        
        .invoice-number {
            font-weight: 700;
            color: var(--dark);
            font-size: 1em;
        }
        
        .invoice-due {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .invoice-amount {
            font-weight: 700;
            color: var(--danger);
            font-size: 1.1em;
            margin-top: 5px;
        }
        
        /* Menu Navigasi */
        .nav-section {
            margin-top: 40px;
        }
        
        .nav-title {
            font-size: 1.4em;
            margin-bottom: 25px;
            color: var(--dark);
            position: relative;
            display: inline-block;
        }
        
        .nav-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        .nav-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 20px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(240, 240, 240, 0.9) 100%);
            color: var(--dark);
            text-decoration: none;
            border-radius: 15px;
            font-size: 1.1em;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
            min-height: 150px;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
        }
        
        .nav-link i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: var(--primary);
            transition: var(--transition);
        }
        
        .nav-link span {
            font-size: 1.1em;
        }
        
        .nav-link:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .nav-link:hover i {
            transform: scale(1.2);
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            color: var(--primary-light);
            font-size: 0.9em;
        }
        
        .app-version {
            background: rgba(33, 99, 62, 0.1);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }
        
        /* Responsif untuk HP */
        @media (max-width: 768px) {
            body {
                padding: 0;
                overflow-y: auto;
            }
            
            .top-nav {
                padding: 10px 15px;
            }
            
            .main-container {
                padding: 20px;
                width: 100%;
                margin: 80px auto 20px;
                border-radius: 15px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .subtitle {
                font-size: 1em;
            }
            
            .nav-links {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .nav-link {
                padding: 20px 15px;
                min-height: 120px;
                flex-direction: row;
                justify-content: flex-start;
            }
            
            .nav-link i {
                font-size: 2em;
                margin-bottom: 0;
                margin-right: 20px;
            }
            
            .logo {
                width: 70px;
                height: 70px;
                font-size: 28px;
            }
            
            .notification-container,
            .tagihan-container {
                padding: 15px;
            }
            
            .notification-title,
            .tagihan-title {
                font-size: 1.1em;
            }
            
            .visit-item,
            .tagihan-item {
                padding: 12px;
            }
            
            .quick-nav {
                gap: 10px;
            }
            
            .quick-nav-item {
                min-width: 60px;
                padding: 6px 10px;
                font-size: 0.75em;
            }
            
            .quick-nav-item i {
                font-size: 1em;
            }
        }
        
        @media (max-width: 480px) {
            h1 {
                font-size: 1.8em;
            }
            
            .main-container {
                padding: 15px;
            }
            
            .nav-link {
                padding: 18px 15px;
                font-size: 1em;
            }
            
            .nav-link i {
                font-size: 1.8em;
                margin-right: 15px;
            }
            
            .notification-header,
            .tagihan-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .notification-icon,
            .tagihan-icon {
                margin-bottom: 10px;
            }
        }
        
        /* Animasi */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .main-container {
            animation: fadeIn 0.5s ease-out;
        }
        
        .nav-link {
            animation: fadeIn 0.5s ease-out;
            animation-fill-mode: both;
        }
        
        .nav-link:nth-child(1) { animation-delay: 0.1s; }
        .nav-link:nth-child(2) { animation-delay: 0.2s; }
        .nav-link:nth-child(3) { animation-delay: 0.3s; }
        
        /* Efek tombol aktif */
        .nav-link:active {
            transform: scale(0.98);
            transition: transform 0.1s;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .status-due {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .status-paid {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Tambahan untuk informasi tagihan */
        .tagihan-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        .item-count {
            background: rgba(33, 99, 62, 0.1);
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.85em;
        }
        
        .warning-text {
            color: #e74c3c;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .order-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .status-belum-dibayar {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #ffb74d;
        }
        
        .status-pending {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #64b5f6;
        }
        
        .status-sudah-dibayar {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #81c784;
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-nav">
        <div class="top-nav-container">
            <div class="nav-logo">
                <div class="nav-logo-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="nav-logo-text">ALBA</div>
            </div>
            <div class="quick-nav">
                <a href="?path=gudang" class="quick-nav-item">
                    <i class="fas fa-boxes"></i>
                    <span>Gudang</span>
                </a>
                <a href="?path=order" class="quick-nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Order</span>
                </a>
                <a href="?path=retur" class="quick-nav-item">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Retur</span>
                </a>
                <a href="?path=laporan" class="quick-nav-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Laporan</span>
                </a>
                <a href="?path=daftar_barang" class="quick-nav-item">
                    <i class="fas fa-list"></i>
                    <span>Barang</span>
                </a>
                <a href="?path=riwayat_order" class="quick-nav-item">
                    <i class="fas fa-history"></i>
                    <span>Riwayat</span>
                </a>
                <a href="index.php?path=order" class="quick-nav-item">
                    <i class="fas fa-users"></i>
                    <span>Sales</span>
                </a>
                <a href="?path=pengaturan" class="quick-nav-item">
                    <i class="fas fa-cogs"></i>
                    <span>Pengaturan</span>
                </a>
                <a href="#" class="quick-nav-item" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-warehouse"></i>
            </div>
            <h1>ALBA System Inventory</h1>
            <p class="subtitle">Mau kemana hari ini?</p>
        </div>
        
        <!-- Statistik Cepat -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <div style="background: rgba(33, 99, 62, 0.1); padding: 15px; border-radius: 10px; text-align: center;">
                <div style="font-size: 1.8em; font-weight: bold; color: var(--primary);">
                    <?php echo count($tagihan_list); ?>
                </div>
                <div style="font-size: 0.9em; color: #666;">Tagihan Aktif</div>
            </div>
            <div style="background: rgba(255, 193, 7, 0.1); padding: 15px; border-radius: 10px; text-align: center;">
                <div style="font-size: 1.8em; font-weight: bold; color: #d35400;">
                    <?php echo count($upcoming_visits); ?>
                </div>
                <div style="font-size: 0.9em; color: #666;">Kunjungan</div>
            </div>
            <div style="background: rgba(220, 53, 69, 0.1); padding: 15px; border-radius: 10px; text-align: center;">
                <div style="font-size: 1.8em; font-weight: bold; color: var(--danger);">
                    Rp <?php echo number_format($total_tagihan_pending, 0, ',', '.'); ?>
                </div>
                <div style="font-size: 0.9em; color: #666;">Total Piutang</div>
            </div>
        </div>
        
        <!-- Notifikasi Tagihan -->
        <?php if (!empty($tagihan_list)): ?>
        <div class="tagihan-container">
            <div class="tagihan-header">
                <div class="tagihan-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h3 class="tagihan-title">Tagihan Belum Dibayar</h3>
                    <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                        Order 60 hari terakhir dengan status "belum_dibayar" atau "pending"
                    </p>
                </div>
            </div>
            
            <ul class="tagihan-list">
                <?php 
                $counter = 0;
                foreach ($tagihan_list as $tagihan): 
                    $counter++;
                    $is_overdue = isset($tagihan['hari_jatuh_tempo']) && $tagihan['hari_jatuh_tempo'] < 0;
                    $is_soon = isset($tagihan['hari_jatuh_tempo']) && $tagihan['hari_jatuh_tempo'] <= 7 && $tagihan['hari_jatuh_tempo'] >= 0;
                    $status_class = '';
                    if (isset($tagihan['status'])) {
                        if ($tagihan['status'] === 'belum_dibayar') {
                            $status_class = 'status-belum-dibayar';
                        } elseif ($tagihan['status'] === 'pending') {
                            $status_class = 'status-pending';
                        } elseif ($tagihan['status'] === 'sudah_dibayar') {
                            $status_class = 'status-sudah-dibayar';
                        }
                    }
                ?>
                <li class="tagihan-item" onclick="window.location.href='?path=monitor_sales&sales_id=<?php echo htmlspecialchars($tagihan['sales_id']); ?>'">
                    <div class="invoice-info">
                        <div>
                            <div class="sales-name">
                                <?php echo htmlspecialchars($tagihan['nama_sales']); ?>
                                <?php if(isset($tagihan['status'])): ?>
                                    <span class="order-status <?php echo $status_class; ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $tagihan['status'])); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if(isset($tagihan['kode_order'])): ?>
                                    <small style="color: #666; background: #f0f0f0; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">
                                        <?php echo htmlspecialchars($tagihan['kode_order']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="company-name"><?php echo htmlspecialchars($tagihan['perusahaan']); ?></div>
                            <?php if(isset($tagihan['kontak']) && $tagihan['kontak']): ?>
                                <small style="color: #888;">
                                    <i class="fas fa-phone" style="margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($tagihan['kontak']); ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="invoice-due">
                                <i class="far fa-calendar-alt" style="margin-right: 5px;"></i>
                                <?php echo date('d/m/Y', strtotime($tagihan['tanggal_jatuh_tempo'])); ?>
                                <?php if($is_overdue): ?>
                                    <span class="status-badge status-due">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Terlambat <?php echo abs($tagihan['hari_jatuh_tempo']); ?> hari
                                    </span>
                                <?php elseif($is_soon): ?>
                                    <span class="status-badge status-pending">
                                        <i class="fas fa-clock"></i>
                                        <?php echo $tagihan['hari_jatuh_tempo']; ?> hari lagi
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="invoice-amount">
                        <i class="fas fa-money-bill-wave" style="margin-right: 8px;"></i>
                        Rp <?php echo isset($tagihan['final_amount']) ? number_format($tagihan['final_amount'], 0, ',', '.') : '0'; ?>
                    </div>
                    
                    <div class="tagihan-info">
                        <div>
                            <?php if(isset($tagihan['jumlah_riwayat']) && $tagihan['jumlah_riwayat'] > 0): ?>
                                <span class="item-count">
                                    <i class="fas fa-history" style="margin-right: 3px;"></i>
                                    <?php echo $tagihan['jumlah_riwayat']; ?> riwayat
                                </span>
                            <?php endif; ?>
                            <span style="margin-left: 10px; color: #888;">
                                <i class="far fa-calendar" style="margin-right: 3px;"></i>
                                Order: <?php echo date('d/m/Y', strtotime($tagihan['tanggal_order'])); ?>
                            </span>
                        </div>
                        <div>
                            <?php if($is_overdue): ?>
                                <span class="warning-text">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Segera ditagih!
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="total-amount">
                Total Piutang: 
                <span style="color: var(--danger);">
                    Rp <?php echo number_format($total_tagihan_pending, 0, ',', '.'); ?>
                </span>
                <small style="color: #666; font-size: 0.8em; margin-left: 10px;">
                    (<?php echo count($tagihan_list); ?> order belum lunas)
                </small>
                <div style="font-size: 0.8em; color: #888; margin-top: 5px;">
                    <i class="fas fa-info-circle"></i>
                    Hanya menampilkan order dengan status "belum_dibayar" atau "pending"
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="notification-container" style="background: rgba(46, 204, 113, 0.1); border-color: rgba(46, 204, 113, 0.3);">
            <div class="notification-header">
                <div class="notification-icon" style="background: #2ecc71;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="notification-title" style="color: #27ae60;">Tidak Ada Tagihan Tertunda</h3>
            </div>
            <p>Semua order dalam 60 hari terakhir sudah dilunasi atau tidak ada order dengan status "belum_dibayar"/"pending".</p>
        </div>
        <?php endif; ?>
        
        <!-- Notifikasi Kunjungan -->
        <?php if (!empty($upcoming_visits)): ?>
        <div class="notification-container">
            <div class="notification-header">
                <div class="notification-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <h3 class="notification-title">Pemberitahuan Kunjungan</h3>
            </div>
            <p>Ada kunjungan sales yang akan datang dalam 2 hari ke depan:</p>
            
            <ul class="visit-list">
                <?php foreach ($upcoming_visits as $visit): ?>
                <li class="visit-item">
                    <div class="sales-name">
                        <?php echo htmlspecialchars($visit['nama_sales']); ?>
                        <?php if(isset($visit['total_subtotal']) && $visit['total_subtotal'] > 0): ?>
                            <span style="margin-left: 10px; font-size: 0.8em; color: #666; background: #f0f0f0; padding: 2px 8px; border-radius: 10px;">
                                <i class="fas fa-shopping-cart"></i> Ada order
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="company-name"><?php echo htmlspecialchars($visit['perusahaan']); ?></div>
                    <?php if(isset($visit['next_visit'])): ?>
                        <div class="visit-date">
                            <i class="far fa-calendar-check" style="margin-right: 5px;"></i>
                            Jadwal: <?php echo date('d/m/Y', strtotime($visit['next_visit'])); ?>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($visit['total_subtotal']) && $visit['total_subtotal'] > 0): ?>
                    <div class="visit-amount">
                        <i class="fas fa-chart-line" style="margin-right: 5px;"></i>
                        Total Order: Rp <?php echo number_format($visit['total_subtotal'], 0, ',', '.'); ?>
                    </div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if($total_tagihan > 0): ?>
            <div class="total-amount">
                <i class="fas fa-chart-pie" style="margin-right: 8px;"></i>
                Total Order Sales: 
                <span style="color: var(--primary);">
                    Rp <?php echo number_format($total_tagihan, 0, ',', '.'); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Menu Navigasi -->
        <div class="nav-section">
            <h2 class="nav-title">Menu Utama</h2>
            <div class="nav-links">
                <a href="?path=gudang" class="nav-link">
                    <i class="fas fa-boxes"></i>
                    <span>Gudang & Stok</span>
                </a>
                <a href="?path=order" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Order & Penjualan</span>
                </a>
                <a href="?path=retur" class="nav-link">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Retur Barang</span>
                </a>
                <a href="?path=daftar_barang.php" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Daftar Barang</span>
                </a>
                <a href="?path=riwayat_order.php" class="nav-link">
                    <i class="fas fa-history"></i>
                    <span>Riwayat Order</span>
                </a>
                <a href="?path=monitor_sales.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Monitor Sales</span>
                </a>
                <a href="?path=supplier_detail.php" class="nav-link">
                    <i class="fas fa-truck"></i>
                    <span>Supplier</span>
                </a>
                <a href="?path=sektor_detail.php" class="nav-link">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Sektor</span>
                </a>
            </div>
        </div>
        
        <!-- Info Sistem -->
        <div style="margin-top: 40px; padding: 20px; background: rgba(33, 99, 62, 0.05); border-radius: 15px; text-align: left;">
            <h4 style="color: var(--primary); margin-bottom: 15px;">
                <i class="fas fa-info-circle"></i> Informasi Sistem Tagihan
            </h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div>
                    <h5 style="color: #666; margin-bottom: 5px;">Status Order:</h5>
                    <ul style="font-size: 0.9em; color: #666; padding-left: 20px;">
                        <li><span class="status-belum-dibayar" style="padding: 2px 6px;">BELUM DIBAYAR</span>: Order belum dibayar</li>
                        <li><span class="status-pending" style="padding: 2px 6px;">PENDING</span>: Menunggu konfirmasi</li>
                        <li><span class="status-sudah-dibayar" style="padding: 2px 6px;">SUDAH DIBAYAR</span>: Tidak ditampilkan</li>
                    </ul>
                </div>
                <div>
                    <h5 style="color: #666; margin-bottom: 5px;">Jatuh Tempo:</h5>
                    <ul style="font-size: 0.9em; color: #666; padding-left: 20px;">
                        <li>Jatuh tempo: 30 hari dari tanggal order</li>
                        <li>Data diambil dari 60 hari terakhir</li>
                        <li>Klik item untuk melihat detail sales</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>ALBA Inventory System v2.1</p>
            <div class="app-version">
                <i class="fas fa-mobile-alt"></i> Aplikasi Mobile Optimized
            </div>
            <div style="margin-top: 10px; font-size: 0.8em; color: #999;">
                Data terakhir diperbarui: <?php echo date('d/m/Y H:i:s'); ?>
                <br>
                Status filter: Hanya order "belum_dibayar" dan "pending"
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link, .quick-nav-item');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size/2;
                    const y = e.clientY - rect.top - size/2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(33, 99, 62, 0.2);
                        transform: scale(0);
                        animation: ripple-animation 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        top: ${y}px;
                        left: ${x}px;
                        pointer-events: none;
                        z-index: 1;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
            
            const quickNav = document.querySelector('.quick-nav');
            if (quickNav) {
                quickNav.addEventListener('wheel', function(e) {
                    e.preventDefault();
                    this.scrollLeft += e.deltaY;
                });
                
                // Auto-scroll ke tengah jika ada item aktif
                const currentPath = window.location.search;
                if (currentPath.includes('path=')) {
                    const activeItem = this.querySelector('a[href*="' + currentPath + '"]');
                    if (activeItem) {
                        setTimeout(() => {
                            activeItem.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest',
                                inline: 'center'
                            });
                        }, 500);
                    }
                }
            }
            
            // Auto-refresh setiap 5 menit
            setTimeout(() => {
                window.location.reload();
            }, 300000); // 5 menit
            
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple-animation {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.05); }
                    100% { transform: scale(1); }
                }
                .status-due {
                    animation: pulse 2s infinite;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>