<?php
// filepath: /rumah adat/admin.php
session_start();

// Cek apakah user sudah login sebagai admin
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'connect.php';

// Tambah data
if (isset($_POST['add'])) {
    $name = trim($_POST['username']);
    $school = trim($_POST['school']);
    $score = (int)$_POST['score'];
    if ($name && $school) {
        $stmt = $pdo->prepare("INSERT INTO users (username, school, score) VALUES (?, ?, ?)");
        $stmt->execute([$name, $school, $score]);
        header("Location: admin.php");
        exit;
    }
}

// Edit data
if (isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['username']);
    $school = trim($_POST['school']);
    $score = (int)$_POST['score'];
    $stmt = $pdo->prepare("UPDATE users SET username=?, school=?, score=? WHERE id=?");
    $stmt->execute([$name, $school, $score, $id]);
    header("Location: admin.php");
    exit;
}

// Hapus data
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);
    header("Location: admin.php");
    exit;
}

// Ambil data untuk edit
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Tampilkan semua data
$users = $pdo->query("SELECT * FROM users ORDER BY score DESC, username ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Rumah Adat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #fff8f0;
            font-family: 'Poppins', sans-serif;
            color: #4b3b2b;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(to bottom, #5c3a21, #a9744f);
            padding: 1rem;
            color: white;
        }
        .sidebar .nav-link {
            color: #ffffff;
            transition: all 0.3s;
            border-radius: 8px;
            margin: 2px 0;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link:hover {
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            background-color: #d4a017;
            color: #5c3a21;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .btn-ethnic {
            background: linear-gradient(135deg, #a9744f, #d4a017);
            color: white;
            border: none;
            transition: all 0.3s;
            border-radius: 5px;
        }
        .btn-ethnic:hover {
            background: linear-gradient(135deg, #d4a017, #a9744f);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(169, 116, 79, 0.3);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar position-fixed">
        <div class="p-3">
            <h4 class="text-white">Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Kembali ke Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="save_score.php">
                        <i class="fas fa-chart-bar"></i> Simpan Skor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://github.com/rumah-adat" target="_blank">
                        <i class="fab fa-github"></i> GitHub Repository
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://rumah-adat-documentation.netlify.app" target="_blank">
                        <i class="fas fa-book"></i> Dokumentasi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://wa.me/6281234567890" target="_blank">
                        <i class="fab fa-whatsapp"></i> Support WhatsApp
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <h1 class="h3 mb-4">
                        <i class="fas fa-cogs"></i> Admin Panel
                    </h1>
                    
                    <!-- Card untuk Link Penting -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fab fa-github text-dark"></i> GitHub
                                    </h5>
                                    <p class="card-text">Akses repository GitHub untuk kode sumber lengkap.</p>
                                    <a href="https://github.com/rumah-adat" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-external-link-alt"></i> Kunjungi GitHub
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fas fa-book text-info"></i> Dokumentasi
                                    </h5>
                                    <p class="card-text">Panduan lengkap penggunaan sistem rumah adat.</p>
                                    <a href="https://rumah-adat-documentation.netlify.app" target="_blank" class="btn btn-info">
                                        <i class="fas fa-external-link-alt"></i> Baca Dokumentasi
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <i class="fab fa-whatsapp text-success"></i> Support
                                    </h5>
                                    <p class="card-text">Hubungi tim support untuk bantuan teknis.</p>
                                    <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-success">
                                        <i class="fas fa-external-link-alt"></i> Chat WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="max-w-2xl mx-auto py-8">
                        <h1 class="text-2xl font-bold mb-6">Manajemen User (CRUD)</h1>
                        <!-- Form Tambah/Edit -->
                        <form method="post" class="mb-8 bg-white p-6 rounded shadow">
                            <input type="hidden" name="id" value="<?= $editUser['id'] ?? '' ?>">
                            <div class="mb-3">
                                <label>Nama:</label>
                                <input type="text" name="username" required class="border p-2 rounded w-full" value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Sekolah:</label>
                                <input type="text" name="school" required class="border p-2 rounded w-full" value="<?= htmlspecialchars($editUser['school'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label>Score:</label>
                                <input type="number" name="score" min="0" max="100" required class="border p-2 rounded w-full" value="<?= htmlspecialchars($editUser['score'] ?? 0) ?>">
                            </div>
                            <div>
                                <?php if ($editUser): ?>
                                    <button type="submit" name="edit" class="bg-blue-600 text-white px-4 py-2 rounded">Update</button>
                                    <a href="admin.php" class="ml-2 text-blue-600">Batal</a>
                                <?php else: ?>
                                    <button type="submit" name="add" class="bg-green-600 text-white px-4 py-2 rounded">Tambah</button>
                                <?php endif; ?>
                            </div>
                        </form>
                        <!-- Tabel Data -->
                        <table class="w-full bg-white rounded shadow">
                            <thead>
                                <tr class="bg-blue-100">
                                    <th class="py-2 px-2">Nama</th>
                                    <th class="py-2 px-2">Sekolah</th>
                                    <th class="py-2 px-2">Score</th>
                                    <th class="py-2 px-2">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="py-2 px-2"><?= htmlspecialchars($u['username']) ?></td>
                                    <td class="py-2 px-2"><?= htmlspecialchars($u['school']) ?></td>
                                    <td class="py-2 px-2"><?= $u['score'] ?></td>
                                    <td class="py-2 px-2">
                                        <a href="admin.php?edit=<?= $u['id'] ?>" class="text-blue-600">Edit</a> |
                                        <a href="admin.php?delete=<?= $u['id'] ?>" class="text-red-600" onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="mt-6">
                            <a href="dashboard.php" class="text-blue-700">← Kembali ke Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
