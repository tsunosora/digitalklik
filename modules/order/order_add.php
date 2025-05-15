<?php
// order_add.php - Tambah order baru
require_once '/includes/config.php';
checkLogin();

$conn = connectDB();
$error = '';
$success = '';

// Ambil data cabang aktif
$id_cabang = $_SESSION['cabang'];
if ($_SESSION['user']['role'] == 'admin' && isset($_GET['cabang'])) {
    $id_cabang = (int)$_GET['cabang'];
}

// Ambil data bahan
$sql = "SELECT id_bahan, nama_bahan, ukuran, stok FROM bahan WHERE id_cabang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cabang);
$stmt->execute();
$bahanResult = $stmt->get_result();
$bahans = [];
while ($row = $bahanResult->fetch_assoc()) {
    $bahans[] = $row;
}

// Ambil data jenis cetak
$sql = "SELECT id_jenis, nama_jenis, biaya_klik FROM jenis_cetak";
$stmt = $conn->prepare($sql);
$stmt->execute();
$jenisCetakResult = $stmt->get_result();
$jenisCetaks = [];
while ($row = $jenisCetakResult->fetch_assoc()) {
    $jenisCetaks[] = $row;
}

// Ambil data mesin
$sql = "SELECT id_mesin, nama_mesin FROM mesin WHERE id_cabang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cabang);
$stmt->execute();
$mesinResult = $stmt->get_result();
$mesins = [];
while ($row = $mesinResult->fetch_assoc()) {
    $mesins[] = $row;
}

// Ambil data reject mesin
$sql = "SELECT id_reject, nama_reject FROM reject_mesin";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rejectResult = $stmt->get_result();
$rejects = [];
while ($row = $rejectResult->fetch_assoc()) {
    $rejects[] = $row;
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nomor_nota = cleanInput($_POST['nomor_nota']);
    $nama_customer = cleanInput($_POST['nama_customer']);
    
    // Validasi data
    if (empty($nomor_nota) || empty($nama_customer)) {
        $error = "Nomor nota dan nama customer harus diisi!";
    } else {
        $conn->begin_transaction();
        try {
            // Simpan data order
            $sql = "INSERT INTO orders (nomor_nota, nama_customer, id_cabang) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nomor_nota, $nama_customer, $id_cabang);
            $stmt->execute();
            $id_order = $conn->insert_id;
            
            // Simpan detail order
            $total_biaya = 0;
            
            for ($i = 1; $i <= count($_POST['bahan']); $i++) {
                if (empty($_POST['bahan'][$i]) || empty($_POST['jenis_cetak'][$i]) || empty($_POST['mesin'][$i]) || empty($_POST['jumlah'][$i])) {
                    continue;
                }
                
                $id_bahan = (int)$_POST['bahan'][$i];
                $id_jenis = (int)$_POST['jenis_cetak'][$i];
                $id_mesin = (int)$_POST['mesin'][$i];
                $jumlah = (int)$_POST['jumlah'][$i];
                
                // Periksa apakah ada reject
                $id_reject = !empty($_POST['reject'][$i]) ? (int)$_POST['reject'][$i] : null;
                $diskon_klik = isset($_POST['diskon_klik'][$i]) ? 1 : 0;
                
                // Ambil harga klik
                $sql = "SELECT biaya_klik FROM jenis_cetak WHERE id_jenis = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_jenis);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $biaya_klik = $row['biaya_klik'];
                
                // Hitung subtotal
                $subtotal = $biaya_klik * $jumlah;
                if ($diskon_klik && $id_reject) {
                    $subtotal = $subtotal * 0.5; // Diskon 50% jika ada reject
                }
                
                // Simpan detail order
                $sql = "INSERT INTO detail_order (id_order, id_bahan, id_jenis, id_mesin, jumlah, id_reject, diskon_klik, subtotal) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiiiiid", $id_order, $id_bahan, $id_jenis, $id_mesin, $jumlah, $id_reject, $diskon_klik, $subtotal);
                $stmt->execute();
                
                // Update stok bahan
                updateStok($id_bahan, $jumlah, $conn);
                
                $total_biaya += $subtotal;
            }
            
            // Update total biaya order
            $sql = "UPDATE orders SET total_biaya = ? WHERE id_order = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $total_biaya, $id_order);
            $stmt->execute();
            
            $conn->commit();
            $success = "Order berhasil disimpan!";
            
            // Redirect ke halaman detail order
            header("Location: order_detail.php?id=" . $id_order . "&success=1");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

$conn->close();

include 'header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-plus-circle me-2"></i>Tambah Order Baru</h2>
            <hr>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Form Order Baru</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nomor_nota" class="form-label">Nomor Nota</label>
                            <input type="text" class="form-control" id="nomor_nota" name="nomor_nota" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_customer" class="form-label">Nama Customer</label>
                            <input type="text" class="form-control" id="nama_customer" name="nama_customer" required>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Item Order</h5>
                
                <div id="order-items">
                    <!-- Template item order - akan diclone dengan JS -->
                    <div id="order-item-template" class="d-none">
                        <div class="row mb-4 align-items-end border-bottom pb-3">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="bahan-0" class="form-label">Bahan</label>
                                    <select class="form-select" id="bahan-0" name="bahan[0]">
                                        <option value="">Pilih Bahan</option>
                                        <?php foreach ($bahans as $bahan): ?>
                                        <option value="<?php echo $bahan['id_bahan']; ?>">
                                            <?php echo $bahan['nama_bahan'] . ' ' . $bahan['ukuran'] . ' (Stok: ' . $bahan['stok'] . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="jenis_cetak-0" class="form-label">Jenis Cetak</label>
                                    <select class="form-select" id="jenis_cetak-0" name="jenis_cetak[0]" onchange="updateHarga(this, 0)">
                                        <option value="">Pilih Jenis Cetak</option>
                                        <?php foreach ($jenisCetaks as $jenis): ?>
                                        <option value="<?php echo $jenis['id_jenis']; ?>" data-biaya="<?php echo $jenis['biaya_klik']; ?>">
                                            <?php echo $jenis['nama_jenis'] . ' (' . formatRupiah($jenis['biaya_klik']) . '/klik)'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label for="jumlah-0" class="form-label">Jumlah</label>
                                    <input type="number" class="form-control" id="jumlah-0" name="jumlah[0]" min="1">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="mesin-0" class="form-label">Mesin</label>
                                    <select class="form-select" id="mesin-0" name="mesin[0]" onchange="toggleReject(this, 0)">
                                        <option value="">Pilih Mesin</option>
                                        <?php foreach ($mesins as $mesin): ?>
                                        <option value="<?php echo $mesin['id_mesin']; ?>">
                                            <?php echo $mesin['nama_mesin']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger" onclick="removeOrderItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <div id="reject-div-0" class="col-12 d-none mt-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reject-0" class="form-label">Reject Mesin</label>
                                            <select class="form-select" id="reject-0" name="reject[0]">
                                                <option value="">Tidak Ada Reject</option>
                                                <?php foreach ($rejects as $reject): ?>
                                                <option value="<?php echo $reject['id_reject']; ?>">
                                                    <?php echo $reject['nama_reject']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="diskon_klik-0" name="diskon_klik[0]">
                                            <label class="form-check-label" for="diskon_klik-0">
                                                Diskon Biaya Klik
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <button type="button" class="btn btn-success" onclick="addOrderItem()">
                        <i class="fas fa-plus me-2"></i>Tambah Item
                    </button>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="orders.php" class="btn btn-secondary me-md-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tambahkan item order pertama saat halaman dimuat
    addOrderItem();
});
</script>

<?php include 'footer.php'; ?>
