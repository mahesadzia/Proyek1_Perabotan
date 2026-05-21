<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
include 'konek.php';

// --- LOGIKA PROSES (BACKEND) ---

// 1. Proses Hapus dengan Alasan & Audit Stok
if (isset($_POST['konfirmasi_hapus'])) {
    $id = $_POST['id_hapus'];
    $alasan = $_POST['alasan_hapus'];
    $admin = $_SESSION['username'];

    // Ambil data stok terakhir sebelum dihapus
    $res = $conn->query("SELECT nama_barang, stok FROM inventori_barang WHERE id_barang = '$id'");
    $data = $res->fetch_assoc();
    $nama_brg = $data['nama_barang'] ?? "Tidak Diketahui";
    $stok_lama = $data['stok'] ?? 0;
    $selisih = 0 - $stok_lama; // Karena dihapus, stok jadi 0

    // Simpan ke riwayat_inventori dengan detail stok
    $stmt_log = $conn->prepare("INSERT INTO riwayat_inventori (id_barang, nama_barang, aksi, stok_lama, stok_baru, selisih, alasan, user_admin) VALUES (?, ?, 'HAPUS', ?, 0, ?, ?, ?)");
    $stmt_log->bind_param("isiisss", $id, $nama_brg, $stok_lama, $selisih, $alasan, $admin);
    $stmt_log->execute();

    // Hapus data barang asli
    $stmt = $conn->prepare("DELETE FROM inventori_barang WHERE id_barang = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    header("Location: inventori.php"); exit;
}

// 2. Proses Tambah Barang Baru
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_barang']; $beli = $_POST['harga_beli'];
    $jual = $_POST['harga_jual'];  $stok = $_POST['stok'];
    $stmt = $conn->prepare("INSERT INTO inventori_barang (nama_barang, harga_beli, harga_jual, stok) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sddi", $nama, $beli, $jual, $stok);
    $stmt->execute(); $stmt->close();
    header("Location: inventori.php"); exit;
}

// 3. Proses Update / Stock Opname
if (isset($_POST['update'])) {
    $id = $_POST['id_barang']; 
    $nama = $_POST['nama_barang'];
    $beli = $_POST['harga_beli']; 
    $jual = $_POST['harga_jual']; 
    $stok_baru = $_POST['stok'];
    $alasan = $_POST['alasan_edit'];
    $admin = $_SESSION['username'];

    // Ambil stok lama untuk hitung selisih opname
    $res = $conn->query("SELECT stok FROM inventori_barang WHERE id_barang = '$id'");
    $old = $res->fetch_assoc();
    $stok_lama = $old['stok'];
    $selisih = $stok_baru - $stok_lama;

    // Update data barang
    $stmt = $conn->prepare("UPDATE inventori_barang SET nama_barang=?, harga_beli=?, harga_jual=?, stok=? WHERE id_barang=?");
    $stmt->bind_param("sddii", $nama, $beli, $jual, $stok_baru, $id);
    $stmt->execute();

    // Simpan ke riwayat_inventori dengan detail audit stok
    $stmt_log = $conn->prepare("INSERT INTO riwayat_inventori (id_barang, nama_barang, aksi, stok_lama, stok_baru, selisih, alasan, user_admin) VALUES (?, ?, 'UPDATE', ?, ?, ?, ?, ?)");
    $stmt_log->bind_param("isiiiss", $id, $nama, $stok_lama, $stok_baru, $selisih, $alasan, $admin);
    $stmt_log->execute();

    header("Location: inventori.php"); exit;
}

