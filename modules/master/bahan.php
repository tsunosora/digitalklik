<?php
// bahan.php - Kelola bahan
require_once '/includes/config.php';
checkLogin();

// Cek akses admin
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$conn = connectDB();
$error = '';
$success = '';

// Hapus bahan
if (isset($_GET['delete'])) {
    $id_bahan = (int)$_GET['delete'];
    
    $sql = "DELETE FROM bahan WHERE id_bahan = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bahan);
    
    if ($stmt->execute()) {
        $success = "Bahan berhasil dihapus!";
    } else {
        $error = "Gagal menghapus bahan. Mungkin sedang digunakan pada order!";
    }
}

// Tambah atau update bahan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_bahan = cleanInput($_POST['nama_bahan']);
    $ukuran = cleanInput($_POST['ukuran']);
    $stok = (int)$_POST['stok'];
    $id_cabang = (int)$_POST['id_cabang'];
    
    if (isset($_POST['id_bahan'])) {
        // Update bahan
        $id_bahan = (int)$_POST['id_bahan'];
        
        $sql = "UPDATE bahan SET nama_bahan = ?, ukuran = ?, stok = ?, id_cabang = ? WHERE id_bahan = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $nama_bahan, $ukuran, $stok, $id_cabang, $id_bahan);
        
        if ($stmt->execute()) {
            $success = "Bahan berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui bahan!";
        }
    } else {
        // Tambah bahan baru
        $sql = "INSERT INTO bahan (nama_bahan, ukuran, stok, id_cabang) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $nama_bahan, $ukuran, $stok, $id_cabang);
        
        if ($stmt->execute()) {
            $success = "Bahan baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan bahan baru!";
        }
    }
}

// Ambil data bahan
$sql = "SELECT b.*, c.nama_cabang FROM bahan b JOIN cabang c ON b.id_cabang = c.id_cabang ORDER BY c.nama_cabang, b.nama_bahan, b.ukuran";
$result = $conn->query($sql);
$bahans = [];
while ($row = $result->fetch_assoc()) {
    $bahans[] = $row;
}

// Ambil data cabang untuk form
$sql = "SELECT id_cabang, nama_cabang FROM cabang";
$result = $conn->query($sql);
$cabangs = [];
while ($row = $result->fetch_assoc()) {
    $cabangs[] = $row;
}

$conn->close();

include 'header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-box me-2"></i>Kelola Bahan</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBahan">
                <i class="fas fa-plus me-2"></i>Tambah Bahan
            </button>
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
            <h5 class="mb-0">Daftar Bahan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Bahan</th>
                            <th>Ukuran</th>
                            <th>Stok</th>
                            <th>Cabang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bahans)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data bahan</td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; foreach ($bahans as $bahan): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $bahan['nama_bahan']; ?></td>
                            <td><?php echo $bahan['ukuran']; ?></td>
                            <td>
                                <?php if ($bahan['stok'] < 20): ?>
                                <span class="badge bg-danger"><?php echo $bahan['stok']; ?></span>
                                <?php else: ?>
                                <?php echo $bahan['stok']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $bahan['nama_cabang']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="<?php echo $bahan['id_bahan']; ?>"
                                        data-nama="<?php echo $bahan['nama_bahan']; ?>"
                                        data-ukuran="<?php echo $bahan['ukuran']; ?>"
                                        data-stok="<?php echo $bahan['stok']; ?>"
                                        data-cabang="<?php echo $bahan['id_cabang']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $bahan['id_bahan']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus bahan ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Bahan -->
<div class="modal fade" id="modalBahan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Bahan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_bahan" id="id_bahan">
                    
                    <div class="mb-3">
                        <label for="nama_bahan" class="form-label">Nama Bahan</label>
                        <input type="text" class="form-control" id="nama_bahan" name="nama_bahan" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ukuran" class="form-label">Ukuran</label>
                        <input type="text" class="form-control" id="ukuran" name="ukuran" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="stok" class="form-label">Stok</label>
                        <input type="number" class="form-control" id="stok" name="stok" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_cabang" class="form-label">Cabang</label>
                        <select class="form-select" id="id_cabang" name="id_cabang" required>
                            <option value="">Pilih Cabang</option>
                            <?php foreach ($cabangs as $cabang): ?>
                            <option value="<?php echo $cabang['id_cabang']; ?>">
                                <?php echo $cabang['nama_cabang']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Event untuk tombol edit
    document.querySelectorAll('.btn-edit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Set judul modal
            document.getElementById('modalTitle').textContent = 'Edit Bahan';
            
            // Isi form dengan data bahan
            document.getElementById('id_bahan').value = this.getAttribute('data-id');
            document.getElementById('nama_bahan').value = this.getAttribute('data-nama');
            document.getElementById('ukuran').value = this.getAttribute('data-ukuran');
            document.getElementById('stok').value = this.getAttribute('data-stok');
            document.getElementById('id_cabang').value = this.getAttribute('data-cabang');
            
            // Tampilkan modal
            let modal = new bootstrap.Modal(document.getElementById('modalBahan'));
            modal.show();
        });
    });
    
    // Reset form ketika modal ditutup
    document.getElementById('modalBahan').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Tambah Bahan';
        document.getElementById('id_bahan').value = '';
        document.getElementById('nama_bahan').value = '';
        document.getElementById('ukuran').value = '';
        document.getElementById('stok').value = '';
        document.getElementById('id_cabang').value = '';
    });
});
</script>

<?php include 'footer.php'; ?>
