<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'konek.php';

// ─── Data user yang sedang login ──────────────────────────────────────────────
$uid = $_SESSION['user_id'];
$q_user = $conn->prepare("SELECT username, email, role, status, last_login, created_at FROM users WHERE id = ?");
$q_user->bind_param("i", $uid);
$q_user->execute();
$user_data = $q_user->get_result()->fetch_assoc();
$q_user->close();
$inisial = strtoupper(substr($user_data['username'] ?? 'A', 0, 1));

$query_total   = mysqli_query($conn, "SELECT SUM(stok) as total FROM inventori_barang");
$total_stok    = mysqli_fetch_assoc($query_total)['total'] ?? 0;
$query_kritis  = mysqli_query($conn, "SELECT nama_barang, stok FROM inventori_barang ORDER BY stok ASC LIMIT 5");

$q_masuk       = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_masuk");
$barang_masuk  = mysqli_fetch_assoc($q_masuk)['total'] ?? 0;

$q_keluar      = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_keluar");
$barang_keluar = mysqli_fetch_assoc($q_keluar)['total'] ?? 0;

// ─── Omset per bulan (keluar - masuk) ─────────────────────────────────────────
$bulan_ini = date('Y-m');

// Total penjualan (barang keluar) per bulan
$q_keluar_bulan = mysqli_query($conn, "
    SELECT DATE_FORMAT(tanggal, '%Y-%m') as bulan,
           DATE_FORMAT(tanggal, '%M %Y') as label,
           SUM(total) as total_keluar
    FROM barang_keluar
    GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
    ORDER BY bulan DESC
    LIMIT 6
");
$data_keluar = [];
while ($r = mysqli_fetch_assoc($q_keluar_bulan)) {
    $data_keluar[$r['bulan']] = ['label' => $r['label'], 'keluar' => $r['total_keluar']];
}

// Total pembelian (barang masuk) per bulan
$q_masuk_bulan = mysqli_query($conn, "
    SELECT DATE_FORMAT(bm.tanggal_masuk, '%Y-%m') as bulan,
           SUM(dbm.jumlah * dbm.harga_beli) as total_masuk
    FROM barang_masuk bm
    JOIN detail_barang_masuk dbm ON bm.id_masuk = dbm.id_masuk
    GROUP BY DATE_FORMAT(bm.tanggal_masuk, '%Y-%m')
");
$data_masuk = [];
while ($r = mysqli_fetch_assoc($q_masuk_bulan)) {
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
$q_omset_ini = mysqli_query($conn, "SELECT SUM(total) as omset FROM barang_keluar WHERE DATE_FORMAT(tanggal,'%Y-%m') = '$bulan_ini'");
$omset_ini   = mysqli_fetch_assoc($q_omset_ini)['omset'] ?? 0;
$q_modal_ini = mysqli_query($conn, "
    SELECT SUM(dbm.jumlah * dbm.harga_beli) as modal
    FROM barang_masuk bm
    JOIN detail_barang_masuk dbm ON bm.id_masuk = dbm.id_masuk
    WHERE DATE_FORMAT(bm.tanggal_masuk,'%Y-%m') = '$bulan_ini'
");
$modal_ini   = mysqli_fetch_assoc($q_modal_ini)['modal'] ?? 0;
$laba_ini    = $omset_ini - $modal_ini;
$omset       = number_format($omset_ini, 0, ',', '.');
$laba        = number_format($laba_ini, 0, ',', '.');
$laba_class  = $laba_ini >= 0 ? 'laba-positif' : 'laba-negatif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporanBarangKeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-th-large"></i> DASHBOARD</span>
        </div>
        <div style="position:relative;">
            <button class="admin-header-btn" id="adminPopupBtn" onclick="toggleAdminPopup()" aria-haspopup="true">
                <div class="avatar-circle"><?php echo $inisial; ?></div>
                <span style="max-width:110px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($user_data['username']); ?></span>
                <i class="fas fa-chevron-down chevron-icon"></i>
            </button>
            <div class="admin-popup" id="adminPopup">
                <div class="popup-header">
                    <div class="popup-avatar-large"><?php echo $inisial; ?></div>
                    <div class="popup-header-info">
                        <div class="popup-name"><?php echo htmlspecialchars($user_data['username']); ?></div>
                        <div class="popup-role-badge">
                            <i class="fas fa-shield-alt"></i>
                            <?php echo ucfirst($user_data['role'] ?? 'admin'); ?>
                        </div>
                    </div>
                </div>
                <div class="popup-body">
                    <div class="popup-row">
                        <i class="fas fa-envelope"></i>
                        <span class="popup-row-val"><?php echo htmlspecialchars($user_data['email'] ?? '-'); ?></span>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-circle" style="color:#48bb78;font-size:0.6rem;"></i>
                        <span>Status:&nbsp;</span>
                        <span class="popup-row-val"><span class="status-dot"></span><?php echo ucfirst($user_data['status'] ?? 'active'); ?></span>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-clock"></i>
                        <span>Login terakhir:&nbsp;</span>
                        <span class="popup-row-val">
                            <?php echo $user_data['last_login'] ? date('d M Y H:i', strtotime($user_data['last_login'])) : '-'; ?>
                        </span>
                    </div>
                    <div class="popup-row">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Bergabung:&nbsp;</span>
                        <span class="popup-row-val">
                            <?php echo $user_data['created_at'] ? date('d M Y', strtotime($user_data['created_at'])) : '-'; ?>
                        </span>
                    </div>
                    <div class="popup-divider"></div>
                </div>
                <div class="popup-footer">
                    <a href="logout.php" class="popup-logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Keluar dari Akun
                    </a>
                </div>
            </div>
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
            <a href="laporanBarangKeluar.php">Lihat Laporan <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="bottom-grid">
        <div class="content-card">
            <h3><i class="fa fa-exclamation-triangle"></i> Stok Kritis</h3>
            <table class="table-kritis">
                <?php while($item = mysqli_fetch_assoc($query_kritis)): ?>
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
function toggleAdminPopup() {
    const btn   = document.getElementById('adminPopupBtn');
    const popup = document.getElementById('adminPopup');
    if (!btn || !popup) return;
    const isOpen = popup.classList.contains('popup-show');
    if (isOpen) {
        popup.classList.remove('popup-show');
        btn.classList.remove('popup-open');
    } else {
        popup.classList.add('popup-show');
        btn.classList.add('popup-open');
    }
}
document.addEventListener('click', function(e) {
    const btn   = document.getElementById('adminPopupBtn');
    const popup = document.getElementById('adminPopup');
    if (btn && popup && !btn.contains(e.target) && !popup.contains(e.target)) {
        popup.classList.remove('popup-show');
        btn.classList.remove('popup-open');
    }
});

</script>
</body>
</html>
