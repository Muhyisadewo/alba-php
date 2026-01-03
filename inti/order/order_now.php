<?php
include __DIR__ . '/../../config.php';

/* =========================================
   1. VALIDASI SALES
========================================= */
if (!isset($_GET['sales_id']) || !is_numeric($_GET['sales_id'])) {
    die("Sales ID tidak valid.");
}
$sales_id = (int) $_GET['sales_id'];

$stmtSales = $conn->prepare(
    "SELECT id, nama_sales, perusahaan FROM sales WHERE id = ?"
);
$stmtSales->bind_param("i", $sales_id);
$stmtSales->execute();
$sales = $stmtSales->get_result()->fetch_assoc();
$stmtSales->close();

if (!$sales) {
    die("Data Sales tidak ditemukan.");
}

/* =========================================
   2. AMBIL DAFTAR BARANG
========================================= */
$sqlBarang = "
    SELECT
        db.id, db.nama_barang, db.harga_ambil, db.gambar,
        COALESCE(g.qty, 0) AS stok_qty,
        COALESCE(gp.qty, 0) AS qty_pc,
        COALESCE(SUM(r.qty), 0) AS qty_retur
    FROM daftar_barang db
    LEFT JOIN gudang g 
        ON g.nama_barang COLLATE utf8mb4_unicode_ci = db.nama_barang COLLATE utf8mb4_unicode_ci
    LEFT JOIN gudang_pecahon gp 
        ON gp.nama_barang COLLATE utf8mb4_unicode_ci = db.nama_barang COLLATE utf8mb4_unicode_ci
    LEFT JOIN returs r 
        ON r.daftar_barang_id = db.id
    WHERE db.sales_id = ?
    GROUP BY db.id
    ORDER BY db.nama_barang ASC
";
$stmtBarang = $conn->prepare($sqlBarang);
$stmtBarang->bind_param("i", $sales_id);
$stmtBarang->execute();
$resultBarang = $stmtBarang->get_result();
$stmtBarang->close();

