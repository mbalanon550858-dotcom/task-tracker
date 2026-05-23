<?php
// ============================================================
// register.php — Registration Page
// ============================================================
require_once 'auth.php';
require_once 'db.php';
requireGuest();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $confirm   = trim($_POST['confirm']   ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $errors[] = 'All fields are required.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Username must be 3-30 characters (letters, numbers, underscore only).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check duplicates
        $stmt = mysqli_prepare($conn,
            "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Username or email is already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = mysqli_prepare($conn,
                "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, 'sss', $username, $email, $hash);

            if (mysqli_stmt_execute($ins)) {
                setFlash('success', 'Account created! Please sign in.');
                header('Location: index.php');
                exit();
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — TaskTracker</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-wrapper">

  <!-- Brand Panel -->
  <div class="auth-brand">
    <div class="brand-logo">Task<span>Tracker</span></div>
    <div class="brand-tagline">Your productivity <em>starts</em> here.</div>
    <p class="brand-sub">Create your free account and start organizing your tasks in seconds.</p>
    <div class="brand-features">
      <div class="brand-feature"><div class="icon">🚀</div><span>Get started in under a minute</span></div>
      <div class="brand-feature"><div class="icon">🎯</div><span>Set priorities and due dates</span></div>
      <div class="brand-feature"><div class="icon">📈</div><span>Watch your completion rate grow</span></div>
    </div>
  </div>

  <!-- Form Panel -->
  <div class="auth-form-side">
    <div class="auth-card">
      <h2>Create account</h2>
      <p class="subtitle">Free, no credit card required.</p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          ⚠️
          <ul style="margin:0;padding-left:16px;">
            <?php foreach ($errors as $e): ?>
              <li><?= sanitize($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="register.php">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username"
                 placeholder="e.g. john_doe"
                 value="<?= sanitize($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email"
                 placeholder="you@example.com"
                 value="<?= sanitize($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password"
                 placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label for="confirm">Confirm Password</label>
          <input type="password" id="confirm" name="confirm"
                 placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:8px;">
          Create Account →
        </button>
      </form>

      <p style="margin-top:24px;text-align:center;color:var(--muted);font-size:.9rem;">
        Already have an account?
        <a href="index.php">Sign in</a>
      </p>
    </div>
  </div>

</div>
<script src="app.js"></script>
</body>
</html>
