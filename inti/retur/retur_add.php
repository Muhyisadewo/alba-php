<?php
// Pastikan file config.php tersedia dan koneksi database dibuat.
include __DIR__ . '/../../config.php';

// Ambil input search
// Menggunakan FILTER_SANITIZE_SPECIAL_CHARS untuk keamanan
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// Query dengan filter search
$query = "
    SELECT 
        db.*, 
        o.sales_id, 
        s.nama_sales, 
        s.perusahaan AS nama_supplier
    FROM 
        daftar_barang db
    LEFT JOIN 
        orders o ON db.order_id = o.id
    LEFT JOIN 
        sales s ON o.sales_id = s.id
    WHERE 
        1
";

$params = [];
$types = '';

if (!empty($search)) {
    // Pastikan kolom yang dicari adalah yang di-JOIN atau di daftar_barang
    $query .= " AND (db.nama_barang LIKE ? OR s.nama_sales LIKE ? OR s.perusahaan LIKE ?)";
    $searchParam = "%$search%";
    // Jika mencari 3 kolom yang berbeda, jenis parameter harus sesuai (s = string)
    $params = [$searchParam, $searchParam, $searchParam];
    $types = "sss";
}

$query .= " ORDER BY db.created_at DESC";

// Menggunakan Prepared Statement untuk keamanan
$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Retur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        /* Gaya Umum dan Reset Ringan */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f9;
            color: #333;
            line-height: 1.6;
        }

        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
        }

        /* Form Pencarian */
        .search-form {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        #searchInput {
            padding: 10px 15px;
            width: 100%;
            max-width: 500px;
            border: 2px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        #searchInput:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
            outline: none;
        }

        /* Tata Letak Kartu (Grid Responsif) */
        .grid {
            display: grid;
            /* Default: Min 150px untuk 2 kolom di layar kecil */
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            padding: 10px;
        }

        /* Media Query untuk Tablet (Layar Sedang) */
        @media (min-width: 600px) {
            .grid {
                /* Tetap 2 kolom, tetapi lebih terstruktur */
                grid-template-columns: repeat(2, 1fr); 
            }
        }

        /* Media Query untuk Desktop */
        @media (min-width: 992px) {
            .grid {
                /* 4 kolom di desktop/layar lebar */
                grid-template-columns: repeat(4, 1fr); 
            }
        }

        /* Gaya Kartu Barang */
        .card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 10px; /* Padding lebih kecil untuk 2 kolom */
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            min-height: 320px; 
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .card img {
            width: 100%;
            height: 120px; /* Tinggi tetap untuk gambar */
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .card h3 {
            font-size: 1.0em;
            margin: 8px 0 4px 0;
            color: #333;
        }

        .card p {
            font-size: 0.85em;
            color: #555;
            margin: 1px 0;
            flex-grow: 1; 
        }

        /* Gaya Tombol */
        .btn {
            display: inline-block;
            padding: 8px 10px;
            margin-top: 10px;
            background-color: #28a745; 
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
            font-weight: bold;
            width: 100%;
            box-sizing: border-box;
            border: none;
            cursor: pointer;
            font-size: 0.9em;
        }

        .btn:hover {
            background-color: #218838;
        }

        /* Tombol Hapus */
        .btn-delete {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #dc3545; 
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            padding: 0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            cursor: pointer;
            z-index: 10;
            opacity: 0.8;
            transition: opacity 0.3s, background-color 0.3s;
        }

        .btn-delete:hover {
            background-color: #c82333;
            opacity: 1;
        }

        /* Pesan Kosong */
        .grid > p {
            grid-column: 1 / -1;
            font-style: italic;
            color: #777;
            margin-top: 30px;
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
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }

        .modal form {
            display: flex;
            flex-direction: column;
        }

        .modal label {
            margin-top: 10px;
            font-weight: bold;
        }

        .modal input,
        .modal textarea {
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }

        .modal textarea {
            resize: vertical;
            min-height: 80px;
        }

        .modal button {
            margin-top: 20px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .modal button:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>
    <h2>Tambah Retur Barang</h2>

    <form class="search-form" onsubmit="event.preventDefault();">
        <input 
            type="text" 
            id="searchInput"
            placeholder="Cari nama barang, supplier, atau sales..."
            autocomplete="off"
            value="<?= htmlspecialchars($search) ?>"
        >
    </form>

    <div class="grid" id="result">
        <?php if ($result->num_rows == 0): ?>
            <p>Tidak ada data barang yang cocok dengan pencarian.</p>
        <?php else: ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <img src="../../uploads/barang/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar <?= htmlspecialchars($row['nama_barang']) ?>">
                    <h3><?= htmlspecialchars($row['nama_barang']) ?></h3>
                    <p>Supplier: <?= htmlspecialchars($row['nama_supplier'] ?? '-') ?></p>
                    <p>Sales: <?= htmlspecialchars($row['nama_sales'] ?? '-') ?></p>

                    <button class="btn" onclick="openModal(<?= $row['id'] ?>, <?= $row['order_id'] ?>, '<?= htmlspecialchars($row['nama_supplier'] ?? '') ?>')">Tambahkan ke Retur</button>

                    <button 
                        class="btn btn-delete"
                        onclick="hapusBarang(<?= $row['id']; ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Modal untuk Retur -->
    <div id="returModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Tambah Retur Barang</h3>
            <form id="returForm" method="POST" action="?path=proses_retur">
                <input type="hidden" id="daftar_barang_id" name="daftar_barang_id">
                <input type="hidden" id="order_id" name="order_id">
                <input type="hidden" id="supplier_id" name="supplier_id">

                <label for="qty">Qty:</label>
                <input type="number" id="qty" name="qty" min="1" required>

                <label for="alasan">Alasan:</label>
                <textarea id="alasan" name="alasan" required></textarea>

                <button type="submit">Simpan Retur</button>
            </form>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const resultBox = document.getElementById('result');

        let delayTimer;

        searchInput.addEventListener('keyup', function () {
            clearTimeout(delayTimer);

            delayTimer = setTimeout(() => {
                const keyword = this.value;

                // Menggunakan AJAX untuk Live Search
                fetch('?path=live_search_barang&search=' + encodeURIComponent(keyword))
                    .then(res => res.text())
                    .then(html => {
                        resultBox.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        resultBox.innerHTML = '<p style="grid-column: 1/-1; color: red;">Gagal memuat data.</p>';
                    });
            }, 300); // delay agar tidak berat (debounce)
        });

        function hapusBarang(id) {
            if (!confirm('Yakin ingin menghapus barang ini? Tindakan ini tidak dapat dibatalkan.')) return;

            fetch('?path=hapus_barang', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            })
            .then(res => res.text())
            .then(res => {
                // Respons yang diharapkan dari hapus_barang.php adalah string 'OK'
                if (res.trim() === 'OK') {
                    alert('Barang berhasil dihapus');
                    // Muat ulang halaman setelah penghapusan berhasil
                    location.reload(); 
                } else {
                    alert('Gagal menghapus barang: ' + res);
                }
            })
            .catch(error => {
                console.error('Error saat menghapus:', error);
                alert('Terjadi kesalahan jaringan saat mencoba menghapus barang.');
            });
        }

        function openModal(dbid, orderId, supplierName) {
            // Cari supplier_id berdasarkan nama_supplier jika perlu, tapi asumsikan supplier_id dari row
            // Untuk sederhana, asumsikan supplier_id dikirim atau cari dari DB
            // Di sini, saya asumsikan supplier_id perlu dicari, tapi untuk demo, gunakan placeholder
            // Anda perlu menyesuaikan untuk mendapatkan supplier_id yang benar
            document.getElementById('daftar_barang_id').value = dbid;
            document.getElementById('order_id').value = orderId;
            // Untuk supplier_id, Anda perlu query lagi atau simpan di data attribute
            // Misalnya, tambahkan data-supplier-id di button
            // Untuk sekarang, placeholder
            document.getElementById('supplier_id').value = 1; // Ganti dengan logic untuk mendapatkan supplier_id

            document.getElementById('returModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('returModal').style.display = 'none';
        }

        // Tutup modal jika klik di luar
        window.onclick = function(event) {
            const modal = document.getElementById('returModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

</body>
</html>