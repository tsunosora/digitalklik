<?php
// users.php - Kelola pengguna
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

// Hapus pengguna
if (isset($_GET['delete'])) {
    $id_user = (int)$_GET['delete'];
    
    // Cek bukan user sendiri yang dihapus
    if ($id_user == $_SESSION['user']['id']) {
        $error = "Anda tidak dapat menghapus akun Anda sendiri!";
    } else {
        $sql = "DELETE FROM users WHERE id_user = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_user);
        
        if ($stmt->execute()) {
            $success = "Pengguna berhasil dihapus!";
        } else {
            $error = "Gagal menghapus pengguna!";
        }
    }
}

// Tambah atau update pengguna
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $nama_lengkap = cleanInput($_POST['nama_lengkap']);
    $role = cleanInput($_POST['role']);
    $id_cabang = null;
    
    if ($role == 'operator') {
        $id_cabang = (int)$_POST['id_cabang'];
    }
    
    if (isset($_POST['id_user'])) {
        // Update pengguna
        $id_user = (int)$_POST['id_user'];
        
        if (!empty($_POST['password'])) {
            // Update dengan password baru
            $password = $_POST['password'];
            
            $sql = "UPDATE users SET username = ?, password = ?, nama_lengkap = ?, role = ?, id_cabang = ? WHERE id_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiii", $username, $password, $nama_lengkap, $role, $id_cabang, $id_user);
        } else {
            // Update tanpa mengubah password
            $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ?, id_cabang = ? WHERE id_user = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiii", $username, $nama_lengkap, $role, $id_cabang, $id_user);
        }
        
        if ($stmt->execute()) {
            $success = "Pengguna berhasil diperbarui!";
        } else {
            $error = "Gagal memperbarui pengguna! Username mungkin sudah digunakan.";
        }
    } else {
        // Tambah pengguna baru
        $password = 'password'; // Default password
        
        $sql = "INSERT INTO users (username, password, nama_lengkap, role, id_cabang) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $password, $nama_lengkap, $role, $id_cabang);
        
        if ($stmt->execute()) {
            $success = "Pengguna baru berhasil ditambahkan! Password default: password";
        } else {
            $error = "Gagal menambahkan pengguna baru! Username mungkin sudah digunakan.";
        }
    }
}

// Ambil data pengguna
$sql = "SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.id_cabang = c.id_cabang ORDER BY u.username";
$result = $conn->query($sql);
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

// Ambil data cabang untuk form
$sql = "SELECT id_cabang, nama_cabang FROM cabang";
$result = $conn->query($sql);
$cabangs = [];
while ($row = $result->fetch_assoc()) {
    $cabangs[] = $row;
}

$conn->close();

include '../../includes/header.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-users me-2"></i>Kelola Pengguna</h2>
        </div>
        <div class="col-md-6 text-md-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUser">
                <i class="fas fa-plus me-2"></i>Tambah Pengguna
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
            <h5 class="mb-0">Daftar Pengguna</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Cabang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Tidak ada data pengguna</td>
                        </tr>
                        <?php else: ?>
                        <?php $no = 1; foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['nama_lengkap']; ?></td>
                            <td>
                                <?php if ($user['role'] == 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                <span class="badge bg-primary">Operator</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo ($user['role'] == 'operator') ? $user['nama_cabang'] : '-'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" 
                                        data-id="<?php echo $user['id_user']; ?>"
                                        data-username="<?php echo $user['username']; ?>"
                                        data-nama="<?php echo $user['nama_lengkap']; ?>"
                                        data-role="<?php echo $user['role']; ?>"
                                        data-cabang="<?php echo $user['id_cabang']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($user['id_user'] != $_SESSION['user']['id']): ?>
                                <a href="?delete=<?php echo $user['id_user']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                                <?php endif; ?>
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

<!-- Modal Tambah/Edit Pengguna -->
<div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="id_user" id="id_user">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required onchange="toggleCabang()">
                            <option value="admin">Administrator</option>
                            <option value="operator">Operator</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="cabang-container" style="display: none;">
                        <label for="id_cabang" class="form-label">Cabang</label>
                        <select class="form-select" id="id_cabang" name="id_cabang">
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
            document.getElementById('modalTitle').textContent = 'Edit Pengguna';
            
            // Isi form dengan data pengguna
            document.getElementById('id_user').value = this.getAttribute('data-id');
            document.getElementById('username').value = this.getAttribute('data-username');
            document.getElementById('nama_lengkap').value = this.getAttribute('data-nama');
            document.getElementById('role').value = this.getAttribute('data-role');
            
            // Tampilkan/sembunyikan pilihan cabang
            toggleCabang();
            
            if (this.getAttribute('data-role') == 'operator') {
                document.getElementById('id_cabang').value = this.getAttribute('data-cabang');
            }
            
            // Tampilkan modal
            let modal = new bootstrap.Modal(document.getElementById('modalUser'));
            modal.show();
        });
    });
    
    // Reset form ketika modal ditutup
    document.getElementById('modalUser').addEventListener('hidden.bs.modal', function() {
        document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
        document.getElementById('id_user').value = '';
        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
        document.getElementById('nama_lengkap').value = '';
        document.getElementById('role').value = 'admin';
        document.getElementById('id_cabang').value = '';
        toggleCabang();
    });
});

// Fungsi untuk menampilkan/menyembunyikan pilihan cabang
function toggleCabang() {
    let role = document.getElementById('role').value;
    let cabangContainer = document.getElementById('cabang-container');
    
    if (role == 'operator') {
        cabangContainer.style.display = 'block';
        document.getElementById('id_cabang').setAttribute('required', 'required');
    } else {
        cabangContainer.style.display = 'none';
        document.getElementById('id_cabang').removeAttribute('required');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
