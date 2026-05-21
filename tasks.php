<?php
// ============================================================
// tasks.php — Full CRUD Task Management
// ============================================================
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$user = currentUser();
$uid  = $user['id'];
$errors = [];

// ── ADD Task ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add'])) {
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority']   ?? 'medium';
    $due_date    = $_POST['due_date']   ?? null;

    if (empty($title)) {
        $errors[] = 'Task title is required.';
    } elseif (strlen($title) > 150) {
        $errors[] = 'Title must be 150 characters or fewer.';
    }
    if (!in_array($priority, ['low','medium','high'])) $priority = 'medium';
    if (empty($due_date)) $due_date = null;

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO tasks (user_id, title, description, priority, due_date)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issss',
            $uid, $title, $description, $priority, $due_date);
        if (mysqli_stmt_execute($stmt)) {
            setFlash('success', '✅ Task added successfully!');
        } else {
            setFlash('error', 'Failed to add task. Please try again.');
        }
        mysqli_stmt_close($stmt);
        header('Location: tasks.php');
        exit();
    }
}

// ── EDIT Task ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit'])) {
    $id          = (int)($_POST['id']          ?? 0);
    $title       = trim($_POST['title']        ?? '');
    $description = trim($_POST['description']  ?? '');
    $priority    = $_POST['priority']    ?? 'medium';
    $due_date    = $_POST['due_date']    ?? null;
    $status      = $_POST['status']     ?? 'pending';

    if (empty($title)) { setFlash('error', 'Title is required.'); header('Location: tasks.php'); exit(); }
    if (!in_array($priority, ['low','medium','high'])) $priority = 'medium';
    if (!in_array($status,   ['pending','completed'])) $status   = 'pending';
    if (empty($due_date)) $due_date = null;

    $stmt = mysqli_prepare($conn,
        "UPDATE tasks SET title=?, description=?, priority=?, due_date=?, status=?
         WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, 'sssssii',
        $title, $description, $priority, $due_date, $status, $id, $uid);
    if (mysqli_stmt_execute($stmt)) {
        setFlash('success', '✏️ Task updated successfully!');
    } else {
        setFlash('error', 'Failed to update task.');
    }
    mysqli_stmt_close($stmt);
    header('Location: tasks.php');
    exit();
}

// ── DELETE Task ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete'])) {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = mysqli_prepare($conn,
        "DELETE FROM tasks WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $uid);
    if (mysqli_stmt_execute($stmt)) {
        setFlash('success', '🗑️ Task deleted.');
    } else {
        setFlash('error', 'Failed to delete task.');
    }
    mysqli_stmt_close($stmt);
    header('Location: tasks.php');
    exit();
}

// ── MARK COMPLETE / PENDING ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_toggle'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $new_status = ($_POST['current_status'] === 'pending') ? 'completed' : 'pending';
    $stmt = mysqli_prepare($conn,
        "UPDATE tasks SET status=? WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, 'sii', $new_status, $id, $uid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $msg = $new_status === 'completed' ? '🎉 Task marked as complete!' : '↩️ Task marked as pending.';
    setFlash('success', $msg);
    header('Location: tasks.php');
    exit();
}

