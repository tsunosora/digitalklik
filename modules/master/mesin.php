<?php
// mesin.php - Kelola mesin
require_once '../../includes/config.php';
checkLogin();

include '../../includes/header.php';

// Cek akses admin
if ($_SESSION['user']['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$conn = connectDB();
$error = '';
$success = '';

// Hapus mesin
if (isset($_GET['delete'])) {
    $id_mesin = (int)$_GET['delete'];
    
    $sql = "DELETE FROM mesin WHERE id_mesin = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_mesin);
    
    if ($stmt->execute()) {
        $success = "Mesin berhasil dihapus!";
    } else {
        $error = "Gagal menghapus mesin. Mungkin sedang digunakan pada order!";
    }
}

// Tambah atau update mesin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_mesin = cleanInput($_POST['nama_mesin']);
    $id_cabang = (int)$_POST['id_cabang'];
    
    if (isset($_POST['id_mesin'])) {
        // Update mesin
        $id_mesin = (int)$_POST['id_mesin'];
        
        $sql = "UPDATE mesin SET nama_mesin = ?, id_cabang = ? WHERE id_mesin = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $nama_mesin, $id_cabang, $id_mesin);
        
        if ($stmt->execute()) {
            $success = "Mesin berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui mesin!";
        }
    } else {
        // Tambah mesin baru
        $sql = "INSERT INTO mesin (nama_mesin, id_cabang) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nama_mesin, $id_cabang);
        
        if ($stmt->execute()) {
            $success = "Mesin baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan mesin baru!";
        }
    }
}

// Ambil data mesin
$sql = "SELECT m.*, c.nama_cabang FROM mesin m JOIN cabang c ON m.id_cabang = c.id_cabang ORDER BY c.nama_cabang, m.nama_mesin";
$result = $conn->query($sql);
$mesins = [];
while ($row = $result->fetch_assoc()) {
    $mesins[] = $row;
}

// Ambil data cabang untuk form
$sql = "SELECT id_cabang, nama_cabang FROM cabang";
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
            <h2><i class="fas fa-print me-2"></i>Kelola Mesin</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMesin">
                <i class="fas fa-plus me-2"></i>Tambah Mesin
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
            <h5 class="mb-0">Daftar Mesin</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Mesin</th>
                            <th>Cabang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mesins)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data mesin</td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; foreach ($mesins as $mesin): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $mesin['nama_mesin']; ?></td>
                            <td><?php echo $mesin['nama_cabang']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="<?php echo $mesin['id_mesin']; ?>"
                                        data-nama="<?php echo $mesin['nama_mesin']; ?>"
                                        data-cabang="<?php echo $mesin['id_cabang']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $mesin['id_mesin']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus mesin ini?')">
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

<!-- Modal Tambah/Edit Mesin -->
<div class="modal fade" id="modalMesin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Mesin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_mesin" id="id_mesin">
                    
                    <div class="mb-3">
                        <label for="nama_mesin" class="form-label">Nama Mesin</label>
                        <input type="text" class="form-control" id="nama_mesin" name="nama_mesin" required>
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
            document.getElementById('modalTitle').textContent = 'Edit Mesin';
            
            // Isi form dengan data mesin
            document.getElementById('id_mesin').value = this.getAttribute('data-id');
            document.getElementById('nama_mesin').value = this.getAttribute('data-nama');
            document.getElementById('id_cabang').value = this.getAttribute('data-cabang');
            
            // Tampilkan modal
            let modal = new bootstrap.Modal(document.getElementById('modalMesin'));
            modal.show();
        });
    });
    
    // Reset form ketika modal ditutup
    document.getElementById('modalMesin').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Tambah Mesin';
        document.getElementById('id_mesin').value = '';
        document.getElementById('nama_mesin').value = '';
        document.getElementById('id_cabang').value = '';
    });
});
</script>

<?php include '../../footer.php'; ?>
