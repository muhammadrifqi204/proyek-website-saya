<?php
// filepath: c:\xamp\htdocs\rumah adat\dashboard.php
include 'connect.php';

/* Pastikan tabel users ada */
function ensureUsersTable(PDO $pdo){
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            school VARCHAR(150) NOT NULL,
            score INT NOT NULL DEFAULT 0,
            total_questions INT NOT NULL DEFAULT 0,
            status ENUM('LULUS','TIDAK LULUS') GENERATED ALWAYS AS (
                CASE WHEN score >= 14 THEN 'LULUS' ELSE 'TIDAK LULUS' END
            ) VIRTUAL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    // Tambah kolom jika belum ada (untuk tabel lama)
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if(!in_array('total_questions',$cols)){
        $pdo->exec("ALTER TABLE users ADD COLUMN total_questions INT NOT NULL DEFAULT 0 AFTER score");
    }
}
try { ensureUsersTable($pdo); } catch(PDOException $e){ die("Gagal inisialisasi tabel: ".$e->getMessage()); }

$sql = "DESCRIBE users";
$result = $pdo->query($sql);

if (!$result) {
    die("Query error: " . $pdo->errorInfo()[2]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <!-- ====== Meta dan Pengaturan Head ====== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rumah Adat Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- ====== Style dan Font ====== -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(255,255,255,0.05) 0%, transparent 50%);
            z-index: 0;
            pointer-events: none;
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .glass-dark {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .glass-button {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        
        .glass-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        
        .header-glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .text-glass {
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .search-glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 12px;
        }
        
        .search-glass::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .modal-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
        }
        
        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 10%;
            right: 30%;
            animation-delay: 1s;
        }
        
        .shape:nth-child(5) {
            width: 70px;
            height: 70px;
            bottom: 40%;
            right: 10%;
            animation-delay: 3s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .container {
            position: relative;
            z-index: 1;
        }
        
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            background: rgba(255, 255, 255, 0.2);
        }
        
        .card-image {
            height: 200px;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .card:hover .card-image img {
            transform: scale(1.05);
        }
        
        .btn-ethnic {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.3s;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-ethnic:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .detail-header {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .feature-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid rgba(255, 255, 255, 0.5);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            color: #333;
        }
        
        .nav-btn {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            overflow-y: auto;
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            margin: 2% auto;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 90%;
            max-width: 900px;
            border-radius: 20px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .close {
            color: #666;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #333;
        }
        
        .visualization-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
        }
        
        .visualization-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .border-ethnic {
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .text-card {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .text-card-dark {
            color: rgba(0, 0, 0, 0.8);
        }
        
        input.search-glass:focus, select.search-glass:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .card-image {
                height: 150px;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    /* ========== OVERRIDE WARNA TEKS KE HITAM ========== */
    body {
        color:#111 !important;
    }
    .text-glass,
    .text-card,
    .text-card-dark,
    .detail-header,
    .feature-box,
    .modal-content,
    .glass,
    .glass-card,
    .glass-dark,
    .btn-ethnic,
    .glass-button,
    .nav-btn {
        color:#111 !important;
        text-shadow:none !important;
    }
    .glass,
    .glass-card,
    .glass-dark,
    .detail-header,
    .feature-box,
    .modal-content {
        background:rgba(255,255,255,0.70) !important;
        backdrop-filter:blur(12px);
        -webkit-backdrop-filter:blur(12px);
        border:1px solid rgba(0,0,0,0.12);
    }
    .header-glass {
        background:rgba(255,255,255,0.55) !important;
        color:#111 !important;
        text-shadow:none !important;
    }
    .search-glass {
        background:rgba(255,255,255,0.85) !important;
        color:#111 !important;
        border:1px solid rgba(0,0,0,0.2);
    }
    .search-glass::placeholder { color:rgba(0,0,0,0.55) !important; }
    .btn-ethnic,
    .glass-button,
    .nav-btn {
        background:rgba(0,0,0,0.06) !important;
        border:1px solid rgba(0,0,0,0.18) !important;
    }
    .btn-ethnic:hover,
    .glass-button:hover,
    .nav-btn:hover {
        background:rgba(0,0,0,0.12) !important;
    }
    .detail-header { background:rgba(255,255,255,0.85) !important; }
    .feature-box { background:rgba(255,255,255,0.9) !important; }
    .close { color:#222 !important; }
    .close:hover { color:#000 !important; }
    /* Pastikan teks paragraf dalam modal hitam */
    #modal-content p,
    #modal-content h2,
    #modal-content h3,
    #modal-content h4 { color:#111 !important; }
    /* ====== STYLE KOMENTAR GLASS ====== */
.comment-box {
    background: rgba(255,255,255,0.65);
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 1rem 1.2rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(0,0,0,0.08);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.comment-box .comment-header {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    margin-bottom: 0.3rem;
}
.comment-box .comment-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 1rem;
}
.comment-box .comment-date {
    font-size: 0.85rem;
    color: #64748b;
}
.comment-box .comment-text {
    color: #222;
    font-size: 0.98rem;
    margin-top: 0.2rem;
    word-break: break-word;
}
.comment-box .comment-actions {
    margin-top: 0.5rem;
    text-align: right;
}
.comment-box .comment-actions button {
    background: rgba(99,102,241,0.08);
    color: #4f46e5;
    border: none;
    border-radius: 8px;
    padding: 3px 12px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: background 0.2s;
}
.comment-box .comment-actions button:hover {
    background: rgba(99,102,241,0.18);
}
.comment-form input,
.comment-form textarea {
    background: rgba(255,255,255,0.85);
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    padding: 0.7rem 1rem;
    margin-bottom: 0.7rem;
    color: #222;
    font-size: 1rem;
}
.comment-form button {
    background: linear-gradient(90deg,#6366f1,#8b5cf6);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 0.7rem 1.2rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(99,102,241,0.12);
    transition: background 0.2s;
}
.comment-form button:hover {
    background: linear-gradient(90deg,#4f46e5,#6366f1);
}

/* ================= NUSANTARA BROWN + LIQUID UI (Updated Colors) ================= */
:root{
  --brown-light: #ab684b;
  --brown-medium: #ab684b;
  --brown-dark: #5e3a30;
  --dark-primary: #341a16;
  --dark-secondary: #222222;
  --dark-accent: #490d01;
  --cream: #F5E6D3;
  --gold: #DAA520;
  --copper: #B87333;
  --mahogany: #C04000;
  --glass-light: rgba(245,230,211,.25);
  --glass-deep:  rgba(245,230,211,.15);
  --liquid-brown: linear-gradient(135deg,#ab684b 0%, #ab684b 50%, #5e3a30 100%);
  --btn-nusantara: linear-gradient(135deg,#5e3a30 0%, #ab684b 50%, #ab684b 100%);
  --liquid-dark: linear-gradient(135deg,#341a16 0%, #222222 50%, #490d01 100%);
  --btn-dark: linear-gradient(135deg,#490d01 0%, #222222 50%, #341a16 100%);
}

/* Background Light Mode */
body{
  background: 
    radial-gradient(1200px 800px at 80% -10%, rgba(171,104,75,.25), transparent 55%),
    radial-gradient(900px 600px at -10% 110%, rgba(94,58,48,.20), transparent 50%),
    linear-gradient(135deg, #ab684b 0%, #ab684b 30%, #5e3a30 70%, #5e3a30 100%) !important;
  background-attachment: fixed !important;
  color: #ffffff !important; /* Text putih untuk kontras */
}

/* Background Dark Mode */
body.theme-dark{
  background: 
    radial-gradient(1000px 700px at 80% -10%, rgba(52,26,22,.35), transparent 55%),
    radial-gradient(800px 500px at -10% 110%, rgba(73,13,1,.25), transparent 50%),
    linear-gradient(135deg, #341a16 0%, #222222 30%, #490d01 70%, #341a16 100%) !important;
  background-attachment: fixed !important;
  color: #f5f5f5 !important; /* Text putih terang untuk dark mode */
}

/* Liquid Glass Cards */
.card, .glass, .glass-card, .glass-dark, .modal-content, .detail-header, .feature-box{
  position: relative;
  background: rgba(255,255,255,.15) !important; /* Lebih transparan agar background terlihat */
  border: 1px solid transparent !important;
  border-radius: 24px !important;
  backdrop-filter: blur(16px) saturate(1.2);
  -webkit-backdrop-filter: blur(16px) saturate(1.2);
  overflow: hidden;
  box-shadow: 
    0 12px 32px rgba(0,0,0,.25),
    inset 0 1px 0 rgba(255,255,255,.2);
  color: #ffffff !important; /* Text putih untuk semua card */
}
.card::before, .glass::before, .glass-card::before, .glass-dark::before,
.modal-content::before, .detail-header::before, .feature-box::before{
  content:"";
  position:absolute; inset:0; padding:2px; border-radius:inherit;
  background: var(--liquid-brown);
  -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite: xor; mask-composite: exclude;
  pointer-events:none; opacity:.7;
  animation: liquid-flow 8s linear infinite;
}
body.theme-dark .card, 
body.theme-dark .glass, 
body.theme-dark .glass-card, 
body.theme-dark .glass-dark, 
body.theme-dark .modal-content, 
body.theme-dark .detail-header, 
body.theme-dark .feature-box{
  background: rgba(0,0,0,.25) !important;
  box-shadow: 
    0 12px 32px rgba(0,0,0,.35),
    inset 0 1px 0 rgba(255,255,255,.1);
  color: #f5f5f5 !important; /* Text putih terang untuk dark mode */
}
body.theme-dark .card::before, 
body.theme-dark .glass::before, 
body.theme-dark .glass-card::before, 
body.theme-dark .glass-dark::before,
body.theme-dark .modal-content::before, 
body.theme-dark .detail-header::before, 
body.theme-dark .feature-box::before{
  background: var(--liquid-dark);
}

/* Liquid Buttons */
.btn-ethnic, .glass-button, .nav-btn{
  position: relative;
  border: 1px solid transparent !important;
  background: var(--btn-nusantara) !important;
  color: #FFFFFF !important; 
  font-weight: 800; 
  letter-spacing: .02em;
  border-radius: 16px !important;
  box-shadow: 
    0 8px 28px rgba(0,0,0,.25), 
    0 0 24px rgba(171,104,75,.20),
    inset 0 1px 0 rgba(255,255,255,.2);
  transition: all .15s cubic-bezier(.4,0,.2,1);
  text-shadow: 0 1px 2px rgba(0,0,0,.5);
}
.btn-ethnic::before, .glass-button::before, .nav-btn::before{
  content:"";
  position:absolute; inset:0; border-radius:inherit;
  background: linear-gradient(135deg, rgba(255,255,255,.1), transparent 50%, rgba(0,0,0,.1));
  pointer-events:none;
}
.btn-ethnic:hover, .glass-button:hover, .nav-btn:hover{
  transform: translateY(-3px) scale(1.02);
  box-shadow: 
    0 16px 40px rgba(0,0,0,.35), 
    0 0 36px rgba(171,104,75,.30),
    inset 0 1px 0 rgba(255,255,255,.3);
}
body.theme-dark .btn-ethnic, 
body.theme-dark .glass-button, 
body.theme-dark .nav-btn{
  background: var(--btn-dark) !important;
  box-shadow: 
    0 8px 28px rgba(0,0,0,.35), 
    0 0 24px rgba(73,13,1,.25),
    inset 0 1px 0 rgba(255,255,255,.15);
}

/* Header Liquid */
.header-glass{
  position: sticky; top: 0;
  border-radius: 24px;
  isolation: isolate;
  overflow: visible;
  background: rgba(255,255,255,0.12) !important;
  backdrop-filter: blur(20px) saturate(1.2);
  -webkit-backdrop-filter: blur(20px) saturate(1.2);
  box-shadow: 
    0 10px 30px rgba(0,0,0,.25),
    inset 0 1px 0 rgba(255,255,255,.3);
  color: #ffffff !important; /* Text putih untuk header */
}
.header-glass::before{
  content:"";
  position:absolute; inset:-2px;
  border-radius: 28px;
  background: var(--liquid-brown);
  padding:2px;
  -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite: xor; mask-composite: exclude;
  pointer-events:none;
  animation: liquid-flow 10s linear infinite;
  filter: blur(1px);
  opacity:.8;
  z-index:-1;
}
.header-glass::after{
  content:"";
  position:absolute;
  width: 280px; height: 280px;
  top:-90px; left:-50px;
  background: radial-gradient(closest-side, rgba(171,104,75,.25), rgba(171,104,75,0) 70%);
  filter: blur(20px);
  opacity:.3;
  transition: opacity .3s ease, transform .3s ease;
  pointer-events:none;
  z-index:-1;
}
.header-glass:hover::after{
  opacity:.5;
  animation: liquid-orb 8s ease-in-out infinite;
}
body.theme-dark .header-glass{
  background: rgba(0,0,0,0.25) !important;
  box-shadow: 
    0 10px 30px rgba(0,0,0,.45), 
    inset 0 1px 0 rgba(255,255,255,.15);
  color: #f5f5f5 !important;
}
body.theme-dark .header-glass::before{
  background: var(--liquid-dark);
}
body.theme-dark .header-glass::after{
  background: radial-gradient(closest-side, rgba(73,13,1,.35), rgba(73,13,1,0) 70%);
}

/* Theme Toggle Button */
.theme-toggle{
  position: absolute; top: 16px; right: 16px;
  display: inline-flex; align-items: center; gap: 10px;
  padding: 10px 16px; border-radius: 20px;
  border: 1px solid transparent;
  background: var(--btn-nusantara) !important;
  color: #FFFFFF !important; 
  font-weight: 700; 
  letter-spacing: .03em;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 
    0 8px 24px rgba(0,0,0,.25), 
    0 0 20px rgba(171,104,75,.20),
    inset 0 1px 0 rgba(255,255,255,.2);
  transition: all .15s cubic-bezier(.4,0,.2,1);
  cursor: pointer;
  z-index: 22;
  text-shadow: 0 1px 2px rgba(0,0,0,.5);
}
.theme-toggle::before{
  content:"";
  position:absolute; inset:0; border-radius:inherit;
  background: linear-gradient(135deg, rgba(255,255,255,.15), transparent 50%, rgba(0,0,0,.1));
  pointer-events:none;
}
.theme-toggle:hover{
  transform: translateY(-2px) scale(1.05);
  box-shadow: 
    0 12px 32px rgba(0,0,0,.35), 
    0 0 28px rgba(171,104,75,.30),
    inset 0 1px 0 rgba(255,255,255,.3);
}
.theme-toggle:active{
  transform: translateY(0) scale(1.02);
  box-shadow: 
    0 6px 20px rgba(0,0,0,.30), 
    0 0 16px rgba(171,104,75,.25),
    inset 0 1px 0 rgba(255,255,255,.2);
}
body.theme-dark .theme-toggle{
  background: var(--btn-dark) !important;
  box-shadow: 
    0 8px 24px rgba(0,0,0,.35), 
    0 0 20px rgba(73,13,1,.25),
    inset 0 1px 0 rgba(255,255,255,.15);
}

/* Input & Form */
.search-glass{ 
  background: rgba(255,255,255,.25) !important; 
  color: #ffffff !important;
  border: 1px solid rgba(255,255,255,.3) !important;
  border-radius: 14px !important;
}
.search-glass::placeholder{ color: rgba(255,255,255,.8) !important; }
.search-glass:focus{
  outline: none !important;
  border-color: rgba(255,255,255,.6) !important;
  box-shadow: 0 0 0 3px rgba(255,255,255,.25) !important;
}
body.theme-dark .search-glass{ 
  background: rgba(0,0,0,.35) !important; 
  color: #f5f5f5 !important;
  border: 1px solid rgba(255,255,255,.2) !important;
}
body.theme-dark .search-glass::placeholder{ color: rgba(245,245,245,.8) !important; }

.comment-box{ 
  background: rgba(255,255,255,.2) !important;
  border: 1px solid rgba(255,255,255,.2) !important;
  color: #ffffff !important;
}
body.theme-dark .comment-box{ 
  background: rgba(0,0,0,.3) !important;
  border: 1px solid rgba(255,255,255,.15) !important;
  color: #f5f5f5 !important;
}

/* Text Colors Override untuk semua elemen */
.header-glass, .text-glass, .text-card{ 
  color: #ffffff !important; 
  text-shadow: 0 1px 3px rgba(0,0,0,.5) !important; 
}
body.theme-dark .header-glass, 
body.theme-dark .text-glass, 
body.theme-dark .text-card{ 
  color: #f5f5f5 !important; 
  text-shadow: 0 1px 3px rgba(0,0,0,.7) !important; 
}

.feature-box, .detail-header, .modal-content{ color: #ffffff !important; }
body.theme-dark .feature-box, 
body.theme-dark .detail-header, 
body.theme-dark .modal-content{ color: #f5f5f5 !important; }

/* Paksa semua teks di modal putih */
#modal-content p,
#modal-content h1,#modal-content h2,#modal-content h3,#modal-content h4,
#modal-content li,#modal-content span,#modal-content small { 
  color: #ffffff !important; 
}
body.theme-dark #modal-content p,
body.theme-dark #modal-content h1,body.theme-dark #modal-content h2,
body.theme-dark #modal-content h3,body.theme-dark #modal-content h4,
body.theme-dark #modal-content li,body.theme-dark #modal-content span,
body.theme-dark #modal-content small { 
  color: #f5f5f5 !important; 
}

/* Comment form styling */
.comment-form input,
.comment-form textarea {
  background: rgba(255,255,255,.2) !important;
  border: 1px solid rgba(255,255,255,.3) !important;
  color: #ffffff !important;
}
.comment-form input::placeholder,
.comment-form textarea::placeholder {
  color: rgba(255,255,255,.8) !important;
}
body.theme-dark .comment-form input,
body.theme-dark .comment-form textarea {
  background: rgba(0,0,0,.3) !important;
  border: 1px solid rgba(255,255,255,.2) !important;
  color: #f5f5f5 !important;
}
body.theme-dark .comment-form input::placeholder,
body.theme-dark .comment-form textarea::placeholder {
  color: rgba(245,245,245,.8) !important;
}

/* User modal styling */
#user-modal .modal-content{
  background: rgba(255,255,255,.15) !important;
  border: 1px solid rgba(255,255,255,.2) !important;
  color: #ffffff !important;
}
#user-modal h3{
  color: #ffffff !important;
}
#user-modal input{
  background: rgba(255,255,255,.2) !important;
  border: 1px solid rgba(255,255,255,.3) !important;
  color: #ffffff !important;
}
#user-modal input::placeholder{
  color: rgba(255,255,255,.8) !important;
}
body.theme-dark #user-modal .modal-content{
  background: rgba(0,0,0,.25) !important;
  border: 1px solid rgba(255,255,255,.15) !important;
  color: #f5f5f5 !important;
}
body.theme-dark #user-modal h3{
  color: #f5f5f5 !important;
}
body.theme-dark #user-modal input{
  background: rgba(0,0,0,.3) !important;
  border: 1px solid rgba(255,255,255,.2) !important;
  color: #f5f5f5 !important;
}
body.theme-dark #user-modal input::placeholder{
  color: rgba(245,245,245,.8) !important;
}

/* Quiz modal styling */
#quiz-modal .modal-content {
  background: rgba(255,255,255,.15) !important;
  border: 1px solid rgba(255,255,255,.2) !important;
  color: #ffffff !important;
}
#quiz-modal h1,#quiz-modal h2,#quiz-modal h3,#quiz-modal p { 
  color: #ffffff !important; 
}
#quiz-modal .modal-content button {
  background: var(--btn-nusantara) !important;
  color: #ffffff !important;
  border: 1px solid rgba(255,255,255,.2) !important;
}
body.theme-dark #quiz-modal .modal-content {
  background: rgba(0,0,0,.25) !important;
  border: 1px solid rgba(255,255,255,.15) !important;
  color: #f5f5f5 !important;
}
body.theme-dark #quiz-modal h1,body.theme-dark #quiz-modal h2,
body.theme-dark #quiz-modal h3,body.theme-dark #quiz-modal p { 
  color: #f5f5f5 !important; 
}
body.theme-dark #quiz-modal .modal-content button {
  background: var(--btn-dark) !important;
  color: #f5f5f5 !important;
}

/* Status Quiz dengan warna yang kontras */
.status-pass {
  background: linear-gradient(90deg, #22c55e, #16a34a) !important;
  border: 2px solid #22c55e !important;
  box-shadow: 0 0 20px rgba(34,197,94,.4) !important;
  color: #ffffff !important;
}
.status-fail {
  background: linear-gradient(90deg, #ef4444, #dc2626) !important;
  border: 2px solid #ef4444 !important;
  box-shadow: 0 0 20px rgba(239,68,68,.4) !important;
  color: #ffffff !important;
}

/* Close button */
.close { color: #ffffff !important; }
.close:hover { color: #ffffff !important; opacity: 0.8; }
body.theme-dark .close { color: #f5f5f5 !important; }
body.theme-dark .close:hover { color: #f5f5f5 !important; opacity: 0.8; }

/* Liquid Blobs dengan warna sesuai tema */
.shape{
  background: radial-gradient(circle at 30% 30%, rgba(171,104,75,.3), rgba(94,58,48,.15));
}
body.theme-dark .shape{
  background: radial-gradient(circle at 30% 30%, rgba(73,13,1,.4), rgba(52,26,22,.20));
}

/* ================= NUSANTARA BROWN + LIQUID UI ================= */
:root{
  --brown-light: #8B4513;
  --brown-medium: #A0522D;
  --brown-dark: #654321;
  --cream: #F5E6D3;
  --gold: #DAA520;
  --copper: #B87333;
  --mahogany: #C04000;
  --glass-light: rgba(245,230,211,.25);
  --glass-deep:  rgba(245,230,211,.15);
  --liquid-brown: linear-gradient(135deg,#8B4513 0%, #A0522D 50%, #DAA520 100%);
  --btn-nusantara: linear-gradient(135deg,#DAA520 0%, #B87333 50%, #8B4513 100%);
}

/* Background Nusantara Brown (Light) */
body{
  background: 
    radial-gradient(1200px 800px at 80% -10%, rgba(218,165,32,.15), transparent 55%),
    radial-gradient(900px 600px at -10% 110%, rgba(184,115,51,.12), transparent 50%),
    linear-gradient(135deg, #F5E6D3 0%, #DEB887 30%, #D2B48C 70%, #CD853F 100%) !important;
  background-attachment: fixed !important;
  color: #3E2723 !important;
}

/* Background Nusantara Brown (Dark) */
body.theme-dark{
  background: 
    radial-gradient(1000px 700px at 80% -10%, rgba(139,69,19,.25), transparent 55%),
    radial-gradient(800px 500px at -10% 110%, rgba(101,67,33,.20), transparent 50%),
    linear-gradient(135deg, #2F1B14 0%, #3E2723 30%, #4E342E 70%, #5D4037 100%) !important;
  background-attachment: fixed !important;
  color: #EFEBE9 !important;
}

/* Liquid Glass Cards dengan warna Nusantara */
.card, .glass, .glass-card, .glass-dark, .modal-content, .detail-header, .feature-box{
  position: relative;
  background: rgba(255,255,255,.15) !important;
  border: 1px solid transparent !important;
  border-radius: 24px !important;
  backdrop-filter: blur(16px) saturate(1.2);
  -webkit-backdrop-filter: blur(16px) saturate(1.2);
  overflow: hidden;
  box-shadow: 
    0 12px 32px rgba(139,69,19,.15),
    inset 0 1px 0 rgba(255,255,255,.3);
}
.card::before, .glass::before, .glass-card::before, .glass-dark::before,
.modal-content::before, .detail-header::before, .feature-box::before{
  content:"";
  position:absolute; inset:0; padding:2px; border-radius:inherit;
  background: var(--liquid-brown);
  -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite: xor; mask-composite: exclude;
  pointer-events:none; opacity:.7;
  animation: liquid-flow 8s linear infinite;
}
body.theme-dark .card, 
body.theme-dark .glass, 
body.theme-dark .glass-card, 
body.theme-dark .glass-dark, 
body.theme-dark .modal-content, 
body.theme-dark .detail-header, 
body.theme-dark .feature-box{
  background: rgba(47,27,20,.35) !important;
  box-shadow: 
    0 12px 32px rgba(0,0,0,.25),
    inset 0 1px 0 rgba(139,69,19,.2);
}

/* Liquid Buttons Nusantara */
.btn-ethnic, .glass-button, .nav-btn{
  position: relative;
  border: 1px solid transparent !important;
  background: var(--btn-nusantara) !important;
  color: #FFFFFF !important; 
  font-weight: 800; 
  letter-spacing: .02em;
  border-radius: 16px !important;
  box-shadow: 
    0 8px 28px rgba(139,69,19,.25), 
    0 0 24px rgba(218,165,32,.20),
    inset 0 1px 0 rgba(255,255,255,.2);
  transition: all .15s cubic-bezier(.4,0,.2,1);
  text-shadow: 0 1px 2px rgba(0,0,0,.3);
}
.btn-ethnic::before, .glass-button::before, .nav-btn::before{
  content:"";
  position:absolute; inset:0; border-radius:inherit;
  background: linear-gradient(135deg, rgba(255,255,255,.1), transparent 50%, rgba(0,0,0,.1));
  pointer-events:none;
}
.btn-ethnic:hover, .glass-button:hover, .nav-btn:hover{
  transform: translateY(-3px) scale(1.02);
  box-shadow: 
    0 16px 40px rgba(139,69,19,.35), 
    0 0 36px rgba(218,165,32,.30),
    inset 0 1px 0 rgba(255,255,255,.3);
}

/* Header Liquid dengan warna Nusantara */
.header-glass{
  position: sticky; top: 0;
  border-radius: 24px;
  isolation: isolate;
  overflow: visible;
  background: rgba(255,255,255,0.15) !important;
  backdrop-filter: blur(20px) saturate(1.2);
  -webkit-backdrop-filter: blur(20px) saturate(1.2);
  box-shadow: 
    0 10px 30px rgba(139,69,19,.15),
    inset 0 1px 0 rgba(255,255,255,.4);
}
.header-glass::before{
  content:"";
  position:absolute; inset:-2px;
  border-radius: 28px;
  background: var(--liquid-brown);
  padding:2px;
  -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite: xor; mask-composite: exclude;
  pointer-events:none;
  animation: liquid-flow 10s linear infinite;
  filter: blur(1px);
  opacity:.8;
  z-index:-1;
}
.header-glass::after{
  content:"";
  position:absolute;
  width: 280px; height: 280px;
  top:-90px; left:-50px;
  background: radial-gradient(closest-side, rgba(218,165,32,.25), rgba(218,165,32,0) 70%);
  filter: blur(20px);
  opacity:.3;
  transition: opacity .3s ease, transform .3s ease;
  pointer-events:none;
  z-index:-1;
}
.header-glass:hover::after{
  opacity:.5;
  animation: liquid-orb 8s ease-in-out infinite;
}
body.theme-dark .header-glass{
  background: rgba(47,27,20,0.35) !important;
  box-shadow: 
    0 10px 30px rgba(0,0,0,.35), 
    inset 0 1px 0 rgba(139,69,19,.3);
}
body.theme-dark .header-glass::after{
  background: radial-gradient(closest-side, rgba(139,69,19,.35), rgba(139,69,19,0) 70%);
}

/* Theme Toggle Button yang Liquid */
.theme-toggle{
  position: absolute; top: 16px; right: 16px;
  display: inline-flex; align-items: center; gap: 10px;
  padding: 10px 16px; border-radius: 20px;
  border: 1px solid transparent;
  background: var(--btn-nusantara) !important;
  color: #FFFFFF !important; 
  font-weight: 700; 
  letter-spacing: .03em;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  box-shadow: 
    0 8px 24px rgba(139,69,19,.25), 
    0 0 20px rgba(218,165,32,.15),
    inset 0 1px 0 rgba(255,255,255,.2);
  transition: all .15s cubic-bezier(.4,0,.2,1);
  cursor: pointer;
  z-index: 22;
  text-shadow: 0 1px 2px rgba(0,0,0,.4);
}
.theme-toggle::before{
  content:"";
  position:absolute; inset:0; border-radius:inherit;
  background: linear-gradient(135deg, rgba(255,255,255,.15), transparent 50%, rgba(0,0,0,.1));
  pointer-events:none;
}
.theme-toggle:hover{
  transform: translateY(-2px) scale(1.05);
  box-shadow: 
    0 12px 32px rgba(139,69,19,.35), 
    0 0 28px rgba(218,165,32,.25),
    inset 0 1px 0 rgba(255,255,255,.3);
}
.theme-toggle:active{
  transform: translateY(0) scale(1.02);
  box-shadow: 
    0 6px 20px rgba(139,69,19,.3), 
    0 0 16px rgba(218,165,32,.2),
    inset 0 1px 0 rgba(255,255,255,.2);
}

/* Liquid Blobs dengan warna Nusantara */
.floating-shapes{ z-index: 0; pointer-events: none; }
.shape{
  position: absolute;
  width: 240px; height: 240px;
  background: radial-gradient(circle at 30% 30%, rgba(218,165,32,.4), rgba(184,115,51,.15));
  filter: blur(8px);
  border-radius: 35% 65% 55% 45% / 35% 35% 65% 65%;
  animation: morph 12s ease-in-out infinite, float 8s ease-in-out infinite;
  mix-blend-mode: multiply;
  opacity: .6;
}
.shape:nth-child(1){ top: 15%; left: 8%;  animation-delay: 0s; }
.shape:nth-child(2){ top: 55%; right: 12%; animation-delay: 1s; }
.shape:nth-child(3){ bottom: 18%; left: 22%; animation-delay: 2s; }
.shape:nth-child(4){ top: 8%; right: 32%; animation-delay: 3s; }
.shape:nth-child(5){ bottom: 38%; right: 8%; animation-delay: 4s; }

body.theme-dark .shape{
  background: radial-gradient(circle at 30% 30%, rgba(139,69,19,.5), rgba(101,67,33,.20));
  mix-blend-mode: screen; 
  opacity: .7;
}

/* Input & Form dengan warna Nusantara */
.search-glass{ 
  background: rgba(245,230,211,.6) !important; 
  color: #3E2723 !important;
  border: 1px solid rgba(139,69,19,.3) !important;
  border-radius: 14px !important;
}
.search-glass::placeholder{ color: rgba(62,39,35,.7) !important; }
.search-glass:focus{
  outline: none !important;
  border-color: var(--gold) !important;
  box-shadow: 0 0 0 3px rgba(218,165,32,.25) !important;
}
body.theme-dark .search-glass{ 
  background: rgba(47,27,20,.6) !important; 
  color: #EFEBE9 !important;
  border: 1px solid rgba(139,69,19,.4) !important;
}
body.theme-dark .search-glass::placeholder{ color: rgba(239,235,233,.7) !important; }

.comment-box{ 
  background: rgba(245,230,211,.5) !important;
  border: 1px solid rgba(139,69,19,.2) !important;
  color: #3E2723 !important;
}
body.theme-dark .comment-box{ 
  background: rgba(47,27,20,.5) !important;
  border: 1px solid rgba(139,69,19,.3) !important;
  color: #EFEBE9 !important;
}

/* Text Colors */
.header-glass, .text-glass, .text-card{ 
  color: #3E2723 !important; 
  text-shadow: 0 1px 3px rgba(245,230,211,.5) !important; 
}
body.theme-dark .header-glass, 
body.theme-dark .text-glass, 
body.theme-dark .text-card{ 
  color: #EFEBE9 !important; 
  text-shadow: 0 1px 3px rgba(47,27,20,.5) !important; 
}

.feature-box, .detail-header, .modal-content{ color: #3E2723 !important; }
body.theme-dark .feature-box, 
body.theme-dark .detail-header, 
body.theme-dark .modal-content{ color: #EFEBE9 !important; }


@keyframes liquid-flow{
  0%   { background-position: 0% 50%; }
  50%  { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
@keyframes liquid-orb{
  0%   { transform: translate(0,0) scale(1); }
  33%  { transform: translate(30px,15px) scale(1.08); }
  66%  { transform: translate(-20px,25px) scale(0.95); }
  100% { transform: translate(0,0) scale(1); }
}
@keyframes morph{
  0%   { border-radius: 35% 65% 55% 45% / 35% 35% 65% 65%; transform: scale(1) rotate(0deg); }
  33%  { border-radius: 55% 45% 35% 65% / 50% 60% 40% 50%; transform: scale(1.06) rotate(120deg); }
  66%  { border-radius: 45% 55% 65% 35% / 40% 50% 60% 40%; transform: scale(0.95) rotate(240deg); }
  100% { border-radius: 35% 65% 55% 45% / 35% 35% 65% 65%; transform: scale(1) rotate(360deg); }
}
@keyframes float{
  0%, 100% { transform: translateY(0); }
  33%      { transform: translateY(-20px); }
  66%      { transform: translateY(10px); }
}

/* Status Quiz dengan warna Nusantara */
.status-pass {
  background: linear-gradient(90deg, var(--gold), var(--copper)) !important;
  border: 2px solid var(--gold) !important;
  box-shadow: 0 0 20px rgba(218,165,32,.4) !important;
}
.status-fail {
  background: linear-gradient(90deg, var(--mahogany), var(--brown-dark)) !important;
  border: 2px solid var(--mahogany) !important;
  box-shadow: 0 0 20px rgba(192,64,0,.4) !important;
}

/* ================= ANIMATED SCROLLING TEXT ================= */
.scrolling-text {
  overflow: hidden;
  white-space: nowrap;
  position: relative;
  width: 100%;
}

.scrolling-text h1 {
  display: inline-block;
  animation: scroll-left 15s linear infinite;
  padding-left: 100%;
}

.scrolling-text p {
  display: inline-block;
  animation: scroll-right 18s linear infinite;
  padding-right: 100%;
  animation-delay: 0.5s;
}

@keyframes scroll-left {
  0% {
    transform: translateX(100%);
  }
  100% {
    transform: translateX(-100%);
  }
}

@keyframes scroll-right {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

/* Pause animation on hover */
.scrolling-text:hover h1,
.scrolling-text:hover p {
  animation-play-state: paused;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .scrolling-text h1 {
    animation-duration: 12s;
  }
  .scrolling-text p {
    animation-duration: 14s;
  }
}
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- ====== Header Utama ====== -->
    <header class="header-glass text-glass shadow-lg py-6 px-4 flex flex-col items-center text-center sticky top-0 z-50" style="position:sticky;">
        <!-- Tombol Light/Dark -->
        <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Toggle theme">
            <span id="theme-icon">🌙</span><span id="theme-label" class="hidden sm:inline">Mode</span>
        </button>
        <h1 class="text-3xl font-bold">🏛️ Rumah Adat Indonesia</h1>
        <p class="mt-2 text-lg opacity-80">Jelajahi Keberagaman Arsitektur Tradisional Nusantara</p>
    </header>

    <!-- ====== Konten Utama ====== -->
    <main class="container mx-auto px-4 py-8">
        <div class="glass p-6 mb-8">
            <h1 class="text-3xl font-bold text-glass text-center mb-2">Selamat Datang di Dashboard Rumah Adat Indonesia</h1>
            <p class="text-lg text-glass text-center opacity-80">Eksplorasi kekayaan budaya dan arsitektur tradisional Indonesia</p>
        </div>
        
        <!-- ====== Daftar Rumah Adat ====== -->
        <section id="house-list" class="mb-12">
            <h2 class="text-2xl font-bold mb-6 border-ethnic pb-2 text-glass">📚 Daftar Rumah Adat</h2>
            
            <!-- ====== Fitur Pencarian dan Filter ====== -->
            <div class="mb-8 glass p-6">
                <div class="flex flex-col md:flex-row gap-4 items-center">
                    <input type="text" id="search-input" placeholder="🔍 Cari rumah adat..." class="search-glass flex-grow p-3 focus:outline-none focus:ring-2 focus:ring-white/30">
                    <select id="province-filter" class="search-glass p-3 focus:outline-none focus:ring-2 focus:ring-white/30">
                        <option value="">🌏 Semua Provinsi</option>
                        <option value="Aceh">Aceh</option>
                        <option value="Sumatera Utara">Sumatera Utara</option>
                        <option value="Sumatera Barat">Sumatera Barat</option>
                        <option value="Riau">Riau</option>
                        <option value="Jambi">Jambi</option>
                        <option value="Sumatera Selatan">Sumatera Selatan</option>
                        <option value="Bengkulu">Bengkulu</option>
                        <option value="Lampung">Lampung</option>
                        <option value="Banten">Banten</option>
                        <option value="Jawa Barat">Jawa Barat</option>
                        <option value="Jawa Tengah">Jawa Tengah</option>
                        <option value="DI Yogyakarta">DI Yogyakarta</option>
                        <option value="Jawa Timur">Jawa Timur</option>
                        <option value="Bali">Bali</option>
                        <option value="Nusa Tenggara Barat">Nusa Tenggara Barat</option>
                        <option value="Nusa Tenggara Timur">Nusa Tenggara Timur</option>
                        <option value="Kalimantan Barat">Kalimantan Barat</option>
                        <option value="Kalimantan Tengah">Kalimantan Tengah</option>
                        <option value="Kalimantan Selatan">Kalimantan Selatan</option>
                        <option value="Kalimantan Timur">Kalimantan Timur</option>
                        <option value="Sulawesi Utara">Sulawesi Utara</option>
                        <option value="Sulawesi Tengah">Sulawesi Tengah</option>
                        <option value="Sulawesi Selatan">Sulawesi Selatan</option>
                        <option value="Sulawesi Tenggara">Sulawesi Tenggara</option>
                        <option value="Gorontalo">Gorontalo</option>
                        <option value="Maluku">Maluku</option>
                        <option value="Maluku Utara">Maluku Utara</option>
                        <option value="Papua">Papua</option>
                        <option value="Papua Barat">Papua Barat</option>
                        <option value="Banyuwangi,Jawa Timur">Banyuwangi,Jawa Timur</option>
                        <option value="Jakarta">Jakarta</option>
                    </select>
                    <button onclick="openBookmarkModal()" class="glass-button px-6 py-3 flex items-center gap-2">
                        <span>⭐</span> Bookmark Saya
                    </button>
                    <button onclick="openQuiz()" class="glass-button px-6 py-3 flex items-center gap-2">
                        <span>📝</span> Kerjakan Soal
                    </button>
                </div>
            </div>
            
            <!-- ====== Grid Rumah Adat (Diisi oleh JS) ====== -->
            <div id="houses-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- House cards will be generated by JavaScript -->
            </div>
        </section>
        
    </main>
    
    <!-- ====== Modal Detail Rumah Adat ====== -->
    <div id="detail-modal" class="modal">
        <div class="modal-content p-0">
            <span class="close p-2 cursor-pointer">&times;</span>
            <div id="modal-content" class="p-4" style="max-height: 85vh; overflow-y: auto;">
                <!-- Content will be filled by JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- ====== Modal Quiz ====== -->
    <div id="quiz-modal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <span class="close" onclick="closeQuiz()">&times;</span>
            <div id="quiz-content"></div>
        </div>
    </div>

    <!-- ====== Modal Bookmark ====== -->
    <div id="bookmark-modal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <span class="close" onclick="closeBookmarkModal()">&times;</span>
            <div id="bookmark-modal-content"></div>
        </div>
    </div>

    <!-- ====== Modal Data Diri ====== -->
    <div id="user-modal" class="modal">
        <div class="modal-content" style="max-width:400px;">
            <span class="close" onclick="closeUserModal()">&times;</span>
            <h3 class="font-bold mb-4 text-card-dark">📝 Isi Data Diri</h3>
            <input type="text" id="user-name" class="w-full p-2 border rounded mb-2" placeholder="Nama Lengkap">
            <input type="text" id="user-school" class="w-full p-2 border rounded mb-4" placeholder="Asal Sekolah">
            <button onclick="submitUserData()" class="btn-ethnic w-full py-2 px-4 rounded-lg">Lanjut ke Soal</button>
        </div>
    </div>
    
    <!-- ====== Footer ====== -->
    <footer class="glass-dark text-glass py-8 mt-8">
        <div class="container mx-auto px-4 text-center">
            <p>© 2025 Rumah Adat Indonesia | Sistem Informasi TAU | Dibangun untuk melestarikan warisan budaya bangsa</p>
            <div class="flex justify-center gap-4 mt-4">
                <a href="#" class="hover:text-yellow-400 transition-colors">Tentang Kami</a>
                <a href="https://wa.me/085179927760" class="hover:text-yellow-400 transition-colors">Kontak</a>
                <a href="#" class="hover:text-yellow-400 transition-colors">Privasi</a>
            </div>
        </div>
    </footer>

    <!-- ====== Comment System Script ====== -->
    <script src="comments.js"></script>
    
    <!-- ====== Script Utama (JavaScript) ====== -->
    <script>
        // ====== Comment Functions ======
        function submitComment(houseId) {
            const nameInput = document.getElementById(`comment-name-${houseId}`);
            const commentInput = document.getElementById(`comment-text-${houseId}`);
            const name = nameInput.value.trim();
            const comment = commentInput.value.trim();
            
            if (!name || !comment) {
                alert('Mohon isi nama dan komentar!');
                return;
            }
            
            commentSystem.addComment(houseId, name, comment);
            nameInput.value = '';
            commentInput.value = '';
            renderComments(houseId);
            updateCommentCount(houseId);
        }
        
        function toggleCommentsDisplay(houseId) {
            const commentsList = document.getElementById(`comments-list-${houseId}`);
            const toggleText = document.getElementById(`toggle-text-${houseId}`);
            
            if (commentsList.style.display === 'none') {
                commentsList.style.display = 'block';
                toggleText.textContent = 'Sembunyikan';
                renderComments(houseId);
            } else {
                commentsList.style.display = 'none';
                toggleText.textContent = 'Tampilkan';
            }
        }
        
        function renderComments(houseId) {
            const comments = commentSystem.getComments(houseId);
            const container = document.getElementById(`comments-container-${houseId}`);

            if (comments.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-sm">Belum ada komentar.</p>';
                return;
            }

            container.innerHTML = comments.map(comment => `
                <div class="comment-box ${!comment.visible ? 'opacity-50' : ''}">
                    <div class="comment-header">
                        <span class="comment-name">${comment.name}</span>
                        <span class="comment-date">${commentSystem.formatTimestamp(comment.timestamp)}</span>
                    </div>
                    <div class="comment-text">${comment.comment}</div>
                    <div class="comment-actions">
                        <button onclick="toggleCommentVisibility(${houseId}, ${comment.id})">
                            ${comment.visible ? 'Sembunyikan' : 'Tampilkan'}
                        </button>
                        <button onclick="deleteComment(${houseId}, ${comment.id})" style="margin-left:8px;color:#dc2626;background:rgba(220,38,38,0.08);">
                            Hapus
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        function updateCommentCount(houseId) {
            const count = commentSystem.getCommentCount(houseId);
            document.getElementById(`comment-count-${houseId}`).textContent = count;
        }
        
        function toggleCommentVisibility(houseId, commentId) {
            commentSystem.toggleCommentVisibility(houseId, commentId);
            renderComments(houseId);
            updateCommentCount(houseId);
        }
        
        function deleteComment(houseId, commentId) {
            if (!confirm('Yakin ingin menghapus komentar ini?')) return;
            
            // Gunakan method dari commentSystem
            commentSystem.deleteComment(houseId, commentId);
            
            // Render ulang
            renderComments(houseId);
            updateCommentCount(houseId);
        }
        
        // ====== Data Rumah Adat ======
        const traditionalHouses = [
            {
                id: 1,
                name: "Rumah Krong Bade",
                province: "Aceh",
                description: "Rumah adat Aceh berbentuk rumah panggung dengan tinggi tiang sekitar 2,5-3 meter.",
                history: "Rumah tradisional Aceh telah ada sejak ratusan tahun yang lalu dan merupakan bagian penting dari budaya masyarakat Aceh.",
                philosophy: "Menggambarkan harmoni antara manusia, alam, dan Sang Pencipta.",
                characteristics: "Atap berbentuk perahu terbalik, bahan utama kayu, tiang penyangga tinggi.",
                layout: "Terdiri dari ruang depan (seuramoë keue), ruang tengah (rumoh inong), dan ruang belakang (seuramoë likot).",
                floorPlan:"https://3.bp.blogspot.com/-ShM14Ok2CfU/WC3bRE4xA2I/AAAAAAAAA3Q/V1usxSxXnWE99qZaEnAksDzgLYwF4b5eQCLcB/s640/ruang%2Bdapur%2Brumoh%2Baceh.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-krong-bade-aceh-8d131cf689a64016aedf96875811c307",
                image: "https://th.bing.com/th/id/R.9f5e844400091a2c57777d0d36a4f712?rik=07g1LAFRXYfcxQ&riu=http%3a%2f%2fasset-a.grid.id%2fcrop%2f0x0%3a0x0%2f700x465%2fphoto%2fbobofoto%2foriginal%2f1535_rumah-adat-aceh-krong-bade-foto-klipingco.jpg&ehk=3W%2f3HlXRCxQ6DkNtTtQMJfYgEk%2b2zeX%2fIlbsEgL8xG8%3d&risl=&pid=ImgRaw&r=0&sres=1&sresct=1"
            },
            {
                id: 2,
                name: "Rumah Gadang",
                province: "Sumatera Barat",
                description: "Rumah adat Minangkabau dengan karakteristik atap bergonjong seperti tanduk kerbau.",
                history: "Telah ada sejak zaman Kerajaan Pagaruyung dan menjadi simbol budaya Minangkabau.",
                philosophy: "Menggambarkan kearifan lokal dan sistem kekerabatan matrilineal.",
                characteristics: "Atap bergonjong, struktur rumah panggung, dinding dari kayu berbentuk papan.",
                layout: "Terdiri dari anjung (ruang tamu), ruang lepas, kamar tidur, dan dapur.",
                floorPlan: "https://s3-ap-southeast-1.amazonaws.com/arsitagx-master-article/article-photo/109/nantigo-3.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-gadang-26f6966cbe2d494d9f72834d1ebb30dc",
                image: "https://indonesiaexpat.id/wp-content/uploads/2014/07/Nagari-1000-Rumah-Gadang.jpg"
            },
            {
                id: 3,
                name: "Rumah Joglo",
                province: "Jawa Tengah",
                description: "Rumah adat Jawa dengan struktur utama berupa empat tiang utama yang disebut soko guru.",
                history: "Berasal dari zaman Majapahit dan berkembang di kerajaan-kerajaan Jawa.",
                philosophy: "Melambangkan hubungan manusia dengan alam semesta dan Sang Pencipta.",
                characteristics: "Atap tinggi dengan kemiringan tajam, struktur kayu jati, pembagian ruang yang jelas.",
                layout: "Terdiri dari pendopo, pringgitan, dalem, sentong, gandok, dan pawon.",
                floorPlan: "https://tse4.mm.bing.net/th/id/OIP.Cz1S2ULRi9Nx-pDZBGi1dAHaE6?pid=Api&P=0&h=180",
                model3d: "https://sketchfab.com/3d-models/rumah-joglo-jawa-0714b6f2db464ed2ac1b1c844196352e",
                image: "https://ruangarsitek.com/wp-content/uploads/2020/10/Rumah-Adat-Joglo.jpg"
            },
            {
                id: 4,
                name: "Rumah Limas",
                province: "Sumatera Selatan",
                description: "Rumah panggung dengan atap yang berbentuk limas dan memiliki kolong di bagian bawah.",
                history: "Tercatat sudah ada sejak zaman Kerajaan Sriwijaya.",
                philosophy: "Simbol strata sosial masyarakat Palembang.",
                characteristics: "Tingkatannya menunjukkan status pemilik, atap limas, tiang kayu ulin.",
                layout: "Memiliki beberapa tingkat dengan fungsi berbeda, ruang tamu di tingkat atas.",
                floorPlan: "https://tse1.mm.bing.net/th/id/OIP.GwSShqhN1gpCrn-1zV6iUwHaEK?pid=Api&P=0&h=180",
                model3d: "https://sketchfab.com/3d-models/malaysia-rumah-limas-potong-perak-6c6b3dff9c6145c6b0f67bd3e6d96095",
                image: "https://seringjalan.com/wp-content/uploads/2020/05/rumahlimaspesonatravel-1024x681.jpg"
            },
            {
                id: 5,
                name: "Rumah Panggung Melayu",
                               province: "Riau",
                description: "Rumah tradisional Melayu berbentuk panggung yang tinggi.",
                history: "Dikembangkan oleh masyarakat Melayu sejak zaman kerajaan-kerajaan Melayu.",
                philosophy: "Menggambarkan adaptasi terhadap lingkungan sungai dan pantai.",
                characteristics: "Bentuk panggung tinggi, atap pelana, tangga depan dengan jumlah anak tangga ganjil.",
                layout: "Ruang tamu (serambi), ruang keluarga, kamar tidur, dan dapur di bagian belakang.",
                floorPlan: "https://tse3.mm.bing.net/th/id/OIP.H8Fkp5FdQjK0GX_ZAPbWJwHaEK?pid=Api&P=0&h=180",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-limas-potong-f94a8351b55b496f8e32e25d8f618e65",
                image: "https://tse1.mm.bing.net/th/id/OIP.3N5V7laLIzM-5B97qWaKLgHaFj?pid=Api&P=0&h=180"
            },
            {
                id: 6,
                name: "Rumah Betang",
                province: "Kalimantan Tengah",
                description: "Rumah panjang yang dihuni oleh beberapa keluarga Dayak secara bersama-sama.",
                history: "Merupakan rumah tradisional suku Dayak yang telah ada selama berabad-abad.",
                philosophy: "Melambangkan kebersamaan dan solidaritas masyarakat Dayak.",
                characteristics: "Bentuk panjang, rumah panggung, atap tinggi, bahan utama kayu besi.",
                layout: "Terdiri dari ruang bersama yang panjang dengan kamar-kamar keluarga di sisi-sisinya.",
                floorPlan: "https://indonesiakaya.com/wp-content/uploads/2020/10/5__IMG_1960_Rumah_ini_dibangun_sekitar_tahun_1875_dan_baru_mengalami_rehabilitasi_di_tahun_2012.jpg",
                model3d: "https://sketchfab.com/3d-models/betang-3961026e63a54053a343fde2a8c4f588",
                image: "https://assets.promediateknologi.id/crop/0x0:0x0/750x500/webp/photo/2023/07/11/Picsart_23-07-11_23-31-53-379-2853883078.jpg"
            },
            {
                id: 7,
                name: "Rumah Tongkonan",
                province: "Sulawesi Selatan",
                description: "Rumah adat Toraja dengan atap melengkung seperti perahu.",
                history: "Merupakan rumah leluhur masyarakat Toraja yang diwariskan turun-temurun.",
                philosophy: "Melambangkan hubungan antara manusia dengan alam dan leluhur.",
                characteristics: "Atap berbentuk perahu, hiasan tanduk kerbau di depan rumah, rumah panggung.",
                layout: "Ruang tamu (sali), ruang tidur keluarga, lumbung padi (alang) terpisah.",
                floorPlan: "https://pbs.twimg.com/media/EhsORPqU4AEL7Y9.jpg",
                model3d: "https://sketchfab.com/3d-models/tongkonan-toraja-house-d614270568e24b0284a98aa1998b122c",
                image: "https://i.pinimg.com/736x/80/9d/e8/809de85608fe6a132cda7f9c15c3fe5b.jpg"
            },
            {
                id: 8,
                name: "Rumah Honai",
                province: "Papua",
                description: "Rumah bundar kecil dengan atap jerami yang khas dari suku Dani.",
                history: "Telah digunakan oleh suku Dani di pegunungan Papua selama ratusan tahun.",
                philosophy: "Melambangkan kesederhanaan dan kekokohan dalam menghadapi alam.",
                characteristics: "Bentuk bulat, atap kerucut dari jerami, dinding kayu, tanpa jendela.",
                layout: "Satu ruang multifungsi untuk tidur, memasak, dan berkumpul.",
                floorPlan: "https://www.selasar.com/wp-content/uploads/2020/08/struktur-tengah-rumah-adat-honai.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-honai-papua-994c2ac920984f89b26ce1a4d766b5b9",
                image: "https://i.pinimg.com/736x/24/ce/f9/24cef917c641c06e61219d4278aab2ce.jpg"
            },
            {
                id: 9,
                name: "Rumah Lamin",
                province: "Kalimantan Timur",
                description: "Rumah panjang suku Dayak dengan ukiran-ukiran yang berwarna-warni.",
                history: "Merupakan rumah adat Dayak Kenyah yang telah ada sejak zaman nenek moyang.",
                philosophy: "Menggambarkan kebersamaan dan komunitas yang erat.",
                characteristics: "Rumah panggung panjang, atap pelana, hiasan ukiran motif Dayak.",
                layout: "Dihuni oleh beberapa keluarga dengan ruang bersama yang panjang.",
                floorPlan: "https://kaltimfaktual.co/wp-content/uploads/2024/02/WhatsApp-Image-2024-02-09-at-16.31.55-1-1200x676.jpeg",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-lamin-502d866136974a2286f91df8d6c89453",
                image: "https://asset.kompas.com/crops/49oDBs3LESEoVs8VaIEJtJQ3xtQ=/27x30:527x363/750x500/data/photo/2020/04/29/5ea8da52d1c7f.png"
            },
            {
                id: 10,
                name: "Rumah Baileo",
                province: "Maluku",
                description: "Rumah adat Maluku yang berfungsi sebagai balai pertemuan adat.",
                history: "Telah digunakan sebagai pusat kegiatan masyarakat sejak zaman kerajaan di Maluku.",
                philosophy: "Melambangkan keterbukaan dan demokrasi adat.",
                characteristics: "Bentuk panggung, atap tinggi, tanpa dinding, hiasan ukiran dan patung.",
                layout: "Ruangan terbuka tanpa sekat dengan tempat duduk mengelilingi.",
                floorPlan: "https://www.selasar.com/wp-content/uploads/2020/08/Ruang-tengah-di-rumah-adat-Jambi-jenis-rumah-Tuo-Rantau-Panjang.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-baileo-maluku-indonesia-a8ff3e31964847b8abf1c2f11401e713",
                image: "https://www.garudacitizen.com/wp-content/uploads/2021/01/Rumah-Adat-Baileo-Nolloth-2.jpg"
            },
            {
                id: 11,
                name: "Kajang Lako",
                province: "Jambi",
                description: "Rumah Kajang Lako adalah rumah adat suku Melayu Jambi yang digunakan sebagai tempat tinggal dan pusat kegiatan adat.",
                history: "Merupakan warisan budaya masyarakat Melayu yang berkembang sejak masa Kesultanan Jambi.",
                philosophy: "Melambangkan kehidupan yang tertib, teratur, dan selaras dengan alam serta norma adat.",
                characteristics: "Berbentuk rumah panggung, atap melengkung seperti perahu (kajang), ukiran khas Melayu, serta memiliki 30 tiang penyangga.",
                layout: "Terdiri dari ruang gaho (tamu), ruang masinding (keluarga), dan ruang dapur di bagian belakang.",
                floorPlan: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcROR1Ua9CNhw5YHXPJcWkw8uj7qm3VM31bxWw&s",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-tradisional-jambi-kajang-lako-deb909440e9c4f1999a25d9e4434a424",
                image: "https://upload.wikimedia.org/wikipedia/commons/0/0e/Kajang_Leko_Rumah_adat_Jambi.jpg"
            },
            {
                id: 12,
                name: "Rumah Dulohupa",
                province: "Gorontalo",
                description: "Rumah adat Gorontalo dengan atap segitiga yang khas.",
                history: "Merupakan rumah adat tradisional yang telah ada sejak zaman kerajaan Gorontalo.",
                philosophy: "Melambangkan kesatuan dan persatuan masyarakat Gorontalo.",
                characteristics: "Atap segitiga bertingkat, tiang kayu, rumah panggung dengan kolong.",
                layout: "Memiliki ruang pertemuan utama dengan kamar-kamar di sekitarnya.",
                floorPlan: "https://jelajah.kompas.id/wp-content/uploads/2019/09/20120704APO05-1024x680.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-dulohupa-e350bcb9cbe24d7caa076994e98365a3",
                image: "https://www.selasar.com/wp-content/uploads/2020/08/rumah-adat-gorontalo.jpg"
            },
            {
                id: 13,
                name: "Rumah Bola Soba",
                province: "Sulawesi Selatan",
                description: "Rumah Bulo Soba adalah rumah adat dari suku Bugis yang dulunya digunakan oleh para bangsawan atau raja di daerah Bone.",
                history: "Didirikan pada masa Kerajaan Bone dan menjadi simbol kekuasaan serta status sosial kaum bangsawan Bugis.",
                philosophy: "Melambangkan keagungan, kehormatan, dan kedudukan tinggi pemilik rumah dalam masyarakat Bugis.",
                characteristics: "Bentuk rumah panggung, struktur dari kayu ulin dan kayu besi, atap bertingkat, ukiran khas Bugis pada dinding dan tangga.",
                layout: "Memiliki tiga bagian utama: rakkeang (loteng penyimpanan), ale bola (ruang utama), dan awa bola (kolong rumah).",
                floorPlan: "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSGqTFNbZ3jQ65GT8ALBFFX75RrMRdv1gzoXw&s",
                model3d: "https://sketchfab.com/3d-models/bugis-traditionals-house-d85593bc47784f899d461a92c33bfca4",
                image: "https://assets.pikiran-rakyat.com/crop/0x0:0x0/249x140/photo/2024/11/09/3033392462.jpg"
            },
            {
                id: 14,
                name: "Rumah Buton",
                province: "Sulawesi Tenggara",
                description: "Rumah adat khas Buton yang dulunya merupakan istana Kesultanan Buton",
                history: "Merupakan bangunan tradisional yang telah berdiri sejak masa kesultanan di Buton, Sulawesi Tenggara.",
                philosophy: "Melambangkan kekuasaan, kehormatan, dan tata nilai adat dalam kehidupan masyarakat Buton.",
                characteristics: "Berbentuk rumah panggung dari kayu, terdiri dari beberapa tingkat, dengan ukiran khas dan struktur bertingkat.",
                layout: "Memiliki beberapa lantai yang masing-masing punya fungsi berbeda seperti ruang tamu, ruang keluarga, dan ruang upacara adat.",
                floorPlan: "https://blue.kumparan.com/image/upload/fl_progressive,fl_lossy,c_fill,f_auto,q_auto:best,w_1024/v1557036260/pj36xthu1uakl8z8dgr4.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-buton-indonesia-option-3-ca7e608b31fe44c8af22942f4f0b32b5",
                image: "https://blue.kumparan.com/image/upload/fl_progressive,fl_lossy,c_fill,f_auto,q_auto:best,w_640/v1557035955/seldra1dbyvao4vvoapt.jpg"
            },
            {
                id: 15,
                name: "Rumah Osing",
                province: "Banyuwangi,Jawa Timur",
                description: "Rumah adat Osing adalah rumah tradisional suku Osing di Banyuwangi yang mencerminkan gaya hidup dan nilai budaya lokal.",
                history: "Berasal dari warisan budaya masyarakat Osing, keturunan Majapahit yang menetap di wilayah ujung timur Pulau Jawa.",
                philosophy: "Melambangkan keharmonisan dengan alam dan keterikatan sosial antarwarga.",
                characteristics: "Bentuk rumah panggung, dinding anyaman bambu (gedheg), atap limasan atau joglo, serta halaman luas.",
                layout: "Terdiri dari tiga bagian utama: jaba (luar), njero (dalam), dan pawon (dapur).",
                floorPlan: "https://i0.wp.com/www.terakota.id/wp-content/uploads/2019/11/rumah-osing2.jpg?w=1040&ssl=1",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-osing-5d19da76945f4447a747e315a5a6e66f",
                image: "https://beritajatim.com/wp-content/uploads/2024/06/Desa-Wisata-Osing-Kemiren.webp"
            },
            {
                id: 16,
                name: "Rumah Sasadu",
                province: "Maluku Utara",
                description: "Rumah adat Halmahera dengan bentuk segi delapan yang unik.",
                history: "Merupakan rumah tradisional masyarakat Suku Tobelo di Halmahera.",
                philosophy: "Melambangkan keterbukaan dan keramahan masyarakat.",
                characteristics: "Bentuk segi delapan, atap piramida, tanpa dinding.",
                layout: "Ruang terbuka dengan tempat duduk mengelilingi pusat rumah.",
                floorPlan: "https://pariwisataindonesia.id/wp-content/uploads/2020/11/rumah-adat-Sasadu-foto-by-maluttodaycom-640x480.jpeg",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-worat-worat-maluku-utara-indonesia-33f84af4d1f34de8844171653a7422cf",
                image: "https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEjCiEgUfo0dIIBOZf51KWm5WyCLdHQR_x69_0PYuoeFiOsWus_bsbpQzFmvqvrBHFu5CQ51EDSRvgr2L_w_MpQ6DCPhzl1KizUcqVFDPrhgBTpgDhT_sONwfDVsseU-_fw5kOEi2ZoRPwU1/s1600/Rumah+Adat+Sasadu.jpg"
            },
            {
                id: 17,
                name: "Rumah Bale Lumbung",
                province: "Nusa Tenggara Barat",
                description: "Rumah adat khas suku Sasak di Lombok yang digunakan sebagai tempat tinggal dan penyimpanan hasil panen.",
                history: "Merupakan bangunan tradisional masyarakat Lombok yang telah digunakan secara turun-temurun oleh suku Sasak.",
                philosophy: "Melambangkan kesederhanaan, kerja keras, dan keharmonisan hidup dengan alam.",
                characteristics: "Berbentuk rumah panggung kecil dari kayu dan bambu, beratap alang-alang, dengan kolong untuk menyimpan hasil pertanian.",
                layout: "Terdiri dari satu ruang utama tanpa sekat, dengan tangga kecil di depan dan atap melengkung seperti lumbung.",
                floorPlan: "https://files.mahasiswa.ung.ac.id/551412035/dtfygujpg",
                model3d: "https://sketchfab.com/3d-models/rumah-adat-bale-lumbung-ntb-indonesia-a44942491a7c40aaba87abc775f96b5e",
                image: "https://image.idntimes.com/post/20190723/sade-00499abad3ffcce1a0ed53acd1e4269a.jpg"
            },
            {
                id: 18,
                name: "Rumah Banjar",
                province: "Kalimantan Selatan",
                description: "Rumah adat khas Banjar dengan ornamen ukir yang indah.",
                history: "Berkembang sejak zaman Kerajaan Banjar dan dipengaruhi oleh Islam.",
                philosophy: "Menggambarkan adaptasi budaya lokal dengan pengaruh Islam.",
                characteristics: "Atap pelana dengan tawing layar, ukiran khas Banjar, rumah panggung.",
                layout: "Memiliki serambi (ruang tamu), ruang tengah, dan kamar-kamar.",
                floorPlan: "https://meratusgeopark.org/wp-content/uploads/2019/07/Rumah-Adat-Banjar-3.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-banjar-55f3578178354de999ff200eea3ad0e8",
                image: "https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Rumah_Bubungan_Tinggi_Anjungan_Kalsel_TMII_Jakarta.JPG/1200px-Rumah_Bubungan_Tinggi_Anjungan_Kalsel_TMII_Jakarta.JPG"
            },
            {
                id: 19,
                name: "Rumah Kebaya",
                province: "Jakarta",
                description: "Rumah adat Betawi yang paling dikenal adalah Rumah Kebaya.",
                history: "Merupakan rumah tradisional khas Betawi yang berkembang di daerah Jakarta sejak zaman kolonial Belanda.",
                philosophy: "Melambangkan kesederhanaan, keterbukaan, dan keharmonisan ",
                characteristics: "Atap berbentuk pelana yang menyerupai lipatan kebaya, dinding dari kayu, dan jendela besar yang banyak.",
                layout: "Ruang tamu berada di bagian depan, ruang keluarga dan kamar tidur di tengah, dapur dan kamar mandi di bagian belakang.",
                floorPlan: "https://cdngnfi2.sgp1.cdn.digitaloceanspaces.com/gnfi/uploads/images/2023/03/1316192023-15860763700_66dc2044a8_c.jpg",
                model3d: "https://sketchfab.com/3d-models/rumah-kebaya-78aaf26028184721af0dc99b6cbb049e",
                image: "https://radarmukomuko.bacakoran.co/upload/81621e9d76fa6a9adb6d91d5444b8daa.jpg"
            },
            {
                id: 20,
                name: "Rumah Bubungan Tinggi",
                province: "Kalimantan Selatan",
                description: "Varian rumah adat Banjar dengan atap yang sangat tinggi.",
                history: "Merupakan rumah kaum bangsawan pada zaman Kerajaan Banjar.",
                philosophy: "Melambangkan status sosial dan kemewahan.",
                characteristics: "Atap sangat tinggi dengan kemiringan tajam, ornamen ukiran mewah.",
                layout: "Pembagian ruang yang lebih kompleks dengan banyak kamar khusus.",
                floorPlan: "https://www.selasar.com/wp-content/uploads/2020/08/Ruang-tengah-di-rumah-adat-Jambi-jenis-rumah-Tuo-Rantau-Panjang.jpg",
                model3d: "https://sketchfab.com/3d-models/bubungan-tinggi-rumah-adat-kalimantan-selatan-c9ede9be05f74d57b6170d212a5fada7",
                image: "https://1.bp.blogspot.com/-K8sOiiIqSHs/WAF_jfutqjI/AAAAAAAABvI/_uqDD3gIOFAzXClqsylXT2kKNrhx3HWnACLcB/s640/rumah%2Badat%2BKalimantan%2BSelatan%2B%2528Bubungan%2BTinggi%2529.JPG"
            }
        ];
        let bookmarks = [];

        // Fungsi untuk menambah komentar (non-persistent)
        function addComment(houseId) {
            alert('Fitur komentar telah dinonaktifkan.');
        }

        // Fungsi untuk toggle komentar (non-persistent)
        function toggleComments(houseId) {
            alert('Fitur komentar telah dinonaktifkan.');
        }

        // Bookmark toggle
        function toggleBookmark(houseId) {
            if (bookmarks.includes(houseId)) {
                bookmarks = bookmarks.filter(id => id !== houseId);
            } else {
                bookmarks.push(houseId);
            }
            createHouseCards(getCurrentHouses());
            renderBookmarks();
        }

        // Mendapatkan data rumah yang sedang difilter
        function getCurrentHouses() {
            const searchText = document.getElementById('search-input').value.toLowerCase();
            const province = document.getElementById('province-filter').value;
            let filteredHouses = traditionalHouses;
            if (searchText) {
                filteredHouses = filteredHouses.filter(house =>
                    house.name.toLowerCase().includes(searchText) ||
                    house.province.toLowerCase().includes(searchText) ||
                    house.description.toLowerCase().includes(searchText)
                );
            }
            if (province) {
                filteredHouses = filteredHouses.filter(house => house.province === province);
            }
            return filteredHouses;
        }

        // Fungsi untuk membuat card rumah adat
        function createHouseCards(houses) {
            const container = document.getElementById('houses-container');
            container.innerHTML = '';
            houses.forEach(house => {
                const isBookmarked = bookmarks.includes(house.id);
                const card = document.createElement('div');
                card.className = 'card';
                card.innerHTML = `
                    <div class="card-image">
                        <img src="${house.image}" alt="${house.name} dari ${house.province}" />
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2 text-card">${house.name}</h3>
                        <p class="text-card mb-3 opacity-80">📍 ${house.province}</p>
                        <p class="text-sm mb-4 line-clamp-2 text-card opacity-70">${house.description}</p>
                        <button onclick="showDetail(${house.id})" class="btn-ethnic w-full py-2 px-4 mb-2">
                            Lihat Detail
                        </button>
                        <button onclick="toggleBookmark(${house.id})" class="glass-button w-full py-2 px-4 ${isBookmarked ? 'bg-yellow-400/30' : ''}">
                            ${isBookmarked ? '⭐ Bookmark' : '☆ Bookmark'}
                        </button>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Fungsi untuk menampilkan detail rumah adat
        function showDetail(houseId) {
            const house = traditionalHouses.find(h => h.id === houseId);
            const modal = document.getElementById('detail-modal');
            const modalContent = document.getElementById('modal-content');
            
            modalContent.innerHTML = `
                <div class="detail-header mb-6 p-4 rounded-lg">
                    <h2 class="text-2xl font-bold">🏛️ ${house.name}</h2>
                    <p class="text-yellow-200">📍 ${house.province}</p>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div>
                        <img src="${house.image}" alt="${house.name} dari ${house.province}" class="w-full rounded-lg mb-4" />
                        <div class="feature-box mb-4">
                            <h3 class="font-bold text-lg mb-2">📚 Sejarah</h3>
                            <p>${house.history}</p>
                        </div>
                        <div class="feature-box">
                            <h3 class="font-bold text-lg mb-2">🧠 Filosofi</h3>
                            <p>${house.philosophy}</p>
                        </div>
                    </div>
                    
                    <div>
                        <div class="feature-box mb-4">
                            <h3 class="font-bold text-lg mb-2">🏗️ Karakteristik Bangunan</h3>
                            <p>${house.characteristics}</p>
                        </div>
                        
                        <div class="feature-box mb-4">
                            <h3 class="font-bold text-lg mb-2">🗺️ Tata Letak Ruang</h3>
                            <p>${house.layout}</p>
                        </div>
                        
                        <img src="${house.floorPlan}" alt="Denah ${house.name}" class="w-full rounded-lg mb-4" />
                    </div>
                </div>
                
                <h3 class="text-xl font-bold mb-4 border-b pb-2 text-card-dark">🎨 Visualisasi 3D</h3>
                <div class="visualization-container mb-6 rounded-lg overflow-hidden" style="height: 400px;">
                    <iframe src="${house.model3d}/embed" frameborder="0" allow="autoplay; fullscreen; vr" allowfullscreen></iframe>
                </div>
                <button onclick="window.open('${house.model3d}', '_blank')" class="btn-ethnic w-full py-2 px-4 rounded-lg mb-8">
                    🚀 Buka Visualisasi Penuh
                </button>
                
                <!-- ====== Area Komentar ====== -->
                <div class="border-t pt-6">
                    <h3 class="text-xl font-bold mb-4 text-card-dark">Komentar</h3>
                    
                    <!-- Comment Count -->
                    <div class="mb-4 flex items-center">
                        <span class="text-sm text-gray-600">
                            <span id="comment-count-${houseId}">${commentSystem.getCommentCount(houseId)}</span> komentar
                        </span>
                        <button onclick="toggleCommentsDisplay(${houseId})" 
                                class="ml-4 px-3 py-1 text-xs bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors duration-200 shadow-sm">
                            <span id="toggle-text-${houseId}">Tampilkan</span> komentar
                        </button>
                    </div>
                    
                    <!-- Comment Form -->
                    <div class="mb-4 comment-form">
    <input type="text" id="comment-name-${houseId}" placeholder="Nama Anda" 
           class="w-full" maxlength="50">
    <textarea id="comment-text-${houseId}" placeholder="Tulis komentar..." 
              class="w-full" rows="3" maxlength="500"></textarea>
    <button onclick="submitComment(${houseId})">
        Kirim Komentar
    </button>
</div>
                    
                    <!-- Comments List -->
                    <div id="comments-list-${houseId}" style="display: none;">
                        <div id="comments-container-${houseId}"></div>
                    </div>
                </div>
                
                <div class="flex justify-between mt-8">
                    <button onclick="navigateDetail(${houseId - 1})" ${houseId <= 1 ? 'disabled' : ''} class="nav-btn px-4 py-2 rounded-lg ${houseId <= 1 ? 'opacity-50 cursor-not-allowed' : ''}">
                        Sebelumnya
                    </button>
                    <button onclick="closeModal()" class="bg-gray-600 text-white px-4 py-2 rounded-lg">
                        Tutup
                    </button>
                    <button onclick="navigateDetail(${houseId + 1})" ${houseId >= 20 ? 'disabled' : ''} class="nav-btn px-4 py-2 rounded-lg ${houseId >= 20 ? 'opacity-50 cursor-not-allowed' : ''}">
                        Selanjutnya
                    </button>
                </div>
            `;
            
            modal.style.display = 'block';
                       renderComments(houseId); // Render comments when opening detail
        }

        // Fungsi untuk menavigasi antar rumah adat di modal
        function navigateDetail(newId) {
            if (newId >= 1 && newId <= 20) {
                showDetail(newId);
            }
        }

        // Fungsi untuk menutup modal
        function closeModal() {
            document.getElementById('detail-modal').style.display = 'none';
        }

        // Fungsi untuk filter dan pencarian
        function filterHouses() {
            const searchText = document.getElementById('search-input').value.toLowerCase();
            const province = document.getElementById('province-filter').value;
            
            let filteredHouses = traditionalHouses;
            
            if (searchText) {
                filteredHouses = filteredHouses.filter(house => 
                    house.name.toLowerCase().includes(searchText) || 
                    house.province.toLowerCase().includes(searchText) ||
                    house.description.toLowerCase().includes(searchText)
                );
            }
            
            if (province) {
                filteredHouses = filteredHouses.filter(house => house.province === province);
            }
            
            createHouseCards(filteredHouses);
        }

        // Render bookmarks
        function renderBookmarks() {
            const modalContent = document.getElementById('bookmark-modal-content');
            if (bookmarks.length === 0) {
               
                modalContent.innerHTML = `<p class="text-gray-600">Belum ada rumah adat yang dibookmark.</p>`;
                return;
            }
            const bookmarkedHouses = traditionalHouses.filter(h => bookmarks.includes(h.id));
            modalContent.innerHTML = `
                <h3 class="font-bold mb-4 text-yellow-700">Bookmark Anda:</h3>
                <div class="flex flex-wrap gap-2 mb-4">
                    ${bookmarkedHouses.map(house => `
                        <button class="bg-yellow-200 px-3 py-1 rounded-full text-sm font-semibold cursor-pointer hover:bg-yellow-300"
                            onclick="showDetail(${house.id}); closeBookmarkModal();">${house.name}</button>
                    `).join('')}
                </div>
            `;
        }

        // Quiz functionality
        const quizQuestions = [
            {
                question: "Apa nama rumah adat dari provinsi Aceh?",
                options: ["Rumah Gadang", "Rumah Krong Bade", "Rumah Joglo", "Rumah Betang"],
                answer: 1
            },
            {
                question: "Rumah Gadang berasal dari provinsi mana?",
                options: ["Sumatera Barat", "Jawa Tengah", "Sulawesi Selatan", "Papua"],
                answer: 0
            },
            {
                question: "Rumah adat Jawa Tengah yang memiliki soko guru disebut?",
                options: ["Rumah Joglo", "Rumah Limas", "Rumah Betang", "Rumah Tongkonan"],
                answer: 0
            },
            {
                question: "Rumah adat Minangkabau memiliki atap berbentuk?",
                options: ["Perahu", "Tanduk Kerbau", "Limas", "Segitiga"],
                answer: 1
            },
            {
                question: "Rumah Limas merupakan rumah adat dari?",
                options: ["Sumatera Selatan", "Riau", "Jambi", "Kalimantan Tengah"],
                answer: 0
            },
            {
                question: "Rumah Betang adalah rumah panjang yang dihuni oleh suku?",
                options: ["Dayak", "Toraja", "Bugis", "Osing"],
                answer: 0
            },
            {
                question: "Rumah Tongkonan memiliki atap menyerupai?",
                options: ["Limas", "Perahu", "Tanduk", "Segitiga"],
                answer: 1
            },
            {
                question: "Rumah Honai berasal dari daerah?",
                options: ["Papua", "Sulawesi Selatan", "Jawa Timur", "Bali"],
                answer: 0
            },
            {
                question: "Rumah Lamin adalah rumah adat dari suku?",
                options: ["Dayak Kenyah", "Bugis", "Betawi", "Osing"],
                answer: 0
            },
            {
                question: "Rumah Baileo digunakan sebagai?",
                options: ["Balai pertemuan adat", "Tempat tinggal", "Gudang", "Istana"],
                answer: 0
            },
            {
                question: "Rumah Kajang Lako memiliki berapa tiang penyangga?",
                options: ["20", "30", "40", "50"],
                answer: 1
            },
            {
                question: "Rumah Dulohupa memiliki atap berbentuk?",
                options: ["Segitiga bertingkat", "Limas", "Perahu", "Tanduk"],
                answer: 0
            },
            {
                question: "Rumah Bola Soba adalah simbol apa dalam masyarakat Bugis?",
                options: ["Kehormatan dan kedudukan tinggi", "Kesederhanaan", "Keterbukaan", "Kerja keras"],
                answer: 0
            },
            {
                question: "Rumah Buton dulunya merupakan?",
                options: ["Istana Kesultanan", "Rumah rakyat", "Gudang", "Balai desa"],
                answer: 0
            },
            {
                question: "Rumah Osing memiliki dinding dari?",
                options: ["Anyaman bambu (gedheg)", "Kayu ulin", "Batu bata", "Jerami"],
                answer: 0
            },
            {
                question: "Rumah Sasadu berbentuk?",
                options: ["Segi delapan", "Segitiga", "Limas", "Perahu"],
                answer: 0
            },
            {
                question: "Rumah Bale Lumbung digunakan untuk?",
                options: ["Tempat tinggal dan penyimpanan hasil panen", "Balai pertemuan", "Gudang senjata", "Tempat ibadah"],
                answer: 0
            },
            {
                question: "Rumah Banjar berkembang sejak zaman?",
                options: ["Kerajaan Banjar", "Kerajaan Majapahit", "Kerajaan Sriwijaya", "Kesultanan Buton"],
                answer: 0
            },
            {
                question: "Rumah Kebaya adalah rumah adat dari?",
                options: ["Betawi", "Bugis", "Dayak", "Osing"],
                answer: 0
            },
            {
                question: "Rumah Bubungan Tinggi melambangkan?",
                options: ["Status sosial dan kemewahan", "Kesederhanaan", "Keterbukaan", "Kerja keras"],
                answer: 0
            }
        ];
        let quizIndex = 0;
        let quizScore = 0;

        let userData = { name: '', school: '' };


function closeUserModal() {
    document.getElementById('user-modal').style.display = 'none';
}

function submitUserData() {
    const name = document.getElementById('user-name').value.trim();
    const school = document.getElementById('user-school').value.trim();
    if (!name || !school) {
        alert('Mohon isi nama dan asal sekolah!');
        return;
    }
    userData.name = name;
    userData.school = school;
    closeUserModal();
    // Lanjut ke quiz
    quizIndex = 0;
    quizScore = 0;
    showQuizQuestion();
    document.getElementById('quiz-modal').style.display = 'block';
}

        function openQuiz() {
            // Tampilkan modal data diri dulu
            document.getElementById('user-modal').style.display = 'block';
        }

        function closeQuiz() {
            document.getElementById('quiz-modal').style.display = 'none';
        }

        function showQuizQuestion() {
            const quizDiv = document.getElementById('quiz-content');
            if (quizIndex >= quizQuestions.length) {
                // Kirim skor ke server via AJAX
                fetch('save_score.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: userData.name,
                        school: userData.school,
                        score: quizScore,
                        total: quizQuestions.length
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Score saved:', data);
                })
                .catch(error => {
                    console.error('Error saving score:', error);
                });

                // ====== STATUS LULUS/TIDAK LULUS ======
                let statusText = '';
                if (quizScore >= 14) {
                    statusText = `<span class="status-pass">LULUS 🎉</span>`;
                } else {
                    statusText = `<span class="status-fail">TIDAK LULUS 😢</span>`;
                }

                quizDiv.innerHTML = `
                    <h2 class="font-bold text-xl mb-4">Quiz Selesai!</h2>
                    <p class="mb-4">Skor Anda: <span class="font-bold">${quizScore} / ${quizQuestions.length}</span></p>
                    <p class="mb-4">${statusText}</p>
                    <button onclick="closeQuiz()" class="btn-ethnic w-full py-2 px-4 rounded-lg">Tutup</button>
                `;
                return;
            }
            const q = quizQuestions[quizIndex];
            quizDiv.innerHTML = `
                <h3 class="font-bold mb-4">${q.question}</h3>
                <div class="flex flex-col gap-2 mb-4">
                    ${q.options.map((opt, i) => `
                        <button onclick="answerQuiz(${i})" class="bg-gray-100 hover:bg-yellow-200 px-4 py-2 rounded">${opt}</button>
                    `).join('')}
                </div>
                <p class="text-sm text-gray-500">Pertanyaan ${quizIndex + 1} dari ${quizQuestions.length}</p>
            `;
        }

        function answerQuiz(selected) {
            if (selected === quizQuestions[quizIndex].answer) {
                quizScore++;
            }
            quizIndex++;
            showQuizQuestion();
        }

        // ========== THEME HANDLER ==========
        const THEME_KEY = 'ui-theme';
        function applyTheme(theme) {
            const isDark = theme === 'dark';
            document.body.classList.toggle('theme-dark', isDark);
            const icon = document.getElementById('theme-icon');
            if (icon) icon.textContent = isDark ? '☀️' : '🌙';
        }
        function toggleTheme() {
            const isDark = document.body.classList.contains('theme-dark');
            const next = isDark ? 'light' : 'dark';
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        }

        // Inisialisasi
        document.addEventListener('DOMContentLoaded', function() {
            // ...existing code...
            const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
            applyTheme(savedTheme);
            const toggleBtn = document.getElementById('theme-toggle');
            if (toggleBtn) toggleBtn.addEventListener('click', toggleTheme);
        });

        // Event listeners
        document.getElementById('search-input').addEventListener('input', filterHouses);
        document.getElementById('province-filter').addEventListener('change', filterHouses);
        document.querySelector('.close').addEventListener('click', closeModal);
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('detail-modal')) {
                closeModal();
            }
        });

        function openBookmarkModal() {
            renderBookmarks();
            document.getElementById('bookmark-modal').style.display = 'block';
        }

        function closeBookmarkModal() {
            document.getElementById('bookmark-modal').style.display = 'none';
        }

        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('bookmark-modal')) {
                closeBookmarkModal();
            }
        });

        // Inisialisasi

        document.addEventListener('DOMContentLoaded', function() {
            createHouseCards(traditionalHouses);
            renderBookmarks();
            document.getElementById('search-input').addEventListener('input', filterHouses);
            document.getElementById('province-filter').addEventListener('change', filterHouses);
            document.querySelector('.close').addEventListener('click', closeModal);
        });
    </script>
</body>
</html>
