<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'konek.php';

$query_total   = mysqli_query($conn, "SELECT SUM(stok) as total FROM inventori_barang");
$total_stok    = mysqli_fetch_assoc($query_total)['total'] ?? 0;
$query_kritis  = mysqli_query($conn, "SELECT nama_barang, stok FROM inventori_barang ORDER BY stok ASC LIMIT 5");

$q_masuk       = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_masuk");
$barang_masuk  = mysqli_fetch_assoc($q_masuk)['total'] ?? 0;

$q_keluar      = mysqli_query($conn, "SELECT COUNT(*) as total FROM barang_keluar");
$barang_keluar = mysqli_fetch_assoc($q_keluar)['total'] ?? 0;

$q_omset       = mysqli_query($conn, "SELECT SUM(dbk.jumlah * ib.harga_jual) as omset FROM detail_barang_keluar dbk JOIN inventori_barang ib ON dbk.id_barang = ib.id_barang");
$omset         = number_format(mysqli_fetch_assoc($q_omset)['omset'] ?? 0, 0, ',', '.');
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
            <h3><i class="fa fa-chart-line"></i> Omset Bulan Ini</h3>
            <div class="omset-value">Rp <?php echo $omset; ?></div>
            <p class="omset-sub">Total dari semua transaksi keluar</p>
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
