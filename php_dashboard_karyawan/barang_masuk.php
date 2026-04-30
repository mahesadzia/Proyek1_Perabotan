<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }
include 'konek.php';

$notif = "";
if (isset($_POST['simpan_masuk'])) {
    $tgl_masuk   = $_POST['tanggal_masuk'];
    $id_supplier = $_POST['id_supplier'];
    $id_user     = $_SESSION['user_id'];
    $id_barang   = $_POST['id_barang'];
    $jumlah      = $_POST['jumlah'];
    $harga_beli  = $_POST['harga_beli'];

    $stmt_header = $conn->prepare("INSERT INTO barang_masuk (tanggal_masuk, id_supplier, id_user) VALUES (?, ?, ?)");
    $stmt_header->bind_param("sii", $tgl_masuk, $id_supplier, $id_user);

    if ($stmt_header->execute()) {
        $id_masuk_terakhir = $conn->insert_id;
        $stmt_header->close();

        $stmt_detail = $conn->prepare("INSERT INTO detail_barang_masuk (id_masuk, id_barang, jumlah, harga_beli) VALUES (?, ?, ?, ?)");
        $stmt_detail->bind_param("iiid", $id_masuk_terakhir, $id_barang, $jumlah, $harga_beli);

        $stmt_stok = $conn->prepare("UPDATE inventori_barang SET stok = stok + ? WHERE id_barang = ?");
        $stmt_stok->bind_param("ii", $jumlah, $id_barang);

        if ($stmt_detail->execute() && $stmt_stok->execute()) { $notif = "sukses"; }
        else { $notif = "gagal"; }
        $stmt_detail->close(); $stmt_stok->close();
    } else { $notif = "gagal"; }
}

$res_barang = mysqli_query($conn, "SELECT * FROM inventori_barang");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Masuk - Sistem Inventaris</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="barang_masuk.css">
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
    <a href="inventori.php"><i class="fas fa-boxes"></i> Inventori</a>
    <h3>TRANSAKSI</h3>
    <a href="barang_masuk.php" class="active"><i class="fas fa-shopping-cart"></i> Barang Masuk</a>
    <a href="barang_keluar.php"><i class="fas fa-file-export"></i> Barang Keluar</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="hamburger" id="hamburger" aria-label="Menu"><span></span></button>
            <span><i class="fas fa-shopping-cart"></i> BARANG MASUK</span>
        </div>
        <div style="font-size:0.875rem;font-weight:500;color:#718096;">
            <?php echo htmlspecialchars($_SESSION['username']); ?> <i class="fa fa-user-circle" style="color:#1565c0;"></i>
        </div>
    </header>

    <div class="page-title">Input Detail Barang Masuk</div>

    <?php if($notif == "sukses"): ?>
        <div style="margin:12px 16px;background:#dcfce7;color:#166534;padding:12px 16px;border-radius:8px;border:1px solid #bbf7d0;font-size:0.875rem;font-weight:600;">
            <i class="fas fa-check-circle"></i> Data Berhasil Disimpan!
        </div>
    <?php elseif($notif == "gagal"): ?>
        <div style="margin:12px 16px;background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:8px;border:1px solid #fecaca;font-size:0.875rem;font-weight:600;">
            <i class="fas fa-times-circle"></i> Gagal menyimpan data. Coba lagi!
        </div>
    <?php endif; ?>

    <div class="content-card">
        <form action="" method="POST">
            <div class="content-grid">
                <div>
                    <h3><i class="fas fa-info-circle"></i> Info Transaksi</h3>
                    <label>Tanggal Masuk</label>
                    <input type="date" name="tanggal_masuk" required>
                    <label>ID Supplier</label>
                    <input type="number" name="id_supplier" placeholder="Masukkan ID Supplier" required>
                </div>
                <div>
                    <h3><i class="fas fa-box"></i> Detail Barang</h3>
                    <label>Pilih Barang</label>
                    <select name="id_barang" required>
                        <?php while($b = mysqli_fetch_assoc($res_barang)): ?>
                            <option value="<?= $b['id_barang']; ?>"><?= htmlspecialchars($b['nama_barang']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <label>Jumlah</label>
                    <input type="number" name="jumlah" placeholder="0" min="1" required>
                    <label>Harga Beli (Per Item)</label>
                    <input type="number" name="harga_beli" placeholder="Rp" min="0" required>
                </div>
            </div>
            <button type="submit" name="simpan_masuk" class="btn-submit">
                <i class="fas fa-save"></i> Simpan Transaksi Masuk
            </button>
        </form>
    </div>

    <div class="content-card">
        <h3><i class="fas fa-history"></i> Log Transaksi Terbaru</h3>
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table>
                <thead>
                    <tr>
                        <th>Tgl Masuk</th><th>Barang</th><th>Qty</th><th>Harga Beli</th><th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $log = mysqli_query($conn, "
                        SELECT h.tanggal_masuk, b.nama_barang, d.jumlah, d.harga_beli 
                        FROM detail_barang_masuk d
                        JOIN barang_masuk h ON d.id_masuk = h.id_masuk
                        JOIN inventori_barang b ON d.id_barang = b.id_barang
                        ORDER BY h.id_masuk DESC LIMIT 5
                    ");
                    while($l = mysqli_fetch_assoc($log)): ?>
                    <tr>
                        <td style="font-family:'DM Mono',monospace;font-size:0.82rem;color:#718096;"><?= date('d/m/Y', strtotime($l['tanggal_masuk'])); ?></td>
                        <td><?= htmlspecialchars($l['nama_barang']); ?></td>
                        <td><?= $l['jumlah']; ?></td>
                        <td>Rp <?= number_format($l['harga_beli'], 0, ',', '.'); ?></td>
                        <td class="total-bold">Rp <?= number_format($l['jumlah'] * $l['harga_beli'], 0, ',', '.'); ?></td>
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
