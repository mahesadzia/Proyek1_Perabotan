<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'konek.php';

// --- LOGIKA FILTER & SORTING ---
$sort_order = isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'DESC' : 'ASC';
$tgl_mulai  = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

// --- LOGIKA SIMPAN BATCH ---
$notif = "";
if (isset($_POST['simpan_masuk'])) {
    $tgl_masuk   = $_POST['tanggal_masuk'];
    $id_supplier = $_POST['id_supplier'];
    $id_user     = $_SESSION['user_id'];
    
    $id_barangs  = $_POST['id_barang'];
    $jumlahs     = $_POST['jumlah'];
    $harga_belis = $_POST['harga_beli'];

    $conn->begin_transaction();
    try {
        $stmt_header = $conn->prepare("INSERT INTO barang_masuk (tanggal_masuk, id_supplier, id_user) VALUES (?, ?, ?)");
        $stmt_header->bind_param("sii", $tgl_masuk, $id_supplier, $id_user);
        $stmt_header->execute();
        $id_masuk_terakhir = $conn->insert_id;

        $stmt_detail = $conn->prepare("INSERT INTO detail_barang_masuk (id_masuk, id_barang, jumlah, harga_beli) VALUES (?, ?, ?, ?)");
        $stmt_stok = $conn->prepare("UPDATE inventori_barang SET stok = stok + ? WHERE id_barang = ?");

        foreach ($id_barangs as $key => $id_barang) {
            if (empty($id_barang)) continue;
            $qty = $jumlahs[$key];
            $hrg = $harga_belis[$key];
            $stmt_detail->bind_param("iiid", $id_masuk_terakhir, $id_barang, $qty, $hrg);
            $stmt_detail->execute();
            $stmt_stok->bind_param("ii", $qty, $id_barang);
            $stmt_stok->execute();
        }
        $conn->commit();
        $notif = "sukses";
    } catch (Exception $e) {
        $conn->rollback();
        $notif = "gagal";
    }
}

// Query untuk dropdown barang
$res_barang = mysqli_query($conn, "SELECT * FROM inventori_barang ORDER BY nama_barang ASC");

// Query untuk tabel riwayat (dengan Filter & Sort)
$query_log = "
    SELECT h.tanggal_masuk, b.nama_barang, d.jumlah, d.harga_beli 
    FROM detail_barang_masuk d
    JOIN barang_masuk h ON d.id_masuk = h.id_masuk
    JOIN inventori_barang b ON d.id_barang = b.id_barang
    WHERE 1=1
";
if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
    $query_log .= " AND h.tanggal_masuk BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}
