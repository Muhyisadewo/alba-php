<?php
include __DIR__ . '/../../config.php';

// Ambil barang dari gudang pecahon
$barang_sql = "SELECT id, nama_barang, harga_ambil, qty, gambar, barcode, created_at
               FROM gudang_pecahon
               ORDER BY created_at DESC";
$barang_stmt = $conn->prepare($barang_sql);
$barang_stmt->execute();
$barang_result = $barang_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gudang Pecahon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
    <style>
        /* Custom responsive styles */
        .container {
            max-width: 1200px;
        }

        .search-form {
            max-width: 400px;
        }

        .barang-item {
            padding: 1rem;
        }

        .barang-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .qty-input {
            width: 60px !important;
            text-align: center;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            .barang-item {
                padding: 0.75rem;
                flex-direction: column !important;
                align-items: flex-start !important;
            }

            .barang-item .d-flex {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .barang-item .d-flex:last-child {
                justify-content: space-between;
                align-items: center;
            }

            .qty-controls {
                gap: 0.25rem;
            }

            .qty-input {
                width: 50px !important;
            }

            .search-form {
                max-width: 100%;
            }

            .fixed-bottom {
                padding: 1rem 15px;
            }
        }

        @media (max-width: 480px) {
            .barang-item h6 {
                font-size: 1rem;
            }

            .barang-item small {
                font-size: 0.8rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }

            .qty-input {
                width: 45px !important;
                font-size: 0.875rem;
            }
        }

        /* Button positioning */
        .top-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 10;
        }

        .add-button {
            background: #007bff;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .add-button:hover {
            background: #0056b3;
            color: white;
        }

        @media (max-width: 768px) {
            .top-buttons {
                top: 15px;
                right: 15px;
            }

            .add-button {
                width: 45px;
                height: 45px;
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Add Button -->
    <div class="top-buttons">
        <button type="button" class="btn add-button" data-bs-toggle="modal" data-bs-target="#tambahBarangModal">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <div class="container mt-5">
        <h1 class="mb-4">Gudang Pecahon</h1>

        <!-- Search Bar -->
        <div class="mb-2">
            <form id="searchForm" class="d-flex search-form">
                <input type="text" id="searchInput" class="form-control" placeholder="Cari barang..." autocomplete="off">
                <button type="button" class="btn btn-outline-secondary ms-2" id="clearSearchBtn">
                    <i class="fas fa-times"></i>
                </button>
            </form>
        </div>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="inti/gudang/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
            </a>
        </div>

        <form action="?path=ambil_barang_pecahon.php" method="POST">
            <div class="list-group" id="barangList">
                <?php if ($barang_result->num_rows > 0): ?>
                    <?php while ($barang = $barang_result->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center barang-item" data-id="<?php echo $barang['id']; ?>" data-nama="<?php echo htmlspecialchars($barang['nama_barang']); ?>">
                            <div class="d-flex align-items-center">
                                <img src="../../uploads/barang/<?php echo htmlspecialchars($barang['gambar'] ?? 'default.jpg'); ?>" alt="Gambar Barang" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($barang['nama_barang']); ?></h6>
                                    <small>Qty: <?php echo htmlspecialchars($barang['qty']); ?> | Harga: Rp <?php echo number_format($barang['harga_ambil'], 0, ',', '.'); ?></small>
                                </div>
                            </div>
                                <div class="d-flex align-items-center">
                                    <a href="?path=edit_barang_pecahon.php?id=<?php echo $barang['id']; ?>" class="btn btn-sm btn-warning me-1">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger me-2" onclick="showDeleteModal(<?php echo $barang['id']; ?>, '<?php echo addslashes($barang['nama_barang']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeQty(this, -1)">-</button>
                                    <input type="number" name="ambil[<?php echo $barang['id']; ?>]" value="0" min="0" max="<?php echo $barang['qty']; ?>" class="form-control form-control-sm mx-2 qty-input" style="width: 60px;" readonly>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeQty(this, 1)">+</button>
                                </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Belum ada barang.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fixed Bottom Button -->
            <div class="fixed-bottom bg-white border-top p-3">
                <div class="container">
                    <button type="submit" class="btn btn-success btn-lg w-100">Ambil Barang</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal untuk tambah barang -->
    <div class="modal fade" id="tambahBarangModal" tabindex="-1" aria-labelledby="tambahBarangModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tambahBarangModalLabel">Tambah Barang ke Gudang Pecahon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="?path=tambah_barang_pecahon.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="namaBarang" class="form-label">Nama Barang</label>
                                    <input type="text" class="form-control" id="namaBarang" name="nama_barang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="hargaAmbil" class="form-label">Harga Ambil</label>
                                    <input type="number" class="form-control" id="hargaAmbil" name="harga_ambil" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="qty" class="form-label">Qty</label>
                                    <input type="number" class="form-control" id="qty" name="qty" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="gambar" class="form-label">Gambar Barang</label>
                                    <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="barcode" class="form-label">Barcode</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="barcode" name="barcode" placeholder="Scan barcode atau ketik manual">
                                        <button type="button" class="btn btn-outline-secondary" id="scanBarcodeBtn">
                                            <i class="fas fa-camera"></i> Scan
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="supplier" class="form-label">Supplier</label>
                                    <select class="form-control" id="supplier" name="supplier_id" required>
                                        <option value="">Pilih Supplier</option>
                                        <?php
                                        $supplier_sql = "SELECT id, nama_supplier FROM supplier ORDER BY nama_supplier ASC";
                                        $supplier_result = $conn->query($supplier_sql);
                                        while ($supplier = $supplier_result->fetch_assoc()) {
                                            echo "<option value='" . $supplier['id'] . "'>" . htmlspecialchars($supplier['nama_supplier']) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sales" class="form-label">Sales</label>
                                    <select class="form-control" id="sales" name="sales_id" required>
                                        <option value="">Pilih Supplier</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah Barang</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="hapusModal" tabindex="-1" aria-labelledby="hapusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="hapusModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus barang "<span id="namaBarangHapus"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="konfirmasiHapus">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to change quantity
        function changeQty(button, delta) {
            var input = button.parentElement.querySelector('.qty-input');
            var currentValue = parseInt(input.value);
            var maxValue = parseInt(input.max);
            var newValue = currentValue + delta;

            if (newValue >= 0 && newValue <= maxValue) {
                input.value = newValue;
            }
        }

        // Real-time search functionality with AJAX
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300); // Debounce search
        });

        // Clear search functionality
        document.getElementById('clearSearchBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            performSearch('');
        });

        function performSearch(searchTerm) {
            const barangList = document.getElementById('barangList');

            fetch(`?path=get_barang_pecahon.php?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const html = data.map(barang => `
                            <div class="list-group-item d-flex justify-content-between align-items-center barang-item">
                                <div class="d-flex align-items-center">
                                    <img src="uploads/barang/${barang.gambar || 'default.jpg'}" alt="Gambar Barang" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                    <div>
                                        <h6 class="mb-1">${barang.nama_barang}</h6>
                                        <small>Qty: ${barang.qty} | Harga: Rp ${new Intl.NumberFormat('id-ID').format(barang.harga_ambil)} | Barcode: ${barang.barcode || '-'}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="edit_barang_pecahon.php?id=${barang.id}" class="btn btn-sm btn-warning me-2">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeQty(this, -1)">-</button>
                                    <input type="number" name="ambil[${barang.id}]" value="0" min="0" max="${barang.qty}" class="form-control form-control-sm mx-2 qty-input" style="width: 60px;" readonly>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeQty(this, 1)">+</button>
                                </div>
                            </div>
                        `).join('');
                        barangList.innerHTML = html;
                    } else {
                        barangList.innerHTML = '<div class="alert alert-info" role="alert">Tidak ada barang yang ditemukan.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    barangList.innerHTML = '<div class="alert alert-danger" role="alert">Terjadi kesalahan saat mencari barang.</div>';
                });
        }



        // Reset form when modal is shown
        document.getElementById('tambahBarangModal').addEventListener('show.bs.modal', function() {
            document.getElementById('sales').innerHTML = '<option value="">Pilih Supplier terlebih dahulu</option>';
        });

        // Supplier change event to load sales
        document.getElementById('supplier').addEventListener('change', function() {
            const supplierId = this.value;
            const salesSelect = document.getElementById('sales');

            if (supplierId) {
                fetch(`?path=get_sales.php?supplier_id=${supplierId}`)
                    .then(response => response.json())
                    .then(data => {
                        salesSelect.innerHTML = '<option value="">Pilih Sales</option>';
                        data.forEach(sales => {
                            salesSelect.innerHTML += `<option value="${sales.id}">${sales.nama_sales}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error loading sales:', error);
                        salesSelect.innerHTML = '<option value="">Error loading sales</option>';
                    });
            } else {
                salesSelect.innerHTML = '<option value="">Pilih Supplier terlebih dahulu</option>';
            }
        });

        // Barcode scanning functionality
        let scanning = false;
        document.getElementById('scanBarcodeBtn').addEventListener('click', function() {
            if (scanning) {
                Quagga.stop();
                scanning = false;
                this.innerHTML = '<i class="fas fa-camera"></i> Scan';
                this.classList.remove('btn-danger');
                this.classList.add('btn-outline-secondary');
            } else {
                startBarcodeScan();
                scanning = true;
                this.innerHTML = '<i class="fas fa-stop"></i> Stop';
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-danger');
            }
        });

        function startBarcodeScan() {
            // Create scanner container if it doesn't exist
            if (!document.getElementById('scanner-container')) {
                const scannerDiv = document.createElement('div');
                scannerDiv.id = 'scanner-container';
                scannerDiv.style.position = 'fixed';
                scannerDiv.style.top = '50%';
                scannerDiv.style.left = '50%';
                scannerDiv.style.transform = 'translate(-50%, -50%)';
                scannerDiv.style.zIndex = '9999';
                scannerDiv.style.background = 'white';
                scannerDiv.style.padding = '20px';
                scannerDiv.style.borderRadius = '10px';
                scannerDiv.style.boxShadow = '0 0 20px rgba(0,0,0,0.5)';
                scannerDiv.innerHTML = '<div id="interactive" class="viewport"></div><button id="closeScanner" class="btn btn-secondary mt-2">Tutup</button>';
                document.body.appendChild(scannerDiv);

                document.getElementById('closeScanner').addEventListener('click', function() {
                    Quagga.stop();
                    document.body.removeChild(scannerDiv);
                    scanning = false;
                    document.getElementById('scanBarcodeBtn').innerHTML = '<i class="fas fa-camera"></i> Scan';
                    document.getElementById('scanBarcodeBtn').classList.remove('btn-danger');
                    document.getElementById('scanBarcodeBtn').classList.add('btn-outline-secondary');
                });
            }

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#interactive'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment"
                    }
                },
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                numOfWorkers: 2,
                decoder: {
                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader"]
                },
                locate: true
            }, function(err) {
                if (err) {
                    console.log(err);
                    return;
                }
                Quagga.start();
            });

            Quagga.onDetected(function(result) {
                var code = result.codeResult.code;
                document.getElementById('barcode').value = code;
                Quagga.stop();
                document.body.removeChild(document.getElementById('scanner-container'));
                scanning = false;
                document.getElementById('scanBarcodeBtn').innerHTML = '<i class="fas fa-camera"></i> Scan';
                document.getElementById('scanBarcodeBtn').classList.remove('btn-danger');
                document.getElementById('scanBarcodeBtn').classList.add('btn-outline-secondary');
                alert('Barcode berhasil dipindai: ' + code);
            });
        }

        // Long-press delete functionality
        let longPressTimer;
        let isLongPress = false;
        let currentItem = null;

        document.addEventListener('DOMContentLoaded', function() {
            const barangItems = document.querySelectorAll('.barang-item');

            barangItems.forEach(item => {
                // Touch events for mobile
                item.addEventListener('touchstart', function(e) {
                    isLongPress = false;
                    currentItem = this;
                    longPressTimer = setTimeout(() => {
                        isLongPress = true;
                        showDeleteModal(this);
                    }, 500); // 500ms for long press
                });

                item.addEventListener('touchend', function(e) {
                    clearTimeout(longPressTimer);
                    if (!isLongPress) {
                        // Normal tap - you can add other functionality here if needed
                    }
                });

                item.addEventListener('touchmove', function(e) {
                    clearTimeout(longPressTimer);
                });

                // Mouse events for desktop
                item.addEventListener('mousedown', function(e) {
                    isLongPress = false;
                    currentItem = this;
                    longPressTimer = setTimeout(() => {
                        isLongPress = true;
                        showDeleteModal(this);
                    }, 500);
                });

                item.addEventListener('mouseup', function(e) {
                    clearTimeout(longPressTimer);
                    if (!isLongPress) {
                        // Normal click - you can add other functionality here if needed
                    }
                });

                item.addEventListener('mousemove', function(e) {
                    clearTimeout(longPressTimer);
                });
            });
        });

        function showDeleteModal(idOrItem, namaBarang) {
            let id, nama, itemElement;

            if (typeof idOrItem === 'object') {
                // Called from long-press with item element
                itemElement = idOrItem;
                id = itemElement.getAttribute('data-id');
                nama = itemElement.getAttribute('data-nama');
            } else {
                // Called from button click with id and nama parameters
                id = idOrItem;
                nama = namaBarang;
                // Find the item element by data-id
                itemElement = document.querySelector(`.barang-item[data-id="${id}"]`);
            }

            document.getElementById('namaBarangHapus').textContent = nama;

            const hapusModal = new bootstrap.Modal(document.getElementById('hapusModal'));
            hapusModal.show();

            // Handle delete confirmation
            document.getElementById('konfirmasiHapus').onclick = function() {
                deleteBarang(id, itemElement);
                hapusModal.hide();
            };
        }

        function deleteBarang(id, itemElement) {
            fetch('?path=hapus_barang_pecahon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the item from the list
                    itemElement.remove();
                    alert('Barang berhasil dihapus!');
                } else {
                    alert('Gagal menghapus barang: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus barang');
            });
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>
