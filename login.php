<?php
session_start();

/* ---------- Helpers ---------- */
function ensure_dirs() {
  if (!is_dir(__DIR__ . '/data')) { @mkdir(__DIR__ . '/data', 0777, true); }
  foreach (['users','jobs','applications','messages'] as $f) {
    $p = __DIR__."/data/$f.json";
    if (!file_exists($p)) { file_put_contents($p, json_encode([])); }
  }
}
function read_json($file){ return json_decode(file_get_contents(__DIR__."/data/$file.json"), true); }
ensure_dirs();

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';
  $users = read_json('users');
  foreach($users as $u){
    if($u['email']===$email && password_verify($pass,$u['password'])){
      $_SESSION['user']=$u;
      echo "<script>alert('Welcome back, ".htmlspecialchars($u['name'])."!'); window.location='home.php';</script>";
      exit;
    }
  }
  $error = "Invalid credentials.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log In</title>
<style>
  body{margin:0;background:#0b1226;color:#e9efff;font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
  .wrap{max-width:420px;margin:60px auto;padding:24px;background:#111a3b;border:1px solid #22306d;border-radius:16px}
  h1{margin:0 0 12px}
  label{display:block;margin:10px 0 6px;color:#a8b4ec}
  input{width:100%;padding:12px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff}
  .actions{display:flex;gap:10px;margin-top:14px}
  button,.link{all:unset;cursor:pointer;padding:12px 16px;border-radius:10px;background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700}
  .ghost{background:#0f1531;color:#e9efff;border:1px solid #22306d}
  .err{background:#2a1030;border:1px solid #7e2a4e;padding:10px;border-radius:10px;margin-bottom:10px;color:#ffc7d1}
</style>
</head>
<body>
  <div class="wrap">
    <h1>Log in</h1>
    <?php if($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post">
      <label>Email</label>
      <input type="email" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <div class="actions">
        <button type="submit">Continue</button>
        <a class="link ghost" href="signup.php">Create account</a>
      </div>
    </form>
  </div>
</body>
</html>