/* =========================================
   3. PROSES SAVE ORDER
========================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['qty'])) {

    $grandTotal = 0;
    $dataOrder  = [];

    foreach ($_POST['qty'] as $barang_id => $qty) {
        $qty    = (int) $qty;
        $satuan = trim($_POST['satuan'][$barang_id] ?? '');

        if ($qty <= 0 || $satuan === '') continue;

        $stmt = $conn->prepare(
            "SELECT nama_barang, harga_ambil, gambar 
             FROM daftar_barang WHERE id = ?"
        );
        $stmt->bind_param("i", $barang_id);
        $stmt->execute();
        $barang = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$barang) continue;

        $subtotal   = $qty * (float) $barang['harga_ambil'];
        $grandTotal += $subtotal;

        $dataOrder[] = [
            'nama_barang' => $barang['nama_barang'],
            'harga'       => (float) $barang['harga_ambil'],
            'qty'         => $qty,
            'subtotal'    => $subtotal,
            'gambar'      => $barang['gambar'],
            'satuan'      => $satuan
        ];
    }

    if (count($dataOrder) === 0) {
        header("Location: ?path=order_now&sales_id=".$sales_id);
        exit;
    }

    /* =========================================
       TRANSAKSI DATABASE
    ========================================= */
    $conn->begin_transaction();

    try {
        // Get payment type
        $payment_type = $_POST['payment_type'] ?? 'cash';
        $status = ($payment_type === 'cash') ? 'sudah_dibayar' : 'belum_dibayar';

        // 1️⃣ BUAT ORDER (HEADER UTAMA)
        $stmtOrder = $conn->prepare(
            "INSERT INTO orders (sales_id, total_harga, status, created_at)
             VALUES (?, ?, ?, NOW())"
        );
        $stmtOrder->bind_param("ids", $sales_id, $grandTotal, $status);
        $stmtOrder->execute();
        $order_id = $conn->insert_id;
        $stmtOrder->close();

        // 2️⃣ BUAT RIWAYAT ORDER
        $stmtRiwayat = $conn->prepare(
            "INSERT INTO riwayat_order (order_id, total_harga, created_at)
             VALUES (?, ?, NOW())"
        );
        $stmtRiwayat->bind_param("id", $order_id, $grandTotal);
        $stmtRiwayat->execute();
        $riwayat_order_id = $conn->insert_id;
        $stmtRiwayat->close();

        // 3️⃣ DETAIL RIWAYAT
        $stmtDetail = $conn->prepare("
            INSERT INTO riwayat_order_detail
            (riwayat_order_id, nama_barang, harga, qty, subtotal, gambar, satuan)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($dataOrder as $item) {
            $stmtDetail->bind_param(
                "isiddss",
                $riwayat_order_id,
                $item['nama_barang'],
                $item['harga'],
                $item['qty'],
                $item['subtotal'],
                $item['gambar'],
                $item['satuan']
            );
            $stmtDetail->execute();
        }
        $stmtDetail->close();

        $conn->commit();

        $_SESSION['show_success_modal'] = true;
        header("Location: /index.php?path=order_now&sales_id=".$sales_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        die("Gagal menyimpan order: " . $e->getMessage());
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Barang - <?= htmlspecialchars($sales['nama_sales']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .alert {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            text-align: center;
        }
        .search-container {
            margin-bottom: 20px;
            text-align: center;
        }
        .search-input {
            padding: 10px;
            width: 100%;
            max-width: 400px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        .list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .list-item {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .list-item img {
            width: 80px;
            height: 80px;
            border-radius: 5px;
            object-fit: cover;
        }
        .item-content {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .left-side {
            flex: 1;
        }
        .right-side {
            text-align: right;
            flex: 1;
        }
        .nama {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .harga-satuan {
            color: #e74c3c;
            font-weight: bold;
        }
        .stok-info {
            font-size: 14px;
            color: #666;
            margin-top: 2px;
        }
        .rowQty {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .satuanInput {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 80px;
        }
        .removeSatuanBtn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 8px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 12px;
        }
        .qtyBtn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }
        .qtyBtn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .qtyInput {
            width: 50px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px;
        }
        .qtyInput:disabled {
            background-color: #f9f9f9;
        }
        #grandTotalBox {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        #saveBtn {
            display: block;
            margin: 0 auto;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        #saveBtn:hover {
            background-color: #218838;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .modal-content h3 {
            margin-top: 0;
            color: #28a745;
        }
        .modal-content p {
            margin: 20px 0;
        }
        .modal-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .modal-btn:hover {
            background-color: #218838;
        }
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            h2 {
                font-size: 18px;
            }
            .search-input {
                font-size: 14px;
                padding: 8px;
            }
            .list-item {
                padding: 10px;
                gap: 10px;
            }
            .list-item img {
                width: 60px;
                height: 60px;
            }
            .nama {
                font-size: 14px;
            }
            .harga-satuan {
                font-size: 14px;
            }
            .stok-info {
                font-size: 12px;
            }
            .rowQty {
                gap: 3px;
            }
            .satuanInput {
                width: 60px;
                padding: 4px;
                font-size: 12px;
            }
            .removeSatuanBtn {
                padding: 4px 6px;
                font-size: 10px;
            }
            .qtyBtn {
                padding: 4px 8px;
                font-size: 12px;
            }
            .qtyInput {
                width: 40px;
                padding: 4px;
                font-size: 12px;
            }
            #grandTotalBox {
                font-size: 16px;
                padding: 8px;
            }
            #saveBtn {
                padding: 8px 16px;
                font-size: 14px;
            }
            .modal-content {
                padding: 15px;
            }
            .modal-btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<h2>Order Barang - <?= htmlspecialchars($sales['nama_sales']) ?></h2>

<div class="search-container">
    <input type="text" id="searchInput" class="search-input" placeholder="Cari barang...">
</div>

<form method="POST">

<ul class="list" id="itemList">
<?php
$resultBarang->data_seek(0); // reset pointer
while($row = $resultBarang->fetch_assoc()) {
?>
    <li class="list-item" data-nama="<?= htmlspecialchars($row['nama_barang']) ?>">
        <img src="<?= $row['gambar'] ? '../../uploads/barang/'.$row['gambar'] : 'https://via.placeholder.com/300/CCCCCC/000000?text=No+Image' ?>" alt="<?= htmlspecialchars($row['nama_barang']) ?>">
        <div class="item-content">
            <div class="left-side">
                <div class="nama"><?= htmlspecialchars(substr($row['nama_barang'], 0, 10) . (strlen($row['nama_barang']) > 10 ? '...' : '')) ?></div>
                <div>Harga: Rp <span class="harga-satuan"><?= number_format($row['harga_ambil'],0,',','.') ?></span></div>
            </div>
            <div class="right-side">
                <div class="stok-info">Qty Pc: <span class="harga-satuan"><?= intval($row['qty_pc'] ?? 0) ?></span></div>
                <div class="stok-info">Qty Ats: <span class="harga-satuan"><?= intval($row['stok_qty'] ?? 0) ?></span></div>
                <div class="stok-info">Retur: <span class="harga-satuan"><?= intval($row['qty_retur'] ?? 0) ?></span></div>
            </div>
        </div>
        <div class="rowQty">
            <input type="text" name="satuan[<?= $row['id'] ?>]" class="satuanInput" placeholder="Satuan" data-harga-ambil="<?= $row['harga_ambil'] ?>">
            <button type="button" class="removeSatuanBtn">Hapus</button>
            <button type="button" class="qtyBtn minus" disabled>−</button>
            <input type="number" name="qty[<?= $row['id'] ?>]" class="qtyInput" value="0" min="0" data-harga="<?= $row['harga_ambil'] ?>" disabled>
            <button type="button" class="qtyBtn plus" disabled>+</button>
        </div>
    </li>
<?php } ?>
</ul>

<div id="grandTotalBox">
    TOTAL PESANAN: Rp <span id="grandTotal">0</span>
</div>

<!-- Payment Type Selection -->
<div style="text-align: center; margin: 20px 0; padding: 15px; background-color: #fff; border: 1px solid #ddd; border-radius: 5px;">
    <h5 style="margin-bottom: 15px; color: #333;">Pilih Tipe Pembayaran</h5>
    <div style="display: flex; justify-content: center; gap: 30px;">
        <label style="display: flex; align-items: center; cursor: pointer;">
            <input type="radio" name="payment_type" value="cash" checked style="margin-right: 8px;">
            <span style="font-weight: bold; color: #28a745;">CASH</span>
        </label>
        <label style="display: flex; align-items: center; cursor: pointer;">
            <input type="radio" name="payment_type" value="tempo" style="margin-right: 8px;">
            <span style="font-weight: bold; color: #007bff;">TEMPO</span>
        </label>
    </div>
</div>

<button id="saveBtn">SAVE ORDER</button>

</form>

<!-- Modal Success -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <h3>Berhasil!</h3>
        <p>Order berhasil disimpan.</p>
        <button class="modal-btn" onclick="redirectToSupplier()">OK</button>
    </div>
</div>

<script>
// --- FUNGSI JAVASCRIPT ---

var supplierNama = '<?= urlencode($sales['perusahaan']) ?>';

function updateTotal() {
    let total = 0;
    document.querySelectorAll(".qtyInput").forEach(el => {
        let qty = parseInt(el.value);
        let harga = parseFloat(el.getAttribute("data-harga"));

        if (qty > 0) {
            total += qty * harga;
        }
    });
    document.getElementById("grandTotal").innerText = total.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

function toggleInputControls(card, enable) {
    const qtyInput = card.querySelector('.qtyInput');
    const minusBtn = card.querySelector('.minus');
    const plusBtn = card.querySelector('.plus');

    qtyInput.disabled = !enable;
    minusBtn.disabled = !enable;
    plusBtn.disabled = !enable;

    if (!enable) {
        qtyInput.value = 0;
    }
    updateTotal();
}

document.querySelectorAll('.list-item').forEach(item => {
    const satuanInput = item.querySelector('.satuanInput');
    const removeSatuanBtn = item.querySelector('.removeSatuanBtn');
    const qtyInput = item.querySelector('.qtyInput');
    const minusBtn = item.querySelector('.minus');
    const plusBtn = item.querySelector('.plus');
    
    satuanInput.addEventListener('input', () => {
        if (satuanInput.value.trim() !== "") {
            toggleInputControls(item, true);
        } else {
            toggleInputControls(item, false);
        }
    });

    removeSatuanBtn.addEventListener('click', () => {
        satuanInput.value = "";
        toggleInputControls(item, false);
    });

    plusBtn.addEventListener('click', () => {
        qtyInput.value = parseInt(qtyInput.value) + 1;
        updateTotal();
    });
    
    minusBtn.addEventListener('click', () => {
        if (parseInt(qtyInput.value) > 0) {
            qtyInput.value = parseInt(qtyInput.value) - 1;
            updateTotal();
        }
    });

    qtyInput.addEventListener('input', updateTotal); 

    toggleInputControls(item, false);
});

// Live search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = document.querySelectorAll('.list-item');
    
    items.forEach(item => {
        const nama = item.getAttribute('data-nama').toLowerCase();
        if (nama.includes(searchTerm)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
});

document.addEventListener('DOMContentLoaded', updateTotal);

// Fungsi redirect ke supplier detail
function redirectToSupplier() {
    window.location.href = '/index.php?path=supplier_detail&nama=' + supplierNama;
}

// Tampilkan modal jika session ada
<?php if (isset($_SESSION['show_success_modal'])): ?>
    document.getElementById('successModal').style.display = 'flex';
    <?php unset($_SESSION['show_success_modal']); ?>
<?php endif; ?>
</script>
</body>
</html>
