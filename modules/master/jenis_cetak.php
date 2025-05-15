<?php
// jenis_cetak.php - Kelola jenis cetak
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

// Hapus jenis cetak
if (isset($_GET['delete'])) {
    $id_jenis = (int)$_GET['delete'];
    
    $sql = "DELETE FROM jenis_cetak WHERE id_jenis = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_jenis);
    
    if ($stmt->execute()) {
        $success = "Jenis cetak berhasil dihapus!";
    } else {
        $error = "Gagal menghapus jenis cetak. Mungkin sedang digunakan pada order!";
    }
}

// Tambah atau update jenis cetak
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_jenis = cleanInput($_POST['nama_jenis']);
    $biaya_klik = cleanInput($_POST['biaya_klik']);
    
    if (isset($_POST['id_jenis'])) {
        // Update jenis cetak
        $id_jenis = (int)$_POST['id_jenis'];
        
        $sql = "UPDATE jenis_cetak SET nama_jenis = ?, biaya_klik = ? WHERE id_jenis = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdi", $nama_jenis, $biaya_klik, $id_jenis);
        
        if ($stmt->execute()) {
            $success = "Jenis cetak berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui jenis cetak!";
        }
    } else {
        // Tambah jenis cetak baru
        $sql = "INSERT INTO jenis_cetak (nama_jenis, biaya_klik) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sd", $nama_jenis, $biaya_klik);
        
        if ($stmt->execute()) {
            $success = "Jenis cetak baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan jenis cetak baru!";
        }
    }
}

// Ambil data jenis cetak
$sql = "SELECT * FROM jenis_cetak ORDER BY nama_jenis";
$result = $conn->query($sql);
$jenisCetaks = [];
while ($row = $result->fetch_assoc()) {
    $jenisCetaks[] = $row;
}

$conn->close();

include 'header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-list me-2"></i>Kelola Jenis Cetak</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalJenisCetak">
                <i class="fas fa-plus me-2"></i>Tambah Jenis Cetak
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
            <h5 class="mb-0">Daftar Jenis Cetak</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Jenis</th>
                            <th>Biaya Klik</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jenisCetaks)): ?>
                        <tr>
                            <td colspan="4" class="text-center">Tidak ada data jenis cetak</td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; foreach ($jenisCetaks as $jenis): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $jenis['nama_jenis']; ?></td>
                            <td><?php echo formatRupiah($jenis['biaya_klik']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="<?php echo $jenis['id_jenis']; ?>"
                                        data-nama="<?php echo $jenis['nama_jenis']; ?>"
                                        data-biaya="<?php echo $jenis['biaya_klik']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $jenis['id_jenis']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus jenis cetak ini?')">
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

<!-- Modal Tambah/Edit Jenis Cetak -->
<div class="modal fade" id="modalJenisCetak" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Jenis Cetak</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_jenis" id="id_jenis">
                    
                    <div class="mb-3">
                        <label for="nama_jenis" class="form-label">Nama Jenis</label>
                        <input type="text" class="form-control" id="nama_jenis" name="nama_jenis" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="biaya_klik" class="form-label">Biaya Klik</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" id="biaya_klik" name="biaya_klik" min="0" step="0.01" required>
                        </div>
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
            document.getElementById('modalTitle').textContent = 'Edit Jenis Cetak';
            
            // Isi form dengan data jenis cetak
            document.getElementById('id_jenis').value = this.getAttribute('data-id');
            document.getElementById('nama_jenis').value = this.getAttribute('data-nama');
            document.getElementById('biaya_klik').value = this.getAttribute('data-biaya');
            
            // Tampilkan modal
            let modal = new bootstrap.Modal(document.getElementById('modalJenisCetak'));
            modal.show();
        });
    });
    
    // Reset form ketika modal ditutup
    document.getElementById('modalJenisCetak').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Tambah Jenis Cetak';
        document.getElementById('id_jenis').value = '';
        document.getElementById('nama_jenis').value = '';
        document.getElementById('biaya_klik').value = '';
    });
});
</script>

<?php include 'footer.php'; ?>
