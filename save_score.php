   <?php
   // filepath: /rumah adat/save_score.php

   header('Content-Type: application/json'); // Memberi tahu browser bahwa respons adalah JSON

   require_once 'connect.php'; // Menggunakan connect.php yang menyediakan $pdo

   $response = ['success' => false, 'message' => ''];

   // Ambil data JSON dari request body
   $input = file_get_contents('php://input');
   $data = json_decode($input, true);

   if (json_last_error() !== JSON_ERROR_NONE) {
       $response['message'] = 'Invalid JSON received.';
       echo json_encode($response);
       exit;
   }

   // Pastikan semua data yang diperlukan ada
   if (!isset($data['name']) || !isset($data['school']) || !isset($data['score']) || !isset($data['total'])) {
       $response['message'] = 'Missing required data.';
       echo json_encode($response);
       exit;
   }

   $username = $data['name'];
   $school = $data['school'];
   $score = $data['score'];
   // $total = $data['total']; // Anda bisa menyimpan total juga jika diperlukan

   try {
       // Persiapkan statement SQL untuk INSERT
       // Pastikan nama kolom di tabel 'users' sesuai: 'username', 'school', 'score'
       $stmt = $pdo->prepare("INSERT INTO users (username, school, score) VALUES (:username, :school, :score)");

       // Bind parameter
       $stmt->bindParam(':username', $username);
       $stmt->bindParam(':school', $school);
       $stmt->bindParam(':score', $score);

       // Eksekusi statement
       if ($stmt->execute()) {
           $response['success'] = true;
           $response['message'] = 'Score saved successfully!';
       } else {
           $response['message'] = 'Failed to save score.';
           // Anda bisa menambahkan detail error PDO di sini untuk debugging
           // $response['error_info'] = $stmt->errorInfo();
       }
   } catch (PDOException $e) {
       $response['message'] = 'Database error: ' . $e->getMessage();
   }

   echo json_encode($response);
   ?>
   