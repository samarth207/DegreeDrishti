<?php
/**
 * All Blog Posts — List Page
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$admin = getCurrentAdmin();
$flash = getFlash();

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$search       = trim($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 15;
$offset       = ($page - 1) * $perPage;

try {
    $pdo = getDBConnection();

    // Build WHERE
    $where  = [];
    $params = [];

    if ($statusFilter !== 'all') {
        $where[]  = 'b.status = ?';
        $params[] = $statusFilter;
    }
    if ($search !== '') {
        $where[]  = '(b.title LIKE ? OR b.slug LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b $whereSQL");
    $countStmt->execute($params);
    $totalRows  = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($totalRows / $perPage);

    // Blogs
    $stmt = $pdo->prepare(
        "SELECT b.id, b.title, b.slug, b.status, b.created_at, b.updated_at, b.views,
                b.read_time, b.author_name,
                GROUP_CONCAT(c.name ORDER BY c.name SEPARATOR ', ') AS categories
           FROM blogs b
           LEFT JOIN blog_category_relations bcr ON b.id = bcr.blog_id
           LEFT JOIN blog_categories c ON bcr.category_id = c.id
         $whereSQL
          GROUP BY b.id
          ORDER BY b.created_at DESC
          LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $blogs = $stmt->fetchAll();

    // Status counts
    $counts = $pdo->query(
        "SELECT status, COUNT(*) cnt FROM blogs GROUP BY status"
    )->fetchAll(PDO::FETCH_KEY_PAIR);

} catch (Exception $e) {
    $blogs = [];
    $totalRows = $totalPages = 0;
    $counts = [];
}

$allCount = array_sum($counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>All Blogs — DegreeDrishti Admin</title>
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
        <span class="topbar-title">Blog Posts</span>
      </div>
      <div class="topbar-right">
        <a href="/admin/blog-edit.php" class="btn btn-yellow btn-sm">
          <i class="fas fa-plus"></i> New Blog
        </a>
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

      <!-- Status tabs + Search -->
      <div class="filter-bar">
        <div class="status-tabs">
          <?php
          $tabs = [
            'all'       => 'All (' . $allCount . ')',
            'published' => 'Published (' . ($counts['published'] ?? 0) . ')',
            'draft'     => 'Drafts (' . ($counts['draft'] ?? 0) . ')',
            'pending'   => 'Pending (' . ($counts['pending'] ?? 0) . ')',
            'scheduled' => 'Scheduled (' . ($counts['scheduled'] ?? 0) . ')',
          ];
          foreach ($tabs as $k => $label):
          ?>
            <a href="/admin/blogs.php?status=<?= $k ?>&search=<?= urlencode($search) ?>"
               class="<?= $statusFilter === $k ? 'active' : '' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </div>

        <form method="GET" action="/admin/blogs.php" style="display:flex;gap:8px;margin-left:auto;">
          <input type="hidden" name="status" value="<?= esc($statusFilter) ?>">
          <input type="search" name="search" class="form-control" placeholder="Search blogs…"
                 value="<?= esc($search) ?>" style="max-width:220px;">
          <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-search"></i></button>
          <?php if ($search): ?>
            <a href="/admin/blogs.php?status=<?= esc($statusFilter) ?>" class="btn btn-outline btn-sm">
              <i class="fas fa-times"></i>
            </a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Blogs Table -->
      <div class="card">
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width:36px"><input type="checkbox" id="check-all"></th>
                <th>Title</th>
                <th>Author</th>
                <th>Categories</th>
                <th>Status</th>
                <th>Views</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($blogs)): ?>
              <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:50px;">
                <?= $search ? 'No blogs found for "' . esc($search) . '".' : 'No blogs yet.' ?>
                <br><a href="/admin/blog-edit.php" style="color:#2C3E50;font-weight:600;">Create a blog post →</a>
              </td></tr>
            <?php else: foreach ($blogs as $b): ?>
              <tr>
                <td><input type="checkbox" name="ids[]" value="<?= $b['id'] ?>"></td>
                <td>
                  <a href="/admin/blog-edit.php?id=<?= $b['id'] ?>" style="font-weight:500;color:#2C3E50;">
                    <?= esc(mb_substr($b['title'], 0, 60)) . (mb_strlen($b['title']) > 60 ? '…' : '') ?>
                  </a>
                  <div style="font-size:11px;color:#9ca3af;margin-top:2px;">/<?= esc($b['slug']) ?></div>
                </td>
                <td style="font-size:12px;"><?= esc($b['author_name'] ?? '—') ?></td>
                <td style="font-size:12px;color:#6b7280;"><?= esc($b['categories'] ?? '—') ?></td>
                <td><span class="badge badge-<?= esc($b['status']) ?>"><?= esc($b['status']) ?></span></td>
                <td style="font-size:12px;"><?= number_format($b['views']) ?></td>
                <td style="font-size:12px;color:#6b7280;white-space:nowrap;">
                  <?= date('d M Y', strtotime($b['created_at'])) ?>
                  <?php if ($b['updated_at']): ?>
                    <br><span style="font-size:10px;">Updated <?= date('d M', strtotime($b['updated_at'])) ?></span>
                  <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                  <a href="/admin/blog-edit.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <?php if ($b['status'] === 'published'): ?>
                  <a href="/blog/<?= esc($b['slug']) ?>" target="_blank"
                     class="btn btn-sm" style="background:#dcfce7;color:#166534;" title="View">
                    <i class="fas fa-eye"></i>
                  </a>
                  <?php endif; ?>
                  <form id="del-<?= $b['id'] ?>" method="POST" action="/admin/blog-delete.php" style="display:inline;">
                    <input type="hidden" name="_token" value="<?= getCsrfToken() ?>">
                    <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">
                    <button type="button" class="btn btn-danger btn-sm" title="Delete"
                            onclick="confirmDelete('<?= esc(addslashes($b['title'])) ?>', 'del-<?= $b['id'] ?>')">
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

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?status=<?= esc($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <a href="?status=<?= esc($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"
             class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?status=<?= esc($statusFilter) ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <p style="font-size:12px;color:#9ca3af;text-align:center;margin-top:10px;">
        Showing <?= count($blogs) ?> of <?= $totalRows ?> blog posts
      </p>

    </div>
  </div>
</div>
<script src="/admin/assets/admin.js"></script>
</body>
</html>
