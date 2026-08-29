<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

if (is_post()) {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? 'primary');
    if ($name === '') {
        flash('error', 'Menu name is required.');
        redirect(admin_url('/menus/index.php'));
    }
    try {
        $stmt = db()->prepare('INSERT INTO menus (name, location, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$name, $location]);
        flash('success', 'Menu created.');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
    redirect(admin_url('/menus/index.php'));
}

$menus = [];
try {
    $menus = db()->query('SELECT * FROM menus ORDER BY id ASC')->fetchAll();
} catch (Throwable $e) {
    $menus = [];
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
  <div class="toolbar">
    <h2>Menus</h2>
    <span class="muted">Create and manage website navigation menus.</span>
  </div>

  <form method="post" class="grid-3">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <div>
      <label>Menu Name</label>
      <input type="text" name="name" placeholder="Primary Menu" required>
    </div>
    <div>
      <label>Location</label>
      <select name="location">
        <option value="primary">Primary (Header)</option>
      </select>
    </div>
    <div style="display:flex; align-items:end;">
      <button class="btn" type="submit">Create Menu</button>
    </div>
  </form>
</div>

<div class="card" style="margin-top:14px;">
  <h3 style="margin-top:0;">Existing Menus</h3>
  <?php if (!$menus): ?>
    <p class="muted">No menus yet. Create one above.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Location</th><th>Items</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($menus as $m):
        $cnt = 0;
        try {
          $st = db()->prepare('SELECT COUNT(*) FROM menu_items WHERE menu_id = ?');
          $st->execute([(int)$m['id']]);
          $cnt = (int)$st->fetchColumn();
        } catch (Throwable $e) { $cnt = 0; }
      ?>
        <tr>
          <td><?= (int)$m['id'] ?></td>
          <td><strong><?= e($m['name']) ?></strong></td>
          <td class="muted"><?= e($m['location']) ?></td>
          <td><?= $cnt ?></td>
          <td><a class="btn light" href="<?= admin_url('/menus/edit.php?id=' . (int)$m['id']) ?>">Manage</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>