// ── FETCH all tasks ──────────────────────────────────────────
$tasks_q = mysqli_query($conn,
    "SELECT * FROM tasks WHERE user_id = $uid ORDER BY
     FIELD(priority,'high','medium','low'),
     FIELD(status,'pending','completed'),
     due_date IS NULL, due_date ASC,
     created_at DESC");
$tasks = mysqli_fetch_all($tasks_q, MYSQLI_ASSOC);

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Tasks — TaskTracker</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="topbar">
  <span class="topbar-logo">Task<span>Tracker</span></span>
  <button class="btn btn-sm btn-primary" onclick="openModal('addModal')">+ Add Task</button>
</div>

<div class="app-layout">
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">⚠️ <?= sanitize(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1>📋 My Tasks</h1>
        <p><?= count($tasks) ?> task<?= count($tasks)!==1?'s':'' ?> total</p>
      </div>
      <button class="btn btn-primary" onclick="openModal('addModal')">+ Add Task</button>
    </div>

    <div class="section-card">
      <!-- Filter Bar -->
      <div class="filter-bar">
        <input type="text" id="search" placeholder="🔍 Search tasks…">
        <select id="filterPriority">
          <option value="">All Priorities</option>
          <option value="high">🔥 High</option>
          <option value="medium">⚡ Medium</option>
          <option value="low">💧 Low</option>
        </select>
        <select id="filterStatus">
          <option value="">All Statuses</option>
          <option value="pending">⏳ Pending</option>
          <option value="completed">✅ Completed</option>
        </select>
      </div>

      <!-- Task Table -->
      <table class="task-table">
        <thead>
          <tr>
            <th style="width:35%">Task</th>
            <th>Priority</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tasks)): ?>
          <tr id="emptyRow">
            <td colspan="5">
              <div class="empty-state">
                <div class="icon">📭</div>
                <h4>No tasks yet</h4>
                <p>Click <strong>+ Add Task</strong> to create your first task!</p>
              </div>
            </td>
          </tr>
          <?php else: ?>
          <tr id="emptyRow" style="display:none">
            <td colspan="5">
              <div class="empty-state">
                <div class="icon">🔍</div>
                <h4>No matching tasks</h4>
                <p>Try adjusting your filters.</p>
              </div>
            </td>
          </tr>
          <?php foreach ($tasks as $t): ?>
          <tr class="task-row <?= $t['status']==='completed'?'completed':'' ?>"
              data-title="<?= strtolower(sanitize($t['title'])) ?>"
              data-priority="<?= $t['priority'] ?>"
              data-status="<?= $t['status'] ?>">

            <td data-label="Task">
              <div class="task-title"><?= sanitize($t['title']) ?></div>
              <?php if ($t['description']): ?>
                <div class="task-desc"><?= sanitize(substr($t['description'],0,80)) . (strlen($t['description'])>80?'…':'') ?></div>
              <?php endif; ?>
            </td>

            <td data-label="Priority">
              <span class="badge badge-<?= $t['priority'] ?>">
                <?= $t['priority']==='high'?'🔥':($t['priority']==='medium'?'⚡':'💧') ?>
                <?= ucfirst($t['priority']) ?>
              </span>
            </td>

            <td data-label="Due Date">
              <?php if ($t['due_date']): ?>
                <?php
                  $due  = strtotime($t['due_date']);
                  $diff = ceil(($due - strtotime('today')) / 86400);
                  $overdue = $diff < 0 && $t['status']==='pending';
                ?>
                <span style="color:<?= $overdue ? 'var(--danger)' : 'inherit' ?>">
                  <?= date('M d, Y', $due) ?>
                  <?php if ($overdue): ?> <small>(overdue)</small><?php endif; ?>
                </span>
              <?php else: ?>
                <span style="color:var(--muted)">—</span>
              <?php endif; ?>
            </td>

            <td data-label="Status">
              <span class="badge badge-<?= $t['status'] ?>">
                <?= $t['status']==='completed'?'✅':'⏳' ?>
                <?= ucfirst($t['status']) ?>
              </span>
            </td>

            <td data-label="Actions">
              <div class="actions">
                <!-- Toggle Complete -->
                <form method="POST" action="tasks.php" style="display:inline">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $t['status'] ?>">
                  <button type="submit" name="action_toggle"
                    class="btn btn-sm <?= $t['status']==='pending'?'btn-success':'btn-outline' ?>"
                    title="<?= $t['status']==='pending'?'Mark Complete':'Mark Pending' ?>">
                    <?= $t['status']==='pending'?'✅':'↩️' ?>
                  </button>
                </form>

                <!-- Edit -->
                <button class="btn btn-sm btn-edit"
                  onclick="openEditModal(
                    <?= $t['id'] ?>,
                    <?= json_encode($t['title']) ?>,
                    <?= json_encode($t['description']) ?>,
                    '<?= $t['priority'] ?>',
                    '<?= $t['due_date'] ?? '' ?>',
                    '<?= $t['status'] ?>'
                  )">✏️</button>

                <!-- Delete -->
                <button class="btn btn-sm btn-danger"
                  onclick="confirmDelete(<?= $t['id'] ?>, <?= json_encode($t['title']) ?>)">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>
</div>

<!-- ── ADD Task Modal ──────────────────────────────────────── -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Add New Task</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST" action="tasks.php">
      <div class="modal-body">
        <div class="form-group">
          <label>Task Title *</label>
          <input type="text" name="title" placeholder="e.g. Submit project report" required maxlength="150">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" placeholder="Optional details…"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="form-group">
            <label>Priority</label>
            <select name="priority">
              <option value="low">💧 Low</option>
              <option value="medium" selected>⚡ Medium</option>
              <option value="high">🔥 High</option>
            </select>
          </div>
          <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" name="action_add" class="btn btn-primary" style="width:auto;">Add Task</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT Task Modal ─────────────────────────────────────── -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Edit Task</h3>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>
    <form method="POST" action="tasks.php">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-body">
        <div class="form-group">
          <label>Task Title *</label>
          <input type="text" name="title" id="edit_title" required maxlength="150">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="edit_description"></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="form-group">
            <label>Priority</label>
            <select name="priority" id="edit_priority">
              <option value="low">💧 Low</option>
              <option value="medium">⚡ Medium</option>
              <option value="high">🔥 High</option>
            </select>
          </div>
          <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" id="edit_due_date">
          </div>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="edit_status">
            <option value="pending">⏳ Pending</option>
            <option value="completed">✅ Completed</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" name="action_edit" class="btn btn-primary" style="width:auto;">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ── DELETE Confirm Modal ────────────────────────────────── -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <div class="modal-header">
      <h3>🗑️ Delete Task</h3>
      <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
    </div>
    <form method="POST" action="tasks.php">
      <input type="hidden" name="id" id="delete_id">
      <div class="modal-body">
        <p>Are you sure you want to delete:</p>
        <p style="margin-top:10px;font-weight:600;color:var(--text);">"<span id="delete_title"></span>"</p>
        <p style="margin-top:10px;color:var(--danger);font-size:.9rem;">⚠️ This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="submit" name="action_delete" class="btn btn-danger" style="width:auto;">Delete Task</button>
      </div>
    </form>
  </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
