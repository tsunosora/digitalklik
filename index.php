<?php
// index.php - Halaman Dashboard
require_once 'includes/config.php';
checkLogin();

// Kode lain...

include 'includes/header.php';
?>

<div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="card-title mb-0">Stok Bahan Menipis</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bahanMenipis)): ?>
                    <p class="text-center">Semua stok bahan mencukupi.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Bahan</th>
                                    <th>Ukuran</th>
                                    <th>Stok</th>
                                    <th>Cabang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bahanMenipis as $bahan): ?>
                                <tr>
                                    <td><?php echo $bahan['nama_bahan']; ?></td>
                                    <td><?php echo $bahan['ukuran']; ?></td>
                                    <td><span class="badge bg-danger"><?php echo $bahan['stok']; ?></span></td>
                                    <td><?php echo $bahan['nama_cabang']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Order Terbaru</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($orderTerbaru)): ?>
                    <p class="text-center">Belum ada order yang dibuat.</p>
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
                                <?php foreach ($orderTerbaru as $order): ?>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
