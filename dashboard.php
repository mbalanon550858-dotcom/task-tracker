<?php
require_once 'auth.php';
require_once 'db.php';
requireLogin();

$user = currentUser();
$uid  = $user['id'];

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status='completed') AS completed, SUM(priority='high' AND status='pending') AS high_pending FROM tasks WHERE user_id = ?");
$stmt->execute([$uid]);
$stats = $stmt->fetch();

$pct = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0;

// Recent Tasks
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

// Upcoming
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? AND status = 'pending' AND due_date IS NOT NULL AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) ORDER BY due_date ASC LIMIT 5");
$stmt->execute([$uid]);
$upcoming = $stmt->fetchAll();

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — TaskTracker</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="topbar">
  <span class="topbar-logo">Task<span>Tracker</span></span>
  <a href="tasks.php" class="btn btn-sm btn-outline">My Tasks</a>
</div>
<div class="app-layout">
  <?php include 'sidebar.php'; ?>
  <main class="main-content">
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>
    <div class="page-header">
      <div>
        <h1>👋 Hello, <?= sanitize($user['username']) ?></h1>
        <p>Here's your productivity snapshot for today.</p>
      </div>
      <a href="tasks.php" class="btn btn-outline">View All Tasks →</a>
    </div>
    <div class="progress-wrap">
      <div class="progress-label"><span>Overall Completion</span><span><?= $pct ?>%</span></div>
      <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
    </div>
    <div class="stats-grid">
      <div class="stat-card total"><div class="stat-icon">📋</div><div class="stat-value"><?= $stats['total'] ?></div><div class="stat-label">Total Tasks</div></div>
      <div class="stat-card pending"><div class="stat-icon">⏳</div><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending</div></div>
      <div class="stat-card done"><div class="stat-icon">✅</div><div class="stat-value"><?= $stats['completed'] ?></div><div class="stat-label">Completed</div></div>
      <div class="stat-card high"><div class="stat-icon">🔥</div><div class="stat-value"><?= $stats['high_pending'] ?></div><div class="stat-label">High Priority</div></div>
    </div>
    <div class="section-card">
      <div class="section-head"><h3>📌 Recent Tasks</h3><a href="tasks.php" class="btn btn-sm btn-outline">Add Task</a></div>
      <?php if (empty($recent)): ?>
        <div class="empty-state"><div class="icon">📭</div><h4>No tasks yet</h4><p>Click "Add Task" to get started!</p></div>
      <?php else: ?>
        <table class="task-table">
          <thead><tr><th>Task</th><th>Priority</th><th>Due Date</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $t): ?>
            <tr class="<?= $t['status']==='completed'?'completed':'' ?>">
              <td data-label="Task"><div class="task-title"><?= sanitize($t['title']) ?></div></td>
              <td data-label="Priority"><span class="badge badge-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
              <td data-label="Due"><?= $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : '—' ?></td>
              <td data-label="Status"><span class="badge badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php if (!empty($upcoming)): ?>
    <div class="section-card" style="margin-top:20px;">
      <div class="section-head"><h3>📅 Due This Week</h3></div>
      <table class="task-table">
        <thead><tr><th>Task</th><th>Priority</th><th>Due</th></tr></thead>
        <tbody>
          <?php foreach ($upcoming as $t): ?>
          <?php $diff = ceil((strtotime($t['due_date']) - strtotime('today')) / 86400); ?>
          <tr>
            <td data-label="Task"><?= sanitize($t['title']) ?></td>
            <td data-label="Priority"><span class="badge badge-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
            <td data-label="Due"><?= $diff===0?'<span style="color:var(--danger)">Today!</span>':($diff===1?'Tomorrow':date('M d', strtotime($t['due_date']))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="app.js"></script>
</body>
</html>
