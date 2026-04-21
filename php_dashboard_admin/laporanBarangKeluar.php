<?php
include "konek.php"; // koneksi database

$query = "
SELECT 
    bk.id_keluar,
    bk.tanggal,
    ib.nama_barang,
    dbk.jumlah,
    ib.harga,
    (dbk.jumlah * ib.harga) AS total
FROM detail_barang_keluar dbk
JOIN barang_keluar bk ON dbk.id_keluar = bk.id_keluar
JOIN inventori_barang ib ON dbk.id_barang = ib.id_barang
ORDER BY bk.id_keluar DESC
";

$result = mysqli_query($conn, "SELECT * FROM detail_barang_keluar");
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Laporan Barang Keluar</title>

    <link rel="stylesheet" href="laporanBarangKeluar.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
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
      <a href="barang_masuk.php"
        ><i class="fas fa-shopping-cart"></i> Barang Masuk</a
      >
      <a href="barang_keluar.php"
        ><i class="fas fa-file-export"></i> Barang Keluar</a
      >

      <h3>REPORT</h3>
      <a href="laporanBarangMasuk.php"
        ><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a
      >
      <a href="#" class="active"
        ><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a
      >

      <a href="logout.php" class="logout">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>

    <div class="main">
      <header>
        <div class="header-title">
          <i class="fa fa-bars"></i> LAPORAN BARANG KELUAR
        </div>
        <div>Admin <i class="fa fa-user-circle"></i></div>
      </header>

      <div class="page-title">
        <h1>Laporan Barang Keluar</h1>
      </div>

      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fa fa-chart-bar"></i> Data Barang Keluar
          </h3>

          <div>
            <button class="btn btn-blue" disabled>
              <i class="fa fa-print"></i> Cetak
            </button>

            <button class="btn btn-green" disabled>
              <i class="fa fa-file-excel"></i> Excel
            </button>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th>No. Invoice</th>
              <th>Tanggal</th>
              <th>Item Jual</th>
              <th>Jumlah</th>
              <th>Harga Satuan</th>
              <th>Total</th>
              <th>Status</th>
            </tr>
          </thead>

          <tbody>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
              <td>INV-<?php echo $row['id_keluar']; ?></td>
              <td><?php echo $row['tanggal']; ?></td>
              <td><?php echo $row['nama_barang']; ?></td>
              <td><?php echo $row['jumlah']; ?></td>
              <td><?php echo number_format($row['harga']); ?></td>
              <td><?php echo number_format($row['total']); ?></td>
              <td><span class="status lunas">Lunas</span></td>
            </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </body>
</html>
