<?php
// ============================================================
// includes/sidebar.php — Shared Sidebar Navigation
// ============================================================
$user = currentUser();
$initial = strtoupper(substr($user['username'], 0, 1));
$current = basename($_SERVER['PHP_SELF']);
?>
<nav class="sidebar">
  <div class="sidebar-logo">Task<span>Tracker</span></div>

  <div class="sidebar-section">
    <div class="sidebar-label">Navigation</div>
    <a href="dashboard.php" class="sidebar-link <?= $current==='dashboard.php'?'active':'' ?>">
      <span class="icon">🏠</span> Dashboard
    </a>
    <a href="tasks.php" class="sidebar-link <?= $current==='tasks.php'?'active':'' ?>">
      <span class="icon">📋</span> My Tasks
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="avatar"><?= $initial ?></div>
      <div>
        <div class="user-name"><?= sanitize($user['username']) ?></div>
        <div class="user-email"><?= sanitize($user['email']) ?></div>
      </div>
    </div>
    <a href="logout.php" class="btn btn-sm btn-danger" style="width:100%;justify-content:center;">
      🚪 Logout
    </a>
  </div>
</nav>
