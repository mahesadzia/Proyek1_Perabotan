<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'konek.php';

$notif = null;

// --- LOGIKA FILTER & SORTING ---
$sort_order = isset($_GET['sort']) && $_GET['sort'] == 'desc' ? 'DESC' : 'ASC';
$tgl_mulai  = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';

/* ─── LOGIKA SIMPAN BATCH ─────────────────────────── */
if (isset($_POST['simpan_batch'])) {
    $tanggal = $_POST['tanggal'];
    $id_barangs = $_POST['id_barang']; 
    $jumlahs    = $_POST['jumlah'];    
    
    if (empty($id_barangs)) {
        $notif = ['type' => 'warning', 'msg' => 'Tambahkan minimal satu barang!'];
    } else {
        $conn->begin_transaction();
        try {
            foreach ($id_barangs as $index => $id_b) {
                $qty = (int)$jumlahs[$index];
                $id_b = (int)$id_b;

                $st = $conn->prepare("SELECT harga_jual, stok, nama_barang FROM inventori_barang WHERE id_barang = ?");
                $st->bind_param("i", $id_b);
                $st->execute();
                $b = $st->get_result()->fetch_assoc();

                if ($b['stok'] < $qty) {
                    throw new Exception("Stok barang '{$b['nama_barang']}' tidak cukup!");
                }

                $total = $qty * $b['harga_jual'];
                $ins = $conn->prepare("INSERT INTO barang_keluar (tanggal, id_barang, jumlah, total) VALUES (?, ?, ?, ?)");
                $ins->bind_param("siid", $tanggal, $id_b, $qty, $total);
                $ins->execute();

                $upd = $conn->prepare("UPDATE inventori_barang SET stok = stok - ? WHERE id_barang = ?");
                $upd->bind_param("ii", $qty, $id_b);
                $upd->execute();
            }
            $conn->commit();
            $notif = ['type' => 'success', 'msg' => 'Batch transaksi berhasil disimpan!'];
        } catch (Exception $e) {
            $conn->rollback();
            $notif = ['type' => 'danger', 'msg' => 'Gagal: ' . $e->getMessage()];
        }
    }
}

/* ─── LOGIKA HAPUS ───────────────────────────────── */
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $stmt = $conn->prepare("SELECT id_barang, jumlah FROM barang_keluar WHERE id_keluar = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    
    if ($res) {
        $conn->query("UPDATE inventori_barang SET stok = stok + {$res['jumlah']} WHERE id_barang = {$res['id_barang']}");
        $conn->query("DELETE FROM barang_keluar WHERE id_keluar = $id");
        $notif = ['type' => 'success', 'msg' => 'Data dihapus & stok dikembalikan.'];
    }
}

// Data barang untuk dropdown
$res_barang = mysqli_query($conn, "SELECT * FROM inventori_barang WHERE stok > 0 ORDER BY nama_barang ASC");
$barang_data = [];
while($row = mysqli_fetch_assoc($res_barang)) { $barang_data[] = $row; }

