<?php
require_once 'connect.php';

$isMysqli = isset($conn) && $conn instanceof mysqli;
$isPDO    = isset($pdo) && $pdo instanceof PDO;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=hasil_ujian.csv');

$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, ['Rank', 'Nama', 'Asal Sekolah', 'Nilai', 'Status']);

// Ambil data semua peserta, urutkan dari skor tertinggi
if ($isMysqli) {
    $res = $conn->query("SELECT username, school, score FROM users ORDER BY score DESC, username ASC");
    $rank = 1;
    while ($row = $res->fetch_assoc()) {
        $score = (int)$row['score'];
        $status = $score >= 14 ? 'Lulus' : 'Tidak Lulus';
        fputcsv($output, [$rank, $row['username'], $row['school'], $score, $status]);
        $rank++;
    }
} elseif ($isPDO) {
    $stmt = $pdo->query("SELECT username, school, score FROM users ORDER BY score DESC, username ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rank = 1;
    foreach ($rows as $row) {
        $score = (int)$row['score'];
        $status = $score >= 14 ? 'Lulus' : 'Tidak Lulus';
        fputcsv($output, [$rank, $row['username'], $row['school'], $score, $status]);
        $rank++;
    }
}

fclose($output);
exit;