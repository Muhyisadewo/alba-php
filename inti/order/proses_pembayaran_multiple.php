<?php
include __DIR__ . '/../../config.php';

if (!isset($_GET['sales_id'])) {
    die("Sales ID tidak ditemukan.");
}

$sales_id = $_GET['sales_id'];

// Ambil data sales
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_orders'])) {
    $selected_orders = $_POST['selected_orders'];
    $tanggal_pembayaran = $_POST['tanggal_pembayaran'] ?? date('Y-m-d');
    $jumlah_bayar = str_replace(['Rp', '.', ','], '', $_POST['jumlah_bayar']);
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? '';
    $catatan = $_POST['catatan'] ?? '';

    // Start transaction
    $conn->begin_transaction();

    try {
        $success_count = 0;

        foreach ($selected_orders as $order_id) {
            // Insert payment record
            $payment_query = "
                INSERT INTO riwayat_pembayaran (order_id, tanggal_pembayaran, jumlah_pembayaran, metode_pembayaran, catatan, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ";
            $payment_stmt = $conn->prepare($payment_query);
            $payment_stmt->bind_param("isdss", $order_id, $tanggal_pembayaran, $jumlah_bayar, $metode_pembayaran, $catatan);
            $payment_stmt->execute();
            $payment_stmt->close();

            // Update status order menjadi sudah dibayar
            $update_sql = "UPDATE orders SET status = 'sudah_dibayar' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $order_id);
            $update_stmt->execute();
            $update_stmt->close();

            $success_count++;
        }

        $conn->commit();

        // Redirect back with success message
        header("Location: ?path=monitor_sales&sales_id=$sales_id&success=$success_count");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing payments: " . $e->getMessage());
    }
}

// Get selected orders details
$selected_orders = $_POST['selected_orders'] ?? [];
$selected_tagihan = [];

if (!empty($selected_orders)) {
    $placeholders = str_repeat('?,', count($selected_orders) - 1) . '?';
    $tagihan_query = "
        SELECT
            o.id as order_id,
            CONCAT('ORD-', LPAD(o.id, 5, '0')) as kode_order,
            o.tanggal_order,
            o.total_harga as total_amount,
            DATE_ADD(o.tanggal_order, INTERVAL 30 DAY) as tanggal_jatuh_tempo,
            DATEDIFF(DATE_ADD(o.tanggal_order, INTERVAL 30 DAY), CURDATE()) as hari_jatuh_tempo,
            COALESCE(
                (SELECT SUM(rod.subtotal)
                 FROM riwayat_order_detail rod
                 WHERE rod.riwayat_order_id IN (
                    SELECT ro.id FROM riwayat_order ro WHERE ro.order_id = o.id
                 )),
                0
            ) as total_detail
        FROM orders o
        WHERE o.id IN ($placeholders) AND o.sales_id = ?
        ORDER BY o.tanggal_order DESC
    ";

    $stmt = $conn->prepare($tagihan_query);
    $params = array_merge($selected_orders, [$sales_id]);
    $stmt->bind_param(str_repeat('i', count($params)), ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['final_amount'] = ($row['total_detail'] > 0) ? $row['total_detail'] : $row['total_amount'];
        $selected_tagihan[] = $row;
    }
    $stmt->close();
}

$total_selected = array_sum(array_column($selected_tagihan, 'final_amount'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pembayaran Multiple - ALBA</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #437057;
            --primary-dark: #2d4d3b;
            --gold-accent: #c5a059;
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

        .card {
            background: var(--white);
            padding: 40px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--gold-accent));
        }

        .selected-orders {
            margin-bottom: 30px;
        }

        .order-item {
            background: rgba(67, 112, 87, 0.05);
            border: 1px solid rgba(67, 112, 87, 0.1);
            border-left: 4px solid var(--primary-color);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 10px;
        }

        .order-item strong {
            color: var(--primary-color);
        }

        .total-amount {
            text-align: center;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 20px;
            border-radius: 15px;
            font-size: 1.3rem;
            margin-bottom: 30px;
            box-shadow: 0 10px 20px rgba(67, 112, 87, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid rgba(67, 112, 87, 0.1);
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
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
            border: none;
            cursor: pointer;
            margin-right: 15px;
        }

        .btn-submit:hover {
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

        @media (max-width: 600px) {
            body { padding: 10px; }
            .card { padding: 20px 15px; }
            h2 { font-size: 1.4rem; }
            .btn-submit { padding: 14px 25px; font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Proses Pembayaran Multiple</h2>

        <div class="card">
            <div class="selected-orders">
                <h3 style="color: var(--primary-color); font-weight: 700; margin-bottom: 20px;">Tagihan Terpilih</h3>
                <?php foreach ($selected_tagihan as $tagihan): ?>
                    <div class="order-item">
                        <strong><?php echo htmlspecialchars($tagihan['kode_order']); ?></strong>
                        <br>
                        <small style="color: #666;">
                            Order: <?php echo date('d/m/Y', strtotime($tagihan['tanggal_order'])); ?> |
                            Jatuh Tempo: <?php echo date('d/m/Y', strtotime($tagihan['tanggal_jatuh_tempo'])); ?>
                        </small>
                        <div style="text-align: right; font-weight: 700; color: var(--primary-color);">
                            Rp <?php echo number_format($tagihan['final_amount'], 0, ',', '.'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="total-amount">
                <strong>Total Pembayaran: Rp <?php echo number_format($total_selected, 0, ',', '.'); ?></strong>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="tanggal_pembayaran">Tanggal Pembayaran</label>
                    <input type="date" id="tanggal_pembayaran" name="tanggal_pembayaran" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="jumlah_bayar">Jumlah Bayar</label>
                    <input type="text" id="jumlah_bayar" name="jumlah_bayar" placeholder="Rp 0" value="Rp <?php echo number_format($total_selected, 0, ',', '.'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="metode_pembayaran">Metode Pembayaran</label>
                    <select id="metode_pembayaran" name="metode_pembayaran" required>
                        <option value="">Pilih Metode Pembayaran</option>
                        <option value="Transfer Bank">Transfer Bank</option>
                        <option value="Cash">Cash</option>
                        <option value="Cek">Cek</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Credit Card">Credit Card</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="catatan">Catatan (Opsional)</label>
                    <textarea id="catatan" name="catatan" placeholder="Tambahkan catatan pembayaran..."></textarea>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn-submit">Proses Pembayaran</button>
                    <a class="btn-back" href="?path=monitor_sales&sales_id=<?= $sales_id ?>">
                        ← Batal
                    </a>
                </div>

                <?php foreach ($selected_orders as $order_id): ?>
                    <input type="hidden" name="selected_orders[]" value="<?php echo $order_id; ?>">
                <?php endforeach; ?>
            </form>
        </div>
    </div>
</body>
</html>
