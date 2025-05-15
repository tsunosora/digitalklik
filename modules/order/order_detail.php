<?php
// order_detail.php - Detail order
require_once '../../includes/config.php';
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$id_order = (int)$_GET['id'];
$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;

$conn = connectDB();

// Ambil data order
$sql = "SELECT o.*, c.nama_cabang 
        FROM orders o 
        JOIN cabang c ON o.id_cabang = c.id_cabang 
        WHERE o.id_order = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_order);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: orders.php");
    exit;
}

$order = $result->fetch_assoc();

// Cek akses untuk operator
if ($_SESSION['user']['role'] == 'operator' && $_SESSION['cabang'] != $order['id_cabang']) {
    header("Location: orders.php");
    exit;
}

// Ambil detail order
$sql = "SELECT do.*, b.nama_bahan, b.ukuran, jc.nama_jenis, jc.biaya_klik, m.nama_mesin, rm.nama_reject 
        FROM detail_order do 
        JOIN bahan b ON do.id_bahan = b.id_bahan 
        JOIN jenis_cetak jc ON do.id_jenis = jc.id_jenis 
        JOIN mesin m ON do.id_mesin = m.id_mesin 
        LEFT JOIN reject_mesin rm ON do.id_reject = rm.id_reject 
        WHERE do.id_order = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_order);
$stmt->execute();
$result = $stmt->get_result();
$detailOrders = [];
while ($row = $result->fetch_assoc()) {
    $detailOrders[] = $row;
}

$conn->close();

include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-file-invoice me-2"></i>Detail Order</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="orders.php" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <a href="order_edit.php?id=<?php echo $id_order; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
        </div>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success" role="alert">
        Order berhasil disimpan!
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Informasi Order</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="30%"><strong>Nomor Nota</strong></td>
                            <td>: <?php echo $order['nomor_nota']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Nama Customer</strong></td>
                            <td>: <?php echo $order['nama_customer']; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="30%"><strong>Tanggal</strong></td>
                            <td>: <?php echo date('d/m/Y H:i', strtotime($order['tanggal_order'])); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Cabang</strong></td>
                            <td>: <?php echo $order['nama_cabang']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Detail Item</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Bahan</th>
                            <th>Jenis Cetak</th>
                            <th>Jumlah</th>
                            <th>Biaya Klik</th>
                            <th>Mesin</th>
                            <th>Reject</th>
                            <th>Diskon</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($detailOrders as $detail): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $detail['nama_bahan'] . ' ' . $detail['ukuran']; ?></td>
                            <td><?php echo $detail['nama_jenis']; ?></td>
                            <td><?php echo $detail['jumlah']; ?></td>
                            <td><?php echo formatRupiah($detail['biaya_klik']); ?></td>
                            <td><?php echo $detail['nama_mesin']; ?></td>
                            <td>
                                <?php if ($detail['id_reject']): ?>
                                <span class="badge bg-danger"><?php echo $detail['nama_reject']; ?></span>
                                <?php else: ?>
                                <span class="badge bg-success">Tidak Ada</span>
                                <?php endif; ?>
                            </td>
							<td>
    <?php if ($detail['tanpa_biaya_klik']): ?>
    <span class="badge bg-danger">Tanpa Biaya Klik</span>
    <?php elseif ($detail['diskon_klik']): ?>
    <span class="badge bg-primary"><?php echo $detail['diskon_persen']; ?>%</span>
    <?php else: ?>
    <span class="badge bg-secondary">Tidak</span>
    <?php endif; ?>
</td>
								
                            <td><?php echo formatRupiah($detail['subtotal']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8" class="text-end"><strong>Total</strong></td>
                            <td><strong><?php echo formatRupiah($order['total_biaya']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
