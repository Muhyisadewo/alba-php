<?php
// Pastikan file config.php sudah ada dan koneksi database berjalan
include __DIR__ . '/../../config.php';

// Ambil data sektor dari tabel sektor
$sql = "SELECT id, nama_sektor, deskripsi, tanggal_update FROM sektor ORDER BY nama_sektor ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Sektor Gudang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        :root {
            /* Palet Warna Mewah */
            --color-primary: #437057; 
            --color-secondary: #f0f0f0; 
            --color-accent: #C8993F; 
            --color-text-dark: #212529;
            --color-text-light: #ffffff;
            --shadow-subtle: 0 4px 12px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.2);
            --shadow-inset: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--color-secondary);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 20px; /* Jarak atas body agar tidak menempel header */
        }
        
        /* --- HEADER CONTAINER (Baru) --- */
        .page-header {
            background-color: var(--color-text-light);
            box-shadow: var(--shadow-subtle);
            padding: 10px 0;
        }

        /* --- Navigasi di Header (Radio Group & Tombol Kembali) --- */
        .header-nav {
            display: flex;
            justify-content: space-between; /* Pisahkan elemen ke kiri dan kanan */
            align-items: center;
            width: 100%;
            max-width: 1320px; /* Lebar maksimal container Bootstrap */
            margin: 0 auto;
            padding: 0 15px; /* Padding samping */
        }
        
        /* Tombol Kembali Hanya Ikon */
        #kembaliBtnContainer .btn-secondary {
            width: 40px; /* Membuat tombol kotak */
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        /* --- Radio Button Styling (Mewah & Sembunyi Bulatan) --- */
        .radio-group {
            display: flex;
            gap: 15px; /* Jarak antar radio */
        }

        .radio-input {
            display: none;
        }

        .radio-label {
            cursor: pointer;
            padding: 8px 20px;
            border: 2px solid var(--color-primary);
            border-radius: 20px; /* Bentuk Pill/Kapsul */
            background-color: var(--color-text-light);
            color: var(--color-primary);
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9em;
        }

        .radio-input:checked + .radio-label {
            background-color: var(--color-primary);
            color: var(--color-text-light);
            border-color: var(--color-primary);
            box-shadow: 0 0 8px rgba(67, 112, 87, 0.5); /* Shadow lebih lembut */
        }
        
        /* --- Card Sektor Styling (Elegan & Modern) --- */
        .sektor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* CSS Grid untuk layout modern */
            gap: 20px; /* Jarak antar card */
            margin-top: 20px;
        }

        .card {
            position: relative; /* Untuk posisi tombol hapus */
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Transisi lebih smooth dengan easing */
            border: none;
            border-radius: 16px; /* Lebih bulat untuk modern */
            overflow: hidden;
            box-shadow: var(--shadow-subtle), var(--shadow-inset);
            backdrop-filter: blur(10px); /* Efek glassmorphism ringan */
            background: rgba(255, 255, 255, 0.9); /* Semi-transparan */
        }

        .card:hover {
            transform: translateY(-8px) scale(1.03); /* Lebih dramatis */
            box-shadow: var(--shadow-hover), var(--shadow-inset);
            backdrop-filter: blur(15px); /* Lebih blur saat hover */
        }
        
        .card-title {
            color: var(--color-primary);
            font-weight: 700;
            border-bottom: 3px solid var(--color-accent);
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        /* --- Tombol Hapus (Diperbarui: Selalu Muncul, Posisi Kanan Bawah) --- */
        .delete-btn {
            position: absolute;
            bottom: 10px; /* Pindah ke bawah */
            right: 10px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(220, 53, 69, 0.8); /* Merah transparan */
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .delete-btn:hover {
            background-color: #dc3545; /* Merah solid */
            transform: scale(1.1) rotate(10deg); /* Efek scale dan rotate */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        /* --- Floating Action Button (FAB) untuk Tambah Sektor --- */
        #tambahSektorFab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow-hover);
            background-color: var(--color-accent); 
            border: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        #tambahSektorFab:hover {
            transform: scale(1.1);
            background-color: #B58D39; 
            box-shadow: 0 10px 25px rgba(200, 153, 63, 0.4);
        }
        
        /* --- Responsivitas (Diperbarui untuk Tablet dan Mobile) --- */
        @media (max-width: 991.98px) and (min-width: 768px) { /* Tablet */
            .sektor-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 kolom di tablet */
                gap: 15px;
            }
        }

        @media (max-width: 767.98px) {
            .container {
                padding: 10px;
            }
            
            .header-nav {
                flex-direction: column; /* Tumpuk vertikal di HP */
                padding: 10px 15px;
            }

            #kembaliBtnContainer {
                order: 2; /* Pindahkan ke bawah agar radio group di atas */
                width: 100%;
                text-align: right; /* Geser tombol kembali ke kanan di HP */
                margin-top: 10px;
            }
            
            .radio-group {
                order: 1; /* Pindahkan ke atas */
                width: 100%;
                justify-content: space-around; /* Ratakan */
            }

            .radio-label {
                padding: 10px 15px;
                flex-grow: 1; /* Memastikan label mengisi ruang */
                text-align: center;
                font-size: 0.85em;
            }

            .sektor-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 kolom di mobile */
                gap: 10px;
            }

            #tambahSektorFab {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    
    <header class="page-header">
        <div class="header-nav">
            <div id="kembaliBtnContainer">
                <a href="?path=index.php" class="btn btn-secondary" title="Kembali ke Beranda">
                    <i class="fas fa-arrow-left"></i>
                </a>
            </div>

            <div class="radio-group">
                <input type="radio" id="gudangAtas" class="radio-input" name="gudang_type" value="atas" checked>
                <label for="gudangAtas" class="radio-label">Gudang Atas</label>
                
                <input type="radio" id="gudangPecahon" class="radio-input" name="gudang_type" value="pecahon">
                <label for="gudangPecahon" class="radio-label">Gudang Pecahon</label>
            </div>
        </div>
    </header>

    <div class="container">
        <h2 class="text-center mb-3" style="color: var(--color-primary); font-weight: 600;">Sektor Gudang</h2>

        <!-- Tambahkan kode untuk menampilkan pesan sukses/error -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="sektor-grid"> <!-- Menggunakan CSS Grid -->
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="card" onclick="window.location.href='?path=sektor_detail&id=<?php echo $row['id']; ?>'">
                        <button class="delete-btn" onclick="event.stopPropagation(); hapusSektor(<?php echo $row['id']; ?>);" title="Hapus Sektor">
                            <i class="fas fa-trash"></i>
                        </button>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['nama_sektor']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($row['deskripsi'] ?? 'Tidak ada deskripsi'); ?></p>
                            <p class="card-text"><small class="text-muted">Diperbarui: <?php echo date('d-m-Y H:i', strtotime($row['tanggal_update'])); ?></small></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                        Belum ada sektor gudang yang ditambahkan.
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <button id="tambahSektorFab" class="btn text-white" data-bs-toggle="modal" data-bs-target="#tambahSektorModal" title="Tambah Sektor Baru">
        <i class="fas fa-plus"></i>
    </button>

    <div class="modal fade" id="tambahSektorModal" tabindex="-1" aria-labelledby="tambahSektorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: var(--color-primary); color: white;">
                    <h5 class="modal-title" id="tambahSektorModalLabel">Tambah Sektor Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="?path=tambah_sektor.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="namaSektor" class="form-label">Nama Sektor</label>
                            <input type="text" class="form-control" id="namaSektor" name="nama_sektor" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsiSektor" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsiSektor" name="deskripsi" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn" style="background-color: var(--color-primary); color: white;">Tambah Sektor</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle gudang type selection
        document.addEventListener('DOMContentLoaded', function() {
            const tambahSektorFab = document.getElementById('tambahSektorFab');
            const radioButtons = document.querySelectorAll('input[name="gudang_type"]');

            // Fungsi untuk mengontrol visibilitas tombol FAB
            function toggleFabVisibility(gudangType) {
                if (gudangType === 'atas') {
                    tambahSektorFab.style.display = 'flex'; // Gunakan flex agar ikon di tengah
                } else {
                    tambahSektorFab.style.display = 'none';
                }
            }

            // Initial state
            const initialType = document.querySelector('input[name="gudang_type"]:checked').value;
            toggleFabVisibility(initialType);

            radioButtons.forEach(radio => {
                radio.addEventListener('change', function() {
                    const selectedValue = this.value;
                    toggleFabVisibility(selectedValue);

                    if (selectedValue === 'pecahon') {
                        // Redirect ke gudang_pecahon.php
                        window.location.href = '?path=gudang_pecahon';
                    }
                });
            });
        });

        // Fungsi hapus sektor
        function hapusSektor(id) {
            if (confirm('Apakah Anda yakin ingin menghapus sektor ini? Tindakan ini tidak dapat dibatalkan.')) {
                window.location.href = '?path=hapus_sektor.php&id=' + id;
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>