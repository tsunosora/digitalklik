<?php
// laporan.php - Halaman laporan
require_once '/includes/config.php';
checkLogin();

// Cek akses admin
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$conn = connectDB();

// Filter laporan
$jenis_laporan = isset($_GET['jenis_laporan']) ? $_GET['jenis_laporan'] : 'order';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$id_cabang = isset($_GET['id_cabang']) ? (int)$_GET['id_cabang'] : 0;

// Ambil data cabang
$sql = "SELECT id_cabang, nama_cabang FROM cabang";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$cabangs = [];
while ($row = $result->fetch_assoc()) {
    $cabangs[] = $row;
}

// Query laporan
$laporanData = [];

if (isset($_GET['generate'])) {
    switch ($jenis_laporan) {
        case 'order':
            // Laporan order
            $sql = "SELECT o.nomor_nota, o.nama_customer, o.tanggal_order, o.total_biaya, c.nama_cabang 
                    FROM orders o 
                    JOIN cabang c ON o.id_cabang = c.id_cabang 
                    WHERE DATE(o.tanggal_order) BETWEEN ? AND ?";
            
            if ($id_cabang > 0) {
                $sql .= " AND o.id_cabang = ?";
            }
            
            $sql .= " ORDER BY o.tanggal_order DESC";
            
            $stmt = $conn->prepare($sql);
            
            if ($id_cabang > 0) {
                $stmt->bind_param("ssi", $dateFrom, $dateTo, $id_cabang);
            } else {
                $stmt->bind_param("ss", $dateFrom, $dateTo);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $laporanData[] = $row;
            }
            break;
            
        case 'jenis_cetak':
            // Laporan jenis cetak
            $sql = "SELECT jc.nama_jenis, COUNT(do.id_detail) AS jumlah_order, 
                    SUM(do.jumlah) AS total_cetak, SUM(do.subtotal) AS total_pendapatan 
                    FROM detail_order do 
                    JOIN jenis_cetak jc ON do.id_jenis = jc.id_jenis 
                    JOIN orders o ON do.id_order = o.id_order 
                    WHERE DATE(o.tanggal_order) BETWEEN ? AND ?";
            
            if ($id_cabang > 0) {
                $sql .= " AND o.id_cabang = ?";
            }
            
            $sql .= " GROUP BY jc.id_jenis ORDER BY total_pendapatan DESC";
            
            $stmt = $conn->prepare($sql);
            
            if ($id_cabang > 0) {
                $stmt->bind_param("ssi", $dateFrom, $dateTo, $id_cabang);
            } else {
                $stmt->bind_param("ss", $dateFrom, $dateTo);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $laporanData[] = $row;
            }
            break;
            
        case 'stok':
            // Laporan stok bahan
            $sql = "SELECT b.nama_bahan, b.ukuran, b.stok, c.nama_cabang 
                    FROM bahan b 
                    JOIN cabang c ON b.id_cabang = c.id_cabang";
            
            if ($id_cabang > 0) {
                $sql .= " WHERE b.id_cabang = ?";
            }
            
            $sql .= " ORDER BY c.nama_cabang, b.nama_bahan, b.ukuran";
            
            $stmt = $conn->prepare($sql);
            
            if ($id_cabang > 0) {
                $stmt->bind_param("i", $id_cabang);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $laporanData[] = $row;
            }
            break;
            
        case 'cabang':
            // Laporan perbandingan cabang
            $sql = "SELECT c.nama_cabang, COUNT(o.id_order) AS jumlah_order, 
                    SUM(o.total_biaya) AS total_pendapatan 
                    FROM cabang c 
                    LEFT JOIN orders o ON c.id_cabang = o.id_cabang";
            
            if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
                $sql .= " AND DATE(o.tanggal_order) BETWEEN ? AND ?";
            }
            
            $sql .= " GROUP BY c.id_cabang ORDER BY total_pendapatan DESC";
            
            $stmt = $conn->prepare($sql);
            
            if (isset($_GET['date_from']) && isset($_GET['date_to'])) {
                $stmt->bind_param("ss", $dateFrom, $dateTo);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $laporanData[] = $row;
            }
            break;
    }
}

$conn->close();

