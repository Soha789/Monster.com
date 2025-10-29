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
function write_json($file,$data){ file_put_contents(__DIR__."/data/$file.json", json_encode($data, JSON_PRETTY_PRINT)); }
ensure_dirs();

if(!isset($_SESSION['user'])){ echo "<script>window.location='login.php';</script>"; exit; }
$user = $_SESSION['user'];
if($user['role']!=='employer'){ echo "<script>alert('Employers only.'); window.location='home.php';</script>"; exit; }

$err = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $title = trim($_POST['title'] ?? '');
  $company = trim($_POST['company'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $category = trim($_POST['category'] ?? 'General');
  $type = strtolower(trim($_POST['type'] ?? 'full-time'));
  $salary_min = (int)($_POST['salary_min'] ?? 0);
  $salary_max = (int)($_POST['salary_max'] ?? 0);
  $exp = (int)($_POST['experience'] ?? 0);
  $desc = trim($_POST['description'] ?? '');
  if(!$title || !$company || !$location || !$desc){ $err='Please fill all required fields.'; }
  else{
    $jobs = read_json('jobs');
    $job = [
      'id'=>time().mt_rand(100,999),
      'employer_id'=>$user['id'],
      'title'=>$title, 'company'=>$company, 'location'=>$location,
      'category'=>$category ?: 'General', 'type'=>$type ?: 'full-time',
      'salary_min'=>$salary_min, 'salary_max'=>$salary_max,
      'experience'=>$exp, 'description'=>$desc, 'created_at'=>date('c')
    ];
    array_unshift($jobs,$job);
    write_json('jobs',$jobs);
    echo "<script>alert('Job posted!'); window.location='home.php';</script>"; exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Post a Job</title>
<style>
  body{margin:0;background:#0b1226;color:#e9efff;font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
  .nav{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#0b1226;border-bottom:1px solid #1e2a57}
  a.btn{all:unset;cursor:pointer;padding:10px 14px;border-radius:10px;background:#0f1531;border:1px solid #22306d;margin-left:8px}
  .primary{background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700}
  .wrap{max-width:740px;margin:24px auto;padding:24px;background:#111a3b;border:1px solid #22306d;border-radius:16px}
  label{display:block;margin:10px 0 6px;color:#a8b4ec}
  input,select,textarea{width:100%;padding:12px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .err{background:#2a1030;border:1px solid #7e2a4e;padding:10px;border-radius:10px;margin-bottom:10px;color:#ffc7d1}
  .actions{display:flex;gap:10px;margin-top:14px}
</style>
</head>
<body>
  <div class="nav">
    <div><strong>HireHub</strong> — Post a Job</div>
    <div>
      <a class="btn" href="home.php">Home</a>
      <a class="btn" href="profile.php">Profile</a>
      <a class="btn primary" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="wrap">
    <h2>New Job Listing</h2>
    <?php if($err): ?><div class="err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>
    <form method="post">
      <label>Job Title*</label><input name="title" required placeholder="e.g., Full-Stack PHP Developer">
      <div class="row">
        <div><label>Company*</label><input name="company" required placeholder="Your Company"></div>
        <div><label>Location*</label><input name="location" required placeholder="Riyadh, Remote"></div>
      </div>
      <div class="row">
        <div><label>Category</label><input name="category" placeholder="Engineering, Marketing, Design..."></div>
        <div><label>Type</label>
          <select name="type">
            <option value="full-time">Full-Time</option>
            <option value="part-time">Part-Time</option>
            <option value="contract">Contract</option>
            <option value="remote">Remote</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div><label>Salary Min</label><input type="number" name="salary_min" placeholder="e.g., 5000"></div>
        <div><label>Salary Max</label><input type="number" name="salary_max" placeholder="e.g., 12000"></div>
      </div>
      <label>Experience (years)</label><input type="number" name="experience" placeholder="e.g., 2">
      <label>Description*</label><textarea name="description" rows="6" required placeholder="Responsibilities, requirements, benefits..."></textarea>
      <div class="actions">
        <button type="submit" class="primary" style="all:unset;cursor:pointer;padding:12px 16px;border-radius:10px;background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700">Post Job</button>
        <a class="btn" href="home.php">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