// Query Riwayat dengan Filter & Sort
$query_riwayat = "SELECT bk.*, ib.nama_barang FROM barang_keluar bk JOIN inventori_barang ib ON bk.id_barang = ib.id_barang WHERE 1=1";
if ($tgl_mulai && $tgl_selesai) {
    $query_riwayat .= " AND bk.tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
}
$query_riwayat .= " ORDER BY ib.nama_barang $sort_order";
$res_riwayat = mysqli_query($conn, $query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Barang Keluar - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #1565c0; --primary-dark: #0d47a1; --primary-light: #e3f0ff;
            --sidebar-width: 240px; --text-main: #1a202c; --text-muted: #718096;
            --bg-page: #f0f4f8; --bg-card: #ffffff; --border: #e2e8f0;
            --radius: 10px; --shadow: 0 2px 12px rgba(21,101,192,0.08);
            --danger: #e74c3c; --success: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-page); color: var(--text-main); display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: var(--primary); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; box-shadow: 4px 0 20px rgba(13,71,161,0.18); }
        .admin-profile { display: flex; align-items: center; gap: 10px; padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.12); color: #fff; font-weight: 600; }
        .admin-profile i { font-size: 2rem; color: rgba(255,255,255,0.85); }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: rgba(255,255,255,0.82); text-decoration: none; font-size: 0.875rem; border-left: 3px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: #fff; }
        .sidebar h3 { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; color: rgba(255,255,255,0.45); padding: 20px 20px 8px; }
        .logout-btn { margin-top: auto; color: #ff6b6b !important; padding-bottom: 24px; }

        /* MAIN CONTENT */
        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        header { display: flex; align-items: center; justify-content: space-between; padding: 18px 28px; background: #fff; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50; }
        .content-container { padding: 24px 28px; animation: fadeUp 0.4s ease both; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        .content-card { background: #fff; border-radius: var(--radius); padding: 24px; border: 1px solid var(--border); box-shadow: var(--shadow); margin-bottom: 24px; }
        .content-card h3 { font-size: 1rem; color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 700; border-bottom: 1px solid var(--border); padding-bottom: 10px; }

        /* BATCH INPUT STYLE */
        .batch-row { display: grid; grid-template-columns: 2fr 1fr 1.5fr 50px; gap: 15px; align-items: center; margin-bottom: 10px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
        .btn-add-row { background: #f1f5f9; color: var(--primary); border: 2px dashed var(--primary); padding: 12px; width: 100%; border-radius: 8px; cursor: pointer; font-weight: 700; margin: 10px 0; transition: 0.2s; }
        .btn-add-row:hover { background: var(--primary-light); }
        .btn-remove-row { color: var(--danger); cursor: pointer; border: none; background: none; font-size: 1.2rem; }

        /* FILTER AREA (Gaya Gambar Laporan Anda) */
        .filter-card-area { display: flex; align-items: flex-end; gap: 15px; flex-wrap: wrap; background: #f8fafc; padding: 18px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 20px; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
        .filter-group label { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }
        .btn-filter { background: var(--primary); color: white; border: none; height: 42px; padding: 0 20px; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-reset { background: #e2e8f0; color: var(--text-main); text-decoration: none; height: 42px; padding: 0 20px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; }

        /* FORM ELEMENTS */
        input, select { width: 100%; padding: 11px 14px; border: 1.5px solid var(--border); border-radius: 8px; font-size: 0.9rem; outline: none; transition: 0.2s; }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1); }
        .btn-submit { background: var(--primary); color: white; border: none; padding: 14px; border-radius: 8px; width: 100%; font-weight: 700; margin-top: 15px; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: var(--primary-dark); }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f7faff; padding: 14px 16px; text-align: left; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--primary); border-bottom: 2px solid var(--border); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
        .total-bold { font-family: 'DM Mono', monospace; font-weight: 700; color: var(--primary-dark); }
        .btn-del { color: var(--danger); background: #fff5f5; padding: 6px 10px; border-radius: 5px; text-decoration: none; font-size: 0.8rem; font-weight: 600; }
        
        .sort-link { color: #cbd5e1; text-decoration: none; font-size: 0.75rem; margin-left: 4px; }
        .sort-link.active { color: var(--primary); }

        /* NOTIF */
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 0.875rem; border: 1px solid transparent; }
        .alert-success { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .alert-warning { background: #fef9c3; color: #854d0e; border-color: #fde68a; }
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
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php" class="active"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>LAPORAN</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporanBarangKeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <h3>LAINNYA</h3>
    <a href="setting.php"><i class="fas fa-cog"></i> Setelan</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div><i class="fas fa-file-export" style="color:var(--primary); margin-right:8px;"></i> BARANG KELUAR</div>
        <div style="font-size:0.875rem; color:var(--text-muted);"><?= date('l, d F Y'); ?></div>
    </header>

    <div class="content-container">
        <?php if ($notif): ?>
            <div class="alert alert-<?= $notif['type'] ?>"><?= $notif['msg'] ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h3><i class="fas fa-plus-circle"></i> Input Penjualan Batch</h3>
            <form method="POST">
                <div style="max-width: 250px; margin-bottom: 20px;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">TANGGAL TRANSAKSI</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div id="batchContainer">
                    </div>

                <button type="button" class="btn-add-row" onclick="addRow()">
                    <i class="fas fa-plus-circle"></i> Tambah Baris Barang
                </button>

                <div style="text-align: right; padding: 15px; border-top: 2px solid #e2e8f0; margin-top: 10px; font-weight: 800; font-size: 1.1rem; color: var(--primary-dark);">
                    GRAND TOTAL: <span id="grandTotal">Rp 0</span>
                </div>

                <button type="submit" name="simpan_batch" class="btn-submit">
                    <i class="fas fa-save"></i> SIMPAN SEMUA DATA BATCH
                </button>
            </form>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-history"></i> Riwayat Penjualan</h3>

            <form method="GET" class="filter-card-area">
                <div class="filter-group">
                    <label>Mulai Tanggal</label>
                    <input type="date" name="tgl_mulai" value="<?= $tgl_mulai ?>">
                </div>
                <div class="filter-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" name="tgl_selesai" value="<?= $tgl_selesai ?>">
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
                <?php if($tgl_mulai): ?>
                    <a href="barang_keluar.php" class="btn-reset">Reset</a>
                <?php endif; ?>
            </form>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>
                                Nama Barang
                                <a href="?sort=asc<?= $tgl_mulai ? "&tgl_mulai=$tgl_mulai&tgl_selesai=$tgl_selesai" : "" ?>" class="sort-link <?= $sort_order == 'ASC' ? 'active' : '' ?>"><i class="fas fa-sort-alpha-up"></i></a>
                                <a href="?sort=desc<?= $tgl_mulai ? "&tgl_mulai=$tgl_mulai&tgl_selesai=$tgl_selesai" : "" ?>" class="sort-link <?= $sort_order == 'DESC' ? 'active' : '' ?>"><i class="fas fa-sort-alpha-down"></i></a>
                            </th>
                            <th>Jumlah</th>
                            <th>Total</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($res_riwayat) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($res_riwayat)): ?>
                            <tr>
                                <td style="font-family:'DM Mono',monospace; font-size:0.82rem; color:var(--text-muted);"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td style="font-weight:600;"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td><?= $row['jumlah'] ?></td>
                                <td class="total-bold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                <td style="text-align:center;">
                                    <a href="barang_keluar.php?hapus=<?= $row['id_keluar'] ?>" class="btn-del" onclick="return confirm('Hapus data?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">Data tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const barangs = <?php echo json_encode($barang_data); ?>;

function addRow() {
    const container = document.getElementById('batchContainer');
    const div = document.createElement('div');
    div.className = 'batch-row';
    
    let options = '<option value="">-- Pilih Barang --</option>';
    barangs.forEach(b => {
        options += `<option value="${b.id_barang}" data-harga="${b.harga_jual}">${b.nama_barang} (Stok: ${b.stok})</option>`;
    });

    div.innerHTML = `
        <div><select name="id_barang[]" required onchange="calculate()">${options}</select></div>
        <div><input type="number" name="jumlah[]" min="1" value="1" required oninput="calculate()"></div>
        <div class="subtotal" style="font-family:'DM Mono'; font-weight:700; color:var(--primary);">Rp 0</div>
        <div style="text-align:center;"><button type="button" class="btn-remove-row" onclick="this.parentElement.parentElement.remove(); calculate()"><i class="fas fa-trash-alt"></i></button></div>
    `;
    container.appendChild(div);
}

function calculate() {
    let grandTotal = 0;
    document.querySelectorAll('.batch-row').forEach(row => {
        const sel = row.querySelector('select');
        const hrg = sel.selectedOptions[0]?.dataset.harga || 0;
        const qty = row.querySelector('input').value || 0;
        const sub = hrg * qty;
        row.querySelector('.subtotal').textContent = "Rp " + sub.toLocaleString('id-ID');
        grandTotal += sub;
    });
    document.getElementById('grandTotal').textContent = "Rp " + grandTotal.toLocaleString('id-ID');
}

window.onload = addRow;
</script>
</body>
</html>
