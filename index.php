<?php
session_start();
include 'db.php';

if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit;
}

$err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $hashed_password, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user'] = ['id' => $id, 'username' => $username, 'role' => $role];
            header("Location: dashboard.php");
            exit;
        } else {
            $err = "Password salah.";
        }
    } else {
        $err = "Username tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            background-image: url('assets/img/kabelindo.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .login-container h3 {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border-radius: 20px;
        }

        .btn-primary {
            border-radius: 20px;
            background: #2575fc;
            border: none;
        }

        .btn-primary:hover {
            background: #6a11cb;
        }

        .alert {
            border-radius: 20px;
        }

        .text-center a {
            color: #2575fc;
            text-decoration: none;
        }

        .text-center a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="text-center mb-4">
            <img src="assets/img/logo.png" alt="PT Kabelindo Murni Tbk Logo" style="max-width: 150px; height: auto;">
        </div>
        <h3 class="text-center mb-4">Login</h3>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success text-center">Registrasi berhasil, silakan login.</div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert-danger text-center"><?= htmlentities($err) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required />
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required />
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
            <div class="text-center mt-3">
                <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            </div>
        </form>
    </div>
</body>

</html>