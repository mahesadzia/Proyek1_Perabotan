<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include 'konek.php';

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM inventori_barang WHERE id_barang = ?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
    header("Location: inventori.php"); exit;
}

if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_barang']; $beli = $_POST['harga_beli'];
    $jual = $_POST['harga_jual'];  $stok = $_POST['stok'];
    $stmt = $conn->prepare("INSERT INTO inventori_barang (nama_barang, harga_beli, harga_jual, stok) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sddi", $nama, $beli, $jual, $stok);
    $stmt->execute(); $stmt->close();
    header("Location: inventori.php"); exit;
}

if (isset($_POST['update'])) {
    $id = $_POST['id_barang']; $nama = $_POST['nama_barang'];
    $beli = $_POST['harga_beli']; $jual = $_POST['harga_jual']; $stok = $_POST['stok'];
    $stmt = $conn->prepare("UPDATE inventori_barang SET nama_barang=?, harga_beli=?, harga_jual=?, stok=? WHERE id_barang=?");
    $stmt->bind_param("sddii", $nama, $beli, $jual, $stok, $id);
    $stmt->execute(); $stmt->close();
    header("Location: inventori.php"); exit;
}

$result = mysqli_query($conn, "SELECT * FROM inventori_barang");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventori - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="inventoribarang.css">
    <link rel="stylesheet" href="responsive.css">
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard_karyawan.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php" class="active"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-boxes"></i> MANAJEMEN STOK</span>
        </div>
        <div style="font-size:0.875rem;font-weight:500;color:#718096;">
            <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fa fa-user-circle" style="color:#1565c0;"></i>
        </div>
    </header>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Inventori</h3>
            <button class="btn-blue" onclick="openModal('modalTambah')"><i class="fas fa-plus"></i> Tambah</button>
        </div>
        <!-- Wrapper scroll horizontal untuk tabel di mobile -->
        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Nama Barang</th><th>Harga Beli</th><th>Harga Jual</th>
                        <th>Stok</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#1565c0;">#<?php echo $row['id_barang']; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                        <td>Rp <?php echo number_format($row['harga_beli'], 0, ',', '.'); ?></td>
                        <td>Rp <?php echo number_format($row['harga_jual'], 0, ',', '.'); ?></td>
                        <td><?php echo $row['stok']; ?></td>
                        <td>
                            <?php
                            if ($row['stok'] == 0)       { $s="Habis";   $c="status-habis"; }
                            elseif ($row['stok'] <= 10)  { $s="Menipis"; $c="status-menipis"; }
                            else                          { $s="Aman";    $c="status-aktif"; }
                            echo "<span class='status-badge $c'>$s</span>";
                            ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="javascript:void(0)" onclick="fillEditForm('<?php echo $row['id_barang']; ?>','<?php echo addslashes($row['nama_barang']); ?>','<?php echo $row['harga_beli']; ?>','<?php echo $row['harga_jual']; ?>','<?php echo $row['stok']; ?>')" style="color:#1565c0;margin-right:12px;">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="inventori.php?hapus=<?php echo $row['id_barang']; ?>" onclick="return confirm('Hapus barang ini?')" style="color:#e74c3c;">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalTambah" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-plus" style="color:#1565c0;margin-right:8px;"></i>Tambah Barang Baru</h3>
        <form method="POST">
            <input type="text"   name="nama_barang" placeholder="Nama Barang"  required>
            <input type="number" name="harga_beli"  placeholder="Harga Beli"   required>
            <input type="number" name="harga_jual"  placeholder="Harga Jual"   required>
            <input type="number" name="stok"        placeholder="Stok Awal"    required>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" name="tambah" class="btn-blue">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-edit" style="color:#1565c0;margin-right:8px;"></i>Perbarui Data Barang</h3>
        <form method="POST">
            <input type="hidden" name="id_barang" id="edit_id">
            <input type="text"   name="nama_barang" id="edit_nama"  required>
            <input type="number" name="harga_beli"  id="edit_beli"  required>
            <input type="number" name="harga_jual"  id="edit_jual"  required>
            <input type="number" name="stok"        id="edit_stok"  required>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" name="update" class="btn-blue">Simpan</button>
            </div>
        </form>
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

function openModal(id)  { document.getElementById(id).style.display = "block"; }
function closeModal(id) { document.getElementById(id).style.display = "none"; }
function fillEditForm(id, nama, beli, jual, stok) {
    document.getElementById('edit_id').value   = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_beli').value = beli;
    document.getElementById('edit_jual').value = jual;
    document.getElementById('edit_stok').value = stok;
    openModal('modalEdit');
}
window.onclick = function(e) { if (e.target.className==='modal') e.target.style.display="none"; }
</script>
</body>
</html>
