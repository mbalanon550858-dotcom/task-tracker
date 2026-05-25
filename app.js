// ============================================================
// Task Tracker — Main JavaScript
// ============================================================

// ── Modal helpers ────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}

// Close modal when clicking overlay background
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

// ── Edit Task — populate modal ───────────────────────────────
function openEditModal(id, title, description, priority, dueDate, status) {
  document.getElementById('edit_id').value          = id;
  document.getElementById('edit_title').value       = title;
  document.getElementById('edit_description').value = description;
  document.getElementById('edit_priority').value    = priority;
  document.getElementById('edit_due_date').value    = dueDate;
  document.getElementById('edit_status').value      = status;
  openModal('editModal');
}

// ── Delete confirm ───────────────────────────────────────────
function confirmDelete(id, title) {
  document.getElementById('delete_id').value    = id;
  document.getElementById('delete_title').textContent = title;
  openModal('deleteModal');
}

// ── Live search / filter ─────────────────────────────────────
function applyFilters() {
  const search   = (document.getElementById('search')?.value || '').toLowerCase();
  const priority = document.getElementById('filterPriority')?.value || '';
  const status   = document.getElementById('filterStatus')?.value   || '';

  document.querySelectorAll('tbody tr.task-row').forEach(row => {
    const title    = row.dataset.title    || '';
    const rowPri   = row.dataset.priority || '';
    const rowStat  = row.dataset.status   || '';

    const matchSearch   = title.includes(search);
    const matchPriority = !priority || rowPri === priority;
    const matchStatus   = !status   || rowStat === status;

    row.style.display = (matchSearch && matchPriority && matchStatus) ? '' : 'none';
  });

  // Show empty-state if no rows visible
  const visible = [...document.querySelectorAll('tbody tr.task-row')]
    .filter(r => r.style.display !== 'none').length;
  const emptyRow = document.getElementById('emptyRow');
  if (emptyRow) emptyRow.style.display = visible === 0 ? '' : 'none';
}

['search','filterPriority','filterStatus'].forEach(id => {
  document.getElementById(id)?.addEventListener('input',  applyFilters);
  document.getElementById(id)?.addEventListener('change', applyFilters);
});

// ── Auto-dismiss flash alerts ────────────────────────────────
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(a => {
    a.style.transition = 'opacity .5s ease';
    a.style.opacity = '0';
    setTimeout(() => a.remove(), 500);
  });
}, 4000);

// ── Animate stat counters ────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.stat-value').forEach(el => {
    const end = parseInt(el.textContent, 10);
    if (isNaN(end)) return;
    let cur = 0;
    const step = Math.ceil(end / 25);
    const timer = setInterval(() => {
      cur = Math.min(cur + step, end);
      el.textContent = cur;
      if (cur >= end) clearInterval(timer);
    }, 30);
  });
});