$query_log .= " ORDER BY b.nama_barang $sort_order";
$log = mysqli_query($conn, $query_log);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Barang Masuk - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1565c0; --primary-dark: #0d47a1; --primary-light: #e3f0ff;
            --sidebar-width: 240px; --text-main: #1a202c; --text-muted: #718096;
            --bg-page: #f0f4f8; --bg-card: #ffffff; --border: #e2e8f0;
            --radius: 10px; --shadow: 0 2px 12px rgba(21,101,192,0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-page); color: var(--text-main); display: flex; min-height: 100vh; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: var(--primary); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; box-shadow: 4px 0 20px rgba(13,71,161,0.18); }
        .admin-profile { display: flex; align-items: center; gap: 10px; padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.12); color: #fff; font-weight: 600; }
        .admin-profile i { font-size: 2rem; color: rgba(255,255,255,0.85); }
        .sidebar h3 { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.45); padding: 20px 20px 8px; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: rgba(255,255,255,0.82); text-decoration: none; font-size: 0.875rem; font-weight: 500; border-left: 3px solid transparent; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: #fff; }
        .sidebar .logout-btn { margin-top: auto; color: #ff6b6b; padding-bottom: 24px; }

        /* MAIN CONTENT */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        header { display: flex; align-items: center; justify-content: space-between; padding: 18px 28px; background: var(--bg-card); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        
        .content-container { padding: 24px 28px; display: flex; flex-direction: column; gap: 24px; animation: fadeUp 0.4s ease both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

        .page-title { font-size: 1.4rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
        .content-card { background: var(--bg-card); border-radius: var(--radius); padding: 24px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .content-card h3 { font-size: 1rem; margin-bottom: 20px; color: var(--primary); display: flex; align-items: center; gap: 8px; font-weight: 700; }

        /* FORM STYLES */
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; display: flex; flex-direction: column; gap: 5px; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 3px; }
        input, select { width: 100%; padding: 10px 12px; border: 1.5px solid var(--border); border-radius: 7px; font-size: 0.875rem; font-family: inherit; transition: 0.2s; outline: none; }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(21,101,192,0.1); }

        /* FILTER CARD SPECIFIC */
        .filter-card-area { display: flex; align-items: flex-end; gap: 15px; flex-wrap: wrap; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 20px; }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.875rem; display: flex; align-items: center; gap: 8px; }
        .btn-reset { background: #e2e8f0; color: var(--text-main); text-decoration: none; padding: 9px 20px; border-radius: 6px; font-size: 0.875rem; font-weight: 600; }

        /* TABLE STYLES */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f7faff; padding: 12px 16px; text-align: left; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: var(--primary); border-bottom: 2px solid var(--border); }
        td { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 0.875rem; color: var(--text-main); }
        tbody tr:hover { background: #f0f7ff; }

        .sort-link { color: #cbd5e1; text-decoration: none; font-size: 0.7rem; margin-left: 3px; }
        .sort-link.active { color: var(--primary); }

        .btn-add { background: var(--primary-light); color: var(--primary); border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; margin-bottom: 15px; }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 7px; cursor: pointer; width: 100%; font-weight: 700; margin-top: 10px; }
        .btn-remove { color: #e74c3c; cursor: pointer; }

        .alert { padding: 15px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; margin-bottom: 10px; border: 1px solid transparent; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php" class="active"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>LAPORAN</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporanBarangKeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <h3>LAINNYA</h3>
    <a href="setting.php"><i class="fas fa-cog"></i> Setelan</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div><i class="fas fa-shopping-cart"></i> BARANG MASUK</div>
        <div style="font-size:0.875rem; color:var(--text-muted);"><?= date('l, d F Y'); ?></div>
    </header>

    <div class="content-container">
        <div>
            <h2 class="page-title">Input Barang Masuk (Batch)</h2>
            <p style="color:var(--text-muted); font-size:0.875rem;">Tambahkan stok barang dari supplier sekaligus.</p>
        </div>

        <?php if($notif == "sukses"): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Transaksi Berhasil Disimpan!</div>
        <?php elseif($notif == "gagal"): ?>
            <div class="alert alert-error"><i class="fas fa-times-circle"></i> Gagal menyimpan data transaksi.</div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>ID Supplier</label>
                        <input type="number" name="id_supplier" placeholder="Contoh: 1" required>
                    </div>
                </div>

                <h3><i class="fas fa-list-ul"></i> Detail Barang</h3>
                <button type="button" class="btn-add" onclick="tambahBaris()">
                    <i class="fas fa-plus"></i> Tambah Item
                </button>
                
                <div class="table-responsive">
                    <table class="batch-table">
                        <thead>
                            <tr>
                                <th>Pilih Nama Barang</th>
                                <th width="140">Jumlah</th>
                                <th width="180">Harga Beli</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="batchBody">
                            <tr>
                                <td>
                                    <select name="id_barang[]" required>
                                        <option value="">-- Cari Barang --</option>
                                        <?php mysqli_data_seek($res_barang, 0); while($b = mysqli_fetch_assoc($res_barang)): ?>
                                            <option value="<?= $b['id_barang'] ?>"><?= $b['nama_barang'] ?> (Stok: <?= $b['stok'] ?>)</option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td><input type="number" name="jumlah[]" min="1" placeholder="0" required></td>
                                <td><input type="number" name="harga_beli[]" min="0" placeholder="Rp" required></td>
                                <td style="text-align: center;"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <button type="submit" name="simpan_masuk" class="btn-submit">
                    <i class="fas fa-save"></i> SIMPAN SEMUA TRANSAKSI
                </button>
            </form>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-history"></i> Riwayat Masuk Terbaru</h3>

            <form method="GET" class="filter-card-area">
                <div class="form-group">
                    <label>Mulai Tanggal</label>
                    <input type="date" name="tgl_mulai" value="<?= $tgl_mulai ?>" class="filter-input">
                </div>
                <div class="form-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" value="<?= $tgl_selesai ?>" class="filter-input">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filter Data
                </button>
                <?php if(!empty($tgl_mulai)): ?>
                    <a href="barang_masuk.php" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="120">Tanggal</th>
                            <th>
                                Nama Barang 
                                <span style="display:inline-flex; flex-direction:column; vertical-align:middle; line-height:0.5;">
                                    <a href="?sort=asc<?= $tgl_mulai ? "&tgl_mulai=$tgl_mulai&tgl_selesai=$tgl_selesai" : "" ?>" class="sort-link <?= $sort_order == 'ASC' ? 'active' : '' ?>"><i class="fas fa-sort-up"></i></a>
                                    <a href="?sort=desc<?= $tgl_mulai ? "&tgl_mulai=$tgl_mulai&tgl_selesai=$tgl_selesai" : "" ?>" class="sort-link <?= $sort_order == 'DESC' ? 'active' : '' ?>"><i class="fas fa-sort-down"></i></a>
                                </span>
                            </th>
                            <th width="100">Qty</th>
                            <th width="180">Harga Beli</th>
                            <th width="180">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($log) > 0): ?>
                            <?php while($l = mysqli_fetch_assoc($log)): ?>
                            <tr>
                                <td style="font-family:'DM Mono',monospace; font-size:0.8rem;"><?= date('d/m/y', strtotime($l['tanggal_masuk'])); ?></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($l['nama_barang']); ?></td>
                                <td><?= $l['jumlah']; ?></td>
                                <td>Rp <?= number_format($l['harga_beli'], 0, ',', '.'); ?></td>
                                <td style="font-weight:700; color:var(--primary-dark);">Rp <?= number_format($l['jumlah'] * $l['harga_beli'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-folder-open" style="display:block; font-size:2rem; margin-bottom:10px; opacity:0.3;"></i>
                                    Data tidak ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function tambahBaris() {
        const body = document.getElementById('batchBody');
        const row = body.rows[0].cloneNode(true);
        row.querySelectorAll('input').forEach(i => i.value = '');
        row.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        row.cells[3].innerHTML = '<i class="fas fa-trash-alt btn-remove" onclick="this.closest(\'tr\').remove()"></i>';
        body.appendChild(row);
    }
</script>

</body>
</html>
