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
ensure_dirs();

$logged_in = isset($_SESSION['user']);
$user = $logged_in ? $_SESSION['user'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Monster-style Job Portal</title>
<style>
  :root{
    --bg:#0a0f1e; --card:#121934; --accent:#6c8cff; --accent2:#00ffa8;
    --text:#e8ecff; --muted:#9aa3c5; --chip:#1a2350; --glow:0 10px 30px rgba(108,140,255,0.35);
  }
  *{box-sizing:border-box}
  body{margin:0; font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:radial-gradient(1200px 800px at 10% -10%, #1b2452 0%, #0a0f1e 45%, #070b15 100%); color:var(--text)}
  .nav{display:flex; align-items:center; justify-content:space-between; padding:18px 24px; position:sticky; top:0; background:linear-gradient(180deg, rgba(7,11,21,.95), rgba(7,11,21,.75)); backdrop-filter: blur(10px); border-bottom:1px solid #1e2a57}
  .brand{display:flex; gap:10px; align-items:center}
  .logo{width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--accent),#9eaefc); box-shadow:var(--glow)}
  .brand h1{margin:0; font-size:18px; letter-spacing:.5px}
  .actions a,.actions button{all:unset; cursor:pointer; padding:10px 16px; border-radius:10px; background:#0f1531; border:1px solid #22306d; color:var(--text); transition:.2s; margin-left:10px}
  .primary{background:linear-gradient(135deg,var(--accent),#9eaefc); color:#0a0f1e; font-weight:600; box-shadow:var(--glow)}
  .actions a:hover,.actions button:hover{transform: translateY(-1px); box-shadow:0 8px 18px rgba(0,0,0,.25)}
  .hero{display:grid; grid-template-columns:1.2fr .8fr; gap:28px; padding:48px 24px; max-width:1100px; margin:0 auto}
  .card{background:var(--card); border:1px solid #1e2a57; border-radius:16px; padding:22px}
  h2{margin:0 0 10px}
  p{color:var(--muted); line-height:1.6}
  .grid{display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-top:18px}
  .chip{background:var(--chip); border:1px solid #27357a; padding:10px 12px; border-radius:999px; font-size:13px; color:#b9c4ff}
  .cta{display:flex; gap:12px; margin-top:18px}
  .cta .btn{all:unset; cursor:pointer; padding:12px 16px; border-radius:12px; background:linear-gradient(135deg,var(--accent),#9eaefc); color:#0a0f1e; font-weight:700; box-shadow:var(--glow)}
  .cta .ghost{background:#0f1531; color:var(--text); border:1px solid #22306d}
  .features{display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:18px}
  .feat{background:#0f1531; border:1px dashed #2b3a80; padding:16px; border-radius:12px}
  .footer{padding:22px; text-align:center; color:#8ea0e6}
  .mono{font-family: ui-monospace, Menlo, Consolas, monospace; color:#a5b2ff}
</style>
</head>
<body>
  <div class="nav">
    <div class="brand">
      <div class="logo"></div>
      <h1>HireHub — Monster-style Portal</h1>
    </div>
    <div class="actions">
      <?php if($logged_in): ?>
        <a href="home.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_jobs.php">Post a Job</a>
        <a class="primary" href="logout.php">Logout</a>
      <?php else: ?>
        <a href="signup.php">Sign Up</a>
        <a class="primary" href="login.php">Log In</a>
      <?php endif; ?>
    </div>
  </div>

  <section class="hero">
    <div class="card">
      <h2>Find your next opportunity</h2>
      <p>Inspired by <span class="mono">Monster.com</span>, this portal lets job seekers discover curated roles, apply with a resume, and track progress — while employers post openings, review applicants, and message candidates directly.</p>
      <div class="grid">
        <div class="chip">Featured jobs</div>
        <div class="chip">Trending categories</div>
        <div class="chip">Remote & On-site</div>
        <div class="chip">Resume uploads</div>
      </div>
      <div class="cta">
        <button class="btn" onclick="window.location='signup.php'">Get Started — Free</button>
        <button class="btn ghost" onclick="window.location='home.php'">Browse Jobs</button>
      </div>
      <div class="features">
        <div class="feat"><strong>Employers</strong><br/>Post jobs, view applicants, shortlist, and reply with messages.</div>
        <div class="feat"><strong>Job Seekers</strong><br/>Upload a resume, apply with one click, track all applications.</div>
        <div class="feat"><strong>Messaging</strong><br/>Talk securely via job-linked threads.</div>
      </div>
    </div>
    <div class="card">
      <h2>Why Monster-style?</h2>
      <p>Monster popularized fast matching, discoverability, and clear job taxonomy. We mirror that vibe with modern visuals, smart chips, and a smooth, mobile-ready layout.</p>
      <ul>
        <li>Fast signup & login</li>
        <li>Simple JSON storage (no DB)</li>
        <li>Fully responsive</li>
        <li>JS-based redirects</li>
      </ul>
    </div>
  </section>

  <div class="footer">© <?php echo date('Y'); ?> HireHub • Crafted with ❤️</div>
</body>
</html>
