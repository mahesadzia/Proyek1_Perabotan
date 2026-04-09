<?php
include 'konek.php';

// 1. Ambil Total Inventori (Jumlah total stok barang)
$query_total = mysqli_query($conn, "SELECT SUM(stok) as total FROM inventori_barang");
$row_total = mysqli_fetch_assoc($query_total);
$total_stok = $row_total['total'] ?? 0;

// 2. Ambil Stok Kritis (Urutkan dari yang paling sedikit, limit 5 barang)
$query_kritis = mysqli_query($conn, "SELECT nama_barang, stok FROM inventori_barang ORDER BY stok ASC LIMIT 5");

// 3. Data Dummy untuk Barang Masuk/Keluar & Omset (Bisa kamu ganti dengan query asli nanti)
$barang_masuk = 25;
$barang_keluar = 18;
$omset = "15.500.000";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<div class="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span>Admin</span>
    </div>

    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>

    <h3>TRANSAKSI</h3>
    <a href="pembelian.php"><i class="fas fa-shopping-cart"></i> Pembelian</a>
    <a href="penjualan.php"><i class="fas fa-file-invoice"></i> Penjualan</a>

    <h3>REPORT</h3>
    <a href="laporan_pembelian.php"><i class="fas fa-chart-line"></i> Laporan Pembelian</a>
    <a href="laporan_penjualan.php"><i class="fas fa-chart-bar"></i> Laporan Penjualan</a>

    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main-wrapper">
        <header>
            <h1>Dashboard</h1>
            <div class="user-welcome">Selamat datang, admin (User) <i class="fa fa-user-circle"></i></div>
        </header>

        <div class="stats-grid">
            <div class="card card-blue">
                <i class="fas fa-file-import icon-bg"></i>
                <h2><?php echo $barang_masuk; ?></h2>
                <p>Barang Masuk</p>
                <a href="#">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a>
            </div>

            <div class="card card-green">
                <i class="fas fa-file-export icon-bg"></i>
                <h2><?php echo $barang_keluar; ?></h2>
                <p>Barang Keluar</p>
                <a href="#">Lihat Detail <i class="fa fa-arrow-circle-right"></i></a>
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
                <a href="#">Lihat Laporan <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="bottom-grid">
        <div class="content-card">
            <h3><i class="fa fa-exclamation-triangle"></i> Stok Kritis</h3>
              <table class="table-kritis">
              <?php while($item = mysqli_fetch_assoc($query_kritis)): ?>
               <tr>
              <td><?php echo $item['nama_barang']; ?></td>
             <td class="text-right">
                <?php 
                    // Logika Status Dashboard
                    if ($item['stok'] == 0) {
                        echo '<span class="status-badge status-habis">Habis</span>';
                    } elseif ($item['stok'] <= 10) {
                        echo '<span class="status-badge status-menipis">Menipis</span>';
                    } else {
                        echo '<span class="status-badge status-aktif">Aman</span>';
                    }
                ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

            <div class="content-card">
                <h3><i class="fa fa-chart-line"></i> Omset Bulan Ini</h3>
                <div class="omset-value">Rp <?php echo $omset; ?></div>
                <p class="omset-sub">+12.5% dari bulan lalu</p>
            </div>
        </div>
    </div>

</body>
</html>