<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include "konek.php";

$result = mysqli_query($conn, "
    SELECT bk.id_keluar, bk.tanggal, ib.nama_barang,
           dbk.jumlah, ib.harga_jual AS harga,
           (dbk.jumlah * ib.harga_jual) AS total
    FROM detail_barang_keluar dbk
    JOIN barang_keluar bk ON dbk.id_keluar = bk.id_keluar
    JOIN inventori_barang ib ON dbk.id_barang = ib.id_barang
    ORDER BY bk.id_keluar DESC
");
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Keluar</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="laporanBarangKeluar.css">
    <link rel="stylesheet" href="responsive.css">
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
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>REPORT</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporanBarangKeluar.php" class="active"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <header>
        <div class="header-title" style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-chart-bar"></i> LAPORAN BARANG KELUAR</span>
        </div>
        <div style="font-size:0.875rem;font-weight:500;color:#718096;">
            <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fa fa-user-circle" style="color:#1565c0;"></i>
        </div>
    </header>

    <div class="page-title"><h1>Laporan Barang Keluar</h1></div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-bar" style="color:#1565c0;margin-right:7px;"></i> Data Barang Keluar</h3>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-blue" disabled><i class="fa fa-print"></i> Cetak</button>
                <button class="btn btn-green" disabled><i class="fa fa-file-excel"></i> Excel</button>
            </div>
        </div>
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table>
                <thead>
                    <tr>
                        <th>No. Invoice</th><th>Tanggal</th><th>Item Jual</th>
                        <th>Jumlah</th><th>Harga Satuan</th><th>Total</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#1565c0;">INV-<?php echo $row['id_keluar']; ?></td>
                        <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#718096;"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                        <td><?php echo $row['jumlah']; ?></td>
                        <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                        <td><strong>Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></strong></td>
                        <td><span class="status lunas">Lunas</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('overlay');

function openSidebar()  { sidebar.classList.add('open'); overlay.classList.add('active'); hamburger.classList.add('open'); document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); hamburger.classList.remove('open'); document.body.style.overflow=''; }

hamburger.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
overlay.addEventListener('click', closeSidebar);
document.addEventListener('keydown', e => { if (e.key==='Escape') closeSidebar(); });
</script>
</body>
</html>
