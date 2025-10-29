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

if(!isset($_SESSION['user'])){
  echo "<script>window.location='login.php';</script>"; exit;
}
$user = $_SESSION['user'];

$jobs = read_json('jobs');
$users = read_json('users');
$applications = read_json('applications');
$messages = read_json('messages');

/* ---------- Apply ---------- */
if(isset($_POST['apply_job_id'])){
  $jid = $_POST['apply_job_id'];
  $cover = trim($_POST['cover'] ?? '');
  // prevent duplicate application
  foreach($applications as $a){
    if($a['job_id']===$jid && $a['seeker_id']===$user['id']){
      echo "<script>alert('You already applied to this job.'); window.location='home.php';</script>"; exit;
    }
  }
  $applications[] = [
    'id'=>time().mt_rand(100,999),
    'job_id'=>$jid,
    'seeker_id'=>$user['id'],
    'status'=>'Applied',
    'cover_letter'=>$cover,
    'created_at'=>date('c')
  ];
  write_json('applications',$applications);
  echo "<script>alert('Application submitted!'); window.location='home.php';</script>"; exit;
}

/* ---------- Shortlist (employer) ---------- */
if(isset($_POST['shortlist_app_id']) && $user['role']==='employer'){
  $aid = $_POST['shortlist_app_id'];
  foreach($applications as &$a){
    if($a['id']===$aid){ $a['status']='Shortlisted'; break; }
  }
  unset($a);
  write_json('applications',$applications);
  echo "<script>alert('Applicant shortlisted.'); window.location='home.php';</script>"; exit;
}

/* ---------- Send Message ---------- */
if(isset($_POST['msg_job_id'], $_POST['to_id'], $_POST['message_text'])){
  $messages[] = [
    'id'=>time().mt_rand(100,999),
    'job_id'=>$_POST['msg_job_id'],
    'from_id'=>$user['id'],
    'to_id'=>$_POST['to_id'],
    'text'=>trim($_POST['message_text']),
    'timestamp'=>date('c')
  ];
  write_json('messages',$messages);
  echo "<script>alert('Message sent!'); window.location='home.php';</script>"; exit;
}

/* ---------- Filters ---------- */
$q = strtolower(trim($_GET['q'] ?? ''));
$cat = strtolower(trim($_GET['category'] ?? ''));
$loc = strtolower(trim($_GET['location'] ?? ''));
$type= strtolower(trim($_GET['type'] ?? ''));
$min = isset($_GET['min'])? (int)$_GET['min'] : null;
$max = isset($_GET['max'])? (int)$_GET['max'] : null;

function job_matches($job,$q,$cat,$loc,$type,$min,$max){
  $hay = strtolower($job['title'].' '.$job['company'].' '.$job['location'].' '.$job['category'].' '.$job['type'].' '.$job['description']);
  if($q && strpos($hay,$q)===false) return false;
  if($cat && strtolower($job['category'])!=$cat) return false;
  if($loc && stripos($job['location'],$loc)===false) return false;
  if($type && strtolower($job['type'])!=$type) return false;
  if($min!==null && (int)$job['salary_min'] < $min) return false;
  if($max!==null && (int)$job['salary_max'] > 0 && (int)$job['salary_max'] > 0 && $max < (int)$job['salary_min']) return false;
  return true;
}
$filtered = array_values(array_filter($jobs, function($j) use($q,$cat,$loc,$type,$min,$max){ return job_matches($j,$q,$cat,$loc,$type,$min,$max); }));

