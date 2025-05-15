<?php
// reject.php - Kelola reject mesin
require_once '../../includes/config.php';
checkLogin();

// Cek akses admin
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$conn = connectDB();
$error = '';
$success = '';

// Hapus reject
if (isset($_GET['delete'])) {
    $id_reject = (int)$_GET['delete'];
    
    $sql = "DELETE FROM reject_mesin WHERE id_reject = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_reject);
    
    if ($stmt->execute()) {
        $success = "Reject mesin berhasil dihapus!";
    } else {
        $error = "Gagal menghapus reject mesin. Mungkin sedang digunakan pada order!";
    }
}

// Tambah atau update reject
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_reject = cleanInput($_POST['nama_reject']);
    $deskripsi = cleanInput($_POST['deskripsi']);
    
    if (isset($_POST['id_reject'])) {
        // Update reject
        $id_reject = (int)$_POST['id_reject'];
        
        $sql = "UPDATE reject_mesin SET nama_reject = ?, deskripsi = ? WHERE id_reject = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nama_reject, $deskripsi, $id_reject);
        
        if ($stmt->execute()) {
            $success = "Reject mesin berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui reject mesin!";
        }
    } else {
        // Tambah reject baru
        $sql = "INSERT INTO reject_mesin (nama_reject, deskripsi) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $nama_reject, $deskripsi);
        
        if ($stmt->execute()) {
            $success = "Reject mesin baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan reject mesin baru!";
        }
    }
}

// Ambil data reject mesin
$sql = "SELECT * FROM reject_mesin ORDER BY nama_reject";
$result = $conn->query($sql);
$rejects = [];
while ($row = $result->fetch_assoc()) {
    $rejects[] = $row;
}

$conn->close();

include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-exclamation-triangle me-2"></i>Kelola Reject Mesin</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalReject">
                <i class="fas fa-plus me-2"></i>Tambah Reject Mesin
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
            <h5 class="mb-0">Daftar Reject Mesin</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Reject</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rejects)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data reject mesin</td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; foreach ($rejects as $reject): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $reject['nama_reject']; ?></td>
                            <td><?php echo $reject['deskripsi']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="<?php echo $reject['id_reject']; ?>"
                                        data-nama="<?php echo $reject['nama_reject']; ?>"
                                        data-deskripsi="<?php echo $reject['deskripsi']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $reject['id_reject']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus reject mesin ini?')">
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

<!-- Modal Tambah/Edit Reject Mesin -->
<div class="modal fade" id="modalReject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Reject Mesin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_reject" id="id_reject">
                    
                    <div class="mb-3">
                        <label for="nama_reject" class="form-label">Nama Reject</label>
                        <input type="text" class="form-control" id="nama_reject" name="nama_reject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
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
            document.getElementById('modalTitle').textContent = 'Edit Reject Mesin';
            
            // Isi form dengan data reject
            document.getElementById('id_reject').value = this.getAttribute('data-id');
            document.getElementById('nama_reject').value = this.getAttribute('data-nama');
            document.getElementById('deskripsi').value = this.getAttribute('data-deskripsi');
            
            // Tampilkan modal
            let modal = new bootstrap.Modal(document.getElementById('modalReject'));
            modal.show();
        });
    });
    
    // Reset form ketika modal ditutup
    document.getElementById('modalReject').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Tambah Reject Mesin';
        document.getElementById('id_reject').value = '';
        document.getElementById('nama_reject').value = '';
        document.getElementById('deskripsi').value = '';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
