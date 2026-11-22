<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require_login();                       // blocks anyone who is not signed in

$user    = $_SESSION['user'];
$since   = date('d M Y', (int)strtotime((string)$user['created_at']));
$loginAt = date('d M Y, h:i A', (int)($_SESSION['login_at'] ?? time()));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard · Secure User Authentication</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="style.css">
</head>
<body class="dash-page">
<div class="shell">
  <header class="glass-nav">
    <a class="brand" href="../index.php">Secure Auth</a>
    <nav aria-label="Other applications">
      <a href="../app1-session/">Session</a><a href="../app2-clock/">Clock</a><a href="../app3-access/">Access</a>
    </nav>
    <form method="post" action="logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn-pill" type="submit">Log out</button>
    </form>
  </header>

  <section class="glass hero">
    <span class="status"><i></i> Authenticated</span>
    <h1>Welcome, <?= h($user['username']) ?>!</h1>
    <p class="sub">Login successful. Your credentials matched the database and your session is active.</p>
  </section>

  <div class="grid">
    <div class="glass stat"><small>Login status</small><strong>Signed in</strong></div>
    <div class="glass stat"><small>Signed in at</small><strong><?= h($loginAt) ?></strong></div>
    <div class="glass stat"><small>Member since</small><strong><?= h($since) ?></strong></div>
  </div>

  <section class="glass checks">
    <h2>How this sign-in was protected</h2>
    <ul>
      <li>Your password is stored as a bcrypt hash and compared with password_verify().</li>
      <li>The user lookup used a PDO prepared statement.</li>
      <li>A new session ID was issued the moment you signed in.</li>
    </ul>
    <form method="post" action="logout.php">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <button class="btn-primary inline" type="submit">Log out</button>
    </form>
  </section>
</div>
</body>
</html>