/* ---------- Derived ---------- */
$cats = array_values(array_unique(array_map(fn($j)=>$j['category']??'General',$jobs)));
sort($cats);
function user_by_id($users,$id){ foreach($users as $u){ if($u['id']===$id) return $u; } return null; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Jobs — HireHub</title>
<style>
  :root{--bg:#0a0f1e; --card:#121934; --accent:#6c8cff; --accent2:#00ffa8; --text:#e8ecff; --muted:#9aa3c5; --chip:#1a2350}
  body{margin:0;background:#070b15;color:var(--text);font-family:Inter,system-ui,Segoe UI,Roboto,Arial}
  .nav{display:flex;justify-content:space-between;align-items:center;padding:14px 18px;background:#0b1226;border-bottom:1px solid #1e2a57;position:sticky;top:0}
  a.btn{all:unset;cursor:pointer;padding:10px 14px;border-radius:10px;background:#0f1531;border:1px solid #22306d;margin-left:8px}
  .primary{background:linear-gradient(135deg,var(--accent),#9eaefc);color:#0a0f1e;font-weight:700}
  .layout{display:grid;grid-template-columns:300px 1fr;gap:18px;max-width:1200px;margin:18px auto;padding:0 18px}
  .card{background:var(--card);border:1px solid #1e2a57;border-radius:14px;padding:16px}
  .filters label{display:block;font-size:13px;color:#a8b4ec;margin:8px 0 6px}
  .filters input,.filters select{width:100%;padding:10px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff}
  .job{display:grid;grid-template-columns:1fr auto;gap:10px;padding:14px;border:1px solid #22306d;border-radius:12px;background:#0f1531;margin-bottom:12px}
  .job h3{margin:0 0 6px}
  .muted{color:var(--muted)}
  .chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
  .chip{background:var(--chip);border:1px solid #27357a;color:#c7d2ff;padding:6px 10px;border-radius:999px;font-size:12px}
  .apply, .msg, .view{all:unset;cursor:pointer;padding:8px 12px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#9eaefc);color:#0a0f1e;font-weight:700}
  .ghost{background:#0f1531;color:#e9efff;border:1px solid #22306d}
  .section-title{margin:6px 0 10px}
  .mini{font-size:12px;color:#a8b4ec}
  .stack{display:flex;flex-direction:column;gap:10px}
  .empty{padding:16px;border:1px dashed #2b3a80;border-radius:12px;color:#97a6f6}
  @media (max-width:920px){ .layout{grid-template-columns:1fr} }
</style>
</head>
<body>
  <div class="nav">
    <div><strong>HireHub</strong> — Welcome, <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['role']); ?>)</div>
    <div>
      <a class="btn" href="index.php">About</a>
      <a class="btn" href="dashboard.php">Dashboard</a>
      <?php if($user['role']==='employer'): ?><a class="btn" href="add_jobs.php">Post Job</a><?php endif; ?>
      <a class="btn" href="profile.php">Profile</a>
      <a class="btn primary" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="layout">
    <div class="card filters">
      <h3 class="section-title">Search & Filters</h3>
      <form method="get">
        <label>Keyword</label><input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="title, company, keyword...">
        <label>Category</label>
        <select name="category">
          <option value="">All</option>
          <?php foreach($cats as $c): ?>
            <option value="<?php echo htmlspecialchars(strtolower($c)); ?>" <?php if($cat===strtolower($c)) echo 'selected'; ?>><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
        <label>Location</label><input name="location" value="<?php echo htmlspecialchars($loc); ?>" placeholder="e.g., Riyadh, Remote">
        <label>Job Type</label>
        <select name="type">
          <option value="">Any</option>
          <option value="full-time" <?php if($type==='full-time') echo 'selected'; ?>>Full-Time</option>
          <option value="part-time" <?php if($type==='part-time') echo 'selected'; ?>>Part-Time</option>
          <option value="contract" <?php if($type==='contract') echo 'selected'; ?>>Contract</option>
          <option value="remote" <?php if($type==='remote') echo 'selected'; ?>>Remote</option>
        </select>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div><label>Min Salary</label><input type="number" name="min" value="<?php echo htmlspecialchars($_GET['min'] ?? ''); ?>"></div>
          <div><label>Max Salary</label><input type="number" name="max" value="<?php echo htmlspecialchars($_GET['max'] ?? ''); ?>"></div>
        </div>
        <div style="margin-top:10px; display:flex; gap:8px;">
          <button class="btn primary" type="submit" style="all:unset;cursor:pointer;padding:10px 14px;border-radius:10px;background:linear-gradient(135deg,#6c8cff,#9eaefc);color:#0a0f1e;font-weight:700">Apply Filters</button>
          <a class="btn" href="home.php">Reset</a>
        </div>
      </form>
    </div>

    <div class="stack">
      <div class="card">
        <h3 class="section-title">Featured Jobs</h3>
        <?php if(empty($filtered)): ?>
          <div class="empty">No jobs match your search yet. Try clearing filters.</div>
        <?php endif; ?>
        <?php foreach($filtered as $j): 
          $emp = user_by_id($users,$j['employer_id']);
          $alreadyApplied = false;
          foreach($applications as $a){ if($a['job_id']===$j['id'] && $a['seeker_id']===$user['id']) {$alreadyApplied=true; break; } }
        ?>
          <div class="job">
            <div>
              <h3><?php echo htmlspecialchars($j['title']); ?></h3>
              <div class="muted"><?php echo htmlspecialchars($j['company']); ?> — <?php echo htmlspecialchars($j['location']); ?> • <span class="mini"><?php echo date('M j, Y', strtotime($j['created_at'])); ?></span></div>
              <div class="chips">
                <span class="chip"><?php echo htmlspecialchars($j['category']); ?></span>
                <span class="chip"><?php echo htmlspecialchars(ucfirst($j['type'])); ?></span>
                <span class="chip">Salary: <?php echo htmlspecialchars($j['salary_min']); ?> - <?php echo htmlspecialchars($j['salary_max']); ?></span>
                <span class="chip">Exp: <?php echo htmlspecialchars($j['experience']); ?> yrs</span>
              </div>
              <p class="muted" style="margin-top:8px"><?php echo nl2br(htmlspecialchars(substr($j['description'],0,220))); ?>...</p>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
              <?php if($user['role']==='seeker'): ?>
                <form method="post" style="margin:0">
                  <input type="hidden" name="apply_job_id" value="<?php echo htmlspecialchars($j['id']); ?>">
                  <textarea name="cover" rows="3" placeholder="Short cover letter..." style="width:260px;padding:8px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff"></textarea>
                  <div style="display:flex;gap:6px;margin-top:6px">
                    <button class="apply" <?php echo $alreadyApplied?'disabled':''; ?> type="submit"><?php echo $alreadyApplied?'Applied':'Apply'; ?></button>
                    <?php if($emp): ?>
                    <button type="button" class="apply ghost" onclick="openMsg('<?php echo $j['id']; ?>','<?php echo $emp['id']; ?>','<?php echo htmlspecialchars(addslashes($j['title'])); ?>')">Message Employer</button>
                    <?php endif; ?>
                  </div>
                </form>
                <?php if($emp && !empty($emp['resume'])): ?>
                  <a class="view" href="<?php echo htmlspecialchars($user['resume'] ?? '#'); ?>" target="_blank" style="<?php echo empty($user['resume'])?'pointer-events:none;opacity:.5':''; ?>">View My Resume</a>
                <?php endif; ?>
              <?php else: ?>
                <div class="mini">Posted by: <?php echo htmlspecialchars($emp['name'] ?? 'Unknown'); ?></div>
                <a class="apply ghost" href="add_jobs.php">Edit / Add Jobs</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if($user['role']==='employer'): ?>
      <div class="card">
        <h3 class="section-title">Your jobs & applicants</h3>
        <?php
          $myJobs = array_values(array_filter($jobs, fn($j)=>$j['employer_id']===$user['id']));
          if(!$myJobs){ echo '<div class="empty">No jobs yet. Post one!</div>'; }
          foreach($myJobs as $mj){
            echo '<div class="job"><div><strong>'.htmlspecialchars($mj['title']).'</strong><div class="muted">'.htmlspecialchars($mj['company']).' — '.htmlspecialchars($mj['location']).'</div></div><div class="mini">Job ID: '.htmlspecialchars($mj['id']).'</div></div>';
            echo '<div style="padding:8px 0 16px">';
            $apps = array_values(array_filter($applications, fn($a)=>$a['job_id']===$mj['id']));
            if(!$apps){ echo '<div class="empty">No applicants yet.</div>'; }
            foreach($apps as $a){
              $seek = user_by_id($users,$a['seeker_id']);
              echo '<div class="job" style="margin-top:8px">';
              echo '<div><strong>'.htmlspecialchars($seek['name']??'Unknown').'</strong> <span class="muted">('.htmlspecialchars($seek['email']??'').')</span><div class="chips"><span class="chip">Status: '.htmlspecialchars($a['status']).'</span></div><p class="muted">'.nl2br(htmlspecialchars($a['cover_letter'])).'</p>';
              if(!empty($seek['resume'])){ echo '<a class="view" target="_blank" href="'.htmlspecialchars($seek['resume']).'">View Resume</a>'; }
              echo '</div>';
              echo '<div style="display:flex;flex-direction:column;gap:8px">';
              echo '<form method="post"><input type="hidden" name="shortlist_app_id" value="'.htmlspecialchars($a['id']).'"><button class="apply" type="submit">Shortlist</button></form>';
              echo '<button class="apply ghost" onclick="openMsg(\''.$mj['id'].'\', \''.$seek['id'].'\', \''.htmlspecialchars(addslashes($mj['title'])).'\')">Message</button>';
              echo '</div></div>';
            }
            echo '</div>';
          }
        ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Message Modal -->
  <div id="msgModal" style="position:fixed;inset:0;display:none;place-items:center;background:rgba(0,0,0,.6)">
    <div style="width:min(560px,92vw);background:#111a3b;border:1px solid #22306d;border-radius:14px;padding:16px">
      <h3>Send Message</h3>
      <form method="post" id="msgForm">
        <input type="hidden" name="msg_job_id" id="msg_job_id">
        <input type="hidden" name="to_id" id="to_id">
        <label style="display:block;color:#a8b4ec;margin:8px 0 6px">Message</label>
        <textarea name="message_text" rows="5" required style="width:100%;padding:10px;border-radius:10px;border:1px solid #2a3b7e;background:#0f1531;color:#e9efff"></textarea>
        <div style="display:flex;gap:8px;margin-top:10px;justify-content:flex-end">
          <button type="button" class="apply ghost" onclick="closeMsg()">Cancel</button>
          <button type="submit" class="apply">Send</button>
        </div>
      </form>
    </div>
  </div>

<script>
  function openMsg(jobId, toId, title){
    document.getElementById('msg_job_id').value = jobId;
    document.getElementById('to_id').value = toId;
    document.getElementById('msgModal').style.display='grid';
  }
  function closeMsg(){ document.getElementById('msgModal').style.display='none'; }
</script>
</body>
</html>
