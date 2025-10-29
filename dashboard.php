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

if(!isset($_SESSION['user'])){ echo "<script>window.location='login.php';</script>"; exit; }
$user = $_SESSION['user'];

$jobs = read_json('jobs');
$applications = read_json('applications');
$myApps = array_values(array_filter($applications, fn($a)=>$a['seeker_id']===$user['id']));
$earnings = count($myApps) * 25; // simple gamified points for applications

$myJobs = array_values(array_filter($jobs, fn($j)=>$j['employer_id']===$user['id']));
$received = 0;
foreach($applications as $a){
  foreach($myJobs as $mj){ if($mj['id']===$a['job_id']) { $received++; break; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard</title>
<style>
  body{margin:0;background:#070b15;color:#e9efff;font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
  .nav{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#0b1226;border-bottom:1px solid #1e2a57}
  a.btn{all:unset;cursor:pointer;padding:10px 14px;border-radius:10px;background:#0f1531;border:1px solid #22306d;margin-left:8px}
  .primary{background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700}
  .wrap{max-width:1100px;margin:18px auto;padding:0 18px;display:grid;grid-template-columns:1fr 1fr;gap:18px}
  .card{background:#121934;border:1px solid #1e2a57;border-radius:14px;padding:16px}
  .stat{display:flex;justify-content:space-between;align-items:center;background:#0f1531;border:1px solid #22306d;border-radius:12px;padding:12px;margin-top:10px}
  .list .item{padding:12px;border:1px solid #22306d;background:#0f1531;border-radius:12px;margin-top:10px}
  .muted{color:#9aa3c5}
  @media (max-width:920px){ .wrap{grid-template-columns:1fr} }
</style>
</head>
<body>
  <div class="nav">
    <div><strong>HireHub</strong> — Dashboard</div>
    <div>
      <a class="btn" href="home.php">Home</a>
      <?php if($user['role']==='employer'): ?><a class="btn" href="add_jobs.php">Post Job</a><?php endif; ?>
      <a class="btn" href="profile.php">Profile</a>
      <a class="btn primary" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="wrap">
    <div class="card">
      <h3>Your Stats</h3>
      <?php if($user['role']==='seeker'): ?>
        <div class="stat"><div>Total Applications</div><strong><?php echo count($myApps); ?></strong></div>
        <div class="stat"><div>Earnings (Points)</div><strong><?php echo $earnings; ?></strong></div>
      <?php else: ?>
        <div class="stat"><div>Jobs Posted</div><strong><?php echo count($myJobs); ?></strong></div>
        <div class="stat"><div>Applications Received</div><strong><?php echo $received; ?></strong></div>
      <?php endif; ?>
    </div>

    <div class="card list">
      <?php if($user['role']==='seeker'): ?>
        <h3>My Applications</h3>
        <?php if(!$myApps){ echo '<div class="item">No applications yet.</div>'; } ?>
        <?php foreach($myApps as $a){
          foreach($jobs as $j){ if($j['id']===$a['job_id']){ $job=$j; break; } }
          if(!isset($job)) continue;
          echo '<div class="item"><strong>'.htmlspecialchars($job['title']).'</strong> — '.htmlspecialchars($job['company']).' <span class="muted">('.htmlspecialchars($job['location']).')</span><br/><span class="muted">Status: '.htmlspecialchars($a['status']).' • '.date('M j, Y', strtotime($a['created_at'])).'</span></div>';
        } ?>
      <?php else: ?>
        <h3>Recent Applicants</h3>
        <?php
          $recent = [];
          foreach($applications as $a){
            foreach($myJobs as $mj){ if($mj['id']===$a['job_id']) { $recent[] = [$a,$mj]; } }
          }
          if(!$recent){ echo '<div class="item">No applicants yet.</div>'; }
          foreach($recent as [$a,$mj]){
            echo '<div class="item"><strong>'.htmlspecialchars($mj['title']).'</strong> — Application <span class="muted">'.date('M j, Y', strtotime($a['created_at'])).'</span><br/>Status: '.htmlspecialchars($a['status']).'</div>';
          }
        ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
