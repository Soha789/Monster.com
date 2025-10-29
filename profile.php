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

if(!isset($_SESSION['user'])){ echo "<script>window.location='login.php';</script>"; exit; }
$user = $_SESSION['user'];

$users = read_json('users');
$messages = read_json('messages');

/* ---------- Update Profile ---------- */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
  $name = trim($_POST['name'] ?? '');
  $bio = trim($_POST['bio'] ?? '');
  $resume_path = $user['resume'] ?? '';
  if(isset($_FILES['resume']) && $_FILES['resume']['tmp_name']){
    $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
    $fname = 'resume_'.time().'_'.mt_rand(1000,9999).'.'.preg_replace('/[^a-z0-9]+/i','',$ext);
    $dest = __DIR__."/uploads/$fname";
    @move_uploaded_file($_FILES['resume']['tmp_name'], $dest);
    $resume_path = 'uploads/'.$fname;
  }
  for($i=0;$i<count($users);$i++){
    if($users[$i]['id']===$user['id']){
      $users[$i]['name']=$name ?: $users[$i]['name'];
      $users[$i]['bio']=$bio;
      $users[$i]['resume']=$resume_path;
      $user = $users[$i];
      $_SESSION['user']=$user;
      break;
    }
  }
  write_json('users',$users);
  echo "<script>alert('Profile updated'); window.location='profile.php';</script>"; exit;
}

/* ---------- My Messages ---------- */
function counterpart_name($users,$id){ foreach($users as $u){ if($u['id']===$id) return $u['name']; } return 'User'; }
$inbox = array_values(array_filter($messages, fn($m)=>$m['to_id']===$user['id'] || $m['from_id']===$user['id']));
usort($inbox, fn($a,$b)=>strcmp($b['timestamp'],$a['timestamp']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profile</title>
<style>
  body{margin:0;background:#070b15;color:#e9efff;font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
  .nav{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#0b1226;border-bottom:1px solid #1e2a57}
  a.btn{all:unset;cursor:pointer;padding:10px 14px;border-radius:10px;background:#0f1531;border:1px solid #22306d;margin-left:8px}
  .primary{background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700}
  .wrap{max-width:1100px;margin:18px auto;padding:0 18px;display:grid;grid-template-columns:1fr 1fr;gap:18px}
  .card{background:#121934;border:1px solid #1e2a57;border-radius:14px;padding:16px}
  label{display:block;margin:10px 0 6px;color:#a8b4ec}
  input,textarea{width:100%;padding:12px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff}
  .chip{background:#1a2350;border:1px solid #27357a;color:#c7d2ff;padding:6px 10px;border-radius:999px;font-size:12px}
  .msg{padding:12px;border:1px solid #22306d;background:#0f1531;border-radius:12px;margin-top:10px}
  .muted{color:#9aa3c5}
  @media (max-width:920px){ .wrap{grid-template-columns:1fr} }
</style>
</head>
<body>
  <div class="nav">
    <div><strong>HireHub</strong> — Profile</div>
    <div>
      <a class="btn" href="home.php">Home</a>
      <a class="btn" href="dashboard.php">Dashboard</a>
      <a class="btn primary" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="wrap">
    <div class="card">
      <h3>Your Profile</h3>
      <p><span class="chip"><?php echo htmlspecialchars($user['role']); ?></span></p>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        <label>Name</label><input name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
        <label>Bio</label><textarea name="bio" rows="5" placeholder="Tell others about you..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
        <label>Resume (upload to replace)</label><input type="file" name="resume" accept=".pdf,.doc,.docx">
        <?php if(!empty($user['resume'])): ?>
          <p class="muted">Current resume: <a class="primary" style="padding:4px 8px;border-radius:8px;display:inline-block" target="_blank" href="<?php echo htmlspecialchars($user['resume']); ?>">View</a></p>
        <?php endif; ?>
        <div style="display:flex;gap:10px;margin-top:10px">
          <button type="submit" class="primary" style="all:unset;cursor:pointer;padding:12px 16px;border-radius:10px;background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700">Save Changes</button>
          <a class="btn" href="home.php">Back</a>
        </div>
      </form>
    </div>

    <div class="card">
      <h3>Messages</h3>
      <?php if(!$inbox){ echo '<div class="msg">No messages yet.</div>'; } ?>
      <?php foreach($inbox as $m): 
        $from = counterpart_name($users,$m['from_id']);
        $to   = counterpart_name($users,$m['to_id']);
      ?>
        <div class="msg">
          <div><strong><?php echo htmlspecialchars($from); ?></strong> → <?php echo htmlspecialchars($to); ?> <span class="muted">• <?php echo date('M j, Y H:i', strtotime($m['timestamp'])); ?></span></div>
          <div class="muted" style="margin-top:6px"><?php echo nl2br(htmlspecialchars($m['text'])); ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
