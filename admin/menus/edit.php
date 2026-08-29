<?php
require_once __DIR__ . '/../../includes/auth.php';
require_admin();

$menuId = (int)($_GET['id'] ?? 0);
if ($menuId <= 0) {
    redirect(admin_url('/menus/index.php'));
}

$menu = null;
try {
    $stmt = db()->prepare('SELECT * FROM menus WHERE id = ? LIMIT 1');
    $stmt->execute([$menuId]);
    $menu = $stmt->fetch();
} catch (Throwable $e) {
    $menu = null;
}

if (!$menu) {
    flash('error', 'Menu not found.');
    redirect(admin_url('/menus/index.php'));
}

function ref_url(string $type, int $refId): ?array {
    if ($type === 'page') {
        $st = db()->prepare("SELECT title, slug FROM pages WHERE id = ? LIMIT 1");
        $st->execute([$refId]);
        $row = $st->fetch();
        if (!$row) return null;
        return ['label' => $row['title'], 'url' => public_page_url($row['slug'])];
    }

    if ($type === 'post') {
        $st = db()->prepare("SELECT title, slug FROM posts WHERE id = ? LIMIT 1");
        $st->execute([$refId]);
        $row = $st->fetch();
        if (!$row) return null;
        return ['label' => $row['title'], 'url' => public_post_url($row['slug'])];
    }

    if ($type === 'category') {
        $st = db()->prepare("SELECT name, slug FROM categories WHERE id = ? LIMIT 1");
        $st->execute([$refId]);
        $row = $st->fetch();
        if (!$row) return null;
        return ['label' => $row['name'], 'url' => public_category_url($row['slug'])];
    }

    return null;
}

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_item') {
        $type        = trim($_POST['item_type'] ?? 'custom');
        $refId       = (int)($_POST['ref_id'] ?? 0);
        $label       = trim($_POST['label'] ?? '');
        $customUrl   = trim($_POST['custom_url'] ?? '');
        $targetBlank = isset($_POST['target_blank']) ? 1 : 0;

        try {
            $url        = '';
            $finalLabel = $label;
            $finalRefId = null;

            if (in_array($type, ['page','post','category'], true)) {
                $ref = ref_url($type, $refId);
                if (!$ref) throw new RuntimeException('Invalid reference.');
                $url = $ref['url'];
                if ($finalLabel === '') $finalLabel = $ref['label'];
                $finalRefId = $refId;
            } else {
                $type = 'custom';
                if ($customUrl === '') throw new RuntimeException('Custom URL is required.');
                $url = $customUrl;
                if ($finalLabel === '') $finalLabel = $customUrl;
            }

            $st = db()->prepare('SELECT COALESCE(MAX(sort_order),0) FROM menu_items WHERE menu_id = ?');
            $st->execute([$menuId]);
            $order = ((int)$st->fetchColumn()) + 10;

            $ins = db()->prepare(
                'INSERT INTO menu_items (menu_id, item_type, ref_id, label, url, target_blank, sort_order, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $ins->execute([$menuId, $type, $finalRefId, $finalLabel, $url, $targetBlank, $order]);

            flash('success', 'Menu item added.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect(admin_url('/menus/edit.php?id=' . $menuId));
    }

    if ($action === 'update_items') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId > 0) {
            try {
                $del = db()->prepare('DELETE FROM menu_items WHERE id = ? AND menu_id = ?');
                $del->execute([$deleteId, $menuId]);
                flash('success', 'Menu item deleted.');
            } catch (Throwable $e) {
                flash('error', $e->getMessage());
            }
            redirect(admin_url('/menus/edit.php?id=' . $menuId));
        }

        $items = $_POST['items'] ?? [];
        try {
            foreach ($items as $id => $row) {
                $id = (int)$id;
                $label = trim($row['label'] ?? '');
                $order = (int)($row['sort_order'] ?? 0);
                $targetBlank = isset($row['target_blank']) ? 1 : 0;

                $up = db()->prepare(
                    'UPDATE menu_items
                     SET label = ?, sort_order = ?, target_blank = ?
                     WHERE id = ? AND menu_id = ?'
                );
                $up->execute([$label, $order, $targetBlank, $id, $menuId]);
            }
            flash('success', 'Menu updated.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect(admin_url('/menus/edit.php?id=' . $menuId));
    }

    if ($action === 'delete_item') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $del = db()->prepare('DELETE FROM menu_items WHERE id = ? AND menu_id = ?');
            $del->execute([$id, $menuId]);
            flash('success', 'Menu item deleted.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect(admin_url('/menus/edit.php?id=' . $menuId));
    }
}

// Load current menu items
$items = [];
try {
    $st = db()->prepare('SELECT * FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC, id ASC');
    $st->execute([$menuId]);
    $items = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $items = [];
}

// Load references for dropdown
$pages = $posts = $cats = [];

try {
    $st = db()->prepare("SELECT id, title FROM pages WHERE status = ? ORDER BY title ASC");
    $st->execute(['published']);
    $pages = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $pages = [];
}

try {
    // Keep your column name as-is. If your table uses created_at instead of published_at, change it here.
    $st = db()->prepare("SELECT id, title FROM posts WHERE status = ? ORDER BY published_at DESC LIMIT 200");
    $st->execute(['published']);
    $posts = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $posts = [];
}

try {
    $st = db()->prepare("SELECT id, name FROM categories ORDER BY name ASC");
    $st->execute();
    $cats = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $cats = [];
}

require_once __DIR__ . '/../../includes/admin_header.php';
?>
<div class="card">
  <div class="toolbar">
    <h2>Manage Menu: <?= e($menu['name']) ?></h2>
    <a class="btn light" href="<?= admin_url('/menus/index.php') ?>">Back</a>
  </div>

  <h3 style="margin-top:0;">Add Menu Item</h3>
  <form method="post" class="grid-3" id="addMenuItemForm">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="add_item">

    <div>
      <label>Type</label>
      <select name="item_type" id="itemType">
        <option value="page">Page</option>
        <option value="post">Post</option>
        <option value="category">Category</option>
        <option value="custom">Custom Link</option>
      </select>
    </div>

    <div id="refWrap">
      <label>Reference</label>
      <select name="ref_id" id="refSelect"></select>
    </div>

    <div id="customWrap" style="display:none;">
      <label>Custom URL</label>
      <input type="text" name="custom_url" placeholder="https://example.com">
    </div>

    <div>
      <label>Label (optional)</label>
      <input type="text" name="label" placeholder="Menu text">
      <label style="margin-top:10px; display:flex; gap:8px; align-items:center;">
        <input type="checkbox" name="target_blank"> Open in new tab
      </label>
    </div>

    <div style="display:flex; align-items:end;">
      <button class="btn" type="submit">Add Item</button>
    </div>
  </form>
</div>

<div class="card" style="margin-top:14px;">
  <div class="toolbar">
    <h3 style="margin:0;">Menu Items</h3>
    <span class="muted small">Use Sort Order: 10, 20, 30... for easy reordering.</span>
  </div>

  <?php if (!$items): ?>
    <p class="muted">No items yet.</p>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="update_items">

      <table class="table">
        <thead>
          <tr>
            <th>Label</th><th>URL</th><th>Type</th><th>Order</th><th>New Tab</th><th>Delete</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><input type="text" name="items[<?= (int)$it['id'] ?>][label]" value="<?= e($it['label']) ?>"></td>
            <td class="muted mono" style="max-width:360px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= e($it['url']) ?></td>
            <td class="muted"><?= e($it['item_type']) ?></td>
            <td><input type="number" name="items[<?= (int)$it['id'] ?>][sort_order]" value="<?= (int)$it['sort_order'] ?>" style="width:90px;"></td>
            <td><input type="checkbox" name="items[<?= (int)$it['id'] ?>][target_blank]" <?= (int)$it['target_blank'] === 1 ? 'checked' : '' ?>></td>
            <td>
              <button class="btn light" type="submit" name="delete_id" value="<?= (int)$it['id'] ?>" onclick="return confirm('Delete this menu item?');">Delete</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <button class="btn" type="submit">Save Menu</button>
    </form>
  <?php endif; ?>
</div>

<script>
(function(){
  const pages = <?= json_encode(array_map(fn($p)=>['id'=>(int)$p['id'],'label'=>$p['title']], $pages)) ?>;
  const posts = <?= json_encode(array_map(fn($p)=>['id'=>(int)$p['id'],'label'=>$p['title']], $posts)) ?>;
  const cats  = <?= json_encode(array_map(fn($c)=>['id'=>(int)$c['id'],'label'=>$c['name']], $cats)) ?>;

  const typeEl = document.getElementById('itemType');
  const refSelect = document.getElementById('refSelect');
  const refWrap = document.getElementById('refWrap');
  const customWrap = document.getElementById('customWrap');

  function fill(list){
    refSelect.innerHTML = '';
    list.forEach(it => {
      const opt = document.createElement('option');
      opt.value = it.id;
      opt.textContent = it.label;
      refSelect.appendChild(opt);
    });
  }

  function onType(){
    const t = typeEl.value;
    if (t === 'custom') {
      refWrap.style.display = 'none';
      customWrap.style.display = 'block';
      return;
    }
    customWrap.style.display = 'none';
    refWrap.style.display = 'block';
    if (t === 'page') fill(pages);
    if (t === 'post') fill(posts);
    if (t === 'category') fill(cats);
  }

  typeEl.addEventListener('change', onType);
  onType();
})();
</script>

<?php require_once __DIR__ . '/../../includes/admin_footer.php'; ?>