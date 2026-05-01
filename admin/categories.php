<?php
/**
 * Blog Categories Management
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$admin = getCurrentAdmin();
$flash = getFlash();

try {
    $pdo = getDBConnection();

    // Handle POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrf($_POST['_token'] ?? '')) {
            setFlash('danger', 'Invalid security token.');
            header('Location: /admin/categories.php');
            exit;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if (!$name) {
                setFlash('danger', 'Category name is required.');
            } else {
                $slug = makeSlug($name);
                $stmt = $pdo->prepare(
                    'INSERT INTO blog_categories (name, slug, description) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description)'
                );
                $stmt->execute([$name, $slug, $desc]);
                setFlash('success', 'Category "' . htmlspecialchars($name) . '" added.');
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['cat_id'] ?? 0);
            if ($catId > 0) {
                $pdo->prepare('DELETE FROM blog_categories WHERE id = ?')->execute([$catId]);
                setFlash('success', 'Category deleted.');
            }
        } elseif ($action === 'edit') {
            $catId = (int)($_POST['cat_id'] ?? 0);
            $name  = trim($_POST['name'] ?? '');
            $desc  = trim($_POST['description'] ?? '');
            if ($catId > 0 && $name) {
                $stmt = $pdo->prepare(
                    'UPDATE blog_categories SET name = ?, description = ? WHERE id = ?'
                );
                $stmt->execute([$name, $desc, $catId]);
                setFlash('success', 'Category updated.');
            }
        }

        header('Location: /admin/categories.php');
        exit;
    }

    // Load categories with blog count
    $categories = $pdo->query(
        "SELECT c.id, c.name, c.slug, c.description, COUNT(bcr.blog_id) AS blog_count
           FROM blog_categories c
           LEFT JOIN blog_category_relations bcr ON c.id = bcr.category_id
          GROUP BY c.id
          ORDER BY c.name"
    )->fetchAll();

} catch (Exception $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Categories — DegreeDrishti Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button id="sidebar-toggle" class="btn btn-outline btn-sm"><i class="fas fa-bars"></i></button>
        <span class="topbar-title">Categories</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-user">
          <div class="avatar"><?= strtoupper(substr($admin['full_name'], 0, 1)) ?></div>
          <span><?= esc($admin['full_name']) ?></span>
        </div>
      </div>
    </div>

    <div class="page-body">
      <?php if ($flash): ?>
        <div class="alert alert-<?= esc($flash['type']) ?>" style="margin-bottom:18px">
          <?= esc($flash['message']) ?>
        </div>
      <?php endif; ?>

      <div class="two-col">
        <!-- Add Category Form -->
        <div class="card">
          <div class="card-header"><span class="card-title">Add New Category</span></div>
          <div class="card-body">
            <form method="POST" action="/admin/categories.php">
              <input type="hidden" name="_token" value="<?= getCsrfToken() ?>">
              <input type="hidden" name="action" value="add">
              <div class="form-group">
                <label>Category Name <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. MBA Insights" required>
              </div>
              <div class="form-group">
                <label>Description <small style="color:#9ca3af">(optional)</small></label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Brief description…"></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-plus"></i> Add Category
              </button>
            </form>
          </div>
        </div>

        <!-- Categories List -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">All Categories</span>
            <span style="font-size:12px;color:#9ca3af;"><?= count($categories) ?> total</span>
          </div>
          <div style="overflow-x:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Posts</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($categories)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:30px;">No categories yet.</td></tr>
              <?php else: foreach ($categories as $cat): ?>
                <tr>
                  <td>
                    <strong><?= esc($cat['name']) ?></strong>
                    <?php if ($cat['description']): ?>
                      <div style="font-size:11px;color:#9ca3af;"><?= esc(mb_substr($cat['description'], 0, 60)) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:12px;color:#6b7280;"><?= esc($cat['slug']) ?></td>
                  <td><?= $cat['blog_count'] ?></td>
                  <td>
                    <button class="btn btn-outline btn-sm"
                            onclick="editCategory(<?= $cat['id'] ?>, '<?= esc(addslashes($cat['name'])) ?>', '<?= esc(addslashes($cat['description'] ?? '')) ?>')">
                      <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="_token" value="<?= getCsrfToken() ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm"
                              onclick="return confirm('Delete category \'<?= esc(addslashes($cat['name'])) ?>\'?\nBlogs using it will be unlinked.')">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /two-col -->

      <!-- Edit Modal -->
      <div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;display:flex;align-items:center;justify-content:center;" hidden>
        <div style="background:#fff;border-radius:12px;padding:32px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.3);">
          <h3 style="margin-bottom:20px;color:#2C3E50;">Edit Category</h3>
          <form method="POST" id="edit-cat-form">
            <input type="hidden" name="_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="cat_id" id="edit-cat-id">
            <div class="form-group">
              <label>Name</label>
              <input type="text" name="name" id="edit-cat-name" class="form-control" required>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" id="edit-cat-desc" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
              <button type="button" class="btn btn-outline" onclick="closeEdit()">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="/admin/assets/admin.js"></script>
<script>
function editCategory(id, name, desc) {
  document.getElementById('edit-cat-id').value   = id;
  document.getElementById('edit-cat-name').value = name;
  document.getElementById('edit-cat-desc').value = desc;
  const modal = document.getElementById('edit-modal');
  modal.hidden = false;
  modal.style.display = 'flex';
}
function closeEdit() {
  const modal = document.getElementById('edit-modal');
  modal.hidden = true;
  modal.style.display = 'none';
}
</script>
</body>
</html>
