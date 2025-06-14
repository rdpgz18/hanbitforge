<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/functions.php';

session_start();

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($username)) {
        $errors['username'] = 'Username harus diisi';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Username minimal 4 karakter';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Username hanya boleh mengandung huruf, angka, dan underscore';
    }

    if (empty($email)) {
        $errors['email'] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid';
    }

    if (empty($password)) {
        $errors['password'] = 'Password harus diisi';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Konfirmasi password tidak cocok';
    }

    // Cek username/email sudah ada
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);

            if ($stmt->rowCount() > 0) {
                $errors['general'] = 'Username atau email sudah terdaftar';
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }
    }

    // Proses registrasi jika tidak ada error
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashed_password]);

            $_SESSION['registration_success'] = true;
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors['general'] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HabitForge - Daftar Akun</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="shortcut icon" href="./assets/icon/pavicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6;
        }

        .error-message {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
    </style>
</head>

<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-6">Daftar Akun</h2>

        <?php if (!empty($errors['general'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($errors['general']) ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" novalidate>
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username:</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= !empty($errors['username']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['username'])): ?>
                    <p class="error-message"><?= htmlspecialchars($errors['username']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= !empty($errors['email']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['email'])): ?>
                    <p class="error-message"><?= htmlspecialchars($errors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                <input type="password" id="password" name="password"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= !empty($errors['password']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['password'])): ?>
                    <p class="error-message"><?= htmlspecialchars($errors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password:</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= !empty($errors['confirm_password']) ? 'border-red-500' : '' ?>">
                <?php if (!empty($errors['confirm_password'])): ?>
                    <p class="error-message"><?= htmlspecialchars($errors['confirm_password']) ?></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg font-semibold hover:bg-indigo-700 focus:outline-none focus:shadow-outline">
                Daftar
            </button>
        </form>

        <p class="text-center text-gray-600 text-sm mt-4">
            Sudah punya akun? <a href="./login.php" class="text-indigo-600 hover:text-indigo-800">Masuk di sini</a>
        </p>
    </div>

    <script>
        // Toggle password visibility
        document.querySelectorAll('input[type="password"]').forEach((input, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            const toggle = document.createElement('span');
            toggle.className = 'absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer';
            toggle.innerHTML = `
                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5c-4.08 0-7.536 3.018-9 7.5S7.92 19.5 12 19.5s7.536-3.018 9-7.5-4.92-7.5-9-7.5zM12 14.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"></path>
                </svg>
            `;
            wrapper.appendChild(toggle);

            toggle.addEventListener('click', () => {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);

                // Update icon
                const icon = toggle.querySelector('svg');
                if (type === 'password') {
                    icon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5c-4.08 0-7.536 3.018-9 7.5S7.92 19.5 12 19.5s7.536-3.018 9-7.5-4.92-7.5-9-7.5zM12 14.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"></path>
                    `;
                } else {
                    icon.innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.08 0-7.536-3.018-9-7.5 1.464-4.482 4.92-7.5 9-7.5 1.797 0 3.488.523 4.95 1.4M9 9l3-3m0 0l3 3m-3-3v12m-6-3l3 3 3-3"></path>
                    `;
                }
            });
        });
    </script>
</body>

</html>