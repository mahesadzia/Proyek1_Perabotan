<?php
include 'konek.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$id_user = $_SESSION['id'] ?? 1;

$notif = "";
$notif_supplier = "";

// ─── SIMPAN SUPPLIER BARU (dari modal) ───────────────────────────────────────
if (isset($_POST['simpan_supplier'])) {
    $nama_supplier = trim($_POST['nama_supplier']);
    $alamat        = trim($_POST['alamat']);
    $no_hp         = trim($_POST['no_hp']);

    $stmt_sup = mysqli_prepare($conn,
        "INSERT INTO supplier (nama_supplier, alamat, no_hp) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt_sup, 'sss', $nama_supplier, $alamat, $no_hp);
    $notif_supplier = mysqli_stmt_execute($stmt_sup) ? "sukses" : "gagal";
}

// ─── SIMPAN TRANSAKSI BARANG MASUK ───────────────────────────────────────────
if (isset($_POST['simpan_masuk'])) {
    $tgl_masuk   = $_POST['tanggal_masuk'];
    $id_supplier = $_POST['id_supplier'];
    $id_barang   = $_POST['id_barang'];
    $jumlah      = $_POST['jumlah'];
    $harga_beli  = $_POST['harga_beli'];

    $stmt_header = mysqli_prepare($conn,
        "INSERT INTO barang_masuk (tanggal_masuk, id_supplier, id_user) VALUES (?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt_header, 'sii', $tgl_masuk, $id_supplier, $id_user);

    if (mysqli_stmt_execute($stmt_header)) {
        $id_masuk_terakhir = mysqli_insert_id($conn);

        $stmt_detail = mysqli_prepare($conn,
            "INSERT INTO detail_barang_masuk (id_masuk, id_barang, jumlah, harga_beli) VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt_detail, 'iiid', $id_masuk_terakhir, $id_barang, $jumlah, $harga_beli);

        $stmt_stok = mysqli_prepare($conn,
            "UPDATE inventori_barang SET stok = stok + ? WHERE id_barang = ?"
        );
        mysqli_stmt_bind_param($stmt_stok, 'ii', $jumlah, $id_barang);

        $notif = (mysqli_stmt_execute($stmt_detail) && mysqli_stmt_execute($stmt_stok)) ? "sukses" : "gagal";
    } else {
        $notif = "gagal";
    }
}

// ─── DATA DROPDOWN ────────────────────────────────────────────────────────────
$res_barang   = mysqli_query($conn, "SELECT id_barang, nama_barang FROM inventori_barang ORDER BY nama_barang ASC");
$res_supplier = mysqli_query($conn, "SELECT id_supplier, nama_supplier FROM supplier ORDER BY nama_supplier ASC");

// ID supplier yang baru ditambah (untuk auto-select di dropdown)
$new_supplier_id = ($notif_supplier == "sukses") ? mysqli_insert_id($conn) : null;

// ─── LOG TRANSAKSI TERBARU ────────────────────────────────────────────────────
$log = mysqli_query($conn, "
    SELECT h.tanggal_masuk, s.nama_supplier, b.nama_barang, 
           d.jumlah, d.harga_beli, (d.jumlah * d.harga_beli) AS total
    FROM detail_barang_masuk d
    JOIN barang_masuk h ON d.id_masuk = h.id_masuk
    JOIN inventori_barang b ON d.id_barang = b.id_barang
    LEFT JOIN supplier s ON h.id_supplier = s.id_supplier
    ORDER BY h.id_masuk DESC LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Barang Masuk</title>
    <link rel="stylesheet" href="barang_masuk.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ── MODAL OVERLAY ── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 30, 60, 0.5);
            backdrop-filter: blur(3px);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.aktif { display: flex; animation: fadeInOverlay 0.2s ease; }
        @keyframes fadeInOverlay { from{opacity:0} to{opacity:1} }

        .modal-box {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
            animation: slideUp 0.25s ease;
            overflow: hidden;
        }
        @keyframes slideUp { from{transform:translateY(30px);opacity:0} to{transform:translateY(0);opacity:1} }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            background: var(--primary, #1565c0);
            color: #fff;
        }
        .modal-header h3 { font-size: 0.95rem; font-weight: 700; display:flex; align-items:center; gap:8px; }
        .modal-close {
            background: none; border: none; color: #fff; font-size: 1.3rem;
            cursor: pointer; padding: 2px 8px; border-radius: 4px; line-height:1;
            transition: background 0.15s;
        }
        .modal-close:hover { background: rgba(255,255,255,0.2); }

        .modal-body { padding: 22px; }
        .modal-body .form-group { margin-bottom: 14px; }
        .modal-body label {
            display: block; font-size: 0.75rem; font-weight: 700;
            color: #64748b; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 5px;
        }
        .modal-body input {
            width: 100%; padding: 9px 12px;
            border: 1.5px solid #e2e8f0; border-radius: 6px;
            font-family: inherit; font-size: 0.875rem; color: #1a202c;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .modal-body input:focus {
            border-color: var(--primary, #1565c0);
            box-shadow: 0 0 0 3px rgba(21,101,192,0.1);
        }

        .modal-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding: 14px 22px; border-top: 1px solid #e2e8f0; background: #f8fafc;
        }
        .btn-batal {
            padding: 9px 18px; border: 1.5px solid #e2e8f0; border-radius: 6px;
            background: #fff; color: #64748b; font-family: inherit;
            font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.15s;
        }
        .btn-batal:hover { background: #f1f5f9; }
        .btn-simpan-supplier {
            padding: 9px 20px; border: none; border-radius: 6px;
            background: var(--primary, #1565c0); color: #fff;
            font-family: inherit; font-size: 0.85rem; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-simpan-supplier:hover { background: #0d47a1; transform: translateY(-1px); }

        /* Tombol "+ Tambah Supplier" di samping label */
        .supplier-label-row {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 5px;
        }
        .supplier-label-row label {
            font-size: 0.78rem; font-weight: 700; color: #64748b;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .btn-tambah-supplier {
            font-size: 0.75rem; font-weight: 600;
            color: var(--primary, #1565c0); background: #e3f0ff;
            border: none; padding: 4px 10px; border-radius: 5px;
            cursor: pointer; display: flex; align-items: center; gap: 4px;
            transition: background 0.15s;
        }
        .btn-tambah-supplier:hover { background: #bfdbfe; }

        /* Notif di dalam modal */
        .modal-notif {
            border-radius: 6px; padding: 10px 14px;
            font-size: 0.85rem; font-weight: 600;
            display: flex; align-items: center; gap: 7px; margin-bottom: 16px;
        }
        .modal-notif.sukses { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
        .modal-notif.gagal  { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    </style>
</head>
<body>

<!-- ════════════ SIDEBAR ════════════ -->
<div class="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span>Admin</span>
    </div>
    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php" class="active"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Pembelian</a>
    <a href="laporan_penjualan.php"><i class="fas fa-chart-bar"></i> Laporan Penjualan</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- ════════════ MAIN ════════════ -->
<div class="main-wrapper">
    <header>
        <div style="font-weight:bold;"><i class="fas fa-shopping-cart"></i> INPUT BARANG MASUK</div>
    </header>

    <?php if ($notif == "sukses"): ?>
        <div class="alert alert-sukses"><i class="fas fa-check-circle"></i> Transaksi berhasil disimpan!</div>
    <?php elseif ($notif == "gagal"): ?>
        <div class="alert alert-gagal"><i class="fas fa-times-circle"></i> Gagal menyimpan transaksi.</div>
    <?php endif; ?>

    <!-- Form Transaksi -->
    <div class="content-card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Form Input Barang Masuk</h3>
        </div>
        <form action="" method="POST">
            <div class="content-grid">

                <!-- Kolom Kiri -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-info-circle"></i> Info Transaksi</h4>

                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" required>
                    </div>

                    <div class="form-group">
                        <div class="supplier-label-row">
                            <label>Supplier</label>
                            <button type="button" class="btn-tambah-supplier" onclick="bukaModal()">
                                <i class="fas fa-plus"></i> Tambah Supplier
                            </button>
                        </div>
                        <select name="id_supplier" id="select-supplier" required>
                            <option value="">-- Pilih Supplier --</option>
                            <?php while ($s = mysqli_fetch_assoc($res_supplier)): ?>
                                <option value="<?= $s['id_supplier'] ?>"
                                    <?= ($new_supplier_id == $s['id_supplier']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_supplier']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="form-section">
                    <h4 class="section-title"><i class="fas fa-box"></i> Detail Barang</h4>

                    <div class="form-group">
                        <label>Pilih Barang</label>
                        <select name="id_barang" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php while ($b = mysqli_fetch_assoc($res_barang)): ?>
                                <option value="<?= $b['id_barang'] ?>">
                                    <?= htmlspecialchars($b['nama_barang']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jumlah</label>
                        <input type="number" name="jumlah" placeholder="0" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Harga Beli (Per Item)</label>
                        <input type="number" name="harga_beli" placeholder="Rp" min="0" required>
                    </div>
                </div>

            </div>

            <div style="text-align:right; padding: 0 24px 24px;">
                <button type="submit" name="simpan_masuk" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan Transaksi
                </button>
            </div>
        </form>
    </div>

    <!-- Log Transaksi -->
    <div class="content-card" style="margin-top:20px; margin-bottom:28px;">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Log Transaksi Terbaru</h3>
        </div>
        <table class="table-report">
            <thead>
                <tr>
                    <th>Tanggal</th><th>Supplier</th><th>Barang</th>
                    <th>Jumlah</th><th>Harga Beli</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($log) == 0): ?>
                    <tr><td colspan="6" class="no-data">Belum ada transaksi</td></tr>
                <?php else: ?>
                    <?php while ($l = mysqli_fetch_assoc($log)): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($l['tanggal_masuk'])) ?></td>
                        <td><?= htmlspecialchars($l['nama_supplier'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($l['nama_barang']) ?></td>
                        <td><?= $l['jumlah'] ?></td>
                        <td>Rp <?= number_format($l['harga_beli'], 0, ',', '.') ?></td>
                        <td><strong>Rp <?= number_format($l['total'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════════════ MODAL TAMBAH SUPPLIER ════════════ -->
<div class="modal-overlay" id="modal-supplier">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-truck"></i> Tambah Supplier Baru</h3>
            <button class="modal-close" onclick="tutupModal()">&times;</button>
        </div>

        <form action="" method="POST">
            <div class="modal-body">

                <?php if ($notif_supplier == "sukses"): ?>
                    <div class="modal-notif sukses">
                        <i class="fas fa-check-circle"></i>
                        Supplier berhasil ditambahkan! Silakan pilih dari dropdown.
                    </div>
                <?php elseif ($notif_supplier == "gagal"): ?>
                    <div class="modal-notif gagal">
                        <i class="fas fa-times-circle"></i> Gagal menambahkan supplier.
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Nama Supplier <span style="color:red">*</span></label>
                    <input type="text" name="nama_supplier" placeholder="Contoh: PT. Maju Jaya" required>
                </div>

                <div class="form-group">
                    <label>Alamat</label>
                    <input type="text" name="alamat" placeholder="Jl. ...">
                </div>

                <div class="form-group">
                    <label>No. HP / Telepon</label>
                    <input type="text" name="no_hp" placeholder="08xxxxxxxxxx">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-batal" onclick="tutupModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="submit" name="simpan_supplier" class="btn-simpan-supplier">
                    <i class="fas fa-save"></i> Simpan Supplier
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function bukaModal() {
        document.getElementById('modal-supplier').classList.add('aktif');
        document.body.style.overflow = 'hidden';
    }
    function tutupModal() {
        document.getElementById('modal-supplier').classList.remove('aktif');
        document.body.style.overflow = '';
    }
    // Klik di luar modal → tutup
    document.getElementById('modal-supplier').addEventListener('click', function(e) {
        if (e.target === this) tutupModal();
    });

    <?php if ($notif_supplier == "sukses"): ?>
        // Buka kembali modal otomatis agar user lihat notif sukses
        bukaModal();
    <?php endif; ?>
</script>

</body>
</html>
