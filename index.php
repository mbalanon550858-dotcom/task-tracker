<?php
require_once 'auth.php';
require_once 'db.php';
requireGuest();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password']   ?? '');
    if (empty($identifier) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            setFlash('success', 'Welcome back, ' . $user['username'] . '! 👋');
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — TaskTracker</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-brand">
    <div class="brand-logo">Task<span>Tracker</span></div>
    <div class="brand-tagline">Get things <em>done</em>, stay in control.</div>
    <p class="brand-sub">A clean, simple system to manage your tasks and boost daily productivity.</p>
    <div class="brand-features">
      <div class="brand-feature"><div class="icon">✅</div><span>Track tasks with priorities & deadlines</span></div>
      <div class="brand-feature"><div class="icon">📊</div><span>Dashboard overview of your progress</span></div>
      <div class="brand-feature"><div class="icon">🔒</div><span>Private & secure — your tasks, only yours</span></div>
    </div>
  </div>
  <div class="auth-form-side">
    <div class="auth-card">
      <h2>Welcome back</h2>
      <p class="subtitle">Sign in to your account to continue.</p>
      <?php if ($error): ?><div class="alert alert-error">⚠️ <?= sanitize($error) ?></div><?php endif; ?>
      <form method="POST" action="index.php">
        <div class="form-group"><label for="identifier">Username or Email</label><input type="text" id="identifier" name="identifier" placeholder="e.g. john_doe" value="<?= sanitize($_POST['identifier'] ?? '') ?>" required></div>
        <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" placeholder="••••••••" required></div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">Sign In →</button>
      </form>
      <p style="margin-top:24px;text-align:center;color:var(--muted);font-size:.9rem;">Don't have an account? <a href="register.php">Create one free</a></p>
    </div>
  </div>
</div>
<script src="app.js"></script>
</body>
</html>
