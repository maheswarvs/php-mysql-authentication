<?php
declare(strict_types=1);
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';

if (is_logged_in()) { redirect('dashboard.php'); }

// Valid bcrypt hash checked when the username doesn't exist, so response time doesn't reveal real usernames.
const DUMMY_HASH = '$2y$12$NQhy3hgWlQ.RWQIwDUf1reBlRnSicZugq1dkdq7TRrhsbskoUcgga';

$errors   = [];
$username = '';
$notice   = ($_GET['msg'] ?? '') === 'logged_out' ? 'You have been logged out successfully.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!csrf_ok($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif (($_SESSION['lock_until'] ?? 0) > time()) {
        $errors['form'] = 'Too many failed attempts. Try again in ' . ($_SESSION['lock_until'] - time()) . ' seconds.';
    } else {
        if (!preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
            $errors['username'] = 'Enter 3–32 characters: letters, numbers, dots, hyphens or underscores.';
        }
        if ($password === '') {
            $errors['password'] = 'Enter your password.';
        } elseif (strlen($password) > 128) {
            $errors['password'] = 'Password is too long.';
        }

        if (!$errors) {
            try {
                $stmt = db()->prepare('SELECT id, username, password, created_at FROM users WHERE username = :u LIMIT 1');
                $stmt->execute([':u' => $username]);
                $user  = $stmt->fetch();
                $valid = password_verify($password, $user['password'] ?? DUMMY_HASH) && $user !== false;

                if ($valid) {
                    session_regenerate_id(true);                       // new ID after authentication
                    $_SESSION['user'] = ['id' => (int)$user['id'], 'username' => $user['username'], 'created_at' => $user['created_at']];
                    $_SESSION['login_at'] = time();
                    $_SESSION['csrf'] = bin2hex(random_bytes(32));
                    unset($_SESSION['fails'], $_SESSION['lock_until']);
                    redirect('dashboard.php');
                }

                $errors['form'] = 'Invalid username or password.';     // same message for every failure
                $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
                if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 60; $_SESSION['fails'] = 0; }
            } catch (PDOException $e) {
                error_log('app4 login DB error: ' . $e->getMessage());
                $errors['form'] = 'The sign-in service is unavailable. Please try again shortly.';
            }
        }
    }
}
$showDemo = filter_var(getenv('SHOW_DEMO_HINT') ?: '', FILTER_VALIDATE_BOOLEAN);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · Secure User Authentication</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="frame">
  <aside class="art" aria-hidden="true">
    <svg viewBox="0 0 800 700" preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a78bfa"/><stop offset="1" stop-color="#3b82f6"/></linearGradient>
        <polygon id="t" points="0,-330 290,170 -290,170" fill="none" vector-effect="non-scaling-stroke"/>
      </defs>
      <g transform="translate(400 380)" stroke="url(#g)" stroke-width="2">
        <use href="#t"/><use href="#t" transform="scale(.88) rotate(6)"/><use href="#t" transform="scale(.77) rotate(12)"/>
        <use href="#t" transform="scale(.67) rotate(18)"/><use href="#t" transform="scale(.58) rotate(24)"/><use href="#t" transform="scale(.5) rotate(30)"/>
        <use href="#t" transform="scale(.42) rotate(36)"/><use href="#t" transform="scale(.35) rotate(42)"/><use href="#t" transform="scale(.29) rotate(48)"/>
      </g>
    </svg>
    <div class="art-copy"><p>Checked on the server</p><h2>Your password is never stored, only verified.</h2></div>
  </aside>

  <section class="pane">
    <header class="glass-nav">
      <a class="brand" href="../index.php">Secure Auth</a>
      <nav aria-label="Other applications">
        <a href="../app1-session/">Session</a><a href="../app2-clock/">Clock</a><a href="../app3-access/">Access</a>
      </nav>
      <a class="btn-pill" href="../index.php">Back to suite</a>
    </header>

    <div class="form-wrap">
      <h1>Nice to see you!</h1>
      <p class="sub">Enter your username and password to sign in.</p>

      <?php if ($notice): ?><div class="alert ok" role="status"><?= h($notice) ?></div><?php endif; ?>
      <?php if (isset($errors['form'])): ?><div class="alert bad" role="alert"><?= h($errors['form']) ?></div><?php endif; ?>

      <form id="login" method="post" action="login.php" novalidate>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label for="username">Username</label>
        <input id="username" name="username" value="<?= h($username) ?>" placeholder="Your username" autocomplete="username"
               maxlength="32" required autofocus <?= isset($errors['username']) ? 'aria-invalid="true"' : '' ?>>
        <span class="err" id="username-err"><?= h($errors['username'] ?? '') ?></span>

        <label for="password">Password</label>
        <div class="field">
          <input id="password" name="password" type="password" placeholder="Your password" autocomplete="current-password"
                 maxlength="128" required <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
          <button type="button" id="toggle" class="reveal" aria-pressed="false" aria-label="Show password">Show</button>
        </div>
        <span class="err" id="password-err"><?= h($errors['password'] ?? '') ?></span>

        <button class="btn-primary" type="submit">Sign in</button>
      </form>

      <?php if ($showDemo): ?><p class="demo">Demo account: <b>demo_user</b> / <b>Demo@2026!</b></p><?php endif; ?>
    </div>
  </section>
</div>
<script>
const f = document.getElementById('login'), pw = document.getElementById('password'), t = document.getElementById('toggle');
t.addEventListener('click', () => {
  const show = pw.type === 'password';
  pw.type = show ? 'text' : 'password';
  t.textContent = show ? 'Hide' : 'Show';
  t.setAttribute('aria-pressed', String(show));
  t.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
f.addEventListener('submit', e => {
  let bad = false;
  const check = (id, msg) => {
    document.getElementById(id + '-err').textContent = msg;
    f.elements[id].setAttribute('aria-invalid', msg ? 'true' : 'false');
    if (msg) bad = true;
  };
  check('username', /^[A-Za-z0-9_.-]{3,32}$/.test(f.elements.username.value.trim()) ? '' : 'Enter 3–32 characters: letters, numbers, dots, hyphens or underscores.');
  check('password', pw.value ? '' : 'Enter your password.');
  if (bad) e.preventDefault();
});
</script>
</body>
</html>
