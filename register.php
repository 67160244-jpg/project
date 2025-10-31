<?php
require __DIR__ . '/config_mysqli.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$success = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($str){ return htmlspecialchars($str ?? "", ENT_QUOTES, "UTF-8"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "CSRF token ไม่ถูกต้อง กรุณารีเฟรชหน้าแล้วลองอีกครั้ง";
    }

    $username  = trim($_POST['username'] ?? "");
    $password  = $_POST['password'] ?? "";
    $email     = trim($_POST['email'] ?? "");
    $full_name = trim($_POST['name'] ?? "");

    if ($username === "" || !preg_match('/^[A-Za-z0-9_\.]{3,30}$/', $username)) {
        $errors[] = "กรุณากรอก username 3–30 ตัวอักษร (a-z, A-Z, 0-9, _, .)";
    }
    if (strlen($password) < 8) {
        $errors[] = "รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "อีเมลไม่ถูกต้อง";
    }
    if ($full_name === "" || mb_strlen($full_name) > 100) {
        $errors[] = "กรุณากรอกชื่อ–นามสกุล (ไม่เกิน 100 ตัวอักษร)";
    }

    if (!$errors) {
        $sql = "SELECT 1 FROM users WHERE username = ? OR email = ? LIMIT 1";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "Username หรือ Email นี้ถูกใช้แล้ว";
            }
            $stmt->close();
        }
    }

    if (!$errors) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("ssss", $username, $email, $password_hash, $full_name);
            try {
                if ($stmt->execute()) {
                    $success = "สมัครสมาชิกสำเร็จ! คุณสามารถล็อกอินได้แล้วค่ะ";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $username = $email = $full_name = "";
                }
            } catch (\mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $errors[] = "Username/Email ซ้ำ กรุณาใช้ค่าอื่น";
                } else {
                    $errors[] = "บันทึกข้อมูลไม่สำเร็จ: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8");
                }
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Register</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { 
    background: #e0d7f6; 
    font-family: 'Segoe UI', sans-serif; 
    color: #1e1b4b; 
    margin:0; padding:0; 
}
.container { 
    max-width: 480px; 
    margin: 60px auto; 
    background: #f3e8ff; 
    border: 1px solid #d3b3ff; 
    border-radius: 1rem; 
    padding: 30px 24px; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
h1 { 
    color: #4B0082; 
    text-align:center; 
    margin-bottom: 24px; 
    text-shadow: 1px 1px 2px #fff; 
}
label { 
    display:block; 
    font-weight:600; 
    margin-top: 12px; 
    color: #4B0082; 
}
input { 
    width:100%; 
    padding:12px; 
    border-radius:12px; 
    border:1px solid #d3b3ff; 
    margin-top:6px;
    background:#fff;
}
button { 
    width:100%; 
    padding:12px; 
    border:none; 
    border-radius:12px; 
    margin-top:20px; 
    background:#8B00FF; 
    color:#fff; 
    font-weight:700; 
    cursor:pointer; 
    transition:0.2s;
}
button:hover { background:#6a00c7; }
.alert { 
    padding:12px 14px; 
    border-radius:12px; 
    margin-bottom:12px; 
    font-size:14px; 
}
.alert.error { 
    background:#ffecec; 
    color:#a40000; 
    border:1px solid #ffc9c9; 
}
.alert.success { 
    background:#eeefff; 
    color:#2a0080; 
    border:1px solid #d3b3ff; 
}
.hint { 
    font-size:12px; 
    color:#6b21a8; 
    margin-top:2px; 
}
</style>
</head>
<body>
<div class="container">
    <h1>สมัครสมาชิก</h1>

    <?php if ($errors): ?>
      <div class="alert error">
        <?php foreach ($errors as $m) echo "<div>".e($m)."</div>"; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert success"><?= e($success) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

      <label>Username</label>
      <input type="text" name="username" value="<?= e($username ?? "") ?>" required>
      <div class="hint">อนุญาต a-z, A-Z, 0-9, _ และ . (3–30 ตัว)</div>

      <label>Password</label>
      <input type="password" name="password" required>
      <div class="hint">อย่างน้อย 8 ตัวอักษร</div>

      <label>Email</label>
      <input type="email" name="email" value="<?= e($email ?? "") ?>" required>

      <label>name-lastname</label>
      <input type="text" name="name" value="<?= e($full_name ?? "") ?>" required>

      <button type="submit">สมัครสมาชิก</button>
    </form>
</div>
</body>
</html>
