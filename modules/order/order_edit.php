<?php
// order_edit.php - Edit order
require_once '../../includes/config.php';
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$id_order = (int)$_GET['id'];
$conn = connectDB();
$error = '';
$success = '';

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

$id_cabang = $order['id_cabang'];

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
            // Update data order
            $sql = "UPDATE orders SET nomor_nota = ?, nama_customer = ? WHERE id_order = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $nomor_nota, $nama_customer, $id_order);
            $stmt->execute();
            
            // Hapus semua detail order lama
            $sql = "DELETE FROM detail_order WHERE id_order = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_order);
            $stmt->execute();
            
            // Simpan detail order baru
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
                $diskon_persen = isset($_POST['diskon_persen'][$i]) ? (int)$_POST['diskon_persen'][$i] : 50; // Default 50%
                $tanpa_biaya_klik = isset($_POST['tanpa_biaya_klik'][$i]) ? 1 : 0;
                
                // Ambil harga klik
                $sql = "SELECT biaya_klik FROM jenis_cetak WHERE id_jenis = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_jenis);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $biaya_klik = $row['biaya_klik'];
                
                // Hitung subtotal
                if ($tanpa_biaya_klik) {
                    $subtotal = 0; // Tanpa biaya klik
                } else {
                    $subtotal = $biaya_klik * $jumlah;
                    if ($diskon_klik && $id_reject) {
                        $subtotal = $subtotal * (1 - ($diskon_persen / 100)); // Terapkan diskon sesuai persentase
                    }
                }
                
                // Simpan detail order
                $sql = "INSERT INTO detail_order (id_order, id_bahan, id_jenis, id_mesin, jumlah, id_reject, diskon_klik, diskon_persen, tanpa_biaya_klik, subtotal) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiiiiiiid", $id_order, $id_bahan, $id_jenis, $id_mesin, $jumlah, $id_reject, $diskon_klik, $diskon_persen, $tanpa_biaya_klik, $subtotal);
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
            $success = "Order berhasil diperbarui!";
            
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

