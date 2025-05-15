<?php
// orders.php - Daftar order
require_once '/includes/config.php';
checkLogin();

$conn = connectDB();

// Filter tanggal
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Query untuk mengambil daftar order
$sql = "SELECT o.id_order, o.nomor_nota, o.nama_customer, o.tanggal_order, o.total_biaya, c.nama_cabang 
        FROM orders o 
        JOIN cabang c ON o.id_cabang = c.id_cabang 
        WHERE DATE(o.tanggal_order) BETWEEN ? AND ?";

// Jika user adalah operator, hanya ambil order dari cabang mereka
if ($_SESSION['user']['role'] == 'operator') {
    $sql .= " AND o.id_cabang = ?";
}

$sql .= " ORDER BY o.tanggal_order DESC";

$stmt = $conn->prepare($sql);

if ($_SESSION['user']['role'] == 'operator') {
    $stmt->bind_param("ssi", $dateFrom, $dateTo, $_SESSION['cabang']);
} else {
    $stmt->bind_param("ss", $dateFrom, $dateTo);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$conn->close();

include 'header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-shopping-cart me-2"></i>Daftar Order</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="order_add.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Order Baru
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Filter</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="date_from" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="col-md-4">
                    <label for="date_to" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Daftar Order</h5>
        </div>
        <div class="card-body">
            <?php if (empty($orders)): ?>
            <p class="text-center">Tidak ada order pada periode yang dipilih.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nomor Nota</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Cabang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['nomor_nota']; ?></td>
                            <td><?php echo $order['nama_customer']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['tanggal_order'])); ?></td>
                            <td><?php echo formatRupiah($order['total_biaya']); ?></td>
                            <td><?php echo $order['nama_cabang']; ?></td>
                            <td>
                                <a href="order_detail.php?id=<?php echo $order['id_order']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                                <a href="order_edit.php?id=<?php echo $order['id_order']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
