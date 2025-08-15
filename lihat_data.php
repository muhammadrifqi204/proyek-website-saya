  <?php
  /***************** KONFIGURASI *****************/
  $showAllScores = true;          // true = tampilkan semua (termasuk score 0)
  $perPage       = 10;            // pagination, ubah dari 14 ke 10
  $debug         = false;         // true untuk melihat detail error koneksi
  /***********************************************/

  // Mencari file connect.php di beberapa lokasi umum
  $paths = [
      __DIR__ . '/connect.php',
      __DIR__ . '/db/connect.php',
      __DIR__ . '/../db/connect.php'
  ];
  $found = false;
  foreach ($paths as $p) {
      if (file_exists($p)) { require_once $p; $found = true; break; }
  }
  if (!$found) {
      http_response_code(500);
      die('connect.php tidak ditemukan. Letakkan di: ' . implode(' | ', $paths));
  }

  $isMysqli = isset($conn) && $conn instanceof mysqli;
  $isPDO    = isset($pdo) && $pdo instanceof PDO;
  if (!$isMysqli && !$isPDO) {
      http_response_code(500);
      die('Koneksi DB tidak valid (tidak menemukan objek mysqli atau PDO).');
  }

  $q = isset($_GET['q']) ? trim($_GET['q']) : '';
  $page = max(1, (int)($_GET['page'] ?? 1));
  $offset = ($page - 1) * $perPage;

  function escapeLike($s) { return str_replace(['%','_'], ['\\%','\\_'], $s); }

  $whereScore = $showAllScores ? 'score >= 0' : 'score > 0';
  $whereSearch = '';
  $params = [];
  $like = '';

  if ($q !== '') {
      $like = '%' . escapeLike($q) . '%';
      $whereSearch = " AND (username LIKE :kw OR school LIKE :kw)";
  }

  $users = [];
  $totalRows = 0;

  try {
      if ($isMysqli) {
          // Hitung total
          if ($q !== '') {
              $stmt = $conn->prepare("SELECT COUNT(*) c FROM users WHERE $whereScore AND (username LIKE ? OR school LIKE ?)");
              $stmt->bind_param('ss', $like, $like);
              $stmt->execute();
              $stmt->bind_result($totalRows); $stmt->fetch(); $stmt->close();

              $stmt = $conn->prepare("SELECT id, username, school, score FROM users WHERE $whereScore AND (username LIKE ? OR school LIKE ?) ORDER BY score DESC, username ASC LIMIT ?, ?");
              $stmt->bind_param('ssii', $like, $like, $offset, $perPage);
              $stmt->execute();
              $res = $stmt->get_result();
          } else {
              $resCount = $conn->query("SELECT COUNT(*) c FROM users WHERE $whereScore");
              $totalRows = (int)$resCount->fetch_assoc()['c'];
              $res = $conn->query("SELECT id, username, school, score FROM users WHERE $whereScore ORDER BY score DESC, username ASC LIMIT $offset,$perPage");
          }
          while ($res && $row = $res->fetch_assoc()) $users[] = $row;
      } else { // PDO
          if ($q !== '') {
              $stmt = $pdo->prepare("SELECT COUNT(*) c FROM users WHERE $whereScore AND (username LIKE :kw OR school LIKE :kw)");
              $stmt->execute([':kw'=>$like]);
              $totalRows = (int)$stmt->fetchColumn();

              $stmt = $pdo->prepare("SELECT id, username, school, score FROM users WHERE $whereScore AND (username LIKE :kw OR school LIKE :kw) ORDER BY score DESC, username ASC LIMIT :off,:lim");
              $stmt->bindValue(':kw', $like, PDO::PARAM_STR);
              $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
              $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
              $stmt->execute();
              $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
          } else {
              $totalRows = (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE $whereScore")->fetchColumn();
              $stmt = $pdo->prepare("SELECT id, username, school, score FROM users WHERE $whereScore ORDER BY score DESC, username ASC LIMIT :off,:lim");
              $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
              $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
              $stmt->execute();
              $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
          }
      }
  } catch (Exception $e) {
      if ($debug) { die("Error Query: " . $e->getMessage()); }
      http_response_code(500);
      die("Terjadi kesalahan mengambil data.");
  }

  $total = count($users); // jumlah baris halaman ini
  // Statistik global (butuh semua, bukan hanya halaman)
  try {
      if ($isMysqli) {
          $resAll = $conn->query("SELECT score FROM users WHERE $whereScore");
          $allScores = [];
          while ($resAll && $r = $resAll->fetch_assoc()) $allScores[] = (int)$r['score'];
      } else {
          $allScores = $pdo->query("SELECT score FROM users WHERE $whereScore")->fetchAll(PDO::FETCH_COLUMN);
          $allScores = array_map('intval',$allScores);
      }
  } catch (Exception $e) { $allScores = []; }

  $globalCount = count($allScores);
  $avg = $globalCount ? round(array_sum($allScores)/$globalCount,1) : 0;
  $maxScore = $globalCount ? max($allScores) : 0;
  $lulusCount = $globalCount ? count(array_filter($allScores, fn($s)=>$s>=15)) : 0;
  $totalPages = max(1, (int)ceil($globalCount / $perPage));

  if (isset($_GET['delete'])) {
      $id = (int)$_GET['delete'];
      if ($isPDO) {
          $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
          $stmt->execute([$id]);
      } elseif ($isMysqli) {
          $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
          $stmt->bind_param('i', $id);
          $stmt->execute();
      }
      header("Location: lihat_data.php");
      exit;
  }
  ?>
  <!DOCTYPE html>
  <html lang="id">
  <head>
  <meta charset="UTF-8">
  <title>Hasil Ujian</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;500;600&display=swap');

  /* ================= LIQUID UI THEME (WHITE/LIGHT) ================= */
  :root{
    --primary: #8b5cf6;
    --secondary: #06b6d4;
    --accent: #22c55e;
    --warm: #f59e0b;
    --danger: #ef4444;
    --success: #10b981;
    --glass-light: rgba(255,255,255,.85);
    --glass-deep: rgba(255,255,255,.95);
    --glass-border: rgba(0,0,0,.08);
    --liquid-grad: linear-gradient(135deg,#8b5cf6 0%, #06b6d4 50%, #22c55e 100%);
    --liquid-reverse: linear-gradient(135deg,#22c55e 0%, #06b6d4 50%, #8b5cf6 100%);
    --text-primary: #1f2937;
    --text-secondary: #6b7280;
    --bg-base: #f8fafc;
  }

  /* Background dengan Liquid Gradient (Light Version) */
  body{
    margin:0; color:#1f2937;
    background: 
      radial-gradient(1200px 800px at 80% -5%, rgba(139,92,246,.06), transparent 60%),
      radial-gradient(1000px 600px at -5% 105%, rgba(6,182,212,.05), transparent 55%),
      radial-gradient(800px 500px at 50% 50%, rgba(34,197,94,.04), transparent 65%),
      linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
    font-family:'Poppins', system-ui, sans-serif;
    min-height:100vh; position:relative; overflow-x:hidden;
    background-attachment: fixed;
  }

  /* Liquid Blobs Background (Light) */
  body::before{
    content:""; position:fixed; inset:0; z-index:0; pointer-events:none;
    background-image:
      radial-gradient(circle at 15% 25%, rgba(139,92,246,.04) 0%, transparent 50%),
      radial-gradient(circle at 85% 75%, rgba(6,182,212,.04) 0%, transparent 50%),
      radial-gradient(circle at 50% 10%, rgba(34,197,94,.03) 0%, transparent 50%);
    animation: liquid-move 20s ease-in-out infinite;
  }

  @keyframes liquid-move{
    0%, 100% { transform: translate(0,0) scale(1) rotate(0deg); }
    33% { transform: translate(20px,-15px) scale(1.05) rotate(120deg); }
    66% { transform: translate(-15px,25px) scale(0.95) rotate(240deg); }
  }

  /* Liquid Glass Components (Light Theme) */
  .card, .header-bar{
    position: relative;
    background: var(--glass-light);
    border: 1px solid transparent;
    border-radius: 24px;
    backdrop-filter: blur(16px) saturate(1.2);
    -webkit-backdrop-filter: blur(16px) saturate(1.2);
    overflow: hidden;
    box-shadow: 
      0 8px 24px rgba(0,0,0,.06),
      inset 0 1px 0 rgba(255,255,255,.9);
    color: #1f2937;
    padding: 1.5rem; /* Diperbesar dari default */
    min-height: 120px; /* Tinggi minimum */
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: transform .2s ease, box-shadow .3s ease;
    z-index: 1;
  }
  .card::before, .header-bar::before{
    content:"";
    position:absolute; inset:0; padding:1px; border-radius:inherit;
    background: var(--liquid-grad);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    pointer-events:none; opacity:.3;
    animation: liquid-border 8s linear infinite;
  }

  @keyframes liquid-border{
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
  }

  /* Header Liquid (Light) */
  .header-bar{
    background: var(--glass-deep);
    border-bottom: 1px solid var(--glass-border);
    box-shadow: 
      0 8px 24px rgba(0,0,0,.08),
      inset 0 1px 0 rgba(255,255,255,.95);
    padding: 2rem 0; /* Diperbesar padding */
  }
  .header-bar h1{
    font-family:'Inter', sans-serif; 
    font-weight:700; 
    font-size: 2.5rem; /* Diperbesar dari default */
    letter-spacing:-.02em;
    background: var(--liquid-grad);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: none;
    line-height: 1.2;
    margin-bottom: 0.5rem;
  }
  .header-bar p{
    color: rgba(31,41,55,.7);
    font-size: 1rem; /* Diperbesar dari default */
    line-height: 1.5;
  }

  /* Cards dengan efek Liquid (Light) - Diperbesar */
  .card{
    position: relative;
    background: var(--glass-light);
    border: 1px solid transparent;
    border-radius: 24px;
    backdrop-filter: blur(16px) saturate(1.2);
    -webkit-backdrop-filter: blur(16px) saturate(1.2);
    overflow: hidden;
    box-shadow: 
      0 8px 24px rgba(0,0,0,.06),
      inset 0 1px 0 rgba(255,255,255,.9);
    color: #1f2937;
    padding: 1.5rem; /* Diperbesar dari default */
    min-height: 120px; /* Tinggi minimum */
    display: flex;
    flex-direction: column;
    justify-content: center;
    transition: transform .2s ease, box-shadow .3s ease;
    z-index: 1;
  }
  .card:hover{
    transform: translateY(-4px) scale(1.02);
    box-shadow: 
      0 16px 32px rgba(0,0,0,.08),
      inset 0 1px 0 rgba(255,255,255,.95);
  }
  .card h3{
    font-weight:600; 
    font-size:.9rem; /* Diperbesar dari .85rem */
    color: var(--text-secondary);
    margin-bottom:.6rem; /* Diperbesar margin */
    text-transform:uppercase; 
    letter-spacing:.1em;
    line-height: 1.4; /* Tinggi baris yang lebih baik */
  }

  /* Stat Value - Diperbesar */
  .stat-value{
    font-weight:700; 
    font-size:2.2rem; /* Diperbesar dari 1.8rem */
    line-height: 1.2;
    background: var(--liquid-grad);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-top: auto; /* Push ke bawah */
  }

  /* Table - Diperbesar cells */
  .table th{
    background: linear-gradient(90deg, rgba(248,250,252,.98), rgba(241,245,249,.98));
    color: #1f2937;
    font-weight: 700; 
    font-family: 'Inter', sans-serif;
    text-transform: uppercase; 
    letter-spacing: .08em; 
    padding: 1.2rem 1rem; /* Diperbesar padding */
    border-bottom: 1px solid rgba(226,232,240,.6);
    position: relative;
    font-size: 0.85rem;
    line-height: 1.4;
  }
  .table th::after{
    content:"";
    position:absolute; bottom:0; left:0; right:0; height:1px;
    background: var(--liquid-grad);
    opacity:.3;
  }
  .table td{
    background: rgba(255,255,255,.95);
    color: #374151;
    border-bottom: 1px solid rgba(226,232,240,.4); 
    padding: 1.2rem 1rem; /* Diperbesar padding */
    transition: background .2s ease;
    font-size: 0.95rem; /* Diperbesar font */
    line-height: 1.5;
  }

  /* Rank Badge - Diperbesar & Warna Diperjelas */
  .badge-rank{
    width: 56px; height: 56px; /* Diperbesar dari 48px */
    border-radius: 18px; /* Sesuaikan border radius */
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; 
    font-size: 1.1rem; /* Diperbesar font */
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(0,0,0,.15);
    position: relative;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,.3);
  }
  .badge-rank::before{
    content:"";
    position:absolute; inset:0; border-radius:inherit;
    background: linear-gradient(135deg, rgba(255,255,255,.25), transparent 50%, rgba(0,0,0,.1));
    pointer-events:none;
  }

  /* Rank 1 - Emas yang lebih mencolok */
  .rank-1{ 
    background: conic-gradient(from 0deg, #ffd700, #ffed4e, #fbbf24, #f59e0b, #ffd700);
    color: #1a1a1a;
    border-color: #ffd700;
    box-shadow: 
      0 8px 24px rgba(255,215,0,.4),
      0 0 20px rgba(255,215,0,.3),
      inset 0 2px 0 rgba(255,255,255,.4);
    animation: rank-glow-gold 2s ease-in-out infinite alternate;
    text-shadow: 0 1px 2px rgba(0,0,0,.3);
  }

  /* Rank 2 - Perak yang lebih terang */
  .rank-2{ 
    background: linear-gradient(135deg, #f8fafc, #e2e8f0, #cbd5e1, #94a3b8);
    color: #1e293b;
    border-color: #cbd5e1;
    box-shadow: 
      0 6px 20px rgba(148,163,184,.3),
      0 0 15px rgba(203,213,225,.2),
      inset 0 2px 0 rgba(255,255,255,.5);
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
  }

  /* Rank 3 - Perunggu yang lebih hangat */
  .rank-3{ 
    background: linear-gradient(135deg, #fed7aa, #fdba74, #fb923c, #ea580c);
    color: #ffffff;
    border-color: #fb923c;
    box-shadow: 
      0 6px 20px rgba(251,146,60,.3),
      0 0 15px rgba(253,186,116,.2),
      inset 0 2px 0 rgba(255,255,255,.3);
    text-shadow: 0 1px 2px rgba(0,0,0,.3);
  }

  /* Rank lainnya - Abu-abu yang lebih kontras */
  .rank-other{ 
    background: linear-gradient(135deg, #6b7280, #4b5563, #374151, #1f2937); 
    color: #ffffff; 
    border-color: #6b7280;
    box-shadow: 
      0 4px 16px rgba(107,114,128,.2),
      inset 0 1px 0 rgba(255,255,255,.2);
    text-shadow: 0 1px 2px rgba(0,0,0,.4);
  }

  /* Animasi khusus untuk rank 1 */
  @keyframes rank-glow-gold{
    0% { 
      box-shadow: 
        0 8px 24px rgba(255,215,0,.3),
        0 0 15px rgba(255,215,0,.2),
        inset 0 2px 0 rgba(255,255,255,.4);
      transform: scale(1);
    }
    100% { 
      box-shadow: 
        0 12px 32px rgba(255,215,0,.5),
        0 0 30px rgba(255,215,0,.4),
        inset 0 2px 0 rgba(255,255,255,.5);
      transform: scale(1.05);
    }
  }

  /* Hover effects untuk semua rank */
  .badge-rank:hover{
    transform: scale(1.1);
    transition: transform .2s ease;
  }
  .rank-1:hover{
    box-shadow: 
      0 12px 36px rgba(255,215,0,.6),
      0 0 35px rgba(255,215,0,.5),
      inset 0 2px 0 rgba(255,255,255,.6);
  }
  .rank-2:hover{
    box-shadow: 
      0 8px 24px rgba(148,163,184,.4),
      0 0 20px rgba(203,213,225,.3),
      inset 0 2px 0 rgba(255,255,255,.6);
  }
  .rank-3:hover{
    box-shadow: 
      0 8px 24px rgba(251,146,60,.4),
      0 0 20px rgba(253,186,116,.3),
      inset 0 2px 0 rgba(255,255,255,.4);
  }
  .rank-other:hover{
    box-shadow: 
      0 6px 20px rgba(107,114,128,.3),
      inset 0 1px 0 rgba(255,255,255,.3);
  }

  /* Score Colors - Diperjelas juga */
  .score-good{ 
    color: #059669; 
    font-weight: 800;
    text-shadow: 0 0 8px rgba(5,150,105,.4);
    background: linear-gradient(90deg, #059669, #10b981);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .score-mid{ 
    color: #0891b2; 
    font-weight: 800;
    text-shadow: 0 0 8px rgba(8,145,178,.4);
    background: linear-gradient(90deg, #0891b2, #06b6d4);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .score-low{ 
    color: #dc2626; 
    font-weight: 800;
    text-shadow: 0 0 8px rgba(220,38,38,.4);
    background: linear-gradient(90deg, #dc2626, #ef4444);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* Status badges - Warna diperjelas */
  .status-pass{ 
    background: linear-gradient(90deg, rgba(16,185,129,.2), rgba(5,150,105,.15)); 
    color: #059669;
    border: 2px solid #10b981;
    box-shadow: 
      0 0 15px rgba(16,185,129,.25),
      inset 0 1px 0 rgba(255,255,255,.3);
    text-shadow: 0 1px 1px rgba(255,255,255,.5);
  }
  .status-fail{ 
    background: linear-gradient(90deg, rgba(239,68,68,.2), rgba(220,38,38,.15)); 
    color: #dc2626;
    border: 2px solid #ef4444;
    box-shadow: 
      0 0 15px rgba(239,68,68,.25),
      inset 0 1px 0 rgba(255,255,255,.2);
    text-shadow: 0 1px 1px rgba(255,255,255,.3);
  }

  /* Status - Hanya Text tanpa kotak */
  .status-pass, .status-fail{
    font-size: .9rem; /* Font lebih besar */
    font-weight: 700; 
    letter-spacing: .04em;
    border: none; /* Hapus border */
    background: none; /* Hapus background */
    box-shadow: none; /* Hapus shadow */
    padding: 0; /* Hapus padding */
    border-radius: 0; /* Hapus border radius */
    position: relative;
    overflow: visible;
    display: inline; /* Inline seperti text biasa */
  }
  .status-pass::before, .status-fail::before{
    display: none; /* Hapus pseudo element */
  }
  .status-pass{ 
    color: #059669;
    text-shadow: 0 1px 2px rgba(5,150,105,.3);
  }
  .status-fail{ 
    color: #dc2626;
    text-shadow: 0 1px 2px rgba(220,38,38,.3);
  }

  /* Pagination - Diperbesar dengan style button */
  .pagination {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
  }
  .pagination a, .pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 0.8rem 1.2rem; /* Padding lebih besar */
    font-size: 1rem; /* Font lebih besar */
    font-weight: 600;
    letter-spacing: 0.02em;
    color: #1f2937;
    background: var(--glass-light);
    border: 2px solid rgba(139,92,246,.2); /* Border seperti button */
    border-radius: 14px; /* Rounded seperti button */
    backdrop-filter: blur(8px);
    transition: all .2s cubic-bezier(.4,0,.2,1);
    position: relative;
    overflow: hidden;
    text-decoration: none;
    line-height: 1.4;
    min-height: 44px; /* Tinggi minimum */
    min-width: 44px; /* Lebar minimum untuk angka */
  }
  .pagination a::before{
    content:"";
    position:absolute; inset:0; border-radius:inherit;
    background: var(--liquid-grad);
    opacity:0;
    transition: opacity .2s ease;
    pointer-events:none;
  }
  .pagination a:hover{
    transform: translateY(-2px) scale(1.05);
    box-shadow: 0 8px 20px rgba(139,92,246,.2);
    color: #ffffff;
    border-color: #8b5cf6;
  }
  .pagination a:hover::before{
    opacity: 1;
  }

  /* Active page styling */
  .pagination .active{
    background: var(--liquid-grad);
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 6px 18px rgba(139,92,246,.3);
    transform: scale(1.05);
    font-weight: 700;
  }

  /* Disabled pagination items */
  .pagination span:not(.active){
    opacity: 0.5;
    background: rgba(255,255,255,.5);
    color: #9ca3af;
    cursor: not-allowed;
    border-color: rgba(156,163,175,.3);
  }

  /* Navigation buttons (First, Prev, Next, Last) */
  .pagination a.nav-btn {
    padding: 0.8rem 1.5rem; /* Padding lebih besar untuk nav buttons */
    font-weight: 700;
    white-space: nowrap;
  }
  .pagination a.nav-btn:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 10px 24px rgba(139,92,246,.25);
  }

  /* Responsive pagination */
  @media (max-width: 768px) {
    .pagination {
      gap: 0.3rem;
    }
    .pagination a, .pagination span {
      padding: 0.6rem 1rem;
      font-size: 0.9rem;
      min-height: 40px;
      min-width: 40px;
    }
    .pagination a.nav-btn {
      padding: 0.6rem 1.2rem;
    }
  }

  @media (max-width: 640px) {
    .pagination {
      gap: 0.2rem;
    }
    .pagination a, .pagination span {
      padding: 0.5rem 0.8rem;
      font-size: 0.85rem;
      min-height: 36px;
      min-width: 36px;
    }
    .pagination a.nav-btn {
      padding: 0.5rem 1rem;
      font-size: 0.8rem;
    }
    
    /* Hide text on very small screens, show only icons */
    .pagination a.nav-btn span.text {
      display: none;
    }
    .pagination a.nav-btn span.icon {
      display: inline;
    }
  }

  /* Fade In Animation */
  .fade-in{ animation: fade .6s ease-out; }
  @keyframes fade{ 
    from { opacity: 0; transform: translateY(12px); } 
    to { opacity: 1; transform: translateY(0); } 
  }

  /* Search Input - Diperbesar & Diperjelas */
  .search-input{
    background: rgba(255,255,255,.95);
    border: 2px solid rgba(139,92,246,.2); /* Border lebih tebal dan berwarna */
    border-radius: 18px; /* Lebih rounded */
    color: #1f2937;
    padding: 1.2rem 1.8rem; /* Padding lebih besar */
    width:100%;
    font-size: 1.1rem; /* Font lebih besar */
    font-weight: 500;
    backdrop-filter: blur(12px);
    transition: all .3s ease;
    box-shadow: 
      0 6px 20px rgba(0,0,0,.06),
      inset 0 1px 0 rgba(255,255,255,.8);
    line-height: 1.5;
    min-height: 56px; /* Tinggi minimum */
  }
  .search-input::placeholder{ 
    color: rgba(107,114,128,.7); 
    font-weight: 400;
  }
  .search-input:focus{
    outline: none;
    border: 2px solid #8b5cf6; /* Border fokus yang jelas */
    background: rgba(255,255,255,.98);
    box-shadow: 
      0 0 0 4px rgba(139,92,246,.15),
      0 8px 28px rgba(0,0,0,.1),
      inset 0 1px 0 rgba(255,255,255,.9);
    transform: scale(1.02);
  }

  /* Buttons - Diperbesar */
  .btn, .btn-secondary{
    position: relative;
    border: 1px solid transparent;
    background: var(--liquid-grad);
    color: #ffffff;
    border-radius: 16px; /* Lebih rounded */
    font-weight: 700; /* Font weight lebih tebal */
    font-size: 1rem; /* Font lebih besar */
    padding: 1.2rem 1.8rem; /* Padding lebih besar */
    min-height: 56px; /* Sama dengan search input */
    transition: all .15s cubic-bezier(.4,0,.2,1);
    box-shadow: 
      0 6px 20px rgba(139,92,246,.25),
      inset 0 1px 0 rgba(255,255,255,.2);
    text-shadow: 0 1px 2px rgba(0,0,0,.2);
    overflow: hidden;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1.4;
    white-space: nowrap;
  }
  .btn::before, .btn-secondary::before{
    content:"";
    position:absolute; inset:0; border-radius:inherit;
    background: linear-gradient(135deg, rgba(255,255,255,.15), transparent 50%, rgba(0,0,0,.05));
    pointer-events:none;
  }
  .btn:hover, .btn-secondary:hover{
    transform: translateY(-2px) scale(1.03);
    box-shadow: 
      0 12px 32px rgba(139,92,246,.35),
      inset 0 1px 0 rgba(255,255,255,.3);
  }
  .btn-secondary{
    background: var(--liquid-reverse);
    box-shadow: 
      0 6px 20px rgba(6,182,212,.25),
      inset 0 1px 0 rgba(255,255,255,.2);
  }
  .btn-secondary:hover{
    box-shadow: 
      0 12px 32px rgba(6,182,212,.35),
      inset 0 1px 0 rgba(255,255,255,.3);
  }

  /* Search Form Container */
  .search-form {
    background: var(--glass-light);
    border: 1px solid transparent;
    border-radius: 24px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(16px) saturate(1.2);
    -webkit-backdrop-filter: blur(16px) saturate(1.2);
    box-shadow: 
      0 8px 24px rgba(0,0,0,.06),
      inset 0 1px 0 rgba(255,255,255,.9);
    position: relative;
    overflow: hidden;
  }
  .search-form::before{
    content:"";
    position:absolute; inset:0; padding:1px; border-radius:inherit;
    background: var(--liquid-grad);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor; mask-composite: exclude;
    pointer-events:none; opacity:.2;
    animation: liquid-border 8s linear infinite;
  }

  /* Filter info styling */
  .filter-info {
    background: rgba(139,92,246,.1);
    border: 1px solid rgba(139,92,246,.2);
    border-radius: 12px;
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
    color: #8b5cf6;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }
  .filter-info::before {
    content: "🔍";
    font-size: 1rem;
  }

  /* Links */
  .link-btn{
    color: var(--primary);
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    position: relative;
    transition: all .2s ease;
    padding: 0.8rem 1.2rem;
    border-radius: 12px;
    background: rgba(139,92,246,.05);
    border: 1px solid rgba(139,92,246,.1);
  }
  .link-btn::after{
    content:"";
    position:absolute; bottom:-2px; left:0; right:100%; height:2px;
    background: var(--liquid-grad);
    transition: right .3s ease;
  }
  .link-btn:hover{
    color: var(--secondary);
    background: rgba(139,92,246,.1);
    border-color: rgba(139,92,246,.2);
    transform: translateY(-1px);
  }
  .link-btn:hover::after{
    right: 0;
  }

  /* Responsive untuk search */
  @media (max-width: 768px) {
    .search-input {
      padding: 1rem 1.4rem;
      font-size: 1rem;
      min-height: 48px;
    }
    .btn, .btn-secondary {
      padding: 1rem 1.4rem;
      font-size: 0.95rem;
      min-height: 48px;
    }
    .search-form {
      padding: 1.2rem;
    }
  }

  @media (max-width: 640px) {
    .search-input {
      padding: 0.9rem 1.2rem;
      font-size: 0.95rem;
      min-height: 44px;
    }
    .btn, .btn-secondary {
      padding: 0.9rem 1.2rem;
      font-size: 0.9rem;
      min-height: 44px;
    }
    .search-form {
      padding: 1rem;
    }
  }
  </style>
  </head>
  <body>
  <!-- Floating Liquid Shapes -->
  <div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
  </div>

  <header class="header-bar py-6 shadow-md">
    <div class="max-w-6xl mx-auto px-4">
      <h1 class="text-3xl font-bold tracking-tight">Hasil Ujian Rumah Adat</h1>
      <p class="mt-1 text-sm text-blue-100">Rekap peserta yang telah mengerjakan ujian<?= $showAllScores ? '' : ' (hanya skor > 0)' ?></p>
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-8">

    <!-- Pencarian -->
    <div class="search-form fade-in">
      <form method="get" class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <input type="text" name="q" class="search-input" placeholder="🔍 Cari nama peserta atau asal sekolah..." value="<?= htmlspecialchars($q,ENT_QUOTES) ?>">
        </div>
        <div class="flex gap-3">
          <button class="btn" type="submit">Cari Data</button>
          <?php if ($q !== ''): ?>
            <a href="lihat_data.php" class="btn-secondary">Reset Filter</a>
          <?php endif; ?>
        </div>
      </form>
      <?php if ($q !== ''): ?>
        <div class="mt-3 flex items-center justify-between">
          <div class="filter-info">Filter aktif: "<?= htmlspecialchars($q) ?>"</div>
          <div class="text-sm text-gray-500"><?= $total ?> hasil ditemukan</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Statistik Global -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="card fade-in"><h3>Total Peserta</h3><div class="stat-value"><?= $globalCount ?></div></div>
      <div class="card fade-in"><h3>Rata-rata Nilai</h3><div class="stat-value"><?= $avg ?></div></div>
      <div class="card fade-in"><h3>Nilai Tertinggi</h3><div class="stat-value"><?= $maxScore ?></div></div>
      <div class="card fade-in"><h3>Lulus (≥14)</h3><div class="stat-value"><?= $lulusCount ?></div></div>
    </div>

    <!-- Tabel -->
    <div class="card fade-in">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
        <h2 class="text-xl font-semibold">Daftar Peserta <?= $q ? '(Filter)' : '' ?></h2>
        <span class="text-sm text-gray-500">Halaman <?= $page ?>/<?= $totalPages ?> | <?= $total ?> baris (<?= $globalCount ?> total)</span>
      </div>

      <?php if ($total > 0): ?>
        <div class="overflow-x-auto">
          <table class="table w-full border-collapse">
            <thead>
              <tr>
                <th class="py-3 px-3 text-left">Rank</th>
                <th class="py-3 px-3 text-left">Nama</th>
                <th class="py-3 px-3 text-left">Asal Sekolah</th>
                <th class="py-3 px-3 text-center">Nilai</th>
                <th class="py-3 px-3 text-center">Status</th>
                <th class="py-3 px-3 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php
            $rankBase = $offset; // rank absolut
            foreach ($users as $i => $u):
                $rank = $rankBase + $i + 1;
                $score = (int)$u['score'];
                $rankClass  = $rank===1?'rank-1':($rank===2?'rank-2':($rank===3?'rank-3':'rank-other'));
                $scoreClass = $score>=17?'score-good':($score>=14?'score-mid':'score-low');
                $statusOk   = $score>=14;
            ?>
              <tr class="border-b last:border-none">
                <td class="py-3 px-3">
                  <div class="badge-rank <?= $rankClass; ?>"><?= $rank; ?></div>
                </td>
                <td class="py-3 px-3 font-medium"><?= htmlspecialchars($u['username']); ?></td>
                <td class="py-3 px-3 text-gray-600"><?= htmlspecialchars($u['school']); ?></td>
                <td class="py-3 px-3 text-center"><span class="<?= $scoreClass; ?>"><?= $score; ?></span></td>
                <td class="py-3 px-3 text-center">
                  <span class="<?= $statusOk?'status-pass':'status-fail'; ?>"><?= $statusOk?'Lulus':'Tidak Lulus'; ?></span>
                </td>
                <td class="py-3 px-3 text-center">
                  <a href="lihat_data.php?delete=<?= $u['id'] ?>" class="text-red-600" onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="pagination mt-6 flex flex-wrap items-center">
            <?php
            $baseUrl = 'lihat_data.php';
            $params = [];
            if ($q !== '') $params['q'] = $q;

            $buildLink = function($p) use ($baseUrl,$params) {
                $params['page'] = $p;
                return $baseUrl . '?' . http_build_query($params);
            };

            // First & Prev
            if ($page > 1) {
                echo '<a href="'.$buildLink(1).'">« Awal</a>';
                echo '<a href="'.$buildLink($page-1).'">‹ Sebelumnya</a>';
            }

            $window = 2;
            $start = max(1, $page - $window);
            $end   = min($totalPages, $page + $window);
            if ($start > 1) echo '<span>…</span>';
            for ($p=$start; $p<=$end; $p++) {
                if ($p == $page) echo '<span class="active">'.$p.'</span>';
                else echo '<a href="'.$buildLink($p).'">'.$p.'</a>';
            }
            if ($end < $totalPages) echo '<span>…</span>';

            // Next & Last
            if ($page < $totalPages) {
                echo '<a href="'.$buildLink($page+1).'">Berikutnya ›</a>';
                echo '<a href="'.$buildLink($totalPages).'">Akhir »</a>';
            }
            ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="py-10 text-center text-gray-500">
          Tidak ada data <?= $q ? 'untuk pencarian ini.' : 'yang tersedia.' ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Navigasi -->
    <div class="mt-8 flex flex-wrap gap-6">
      <a href="index.php" class="link-btn">Kembali ke Beranda</a>
      <a href="export.php" class="btn-secondary" target="_blank">Ekspor ke CSV</a>
    </div>
  </main>

  <footer class="mt-12 py-8 text-center text-gray-500">
    <div class="max-w-6xl mx-auto px-4">
      &copy; <?= date('Y') ?> Rumah Adat. Dibuat dengan &#10084; oleh Tim IT.
    </div>
  </footer>
  </body>
  </html>