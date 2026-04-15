<?php 
include 'konek.php'; 

// --- LOGIKA HAPUS ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM inventori_barang WHERE id_barang = '$id'");
    header("Location: inventori.php");
    exit;
}

// --- LOGIKA TAMBAH ---
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_barang'];
    $beli = $_POST['harga_beli'];
    $jual = $_POST['harga_jual'];
    $stok = $_POST['stok'];
    mysqli_query($conn, "INSERT INTO inventori_barang (nama_barang, harga_beli, harga_jual, stok) VALUES ('$nama', '$beli', '$jual', '$stok')");
    header("Location: inventori.php");
    exit;
}

// --- LOGIKA UPDATE ---
if (isset($_POST['update'])) {
    $id = $_POST['id_barang'];
    $nama = $_POST['nama_barang'];
    $beli = $_POST['harga_beli'];
    $jual = $_POST['harga_jual'];
    $stok = $_POST['stok'];
    mysqli_query($conn, "UPDATE inventori_barang SET nama_barang='$nama', harga_beli='$beli', harga_jual='$jual', stok='$stok' WHERE id_barang='$id'");
    header("Location: inventori.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM inventori_barang");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventaris - Perabotan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="inventoribarang.css">
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
        <div style="font-weight:bold;"><i class="fa fa-bars"></i> MANAJEMEN STOK</div>
        <div>Admin <i class="fa fa-user-circle"></i></div>
    </header>

    <div class="card">
        <div class="card-header">
            <h3 style="margin:0">Daftar Inventori</h3>
            <button class="btn-blue" onclick="openModal('modalTambah')">+ Tambah Barang</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Beli</th>
                    <th>Jual</th>
                    <th>Stok</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo $row['id_barang']; ?></td>
                    <td><?php echo $row['nama_barang']; ?></td>
                    <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                    <td>Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                    <td><?php echo $row['stok']; ?></td>
<td>
    <?php 
        // Logika Status 3 Kondisi
        if ($row['stok'] == 0) {
            $status = "Habis";
            $class = "status-habis";
        } elseif ($row['stok'] <= 10) {
            $status = "Menipis";
            $class = "status-menipis";
        } else {
            $status = "Aman";
            $class = "status-aktif";
        }
    ?>
    <span class="status-badge <?php echo $class; ?>">
        <?php echo $status; ?>
    </span>
</td>
                    <td>
                        <a href="javascript:void(0)" onclick="fillEditForm('<?php echo $row['id_barang']; ?>', '<?php echo addslashes($row['nama_barang']); ?>', '<?php echo $row['harga_beli']; ?>', '<?php echo $row['harga_jual']; ?>', '<?php echo $row['stok']; ?>')" style="color: var(--primary); margin-right: 10px;">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="inventori.php?hapus=<?php echo $row['id_barang']; ?>" onclick="return confirm('Hapus barang ini?')" style="color: var(--danger);">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalTambah" class="modal">
    <div class="modal-content">
        <h3>Tambah Barang Baru</h3>
        <form method="POST">
            <input type="text" name="nama_barang" placeholder="Nama Barang" required>
            <input type="number" name="harga_beli" placeholder="Harga Beli" required>
            <input type="number" name="harga_jual" placeholder="Harga Jual" required>
            <input type="number" name="stok" placeholder="Stok" required>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" name="tambah" class="btn-blue">Simpan Data</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEdit" class="modal">
    <div class="modal-content">
        <h3>Perbarui Data Barang</h3>
        <form method="POST">
            <input type="hidden" name="id_barang" id="edit_id">
            <input type="text" name="nama_barang" id="edit_nama" required>
            <input type="number" name="harga_beli" id="edit_beli" required>
            <input type="number" name="harga_jual" id="edit_jual" required>
            <input type="number" name="stok" id="edit_stok" required>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" name="update" class="btn-blue">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = "block"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }

    function fillEditForm(id, nama, beli, jual, stok) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_beli').value = beli;
        document.getElementById('edit_jual').value = jual;
        document.getElementById('edit_stok').value = stok;
        openModal('modalEdit');
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = "none";
        }
    }
</script>

</body>
</html>