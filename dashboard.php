<?php
// ============================================================
// dashboard.php — Main Dashboard
// ============================================================
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$user = currentUser();
$uid  = $user['id'];

// ── Stats ────────────────────────────────────────────────────
$stats_q = mysqli_query($conn,
    "SELECT
       COUNT(*) AS total,
       SUM(status='pending')   AS pending,
       SUM(status='completed') AS completed,
       SUM(priority='high' AND status='pending') AS high_pending
     FROM tasks WHERE user_id = $uid");
$stats = mysqli_fetch_assoc($stats_q);

$pct = $stats['total'] > 0
    ? round(($stats['completed'] / $stats['total']) * 100)
    : 0;

// ── Recent Tasks (last 5) ────────────────────────────────────
$recent_q = mysqli_query($conn,
    "SELECT * FROM tasks WHERE user_id = $uid
     ORDER BY created_at DESC LIMIT 5");
$recent = mysqli_fetch_all($recent_q, MYSQLI_ASSOC);

// ── Upcoming (due in 7 days, pending) ───────────────────────
$upcoming_q = mysqli_query($conn,
    "SELECT * FROM tasks
     WHERE user_id = $uid
       AND status = 'pending'
       AND due_date IS NOT NULL
       AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
     ORDER BY due_date ASC LIMIT 5");
$upcoming = mysqli_fetch_all($upcoming_q, MYSQLI_ASSOC);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — TaskTracker</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="topbar">
  <span class="topbar-logo">Task<span>Tracker</span></span>
  <a href="tasks.php" class="btn btn-sm btn-outline">My Tasks</a>
</div>

<div class="app-layout">
  <!-- Sidebar -->
  <?php include 'includes/sidebar.php'; ?>

  <!-- Main -->
  <main class="main-content">
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>">
        <?= sanitize($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1>👋 Hello, <?= sanitize($user['username']) ?></h1>
        <p>Here's your productivity snapshot for today.</p>
      </div>
      <a href="tasks.php" class="btn btn-outline">View All Tasks →</a>
    </div>

    <!-- Progress -->
    <div class="progress-wrap">
      <div class="progress-label">
        <span>Overall Completion</span>
        <span><?= $pct ?>%</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill" style="width:<?= $pct ?>%"></div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card total">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?= $stats['total'] ?></div>
        <div class="stat-label">Total Tasks</div>
      </div>
      <div class="stat-card pending">
        <div class="stat-icon">⏳</div>
        <div class="stat-value"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card done">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $stats['completed'] ?></div>
        <div class="stat-label">Completed</div>
      </div>
      <div class="stat-card high">
        <div class="stat-icon">🔥</div>
        <div class="stat-value"><?= $stats['high_pending'] ?></div>
        <div class="stat-label">High Priority</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">
      <!-- Recent Tasks -->
      <div class="section-card" style="grid-column:1/-1;">
        <div class="section-head">
          <h3>📌 Recent Tasks</h3>
          <a href="tasks.php" class="btn btn-sm btn-outline">Add Task</a>
        </div>
        <?php if (empty($recent)): ?>
          <div class="empty-state">
            <div class="icon">📭</div>
            <h4>No tasks yet</h4>
            <p>Click "Add Task" to get started!</p>
          </div>
        <?php else: ?>
          <table class="task-table">
            <thead>
              <tr>
                <th>Task</th>
                <th>Priority</th>
                <th>Due Date</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $t): ?>
              <tr class="<?= $t['status'] === 'completed' ? 'completed' : '' ?>">
                <td data-label="Task">
                  <div class="task-title"><?= sanitize($t['title']) ?></div>
                  <?php if ($t['description']): ?>
                    <div class="task-desc"><?= sanitize(substr($t['description'],0,60)) . (strlen($t['description'])>60?'…':'') ?></div>
                  <?php endif; ?>
                </td>
                <td data-label="Priority">
                  <span class="badge badge-<?= $t['priority'] ?>">
                    <?= $t['priority'] === 'high' ? '🔥' : ($t['priority'] === 'medium' ? '⚡' : '💧') ?>
                    <?= ucfirst($t['priority']) ?>
                  </span>
                </td>
                <td data-label="Due Date">
                  <?= $t['due_date'] ? date('M d, Y', strtotime($t['due_date'])) : '<span style="color:var(--muted)">—</span>' ?>
                </td>
                <td data-label="Status">
                  <span class="badge badge-<?= $t['status'] ?>">
                    <?= $t['status'] === 'completed' ? '✅' : '⏳' ?>
                    <?= ucfirst($t['status']) ?>
                  </span>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <!-- Upcoming Deadlines -->
      <?php if (!empty($upcoming)): ?>
      <div class="section-card" style="grid-column:1/-1;">
        <div class="section-head">
          <h3>📅 Due This Week</h3>
        </div>
        <table class="task-table">
          <thead><tr><th>Task</th><th>Priority</th><th>Due</th></tr></thead>
          <tbody>
            <?php foreach ($upcoming as $t): ?>
            <?php
              $due  = strtotime($t['due_date']);
              $diff = ceil(($due - strtotime('today')) / 86400);
              $label = $diff === 0 ? '<span style="color:var(--danger)">Today!</span>'
                     : ($diff === 1 ? '<span style="color:var(--medium)">Tomorrow</span>'
                     : date('M d', $due));
            ?>
            <tr>
              <td data-label="Task"><div class="task-title"><?= sanitize($t['title']) ?></div></td>
              <td data-label="Priority"><span class="badge badge-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
              <td data-label="Due"><?= $label ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </main>
</div>
<script src="js/app.js"></script>
</body>
</html>
