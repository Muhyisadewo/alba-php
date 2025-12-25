<?php
include __DIR__ . '/../../config.php';

$message = '';

if (!isset($_GET['id'])) {
    die("ID sales tidak ditemukan.");
}

$id = intval($_GET['id']);

// Ambil data sales
$sql = "SELECT * FROM sales WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Sales tidak ditemukan.");
}

$row = $result->fetch_assoc();
$stmt->close();

// Ambil jenis kunjungan
$jenisKunjungan = [];
$res = $conn->query("SELECT id, nama_jenis FROM jenis_kunjungan ORDER BY nama_jenis ASC");
while ($r = $res->fetch_assoc()) {
    $jenisKunjungan[] = $r;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_sales = trim($_POST['nama_sales']);
    $nama_supplier = $row['perusahaan']; // Keep supplier as is, since it's not editable
    $no_sales = trim($_POST['kontak']);
    $jenis_id = (int)$_POST['jenis_kunjungan'];
    $interval_kunjungan = (int)$_POST['interval_kunjungan'];

    // Ambil nama_jenis untuk kolom kunjungan
    $stmt_jenis = $conn->prepare("SELECT nama_jenis FROM jenis_kunjungan WHERE id = ?");
    $stmt_jenis->bind_param("i", $jenis_id);
    $stmt_jenis->execute();
    $result_jenis = $stmt_jenis->get_result();
    $jenis_data = $result_jenis->fetch_assoc();
    $nama_jenis = $jenis_data['nama_jenis'] ?? '';
    $stmt_jenis->close();

    $sql = "UPDATE sales SET nama_sales=?, perusahaan=?, kontak=?, jenis_kunjungan_id=?, kunjungan=?, interval_kunjungan=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisisi", $nama_sales, $nama_supplier, $no_sales, $jenis_id, $nama_jenis, $interval_kunjungan, $id);

    if ($stmt->execute()) {
        $message = "Sales berhasil diperbarui!";

        // Refresh data setelah update
        $sql = "SELECT * FROM sales WHERE id = ?";
        $stmtRefresh = $conn->prepare($sql);
        $stmtRefresh->bind_param("i", $id);
        $stmtRefresh->execute();
        $resultRefresh = $stmtRefresh->get_result();
        $row = $resultRefresh->fetch_assoc();
        $stmtRefresh->close();
    } else {
        $message = "Error update sales: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sales</title>
    <style>
        body {
            background-color: #437057;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: Arial, sans-serif;
        }
        .card {
            background-color: white;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            padding: 1.5rem;
            width: 100%;
            max-width: 28rem;
        }
        @media (min-width: 768px) {
            .card {
                max-width: 32rem;
            }
        }
        @media (min-width: 1024px) {
            .card {
                max-width: 36rem;
            }
        }
        /* Responsif untuk layar kecil: penuhi semua layar */
        @media (max-width: 767px) {
            body {
                padding: 0;
            }
            .card {
                width: 100%;
                max-width: none;
                padding: 1rem;
                border-radius: 0;
                box-shadow: none;
            }
        }
        h2 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #437057;
            margin-bottom: 1rem;
            text-align: center;
        }
        @media (min-width: 768px) {
            h2 {
                font-size: 1.875rem;
            }
        }
        p {
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 1rem;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        input, select {
            width: 98%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus, select:focus {
            border-color: #437057;
            box-shadow: 0 0 0 3px rgba(67, 112, 87, 0.1);
        }
        .jenis-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .jenis-container select {
            flex: 1;
        }
        .btn-add {
            background-color: #437057;
            color: white;
            padding: 0.5rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
            font-size: 1.25rem;
            line-height: 1;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-add:hover {
            background-color: #365a46;
        }
        .btn-add:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        button[type="submit"] {
            width: 100%;
            background-color: #437057;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #365a46;
        }
        button[type="submit"]:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        a {
            display: block;
            text-align: center;
            color: #437057;
            font-weight: bold;
            text-decoration: none;
            margin-top: 1.5rem;
            padding: 0.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }
        a:hover {
            background-color: rgba(67, 112, 87, 0.1);
        }
        a:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-box {
            background-color: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .modal-box h3 {
            margin-top: 0;
            color: #437057;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        .modal-box input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .modal-box button {
            width: 100%;
            background-color: #437057;
            color: white;
            padding: 0.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .modal-box button:hover {
            background-color: #365a46;
        }
        .modal-box button:focus {
            outline: 2px solid #437057;
            outline-offset: 2px;
        }
        .modal-box button:last-child {
            background-color: #aaa;
            margin-top: 0.5rem;
        }
        .modal-box button:last-child:hover {
            background-color: #888;
        }
    </style>
</head>

<body>

<div class="card">
<h2>Edit Sales</h2>
<p>Supplier: <b><?= htmlspecialchars($row['perusahaan']) ?></b></p>

<form method="POST">
<input type="hidden" name="edit_sales" value="1">

<label>Nama Sales</label>
<input type="text" name="nama_sales" value="<?= htmlspecialchars($row['nama_sales']) ?>" required>

<label>No HP</label>
<input type="text" name="kontak" value="<?= htmlspecialchars($row['kontak']) ?>" required>

<label>Jenis Kunjungan</label>
<div class="jenis-container">
<select name="jenis_kunjungan" id="jenisSelect" required onchange="aktifkanInterval()">
<option value="">-- Pilih Jenis --</option>
<?php foreach ($jenisKunjungan as $j): ?>
<option value="<?= $j['id'] ?>" <?= $row['jenis_kunjungan_id'] == $j['id'] ? 'selected' : '' ?>><?= htmlspecialchars($j['nama_jenis']) ?></option>
<?php endforeach ?>
</select>

<button type="button" class="btn-add" onclick="openModal()" title="Tambah Jenis Kunjungan">+</button>
</div>

<label id="intervalLabel">Interval Kunjungan</label>
<input type="number" name="interval_kunjungan" id="intervalInput" min="1" value="<?= htmlspecialchars($row['interval_kunjungan']) ?>" required>

<button type="submit">UPDATE SALES</button>
</form>

<a href="?path=supplier_detail.php?nama=<?= urlencode($row['perusahaan']) ?>">Kembali</a>
</div>
<div class="modal" id="modalJenis">
<div class="modal-box">
<h3>Tambah Jenis Kunjungan</h3>

<input type="text" id="namaJenis" placeholder="Contoh: Mingguan">

<button onclick="simpanJenis()">Simpan</button>
<button onclick="closeModal()" style="background:#aaa;margin-top:6px">Batal</button>
</div>
</div>

<script>
function openModal(){
    document.getElementById('modalJenis').style.display='flex';
    document.getElementById('namaJenis').focus();
    // Add keyboard event listener for Escape key
    document.addEventListener('keydown', handleModalKeydown);
}

function closeModal(){
    document.getElementById('modalJenis').style.display='none';
    // Remove keyboard event listener
    document.removeEventListener('keydown', handleModalKeydown);
}

function handleModalKeydown(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
}

function aktifkanInterval(){
    document.getElementById('intervalInput').disabled = false;
    let select = document.getElementById('jenisSelect');
    let selectedOption = select.options[select.selectedIndex];
    let jenis = selectedOption.text;
    if (jenis) {
        document.getElementById('intervalLabel').textContent = 'Berapa ' + jenis + '?';
    } else {
        document.getElementById('intervalLabel').textContent = 'Interval Kunjungan';
    }
}

function simpanJenis(){
    let nama = document.getElementById('namaJenis').value.trim();
    if(!nama){ alert('Nama wajib diisi'); return; }

    // Disable button to prevent multiple submissions
    let btn = document.querySelector('button[onclick="simpanJenis()"]');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    fetch('inti/order/ajax_tambah_jenis.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'nama_jenis='+encodeURIComponent(nama)
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        return res.json();
    })
    .then(data => {
        if(data.success){
            let select = document.getElementById('jenisSelect');
            let opt = document.createElement('option');
            opt.value = data.id;
            opt.text = data.nama;
            opt.selected = true;
            select.appendChild(opt);
            aktifkanInterval();
            closeModal();
            document.getElementById('namaJenis').value = '';
            alert(data.message || 'Jenis kunjungan berhasil ditambahkan');
        } else {
            alert(data.message || 'Terjadi kesalahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan. Silakan coba lagi.');
    })
    .finally(() => {
        // Re-enable button
        btn.disabled = false;
        btn.textContent = 'Simpan';
    });
}

// Initialize interval label on page load
aktifkanInterval();
</script>
</body>
</html>

<?php
$conn->close();
?>
