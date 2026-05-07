<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../login.php"); 
    exit(); 
}

try {
    include 'konek.php'; // Pastikan konek.php sudah menggunakan PDO
    
    // Total stok inventori
    $query_total = $conn->query("SELECT SUM(stok) as total FROM inventori_barang");
    $total_stok = $query_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Stok kritis
    $query_kritis = $conn->query("SELECT nama_barang, stok FROM inventori_barang ORDER BY stok ASC LIMIT 5");
    
    // Total barang masuk
    $q_masuk = $conn->query("SELECT COUNT(*) as total FROM barang_masuk");
    $barang_masuk = $q_masuk->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total barang keluar
    $q_keluar = $conn->query("SELECT COUNT(*) as total FROM barang_keluar");
    $barang_keluar = $q_keluar->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // ─── Omset per bulan (keluar - masuk) ─────────────────────────────────────────
    $bulan_ini = date('Y-m');
    
    // Total penjualan (barang keluar) per bulan
    $q_keluar_bulan = $conn->query("
        SELECT DATE_FORMAT(tanggal, '%Y-%m') as bulan,
               DATE_FORMAT(tanggal, '%M %Y') as label,
               SUM(total) as total_keluar
        FROM barang_keluar
        GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
        ORDER BY bulan DESC
        LIMIT 6
    ");
    $data_keluar = [];
    while ($r = $q_keluar_bulan->fetch(PDO::FETCH_ASSOC)) {
        $data_keluar[$r['bulan']] = ['label' => $r['label'], 'keluar' => $r['total_keluar']];
    }
    
    // Total pembelian (barang masuk) per bulan
    $q_masuk_bulan = $conn->query("
        SELECT DATE_FORMAT(bm.tanggal_masuk, '%Y-%m') as bulan,
               SUM(dbm.jumlah * dbm.harga_beli) as total_masuk
        FROM barang_masuk bm
        JOIN detail_barang_masuk dbm ON bm.id_masuk = dbm.id_masuk
        GROUP BY DATE_FORMAT(bm.tanggal_masuk, '%Y-%m')
    ");
    $data_masuk = [];
    while ($r = $q_masuk_bulan->fetch(PDO::FETCH_ASSOC)) {
        $data_masuk[$r['bulan']] = $r['total_masuk'];
    }
    
    // Gabungkan semua bulan yang ada
    $semua_bulan = array_unique(array_merge(array_keys($data_keluar), array_keys($data_masuk)));
    rsort($semua_bulan);
    $semua_bulan = array_slice($semua_bulan, 0, 6);
    
    $laporan_bulan = [];
    foreach ($semua_bulan as $b) {
        $keluar = $data_keluar[$b]['keluar'] ?? 0;
        $masuk  = $data_masuk[$b] ?? 0;
        $label  = $data_keluar[$b]['label'] ?? date('F Y', strtotime($b . '-01'));
        $laporan_bulan[] = [
            'bulan'  => $b,
            'label'  => $label,
            'keluar' => $keluar,
            'masuk'  => $masuk,
            'laba'   => $keluar - $masuk,
        ];
    }
    
    // Omset bulan ini (untuk card)
    $q_omset_ini = $conn->prepare("SELECT SUM(total) as omset FROM barang_keluar WHERE DATE_FORMAT(tanggal,'%Y-%m') = ?");
    $q_omset_ini->execute([$bulan_ini]);
    $omset_ini = $q_omset_ini->fetch(PDO::FETCH_ASSOC)['omset'] ?? 0;
    
    $q_modal_ini = $conn->prepare("
        SELECT SUM(dbm.jumlah * dbm.harga_beli) as modal
        FROM barang_masuk bm
        JOIN detail_barang_masuk dbm ON bm.id_masuk = dbm.id_masuk
        WHERE DATE_FORMAT(bm.tanggal_masuk,'%Y-%m') = ?
    ");
    $q_modal_ini->execute([$bulan_ini]);
    $modal_ini = $q_modal_ini->fetch(PDO::FETCH_ASSOC)['modal'] ?? 0;
    
    $laba_ini = $omset_ini - $modal_ini;
    $omset = number_format($omset_ini, 0, ',', '.');
    $laba = number_format($laba_ini, 0, ',', '.');
    $laba_class = $laba_ini >= 0 ? 'laba-positif' : 'laba-negatif';

} catch(PDOException $e) {
    // Handle error - log error dan tampilkan pesan ramah pengguna
    error_log("Database error in dashboard: " . $e->getMessage());
    $total_stok = 0;
    $barang_masuk = 0;
    $barang_keluar = 0;
    $omset_ini = 0;
    $modal_ini = 0;
    $laba_ini = 0;
    $laporan_bulan = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard_admin.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard_admin.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> barang_masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> barang_keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> laporan_barang_masuk</a>
    <a href="laporan_barangkeluar.php"><i class="fas fa-chart-bar"></i> laporan_barang_keluar</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-th-large"></i> DASHBOARD</span>
        </div>
        <div style="font-size:0.875rem;font-weight:500;color:#718096;">
            <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fa fa-user-circle" style="color:#1565c0;"></i>
        </div>
    </header>

    <div class="stats-grid">
        <div class="card card-blue">
            <i class="fas fa-file-import icon-bg"></i>
            <h2><?php echo $barang_masuk; ?></h2>
            <p>Barang Masuk</p>
            <a href="barang_masuk.php">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
        <div class="card card-green">
            <i class="fas fa-file-export icon-bg"></i>
            <h2><?php echo $barang_keluar; ?></h2>
            <p>Barang Keluar</p>
            <a href="barang_keluar.php">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
        <div class="card card-yellow">
            <i class="fas fa-warehouse icon-bg"></i>
            <h2><?php echo number_format($total_stok, 0, ',', '.'); ?></h2>
            <p>Total Inventori</p>
            <a href="inventori.php">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a>
        </div>
        <div class="card card-red">
            <i class="fas fa-file-alt icon-bg"></i>
            <h2>Laporan</h2>
            <p>Data Bulanan</p>
            <a href="laporan_barangkeluar.php">Lihat Laporan <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="content-card">
            <h3><i class="fa fa-exclamation-triangle"></i> Stok Kritis</h3>
            <table class="table-kritis">
                <?php while($item = $query_kritis->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                    <td class="text-right">
                        <?php
                        if ($item['stok'] == 0)      echo '<span class="status-badge status-habis">Habis</span>';
                        elseif ($item['stok'] <= 10) echo '<span class="status-badge status-menipis">Menipis</span>';
                        else                          echo '<span class="status-badge status-aktif">Aman</span>';
                        ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        
        <div class="content-card">
            <h3><i class="fa fa-chart-line"></i> Laporan Laba per Bulan</h3>

            <div class="omset-bulan-ini">
                <div class="omset-row">
                    <span class="omset-label"><i class="fas fa-arrow-up" style="color:#22c55e;"></i> Penjualan Bulan Ini</span>
                    <span class="omset-val">Rp <?php echo $omset; ?></span>
                </div>
                <div class="omset-row">
                    <span class="omset-label"><i class="fas fa-arrow-down" style="color:#ef4444;"></i> Pembelian Bulan Ini</span>
                    <span class="omset-val">Rp <?php echo number_format($modal_ini, 0, ',', '.'); ?></span>
                </div>
                <div class="omset-row omset-laba">
                    <span class="omset-label"><i class="fas fa-wallet"></i> Laba Bersih</span>
                    <span class="omset-val <?php echo $laba_class; ?>">Rp <?php echo $laba; ?></span>
                </div>
            </div>

            <?php if (!empty($laporan_bulan)): ?>
            <div class="tabel-bulan-wrap">
                <table class="tabel-bulan">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Penjualan</th>
                            <th>Pembelian</th>
                            <th>Laba</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($laporan_bulan as $lb): ?>
                        <tr>
                            <td><?= htmlspecialchars($lb['label']) ?></td>
                            <td class="num">Rp <?= number_format($lb['keluar'], 0, ',', '.') ?></td>
                            <td class="num">Rp <?= number_format($lb['masuk'],  0, ',', '.') ?></td>
                            <td class="num <?= $lb['laba'] >= 0 ? 'laba-positif' : 'laba-negatif' ?>">
                                <?= $lb['laba'] >= 0 ? '+' : '' ?>Rp <?= number_format(abs($lb['laba']), 0, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    hamburger.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    hamburger.classList.remove('open');
    document.body.style.overflow = '';
}

hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
</script>
</body>
</html>