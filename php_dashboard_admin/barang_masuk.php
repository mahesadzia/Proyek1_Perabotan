<?php
include 'konek.php';

$notif = "";
if (isset($_POST['simpan_masuk'])) {
    $tgl_masuk   = $_POST['tanggal_masuk'];
    $id_supplier = $_POST['id_supplier'];
    $id_user     = 1; // Contoh id_user admin
    
    // Data Detail Barang
    $id_barang  = $_POST['id_barang'];
    $jumlah     = $_POST['jumlah'];
    $harga_beli = $_POST['harga_beli'];

    // 1. Simpan ke Tabel Header (barang_masuk)
    $sql_header = "INSERT INTO barang_masuk (tanggal_masuk, id_supplier, id_user) 
                   VALUES ('$tgl_masuk', '$id_supplier', '$id_user')";
    
    if (mysqli_query($conn, $sql_header)) {
        $id_masuk_terakhir = mysqli_insert_id($conn); // Ambil ID yang baru saja dibuat

        // 2. Simpan ke Tabel Detail (detail_barang_masuk)
        $sql_detail = "INSERT INTO detail_barang_masuk (id_masuk, id_barang, jumlah, harga_beli) 
                       VALUES ('$id_masuk_terakhir', '$id_barang', '$jumlah', '$harga_beli')";
        
        // 3. Update Stok di tabel Inventori
        $sql_update_stok = "UPDATE inventori_barang SET stok = stok + $jumlah WHERE id_barang = '$id_barang'";

        if (mysqli_query($conn, $sql_detail) && mysqli_query($conn, $sql_update_stok)) {
            $notif = "sukses";
        }
    } else {
        $notif = "gagal";
    }
}

// Ambil data untuk dropdown
$res_barang = mysqli_query($conn, "SELECT * FROM inventori_barang");
// Karena kamu ada id_supplier di tabel, pastikan ada tabel supplier atau buat manual sementara
?>

<!DOCTYPE html>
<html>
<head>
    <title>Barang Masuk - Detail System</title>
    <link rel="stylesheet" href="barang_masuk.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>

    <h3>REPORT</h3>
    <a href="laporan_pembelian.php"><i class="fas fa-chart-line"></i> Laporan Pembelian</a>
    <a href="laporan_penjualan.php"><i class="fas fa-chart-bar"></i> Laporan Penjualan</a>

    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

    <div class="main-wrapper">
        <h1>Input Detail Barang Masuk</h1>

        <?php if($notif == "sukses"): ?>
            <div style="background:#2ecc71; color:white; padding:10px; border-radius:5px; margin-bottom:15px;">Data Berhasil Disimpan!</div>
        <?php endif; ?>

        <div class="content-card">
            <form action="" method="POST">
                <div class="content-grid">
                    <div>
                        <h3>Info Transaksi</h3><br>
                        <label>Tanggal Masuk</label>
                        <input type="date" name="tanggal_masuk" required style="width:100%; padding:8px; margin:10px 0;">
                        
                        <label>ID Supplier</label>
                        <input type="number" name="id_supplier" placeholder="Masukkan ID Supplier" required style="width:100%; padding:8px; margin:10px 0;">
                    </div>

                    <div>
                        <h3>Detail Barang</h3><br>
                        <label>Pilih Barang</label>
                        <select name="id_barang" required style="width:100%; padding:8px; margin:10px 0;">
                            <?php while($b = mysqli_fetch_assoc($res_barang)): ?>
                                <option value="<?= $b['id_barang']; ?>"><?= $b['nama_barang']; ?></option>
                            <?php endwhile; ?>
                        </select>

                        <label>Jumlah</label>
                        <input type="number" name="jumlah" placeholder="0" required style="width:100%; padding:8px; margin:10px 0;">

                        <label>Harga Beli (Per Item)</label>
                        <input type="number" name="harga_beli" placeholder="Rp" required style="width:100%; padding:8px; margin:10px 0;">
                    </div>
                </div>
                <button type="submit" name="simpan_masuk" class="btn-submit" style="margin-top:20px;">Simpan Transaksi Masuk</button>
            </form>
        </div>

        <div class="content-card" style="margin-top:20px;">
            <h3>Log Transaksi Terbaru</h3>
            <table>
                <thead>
                    <tr>
                        <th>Tgl Masuk</th>
                        <th>Barang</th>
                        <th>Qty</th>
                        <th>Harga Beli</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $log = mysqli_query($conn, "SELECT h.tanggal_masuk, b.nama_barang, d.jumlah, d.harga_beli 
                                              FROM detail_barang_masuk d
                                              JOIN barang_masuk h ON d.id_masuk = h.id_masuk
                                              JOIN inventori_barang b ON d.id_barang = b.id_barang
                                              ORDER BY h.id_masuk DESC LIMIT 5");
                    while($l = mysqli_fetch_assoc($log)): ?>
                    <tr>
                        <td><?= $l['tanggal_masuk']; ?></td>
                        <td><?= $l['nama_barang']; ?></td>
                        <td><?= $l['jumlah']; ?></td>
                        <td>Rp <?= number_format($l['harga_beli']); ?></td>
                        <td>Rp <?= number_format($l['jumlah'] * $l['harga_beli']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>