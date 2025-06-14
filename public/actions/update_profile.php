<?php
// actions/update_profile.php
require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/functions.php'; // Include file fungsi kebiasaan

require_once "../auth/auth.php";

header('Content-Type: application/json'); // Respons selalu dalam JSON

$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Anda harus login untuk memperbarui profil.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id']; // ID user dari sesi yang sedang login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_to_update = [];

    // Ambil dan sanitasi data yang dikirimkan
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $bio = filter_input(INPUT_POST, 'bio', FILTER_SANITIZE_STRING);

    // Validasi dan siapkan data untuk update
    if (!empty($full_name)) {
        $data_to_update['full_name'] = $full_name;
    }
    if ($bio !== null) { // Izinkan bio kosong
        $data_to_update['bio'] = $bio;
    }

    // --- Penanganan Upload Foto Profil ---
    $avatar_url = null;
    if (isset($_FILES['avatar_upload']) && $_FILES['avatar_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/avatars/'; // Direktori tempat menyimpan avatar (sesuaikan path)
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Buat direktori jika belum ada
        }

        $fileTmpPath = $_FILES['avatar_upload']['tmp_name'];
        $fileName = $_FILES['avatar_upload']['name'];
        $fileSize = $_FILES['avatar_upload']['size'];
        $fileType = $_FILES['avatar_upload']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['jpg', 'gif', 'png', 'jpeg'];
        if (in_array($fileExtension, $allowedfileExtensions)) {
            // Buat nama file unik untuk menghindari timpaan
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $avatar_url = 'uploads/avatars/' . $newFileName; // Path relatif untuk disimpan di DB
                $data_to_update['avatar_url'] = $avatar_url;
            } else {
                $response['message'] = 'Gagal mengunggah gambar.';
                echo json_encode($response);
                exit();
            }
        } else {
            $response['message'] = 'Jenis file gambar tidak didukung. Hanya JPG, JPEG, PNG, GIF yang diizinkan.';
            echo json_encode($response);
            exit();
        }
    }

    // Lakukan update ke database
    if (!empty($data_to_update)) {
        if (updateUserProfile($pdo, $user_id, $data_to_update)) {
            $response['success'] = true;
            $response['message'] = 'Profil berhasil diperbarui!';
            // Anda bisa mengembalikan URL avatar baru jika diunggah
            if ($avatar_url) {
                $response['avatar_url'] = $avatar_url;
            }
        } else {
            $response['message'] = 'Gagal memperbarui profil. Tidak ada perubahan atau terjadi kesalahan database.';
        }
    } else {
        $response['message'] = 'Tidak ada data yang dikirim untuk diperbarui.';
    }
} else {
    $response['message'] = 'Metode request tidak diizinkan.';
}

echo json_encode($response);
exit();


?>