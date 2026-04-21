<?php
$conn = mysqli_connect("localhost", "root", "", "balnis_db");

// ================== SIMPAN ==================
if (isset($_POST['simpan'])) {
    $tanggal   = $_POST['tanggal'];
    $id_barang = $_POST['id_barang'];
    $jumlah    = $_POST['jumlah'];

    $barang = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM inventori_barang WHERE id_barang='$id_barang'"
    ));

    $harga = $barang['harga_jual'];
    $stok  = $barang['stok'];

    if ($jumlah <= 0) {
        echo "<script>alert('Jumlah harus lebih dari 0');</script>";
    } elseif ($jumlah > $stok) {
        echo "<script>alert('Stok tidak cukup!');</script>";
    } else {
        $total = $jumlah * $harga;

        mysqli_query($conn, "
            INSERT INTO barang_keluar (tanggal, id_barang, jumlah, total)
            VALUES ('$tanggal', '$id_barang', '$jumlah', '$total')
        ");

        mysqli_query($conn, "
            UPDATE inventori_barang
            SET stok = stok - $jumlah
            WHERE id_barang = '$id_barang'
        ");

        echo "<script>alert('Transaksi berhasil!');</script>";
    }
}

// ================== HAPUS ==================
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    $data = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM barang_keluar WHERE id_keluar='$id'"
    ));

    $id_barang = $data['id_barang'];
    $jumlah    = $data['jumlah'];

    mysqli_query($conn, "
        UPDATE inventori_barang
        SET stok = stok + $jumlah
        WHERE id_barang = '$id_barang'
    ");

    mysqli_query($conn, "DELETE FROM barang_keluar WHERE id_keluar='$id'");

    echo "<script>alert('Data dihapus & stok dikembalikan!');</script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Penjualan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="barang_keluar.css">
</head>

<body>

<!-- ===== SIDEBAR ===== -->
<div class="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span>Admin</span>
    </div>

    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>

    <h3>TRANSAKSI</h3>
    <a href="pembelian.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php" class="active"><i class="fas fa-file-invoice"></i> Barang Keluar</a>

    <h3>REPORT</h3>
    <a href="laporan_pembelian.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporan_penjualan.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>

    <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

<!-- ===== MAIN WRAPPER ===== -->
<div class="main-wrapper">

    <!-- Header -->
    <header>
        <div style="font-weight: bold;"><i class="fa fa-bars"></i> TRANSAKSI PENJUALAN</div>
        <div>Admin <i class="fa fa-user-circle"></i></div>
    </header>

    <!-- Form Input -->
    <div class="content-card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Input Penjualan</h3>
        </div>

        <form method="POST">
            <div class="form-grid">

                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required>
                </div>

                <div class="form-group">
                    <label>Barang</label>
                    <select name="id_barang" id="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php
                        $barang = mysqli_query($conn, "SELECT * FROM inventori_barang");
                        while ($b = mysqli_fetch_assoc($barang)) {
                            echo "<option value='{$b['id_barang']}' data-harga='{$b['harga_jual']}'>
                                    {$b['nama_barang']} | Stok: {$b['stok']} | Rp " . number_format($b['harga_jual'], 0, ',', '.') . "
                                  </option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" id="jumlah" min="1" required>
                </div>

                <div class="form-group">
                    <label>Total Harga</label>
                    <input type="text" id="total_display" placeholder="Rp 0" readonly class="input-total">
                    <input type="hidden" name="total_harga" id="total_harga">
                </div>

            </div>

            <button type="submit" name="simpan" class="btn-submit">
                <i class="fas fa-save"></i> Simpan
            </button>
        </form>
    </div>

    <!-- Tabel Riwayat -->
    <div class="content-card" style="margin-top: 25px;">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> Riwayat Penjualan</h3>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Barang</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no   = 1;
                $data = mysqli_query($conn, "
                    SELECT bk.*, ib.nama_barang
                    FROM barang_keluar bk
                    JOIN inventori_barang ib ON bk.id_barang = ib.id_barang
                    ORDER BY bk.id_keluar DESC
                ");

                while ($row = mysqli_fetch_assoc($data)) :
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= $row['tanggal'] ?></td>
                    <td><?= $row['nama_barang'] ?></td>
                    <td><?= $row['jumlah'] ?></td>
                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    <td class="text-center">
                        <a href="?hapus=<?= $row['id_keluar'] ?>"
                           onclick="return confirm('Hapus data ini?')"
                           class="btn-action btn-delete">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div><!-- /.main-wrapper -->

<script>
    const selectBarang = document.getElementById('id_barang');
    const inputJumlah  = document.getElementById('jumlah');
    const totalDisplay = document.getElementById('total_display');
    const totalHarga   = document.getElementById('total_harga');

    function hitungTotal() {
        const harga  = parseFloat(selectBarang.selectedOptions[0]?.dataset.harga) || 0;
        const jumlah = parseFloat(inputJumlah.value) || 0;
        const total  = harga * jumlah;

        totalHarga.value   = total;
        totalDisplay.value = total > 0
            ? 'Rp ' + total.toLocaleString('id-ID')
            : 'Rp 0';
    }

    selectBarang.addEventListener('change', hitungTotal);
    inputJumlah.addEventListener('input', hitungTotal);
</script>

</body>
</html>