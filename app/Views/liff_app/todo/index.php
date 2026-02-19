<div class="page-header d-flex" style="justify-content:space-between;align-items:center">
  <h1><?= esc($page_title) ?></h1>
  <span style="font-size:13px;color:#94A3B8"><?= count(array_filter((array)$todos, fn($t) => $t->status !== 'done')) ?> รายการ</span>
</div>

<!-- Quick add form -->
<div class="card" style="margin-bottom:16px">
  <form onsubmit="addTodo(event)" style="display:flex;gap:8px;align-items:center">
    <input class="form-control" id="todo-input" placeholder="เพิ่มรายการ..." required style="flex:1;margin-bottom:0">
    <button class="btn btn-primary" type="submit" style="padding:10px 16px;flex-shrink:0">+</button>
  </form>
</div>

<!-- Todo list -->
<div id="todo-list">
<?php $pending = array_filter((array)$todos, fn($t) => $t->status !== 'done'); ?>
<?php $done_items = array_filter((array)$todos, fn($t) => $t->status === 'done'); ?>

<?php foreach ($pending as $t): ?>
<div class="todo-item" id="todo-<?= $t->id ?>" data-id="<?= $t->id ?>">
  <label class="d-flex" style="align-items:center;gap:12px;cursor:pointer;flex:1">
    <input type="checkbox" onchange="toggleTodo(<?= $t->id ?>, this)"
      style="width:20px;height:20px;accent-color:var(--blue);cursor:pointer">
    <span style="font-size:14px;color:#1E293B"><?= esc($t->title) ?></span>
  </label>
  <?php if ($t->start_date): ?>
  <div style="font-size:11px;color:#94A3B8;margin-left:32px;margin-top:2px">
    <?= date('d M', strtotime($t->start_date)) ?>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if (!empty($done_items)): ?>
<div style="margin-top:20px;margin-bottom:8px;font-size:12px;color:#94A3B8;font-weight:600;text-transform:uppercase;padding:0 4px">
  เสร็จแล้ว (<?= count($done_items) ?>)
</div>
<?php foreach ($done_items as $t): ?>
<div class="todo-item done" id="todo-<?= $t->id ?>" data-id="<?= $t->id ?>">
  <label class="d-flex" style="align-items:center;gap:12px;cursor:pointer;flex:1">
    <input type="checkbox" checked onchange="toggleTodo(<?= $t->id ?>, this)"
      style="width:20px;height:20px;accent-color:var(--blue);cursor:pointer">
    <span style="font-size:14px;color:#94A3B8;text-decoration:line-through"><?= esc($t->title) ?></span>
  </label>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php if (empty($todos)): ?>
<div style="text-align:center;padding:40px 20px;color:#94A3B8">
  <p>ยังไม่มีรายการ เริ่มเพิ่มได้เลย!</p>
</div>
<?php endif; ?>
</div>

<style>
.todo-item {
  background:#fff;
  border-radius:14px;
  padding:12px 16px;
  margin-bottom:8px;
  box-shadow:0 1px 4px rgba(0,0,0,0.06);
  transition:all .2s;
  animation:fadeIn .2s ease;
}
.todo-item.done { opacity:0.6; }
@keyframes fadeIn { from{opacity:0;transform:translateY(-6px)} to{opacity:1;transform:translateY(0)} }
</style>

<script>
async function addTodo(e) {
  e.preventDefault();
  const input = document.getElementById('todo-input');
  const title = input.value.trim();
  if (!title) return;

  const res = await LiffApp.api('liff/api/todo/save', 'POST', { title });
  if (res.success) {
    input.value = '';
    const item = document.createElement('div');
    item.className = 'todo-item';
    item.id = 'todo-' + res.id;
    item.dataset.id = res.id;
    item.innerHTML = `
      <label class="d-flex" style="align-items:center;gap:12px;cursor:pointer;flex:1">
        <input type="checkbox" onchange="toggleTodo(${res.id}, this)"
          style="width:20px;height:20px;accent-color:var(--blue);cursor:pointer">
        <span style="font-size:14px;color:#1E293B">${escHtml(title)}</span>
      </label>`;
    document.getElementById('todo-list').prepend(item);
    LiffApp.toast('เพิ่มแล้ว', 'success');
  }
}

async function toggleTodo(id, cb) {
  const res = await LiffApp.api('liff/api/todo/toggle', 'POST', { id });
  if (res.success) {
    const el = document.getElementById('todo-' + id);
    const span = el.querySelector('span');
    if (res.done) {
      el.classList.add('done');
      span.style.textDecoration = 'line-through';
      span.style.color = '#94A3B8';
    } else {
      el.classList.remove('done');
      span.style.textDecoration = '';
      span.style.color = '#1E293B';
    }
  }
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
