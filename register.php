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
    $cpassword = $_POST['cpassword'];

    if ($password !== $cpassword) {
        $err = "Password dan konfirmasi password tidak sama.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hash);
        if ($stmt->execute()) {
            header("Location: index.php?registered=1");
            exit;
        } else {
            $err = "Username sudah digunakan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register</title>
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

        .register-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 100%;
            max-width: 400px;
        }

        .register-container h3 {
            font-weight: bold;
            color: #333;
            text-align: center;
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
    <div class="register-container">
        <h3><i class="fas fa-user-plus"></i> Register</h3>
        <?php if ($err): ?>
            <div class="alert alert-danger text-center"><?= htmlentities($err) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required />
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required />
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Konfirmasi Password</label>
                <input type="password" name="cpassword" class="form-control" placeholder="Konfirmasi password" required />
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-user-plus"></i> Daftar</button>
            <div class="text-center mt-3">
                <p>Sudah punya akun? <a href="index.php">Login di sini</a></p>
            </div>
        </form>
    </div>
</body>

</html>