<?php
include 'konek.php';

// Ambil filter tanggal
$filter_dari   = $_GET['dari']   ?? '';
$filter_sampai = $_GET['sampai'] ?? '';

$where  = "";
$params = [];

if ($filter_dari && $filter_sampai) {
    $where  = "WHERE bm.tanggal_masuk BETWEEN ? AND ?";
    $params = [$filter_dari, $filter_sampai];
}

$sql = "
SELECT 
    bm.id_masuk,
    bm.tanggal_masuk,
    s.nama_supplier,
    ib.nama_barang,
    dbm.jumlah,
    dbm.harga_beli,
    (dbm.jumlah * dbm.harga_beli) AS total
FROM barang_masuk bm
JOIN detail_barang_masuk dbm ON bm.id_masuk = dbm.id_masuk
JOIN inventori_barang ib ON dbm.id_barang = ib.id_barang
LEFT JOIN supplier s ON bm.id_supplier = s.id_supplier
$where
ORDER BY bm.tanggal_masuk DESC
";

$stmt = mysqli_prepare($conn, $sql);
if ($params) {
    mysqli_stmt_bind_param($stmt, 'ss', $params[0], $params[1]);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$grand_total = 0;
$rows = [];
while ($data = mysqli_fetch_assoc($result)) {
    $grand_total += $data['total'];
    $rows[] = $data;
}

// Format tanggal untuk ditampilkan di input (dd/mm/yyyy → value input date yyyy-mm-dd)
$tampil_dari   = $filter_dari   ? date('d/m/Y', strtotime($filter_dari))   : '';
$tampil_sampai = $filter_sampai ? date('d/m/Y', strtotime($filter_sampai)) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pembelian</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="laporan_barangmasuk.css">

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
    <a href="laporan_barangmasuk.php" class="active"><i class="fas fa-chart-line"></i> Laporan Pembelian</a>
    <a href="laporan_penjualan.php"><i class="fas fa-chart-bar"></i> Laporan Penjualan</a>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-wrapper">
    <header>
        <div style="font-weight:bold;"><i class="fa fa-file-invoice"></i> LAPORAN PEMBELIAN</div>
        <button class="btn-print" onclick="window.print()"><i class="fa fa-print"></i> Cetak</button>
    </header>

    <div class="card">

        <!-- Filter Tanggal -->
        <div class="filter-form">
            <div>
                <label>Dari Tanggal</label>
                <input type="text"
                       class="input-tgl"
                       id="dari"
                       placeholder="dd/mm/yyyy"
                       maxlength="10"
                       value="<?= htmlspecialchars($tampil_dari) ?>"
                       oninput="formatTanggal(this)">
                <span class="tgl-hint">contoh: 01/01/2025</span>
            </div>
            <div>
                <label>Sampai Tanggal</label>
                <input type="text"
                       class="input-tgl"
                       id="sampai"
                       placeholder="dd/mm/yyyy"
                       maxlength="10"
                       value="<?= htmlspecialchars($tampil_sampai) ?>"
                       oninput="formatTanggal(this)">
                <span class="tgl-hint">contoh: 31/12/2025</span>
            </div>
            <button onclick="filterData()">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="laporan_barangmasuk.php">
                <button type="button"><i class="fas fa-undo"></i> Reset</button>
            </a>

            <!-- Info filter aktif -->
            <?php if ($filter_dari && $filter_sampai): ?>
                <div class="filter-info">
                    <i class="fas fa-filter"></i>
                    <?= date('d/m/Y', strtotime($filter_dari)) ?> &ndash; <?= date('d/m/Y', strtotime($filter_sampai)) ?>
                    &nbsp;(<?= count($rows) ?> data)
                </div>
            <?php endif; ?>
        </div>

        <div class="card-header">
            <h3><i class="fa fa-history"></i> Riwayat Barang Masuk</h3>
        </div>

        <table class="table-report">
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Transaksi</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Barang</th>
                    <th>Jumlah</th>
                    <th>Harga Beli</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" class="no-data">
                            <i class="fas fa-inbox" style="font-size:1.5rem; display:block; margin-bottom:8px; opacity:0.3;"></i>
                            Tidak ada data<?= ($filter_dari && $filter_sampai) ? ' pada rentang tanggal tersebut' : '' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rows as $no => $data): ?>
                    <tr>
                        <td><?= $no + 1 ?></td>
                        <td>#<?= htmlspecialchars($data['id_masuk']) ?></td>
                        <td><?= date('d/m/Y', strtotime($data['tanggal_masuk'])) ?></td>
                        <td><?= htmlspecialchars($data['nama_supplier'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($data['nama_barang']) ?></td>
                        <td><?= $data['jumlah'] ?></td>
                        <td>Rp <?= number_format($data['harga_beli'], 0, ',', '.') ?></td>
                        <td><strong>Rp <?= number_format($data['total'], 0, ',', '.') ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="grand-total">
                        <td colspan="7" style="text-align:right; padding-right:16px;">GRAND TOTAL</td>
                        <td>Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Auto-format input jadi dd/mm/yyyy saat mengetik
function formatTanggal(input) {
    let val = input.value.replace(/\D/g, ''); // hapus non-angka
    if (val.length >= 3 && val.length <= 4) {
        val = val.slice(0,2) + '/' + val.slice(2);
    } else if (val.length >= 5) {
        val = val.slice(0,2) + '/' + val.slice(2,4) + '/' + val.slice(4,8);
    }
    input.value = val;
    input.classList.remove('error');
}

// Validasi dan konversi dd/mm/yyyy → yyyy-mm-dd untuk dikirim ke PHP
function parseDate(str) {
    const parts = str.split('/');
    if (parts.length !== 3) return null;
    const [dd, mm, yyyy] = parts;
    if (dd.length !== 2 || mm.length !== 2 || yyyy.length !== 4) return null;
    const d = new Date(`${yyyy}-${mm}-${dd}`);
    if (isNaN(d.getTime())) return null;
    return `${yyyy}-${mm}-${dd}`; // format MySQL
}

function filterData() {
    const dariInput   = document.getElementById('dari');
    const sampaiInput = document.getElementById('sampai');

    const dari   = parseDate(dariInput.value);
    const sampai = parseDate(sampaiInput.value);

    let valid = true;

    if (!dari) {
        dariInput.classList.add('error');
        dariInput.focus();
        valid = false;
    }
    if (!sampai) {
        sampaiInput.classList.add('error');
        if (valid) sampaiInput.focus();
        valid = false;
    }
    if (!valid) {
        alert('Format tanggal tidak valid. Gunakan format dd/mm/yyyy\nContoh: 01/01/2025');
        return;
    }
    if (dari > sampai) {
        alert('Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai"');
        dariInput.classList.add('error');
        return;
    }

    // ✅ PERBAIKAN: nama file yang benar
    window.location.href = `laporan_barangmasuk.php?dari=${dari}&sampai=${sampai}`;
}

// Tekan Enter di input tanggal → langsung filter
document.getElementById('dari').addEventListener('keydown', e => { if(e.key==='Enter') filterData(); });
document.getElementById('sampai').addEventListener('keydown', e => { if(e.key==='Enter') filterData(); });
</script>

</body>
</html>
