<?php
include __DIR__ . '/../../config.php';

// Ambil input filter/search
$search = $_GET['search'] ?? '';
$filterSupplier = intval($_GET['supplier_id'] ?? 0);
$filterSales = intval($_GET['sales_id'] ?? 0);

// Ambil data supplier dan sales untuk filter
$supplierResult = $conn->query("SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC");
$salesResult = $conn->query("SELECT DISTINCT db.sales_id, s.nama_sales 
                             FROM daftar_barang db 
                             LEFT JOIN sales s ON db.sales_id = s.id
                             ORDER BY s.nama_sales ASC");

// Build query
$query = "
    SELECT 
        r.id AS retur_id,
        r.daftar_barang_id,
        r.created_at,
        db.nama_barang,
        db.qty,
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
$types = '';

if (!empty($search)) {
    $query .= " AND db.nama_barang LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($filterSupplier > 0) {
    $query .= " AND r.supplier_id = ?";
    $params[] = $filterSupplier;
    $types .= 'i';
}
if ($filterSales > 0) {
    $query .= " AND db.sales_id = ?";
    $params[] = $filterSales;
    $types .= 'i';
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Retur Barang</title>
<style>
/* 1. Atur Ulang Dasar (Box Model) */
* {
    box-sizing: border-box; /* Memastikan padding dan border disertakan dalam lebar/tinggi elemen */
}

/* 2. Gaya Dasar */
body {
    font-family: 'Arial', sans-serif;
    background: #f5f5f5;
    padding: 1rem; /* Gunakan satuan rem untuk padding */
    margin: 0;
}

.container {
    max-width: 1200px;
    margin: auto;
    padding: 0 1rem; /* Tambahkan padding horizontal agar konten tidak menempel di tepi layar kecil */
}

h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #333;
    font-size: 1.8rem;
}

/* 3. Gaya Tombol Tambah */
.add-btn {
    display: inline-block;
    padding: 0.6rem 1rem;
    background: #0d7a3c;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    margin-bottom: 1rem;
    transition: background 0.3s;
}
.add-btn:hover {
    background: #09592a;
}

/* 4. Form Filter Responsif */
.form-filter {
    display: flex;
    flex-wrap: wrap; /* Penting: Mengizinkan elemen melompat ke baris baru */
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.form-filter input[type="text"], 
.form-filter select {
    flex-grow: 1; /* Mengizinkan input tumbuh di layar lebar */
    min-width: 150px; /* Lebar minimum untuk setiap input/select */
    padding: 0.6rem;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 1rem;
}

.form-filter button {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 6px;
    background: #00796b;
    color: white;
    cursor: pointer;
    transition: background 0.3s;
}
.form-filter button:hover {
    background: #005f56;
}

/* 5. Grid/Card Tata Letak */
.grid {
    display: grid;
    /* GRID UTAMA: Tentukan jumlah kolom dan lebar minimum */
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
    gap: 1.5rem;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 1.2rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1); /* Bayangan yang lebih lembut */
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-3px); /* Efek hover */
}

.card img {
    width: 120px; /* Ukuran gambar sedikit lebih besar */
    height: 120px;
    object-fit: cover;
    border-radius: 50%; /* Ubah menjadi lingkaran */
    margin-bottom: 1rem;
    border: 3px solid #eee;
}

.card h3 {
    margin: 0.3rem 0;
    font-size: 1.2rem;
    color: #2c3e50;
}

.card p {
    margin: 0.2rem 0;
    font-size: 0.95rem;
    color: #7f8c8d;
}

.card p strong {
    color: #34495e;
}

.actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
}

.actions a {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    color: white;
    transition: opacity 0.3s;
}
.actions a:hover {
    opacity: 0.8;
}

.btn-edit { background: #007bff; } /* Warna biru baru */
.btn-delete { background: #dc3545; } /* Warna merah baru */


/* 6. Media Query untuk Layar Sangat Kecil (Ponsel) */
@media (max-width: 600px) {
    .container {
        padding: 0 0.5rem;
    }
    
    h2 {
        font-size: 1.5rem;
    }

    /* Stack Filter Vertikal di ponsel */
    .form-filter {
        flex-direction: column;
        gap: 10px;
    }

    .form-filter input[type="text"], 
    .form-filter select,
    .form-filter button {
        width: 100%; /* Mengambil lebar penuh */
        min-width: initial;
    }

    /* Grid akan otomatis menyesuaikan karena minmax(280px, 1fr) 
       dan akan menampilkan satu kolom jika lebar layar < 280px, 
       tapi kita bisa paksa 1 kolom jika perlu:
    .grid {
        grid-template-columns: 1fr;
    } */
}

/* 7. Gaya Jika Tidak Ada Data */
p {
    text-align: center;
    margin-top: 20px;
    color: #7f8c8d;
}
</style>
</head>
<body>
<div class="container">
    <h2>Daftar Retur Barang</h2>
    <a href="?path=retur_add" class="add-btn">+ Tambah Retur Baru</a>

    <!-- Form Filter -->
    <form method="GET" class="form-filter">
        <input type="text" name="search" placeholder="Cari nama barang..." value="<?= htmlspecialchars($search) ?>">
        <select name="supplier_id">
            <option value="0">Semua Supplier</option>
            <?php while ($sup = $supplierResult->fetch_assoc()): ?>
                <option value="<?= $sup['id'] ?>" <?= $filterSupplier==$sup['id']?'selected':'' ?>>
                    <?= htmlspecialchars($sup['nama_supplier']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <select name="sales_id">
            <option value="0">Semua Sales</option>
            <?php while ($sa = $salesResult->fetch_assoc()): ?>
                <option value="<?= $sa['sales_id'] ?>" <?= $filterSales==$sa['sales_id']?'selected':'' ?>>
                    <?= htmlspecialchars($sa['nama_sales']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Filter</button>
    </form>

    <div class="grid">
        <?php if($result->num_rows == 0): ?>
            <p>Tidak ada data retur.</p>
        <?php else: ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <img src="<?= !empty($row['gambar']) ? '../../uploads/barang/'.htmlspecialchars($row['gambar']) : 'https://via.placeholder.com/100' ?>" alt="gambar barang">
                    <h3><?= htmlspecialchars($row['nama_barang'] ?? '-') ?></h3>
                    <p><strong>Supplier:</strong> <?= htmlspecialchars($row['nama_supplier'] ?? '-') ?></p>
                    <p><strong>Sales:</strong> <?= htmlspecialchars($row['nama_sales'] ?? '-') ?></p>
                    <p><strong>Qty:</strong> <?= intval($row['qty'] ?? 0) ?></p>
                    <div class="actions">
                        <a href="?path=retur_edit&id=<?= $row['retur_id'] ?>" class="btn-edit">Edit</a>
                        <a href="?path=retur_delete&id=<?= $row['retur_id'] ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus retur ini?')">Hapus</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