include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-edit me-2"></i>Edit Order</h2>
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
            <h5 class="mb-0">Form Edit Order</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nomor_nota" class="form-label">Nomor Nota</label>
                            <input type="text" class="form-control" id="nomor_nota" name="nomor_nota" value="<?php echo $order['nomor_nota']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_customer" class="form-label">Nama Customer</label>
                            <input type="text" class="form-control" id="nama_customer" name="nama_customer" value="<?php echo $order['nama_customer']; ?>" required>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Item Order</h5>
                
                <div id="order-items">
                    <!-- Tampilkan item order yang sudah ada -->
                    <?php $index = 1; foreach ($detailOrders as $detail): ?>
                    <div class="order-item row mb-4 align-items-end border-bottom pb-3">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="bahan-<?php echo $index; ?>" class="form-label">Bahan</label>
                                <select class="form-select" id="bahan-<?php echo $index; ?>" name="bahan[<?php echo $index; ?>]" required>
                                    <option value="">Pilih Bahan</option>
                                    <?php foreach ($bahans as $bahan): ?>
                                    <option value="<?php echo $bahan['id_bahan']; ?>" <?php echo ($detail['id_bahan'] == $bahan['id_bahan']) ? 'selected' : ''; ?>>
                                        <?php echo $bahan['nama_bahan'] . ' ' . $bahan['ukuran'] . ' (Stok: ' . $bahan['stok'] . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="jenis_cetak-<?php echo $index; ?>" class="form-label">Jenis Cetak</label>
                                <select class="form-select" id="jenis_cetak-<?php echo $index; ?>" name="jenis_cetak[<?php echo $index; ?>]" onchange="updateHarga(this, <?php echo $index; ?>)" required>
                                    <option value="">Pilih Jenis Cetak</option>
                                    <?php foreach ($jenisCetaks as $jenis): ?>
                                    <option value="<?php echo $jenis['id_jenis']; ?>" data-biaya="<?php echo $jenis['biaya_klik']; ?>" <?php echo ($detail['id_jenis'] == $jenis['id_jenis']) ? 'selected' : ''; ?>>
                                        <?php echo $jenis['nama_jenis'] . ' (' . formatRupiah($jenis['biaya_klik']) . '/klik)'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-1">
                                    <small>Biaya Klik: <span id="biaya-klik-<?php echo $index; ?>"><?php echo formatRupiah($detail['biaya_klik']); ?></span></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label for="jumlah-<?php echo $index; ?>" class="form-label">Jumlah</label>
                                <input type="number" class="form-control" id="jumlah-<?php echo $index; ?>" name="jumlah[<?php echo $index; ?>]" min="1" value="<?php echo $detail['jumlah']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="mesin-<?php echo $index; ?>" class="form-label">Mesin</label>
                                <select class="form-select" id="mesin-<?php echo $index; ?>" name="mesin[<?php echo $index; ?>]" onchange="toggleReject(this, <?php echo $index; ?>)" required>
                                    <option value="">Pilih Mesin</option>
                                    <?php foreach ($mesins as $mesin): ?>
                                    <option value="<?php echo $mesin['id_mesin']; ?>" <?php echo ($detail['id_mesin'] == $mesin['id_mesin']) ? 'selected' : ''; ?>>
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
                        
                        <div id="reject-div-<?php echo $index; ?>" class="col-12 mt-2 <?php echo ($detail['id_reject']) ? '' : 'd-none'; ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="reject-<?php echo $index; ?>" class="form-label">Reject Mesin</label>
                                        <select class="form-select" id="reject-<?php echo $index; ?>" name="reject[<?php echo $index; ?>]">
                                            <option value="">Tidak Ada Reject</option>
                                            <?php foreach ($rejects as $reject): ?>
                                            <option value="<?php echo $reject['id_reject']; ?>" <?php echo ($detail['id_reject'] == $reject['id_reject']) ? 'selected' : ''; ?>>
                                                <?php echo $reject['nama_reject']; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="diskon_persen-<?php echo $index; ?>" class="form-label">Persentase Diskon</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="diskon_persen-<?php echo $index; ?>" name="diskon_persen[<?php echo $index; ?>]" min="0" max="100" value="<?php echo isset($detail['diskon_persen']) ? $detail['diskon_persen'] : 50; ?>">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="diskon_klik-<?php echo $index; ?>" name="diskon_klik[<?php echo $index; ?>]" <?php echo ($detail['diskon_klik']) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="diskon_klik-<?php echo $index; ?>">
                                            Terapkan Diskon
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="tanpa_biaya_klik-<?php echo $index; ?>" name="tanpa_biaya_klik[<?php echo $index; ?>]" onchange="toggleTanpaBiaya(this, <?php echo $index; ?>)" <?php echo isset($detail['tanpa_biaya_klik']) && $detail['tanpa_biaya_klik'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="tanpa_biaya_klik-<?php echo $index; ?>">
                                            Tanpa Biaya Klik
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $index++; endforeach; ?>
                </div>
                
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
                                <div class="mt-1">
                                    <small>Biaya Klik: <span id="biaya-klik-0">Rp 0</span></small>
                                </div>
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
                                <div class="col-md-4">
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
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="diskon_persen-0" class="form-label">Persentase Diskon</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="diskon_persen-0" name="diskon_persen[0]" min="0" max="100" value="50">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="diskon_klik-0" name="diskon_klik[0]">
                                        <label class="form-check-label" for="diskon_klik-0">
                                            Terapkan Diskon
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="tanpa_biaya_klik-0" name="tanpa_biaya_klik[0]" onchange="toggleTanpaBiaya(this, 0)">
                                        <label class="form-check-label" for="tanpa_biaya_klik-0">
                                            Tanpa Biaya Klik
                                        </label>
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
                    <a href="order_detail.php?id=<?php echo $id_order; ?>" class="btn btn-secondary me-md-2">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Jika tidak ada item order, tambahkan satu item kosong
    if (document.querySelectorAll('.order-item').length === 0) {
        addOrderItem();
    }
    
    // Inisialisasi status tombol Tanpa Biaya Klik
    document.querySelectorAll('[id^="tanpa_biaya_klik-"]').forEach(function(checkbox) {
        if (checkbox.checked) {
            let index = checkbox.id.split('-')[1];
            toggleTanpaBiaya(checkbox, index);
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