include 'header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-chart-bar me-2"></i>Laporan</h2>
            <hr>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="jenis_laporan" class="form-label">Jenis Laporan</label>
                    <select class="form-select" id="jenis_laporan" name="jenis_laporan">
                        <option value="order" <?php echo ($jenis_laporan == 'order') ? 'selected' : ''; ?>>Laporan Order</option>
                        <option value="jenis_cetak" <?php echo ($jenis_laporan == 'jenis_cetak') ? 'selected' : ''; ?>>Laporan Jenis Cetak</option>
                        <option value="stok" <?php echo ($jenis_laporan == 'stok') ? 'selected' : ''; ?>>Laporan Stok</option>
                        <option value="cabang" <?php echo ($jenis_laporan == 'cabang') ? 'selected' : ''; ?>>Laporan Perbandingan Cabang</option>
                    </select>
                </div>
                <div class="col-md-2 laporan-tanggal <?php echo ($jenis_laporan == 'stok') ? 'd-none' : ''; ?>">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="col-md-2 laporan-tanggal <?php echo ($jenis_laporan == 'stok') ? 'd-none' : ''; ?>">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                <div class="col-md-3 laporan-cabang <?php echo ($jenis_laporan == 'cabang') ? 'd-none' : ''; ?>">
                    <label for="id_cabang" class="form-label">Cabang</label>
                    <select class="form-select" id="id_cabang" name="id_cabang">
                        <option value="0">Semua Cabang</option>
                        <?php foreach ($cabangs as $cabang): ?>
                        <option value="<?php echo $cabang['id_cabang']; ?>" <?php echo ($id_cabang == $cabang['id_cabang']) ? 'selected' : ''; ?>>
                            <?php echo $cabang['nama_cabang']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="generate" value="1" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($_GET['generate']) && !empty($laporanData)): ?>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php
                switch ($jenis_laporan) {
                    case 'order':
                        echo 'Laporan Order';
                        break;
                    case 'jenis_cetak':
                        echo 'Laporan Jenis Cetak';
                        break;
                    case 'stok':
                        echo 'Laporan Stok';
                        break;
                    case 'cabang':
                        echo 'Laporan Perbandingan Cabang';
                        break;
                }
                ?>
            </h5>
            <button class="btn btn-sm btn-success" onclick="printReport()">
                <i class="fas fa-print me-2"></i>Cetak
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if ($jenis_laporan == 'order'): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Nota</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Cabang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; $total = 0; foreach ($laporanData as $item): $total += $item['total_biaya']; ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $item['nomor_nota']; ?></td>
                            <td><?php echo $item['nama_customer']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($item['tanggal_order'])); ?></td>
                            <td><?php echo formatRupiah($item['total_biaya']); ?></td>
                            <td><?php echo $item['nama_cabang']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total Pendapatan</strong></td>
                            <td colspan="2"><strong><?php echo formatRupiah($total); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                <?php elseif ($jenis_laporan == 'jenis_cetak'): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Cetak</th>
                            <th>Jumlah Order</th>
                            <th>Total Cetak</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; $total = 0; foreach ($laporanData as $item): $total += $item['total_pendapatan']; ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $item['nama_jenis']; ?></td>
                            <td><?php echo $item['jumlah_order']; ?></td>
                            <td><?php echo $item['total_cetak']; ?></td>
                            <td><?php echo formatRupiah($item['total_pendapatan']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total Pendapatan</strong></td>
                            <td><strong><?php echo formatRupiah($total); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                <?php elseif ($jenis_laporan == 'stok'): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Cabang</th>
                            <th>Bahan</th>
                            <th>Ukuran</th>
                            <th>Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($laporanData as $item): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $item['nama_cabang']; ?></td>
                            <td><?php echo $item['nama_bahan']; ?></td>
                            <td><?php echo $item['ukuran']; ?></td>
                            <td>
                                <?php if ($item['stok'] < 20): ?>
                                <span class="badge bg-danger"><?php echo $item['stok']; ?></span>
                                <?php else: ?>
                                <?php echo $item['stok']; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php elseif ($jenis_laporan == 'cabang'): ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Cabang</th>
                            <th>Jumlah Order</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; $total = 0; foreach ($laporanData as $item): $total += $item['total_pendapatan']; ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $item['nama_cabang']; ?></td>
                            <td><?php echo $item['jumlah_order']; ?></td>
                            <td><?php echo formatRupiah($item['total_pendapatan']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Pendapatan</strong></td>
                            <td><strong><?php echo formatRupiah($total); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php elseif (isset($_GET['generate'])): ?>
    <div class="alert alert-info">
        Tidak ada data yang tersedia untuk periode yang dipilih.
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk toggle tampilan form filter sesuai jenis laporan
    document.getElementById('jenis_laporan').addEventListener('change', function() {
        let jenisLaporan = this.value;
        
        // Tampilkan/sembunyikan field tanggal
        let elTanggal = document.querySelectorAll('.laporan-tanggal');
        elTanggal.forEach(function(el) {
            if (jenisLaporan === 'stok') {
                el.classList.add('d-none');
            } else {
                el.classList.remove('d-none');
            }
        });
        
        // Tampilkan/sembunyikan field cabang
        let elCabang = document.querySelectorAll('.laporan-cabang');
        elCabang.forEach(function(el) {
            if (jenisLaporan === 'cabang') {
                el.classList.add('d-none');
            } else {
                el.classList.remove('d-none');
            }
        });
    });
});

// Fungsi untuk mencetak laporan
function printReport() {
    window.print();
}
</script>

<?php include 'footer.php'; ?>
