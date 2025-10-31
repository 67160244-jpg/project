<?php
// login.php
session_start();
require __DIR__ . '/config_mysqli.php'; // เชื่อมต่อ DB

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        // ตรวจสอบจาก DB
        $sql = "SELECT id, full_name, password_hash FROM users WHERE email = ? LIMIT 1";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $full_name, $password_hash);
                $stmt->fetch();
                if (password_verify($password, $password_hash)) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['full_name'] = $full_name;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $message = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
                }
            } else {
                $message = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
            }
            $stmt->close();
        } else {
            $message = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล';
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background: #e0d7f6;
  font-family: 'Segoe UI', sans-serif;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}
.login-card {
  background: #f3e8ff;
  border: 1px solid #d3b3ff;
  border-radius: 1rem;
  padding: 2rem;
  width: 100%;
  max-width: 400px;
  box-shadow: 0 0 20px rgba(139,0,255,0.2);
}
.login-card h2 {
  color: #4B0082;
  text-align: center;
  margin-bottom: 1rem;
}
.form-label {
  color: #4B0082;
  font-weight: 600;
}
.form-control:focus {
  border-color: #8B00FF;
  box-shadow: 0 0 0 0.2rem rgba(139,0,255,0.25);
}
.btn-login {
  background: #8B00FF;
  color: #fff;
  font-weight: bold;
}
.btn-login:hover {
  background: #9400D3;
}
.alert {
  background: #ffe6ff;
  border: 1px solid #d3b3ff;
  color: #4B0082;
}
.subtext {
  color: #6b21a8;
  font-size: 0.85rem;
  text-align: center;
  margin-top: 0.5rem;
}
</style>
</head>
<body>

<div class="login-card">
  <h2>เข้าสู่ระบบ Dashboard</h2>
  <?php if($message): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label" for="email">อีเมล</label>
      <input type="email" class="form-control" id="email" name="email" placeholder="กรอกอีเมล" required>
    </div>
    <div class="mb-3">
      <label class="form-label" for="password">รหัสผ่าน</label>
      <input type="password" class="form-control" id="password" name="password" placeholder="กรอกรหัสผ่าน" required>
    </div>
    <button type="submit" class="btn btn-login w-100">เข้าสู่ระบบ</button>
  </form>
  <div class="subtext">แหล่งข้อมูล: MySQL (mysqli)</div>
</div>

</body>
</html>
