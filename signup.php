<?php
session_start();

/* ---------- Helpers ---------- */
function ensure_dirs() {
  if (!is_dir(__DIR__ . '/data')) { @mkdir(__DIR__ . '/data', 0777, true); }
  if (!is_dir(__DIR__ . '/uploads')) { @mkdir(__DIR__ . '/uploads', 0777, true); }
  foreach (['users','jobs','applications','messages'] as $f) {
    $p = __DIR__."/data/$f.json";
    if (!file_exists($p)) { file_put_contents($p, json_encode([])); }
  }
}
function read_json($file){ return json_decode(file_get_contents(__DIR__."/data/$file.json"), true); }
function write_json($file,$data){ file_put_contents(__DIR__."/data/$file.json", json_encode($data, JSON_PRETTY_PRINT)); }
ensure_dirs();

$error = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name = trim($_POST['name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? 'seeker';
  $bio  = trim($_POST['bio'] ?? '');
  if(!$name || !$email || !$pass){ $error = "Please fill all required fields."; }
  else {
    $users = read_json('users');
    foreach($users as $u){ if($u['email']===$email){ $error="Email already registered."; break; } }
    if(!$error){
      $resume_path = '';
      if(isset($_FILES['resume']) && $_FILES['resume']['tmp_name']){
        $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        $fname = 'resume_'.time().'_'.mt_rand(1000,9999).'.'.preg_replace('/[^a-z0-9]+/i','',$ext);
        $dest = __DIR__."/uploads/$fname";
        @move_uploaded_file($_FILES['resume']['tmp_name'], $dest);
        $resume_path = 'uploads/'.$fname;
      }
      $user = [
        'id' => time().mt_rand(100,999),
        'name'=>$name,
        'email'=>$email,
        'password'=>password_hash($pass, PASSWORD_DEFAULT),
        'role'=>$role,
        'bio'=>$bio,
        'resume'=>$resume_path,
        'created_at'=>date('c')
      ];
      $users[] = $user;
      write_json('users',$users);
      $_SESSION['user']=$user;
      echo "<script>alert('Signup successful!'); window.location='home.php';</script>";
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sign Up</title>
<style>
  body{margin:0;background:#0b1226;color:#e9efff;font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
  .wrap{max-width:520px;margin:40px auto;padding:24px;background:#111a3b;border:1px solid #22306d;border-radius:16px}
  h1{margin:0 0 12px}
  label{display:block;margin:10px 0 6px;color:#a8b4ec}
  input,select,textarea{width:100%;padding:12px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .actions{display:flex;gap:10px;margin-top:14px}
  button,.link{all:unset;cursor:pointer;padding:12px 16px;border-radius:10px;background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700}
  .ghost{background:#0f1531;color:#e9efff;border:1px solid #22306d}
  .err{background:#2a1030;border:1px solid #7e2a4e;padding:10px;border-radius:10px;margin-bottom:10px;color:#ffc7d1}
</style>
</head>
<body>
  <div class="wrap">
    <h1>Create your account</h1>
    <p>Choose a role: <strong>Employer</strong> or <strong>Job Seeker</strong>.</p>
    <?php if($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <label>Name*</label>
      <input name="name" required>
      <label>Email*</label>
      <input type="email" name="email" required>
      <div class="row">
        <div>
          <label>Password*</label>
          <input type="password" name="password" minlength="4" required>
        </div>
        <div>
          <label>Role*</label>
          <select name="role">
            <option value="seeker">Job Seeker</option>
            <option value="employer">Employer</option>
          </select>
        </div>
      </div>
      <label>Short Bio</label>
      <textarea name="bio" rows="3" placeholder="Tell us about yourself or your company..."></textarea>
      <label>Upload Resume (PDF/DOC)</label>
      <input type="file" name="resume" accept=".pdf,.doc,.docx">
      <div class="actions">
        <button type="submit">Sign Up</button>
        <a class="link ghost" href="login.php">Already have an account? Log in</a>
      </div>
    </form>
  </div>
</body>
</html>
