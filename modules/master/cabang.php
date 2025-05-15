<?php
// orders.php - Daftar order
require_once '../../includes/config.php';
checkLogin();

// Kode lain...

include '../../includes/header.php';

// Cek akses admin
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$conn = connectDB();
$error = '';
$success = '';

// Hapus cabang
if (isset($_GET['delete'])) {
    $id_cabang = (int)$_GET['delete'];
    
    $sql = "DELETE FROM cabang WHERE id_cabang = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_cabang);
    
    if ($stmt->execute()) {
        $success = "Cabang berhasil dihapus!";
    } else {
        $error = "Gagal menghapus cabang. Mungkin masih ada mesin, bahan, atau order yang terkait dengan cabang ini!";
    }
}

// Tambah atau update cabang
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_cabang = cleanInput($_POST['nama_cabang']);
    $alamat = cleanInput($_POST['alamat']);
    
    if (isset($_POST['id_cabang'])) {
        // Update cabang
        $id_cabang = (int)$_POST['id_cabang'];
        
        $sql = "UPDATE cabang SET nama_cabang = ?, alamat = ? WHERE id_cabang = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nama_cabang, $alamat, $id_cabang);
        
        if ($stmt->execute()) {
            $success = "Cabang berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui cabang!";
        }
    } else {
        // Tambah cabang baru
        $sql = "INSERT INTO cabang (nama_cabang, alamat) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nama_cabang, $alamat);
        
        if ($stmt->execute()) {
            $success = "Cabang baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan cabang baru!";
        }
    }
}

// Ambil data cabang
$sql = "SELECT * FROM cabang ORDER BY nama_cabang";
$result = $conn->query($sql);
$cabangs = [];
while ($row = $result->fetch_assoc()) {
    $cabangs[] = $row;
}

$conn->close();
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-building me-2"></i>Kelola Cabang</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCabang">
                <i class="fas fa-plus me-2"></i>Tambah Cabang
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
            <h5 class="mb-0">Daftar Cabang</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Cabang</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cabangs)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data cabang</td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; foreach ($cabangs as $cabang): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $cabang['nama_cabang']; ?></td>
                            <td><?php echo $cabang['alamat']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="<?php echo $cabang['id_cabang']; ?>"
                                        data-nama="<?php echo $cabang['nama_cabang']; ?>"
                                        data-alamat="<?php echo $cabang['alamat']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $cabang['id_cabang']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus cabang ini? Semua data terkait (mesin, bahan, order) juga akan terhapus.')">
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

<!-- Modal Tambah/Edit Cabang -->
<div class="modal fade" id="modalCabang" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Cabang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_cabang" id="id_cabang">
                    
                    <div class="mb-3">
                        <label for="nama_cabang" class="form-label">Nama Cabang</label>
                        <input type="text" class="form-control" id="nama_cabang" name="nama_cabang" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
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
            document.getElementById('modalTitle').textContent = 'Edit Cabang';
            
            // Isi form dengan data cabang
            document.getElementById('id_cabang').value = this.getAttribute('data-id');
            document.getElementById('nama_cabang').value = this.getAttribute('data-nama');
            document.getElementById('alamat').value = this.getAttribute('data-alamat');
            
            // Tampilkan modal
            let modal = new bootstrap.Modal(document.getElementById('modalCabang'));
            modal.show();
        });
    });
    
    // Reset form ketika modal ditutup
    document.getElementById('modalCabang').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Tambah Cabang';
        document.getElementById('id_cabang').value = '';
        document.getElementById('nama_cabang').value = '';
        document.getElementById('alamat').value = '';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
