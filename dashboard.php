<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? 'Admin';

// bates
$barang_masuk = 25;
$barang_keluar = 18;
$total_inventori = 150;
$omset = '15.500.000';
$stok_kritis = [
    ['nama' => 'Ember besar', 'status' => 'kurang'],
    ['nama' => 'Blender', 'status' => 'kurang'],
    ['nama' => 'Panci', 'status' => 'aman']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventori Barang - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="sidebar">
        <div class="admin-profile">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
        
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="inventori.php"><i class="fas fa-boxes-stacked"></i> Inventori</a>
        
        <h3>Transaksi</h3>
        <a href="pembelian.php"><i class="fas fa-shopping-cart"></i> Pembelian</a>
        <a href="penjualan.php"><i class="fas fa-file-invoice"></i> Penjualan</a>
        
        <h3>Report</h3>
        <a href="laporan_pembelian.php"><i class="fas fa-chart-line"></i> Laporan Pembelian</a>
        <a href="laporan_penjualan.php"><i class="fas fa-chart-bar"></i> Laporan Penjualan</a>
        
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Dashboard</h2>
            <div class="user-info">
                <span>Selamat datang</span>
                <i class="fas fa-user-circle"></i>
            </div>
        </div>

        <div class="card-container">
            <div class="card blue">
                <i class="fas fa-file-import main-card-icon"></i>
                <h3><?php echo $barang_masuk; ?></h3>
                <p>Barang Masuk</p>
                <a href="pembelian.php" class="more-info-btn">
                    <span>Lihat Detail</span>
                    <i class="fas fa-arrow-circle-right small-arrow"></i>
                </a>
            </div>

            <div class="card green">
                <i class="fas fa-file-export main-card-icon"></i>
                <h3><?php echo $barang_keluar; ?></h3>
                <p>Barang Keluar</p>
                <a href="penjualan.php" class="more-info-btn">
                    <span>Lihat Detail</span>
                    <i class="fas fa-arrow-circle-right small-arrow"></i>
                </a>
            </div>

            <div class="card orange">
                <i class="fas fa-warehouse main-card-icon"></i>
                <h3><?php echo $total_inventori; ?></h3>
                <p>Total Inventori</p>
                <a href="inventori.php" class="more-info-btn">
                    <span>Lihat Detail</span>
                    <i class="fas fa-arrow-circle-right small-arrow"></i>
                </a>
            </div>

            <div class="card red">
                <i class="fas fa-file-contract main-card-icon"></i>
                <h3>Laporan</h3>
                <a href="laporan.php" class="more-info-btn">
                    <span>Lihat Laporan</span>
                    <i class="fas fa-arrow-circle-right small-arrow"></i>
                </a>
            </div>
        </div>

        <div class="info-container">
            <div class="info-panel info-stock">
                <h3><i class="fas fa-exclamation-triangle"></i> Stok Kritis</h3>
                <ul>
                    <?php foreach ($stok_kritis as $item): ?>
                    <li>
                        <span><?php echo htmlspecialchars($item['nama']); ?></span>
                        <span class="stok-status <?php echo $item['status']; ?>">
                            <?php echo ucfirst($item['status']); ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="info-panel omset-penjualan">
                <h3><i class="fas fa-chart-line"></i> Omset Bulan Ini</h3>
                <p class="omset-value">Rp <?php echo $omset; ?></p>
                <p style="color: #666; margin-top: 10px;">+12.5% dari bulan lalu</p>
            </div>
        </div>
    </div>

    <script>
        function toggleProfile() {
            const profileLinks = document.querySelector('.profile-links');
            if (profileLinks) {
                profileLinks.style.display = profileLinks.style.display === 'block' ? 'none' : 'block';
            }
        }
    </script>
</body>
</html>