// Ambil Data Utama & Riwayat
$result = mysqli_query($conn, "SELECT * FROM inventori_barang");
$riwayat = mysqli_query($conn, "SELECT * FROM riwayat_inventori ORDER BY tanggal DESC LIMIT 15");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventori - Audit & Opname</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* CSS INTEGRASI */
        :root {
            --primary: #1565c0; --primary-dark: #0d47a1; --primary-light: #e3f0ff;
            --sidebar-width: 240px; --text-main: #1a202c; --text-muted: #718096;
            --bg-page: #f0f4f8; --bg-card: #ffffff; --border: #e2e8f0;
            --radius: 10px; --shadow: 0 2px 12px rgba(21,101,192,0.08);
            --danger: #e74c3c; --success: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-page); color: var(--text-main); display: flex; min-height: 100vh; }

        .sidebar { width: var(--sidebar-width); background: var(--primary); display: flex; flex-direction: column; position: fixed; height: 100vh; z-index: 100; box-shadow: 4px 0 20px rgba(13,71,161,0.18); }
        .admin-profile { display: flex; align-items: center; gap: 10px; padding: 24px 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.12); margin-bottom: 8px; color: #fff; font-weight: 600; }
        .sidebar a { display: flex; align-items: center; gap: 10px; padding: 11px 20px; color: rgba(255,255,255,0.82); text-decoration: none; font-size: 0.875rem; border-left: 3px solid transparent; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: #fff; }
        .sidebar h3 { font-size: 0.65rem; color: rgba(255,255,255,0.45); padding: 16px 20px 6px; text-transform: uppercase; }

        .main-wrapper { margin-left: var(--sidebar-width); flex: 1; display: flex; flex-direction: column; }
        header { display: flex; align-items: center; justify-content: space-between; padding: 18px 28px; background: #fff; border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 50; }
        .card { margin: 20px 28px; background: #fff; border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); }
        .card-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f7faff; text-align: left; padding: 13px 16px; font-size: 0.75rem; color: var(--primary); text-transform: uppercase; border-bottom: 2px solid var(--border); }
        td { padding: 13px 16px; border-bottom: 1px solid var(--border); font-size: 0.875rem; }
        
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); backdrop-filter: blur(3px); }
        .modal-content { background: #fff; margin: 5% auto; padding: 28px; width: 450px; border-radius: var(--radius); }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 8px 0; border: 1.5px solid var(--border); border-radius: 7px; }
        
        .selisih-plus { color: var(--success); font-weight: bold; }
        .selisih-minus { color: var(--danger); font-weight: bold; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; }
        .status-aktif { background: var(--success); } .status-habis { background: var(--danger); }
        .btn-blue { background: var(--primary); color: white; border: none; padding: 9px 18px; border-radius: 7px; cursor: pointer; font-weight: 600; }
        .btn-cancel { background: #f1f5f9; color: var(--text-muted); border: 1.5px solid var(--border); padding: 9px 16px; border-radius: 7px; cursor: pointer; }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="overlay"></div>

<div class="sidebar" id="sidebar">
    <div class="admin-profile">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
    </div>
    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <a href="inventori.php" class="active"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <h3>LAPORAN</h3>
    <a href="laporan_barangmasuk.php"><i class="fas fa-chart-line"></i> Laporan Barang Masuk</a>
    <a href="laporanBarangKeluar.php"><i class="fas fa-chart-bar"></i> Laporan Barang Keluar</a>
    <h3>LAINNYA</h3>
    <a href="setting.php"><i class="fas fa-cog"></i> Setelan</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <span><i class="fas fa-boxes"></i> <strong>MANAJEMEN STOK & AUDIT</strong></span>
        <div><?php echo $_SESSION['username']; ?> <i class="fa fa-user-circle" style="color:var(--primary);"></i></div>
    </header>

    <div class="card">
        <div class="card-header">
            <h3>Daftar Inventori</h3>
            <button class="btn-blue" onclick="openModal('modalTambah')"><i class="fas fa-plus"></i> Tambah</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Barang</th><th>Beli</th><th>Jual</th><th>Stok</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td style="font-family:'DM Mono';">#<?php echo $row['id_barang']; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?php echo number_format($row['harga_beli']); ?></td>
                    <td><?php echo number_format($row['harga_jual']); ?></td>
                    <td><strong><?php echo $row['stok']; ?></strong></td>
                    <td>
                        <a href="javascript:void(0)" onclick="fillEditForm('<?php echo $row['id_barang']; ?>','<?php echo addslashes($row['nama_barang']); ?>','<?php echo $row['harga_beli']; ?>','<?php echo $row['harga_jual']; ?>','<?php echo $row['stok']; ?>')" style="color:var(--primary); margin-right:10px;"><i class="fa fa-edit"></i></a>
                        <a href="javascript:void(0)" onclick="openModalHapus('<?php echo $row['id_barang']; ?>', '<?php echo addslashes($row['nama_barang']); ?>')" style="color:var(--danger);"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top: 0;">
        <div class="card-header" style="background: #fcfcfc;">
            <h3><i class="fas fa-history"></i> Riwayat Audit & Stock Opname</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="font-size: 0.82rem;">
                <thead>
                    <tr>
                        <th>Waktu</th><th>Barang</th><th>Aksi</th><th>S.Lama</th><th>S.Baru</th><th>Selisih</th><th>Alasan</th><th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r = mysqli_fetch_assoc($riwayat)): ?>
                    <tr>
                        <td style="color:var(--text-muted);"><?php echo date('d/m H:i', strtotime($r['tanggal'])); ?></td>
                        <td><strong><?php echo $r['nama_barang']; ?></strong></td>
                        <td><span class="status-badge <?php echo $r['aksi']=='UPDATE'?'status-aktif':'status-habis'; ?>"><?php echo $r['aksi']; ?></span></td>
                        <td align="center"><?php echo $r['stok_lama']; ?></td>
                        <td align="center"><?php echo $r['stok_baru']; ?></td>
                        <td align="center">
                            <?php 
                                $class = $r['selisih'] >= 0 ? 'selisih-plus' : 'selisih-minus';
                                $sign = $r['selisih'] > 0 ? '+' : '';
                                echo "<span class='$class'>$sign{$r['selisih']}</span>";
                            ?>
                        </td>
                        <td><small><i><?php echo htmlspecialchars($r['alasan']); ?></i></small></td>
                        <td><?php echo $r['user_admin']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="modalEdit" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> Edit / Stock Opname</h3>
        <form method="POST">
            <input type="hidden" name="id_barang" id="edit_id">
            <label>Nama Barang</label>
            <input type="text" name="nama_barang" id="edit_nama" required>
            <input type="number" name="harga_beli" id="edit_beli" required>
            <input type="number" name="harga_jual" id="edit_jual" required>
            
            <label style="font-weight:bold; color:var(--primary);">Jumlah Stok Fisik (Opname):</label>
            <input type="number" name="stok" id="edit_stok" required style="border: 2px solid var(--primary); font-size: 1.1rem;">
            
            <label>Alasan Perubahan:</label>
            <select name="alasan_edit" required>
                <option value="Penyesuaian Stok Fisik (Opname)">Penyesuaian Stok Fisik (Opname)</option>
                <option value="Koreksi Data / Salah Input">Koreksi Data / Salah Input</option>
                <option value="Barang Rusak / Expired">Barang Rusak / Expired</option>
                <option value="Perubahan Harga Saja">Perubahan Harga Saja</option>
            </select>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" name="update" class="btn-blue">Simpan Audit</button>
            </div>
        </form>
    </div>
</div>

<div id="modalHapus" class="modal">
    <div class="modal-content">
        <h3 style="color:var(--danger);"><i class="fas fa-trash"></i> Konfirmasi Hapus</h3>
        <p id="teks_hapus" style="margin: 10px 0; font-size: 0.9rem;"></p>
        <form method="POST">
            <input type="hidden" name="id_hapus" id="id_hapus">
            <label>Alasan Penghapusan:</label>
            <select name="alasan_hapus" required>
                <option value="Barang Rusak Total">Barang Rusak Total</option>
                <option value="Tidak Dijual Lagi">Tidak Dijual Lagi</option>
                <option value="Kesalahan Input">Kesalahan Input</option>
            </select>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalHapus')">Batal</button>
                <button type="submit" name="konfirmasi_hapus" class="btn-blue" style="background:var(--danger);">Hapus & Catat</button>
            </div>
        </form>
    </div>
</div>

<div id="modalTambah" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-plus"></i> Tambah Barang</h3>
        <form method="POST">
            <input type="text" name="nama_barang" placeholder="Nama Barang" required>
            <input type="number" name="harga_beli" placeholder="Harga Beli" required>
            <input type="number" name="harga_jual" placeholder="Harga Jual" required>
            <input type="number" name="stok" placeholder="Stok Awal" required>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" name="tambah" class="btn-blue">Simpan</button>
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
function openModalHapus(id, nama) {
    document.getElementById('id_hapus').value = id;
    document.getElementById('teks_hapus').innerText = "Apakah Anda yakin ingin menghapus '" + nama + "'?";
    openModal('modalHapus');
}
window.onclick = function(e) { if (e.target.className === 'modal') e.target.style.display = "none"; }
</script>

</body>
</html>
