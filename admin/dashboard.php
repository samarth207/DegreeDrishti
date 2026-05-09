<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$admin = getCurrentAdmin();
$flash = getFlash();

try {
    $pdo = getDBConnection();

    // Stats
    $total     = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
    $published = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='published'")->fetchColumn();
    $draft     = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='draft'")->fetchColumn();
    $scheduled = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='scheduled'")->fetchColumn();
    $pending   = $pdo->query("SELECT COUNT(*) FROM blogs WHERE status='pending'")->fetchColumn();

    // Recent 8 blogs
    $recentBlogs = $pdo->query(
        "SELECT b.id, b.title, b.slug, b.status, b.created_at, b.views,
                GROUP_CONCAT(c.name SEPARATOR ', ') AS categories
           FROM blogs b
           LEFT JOIN blog_category_relations bcr ON b.id = bcr.blog_id
           LEFT JOIN blog_categories c ON bcr.category_id = c.id
          GROUP BY b.id
          ORDER BY b.created_at DESC
          LIMIT 8"
    )->fetchAll();

    // Total views
    $totalViews = $pdo->query("SELECT COALESCE(SUM(views),0) FROM blogs")->fetchColumn();

} catch (Exception $e) {
    $total = $published = $draft = $scheduled = $pending = 0;
    $recentBlogs = [];
    $totalViews  = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard — DegreeDrishti Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-wrapper">
  <!-- Sidebar -->
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <!-- Main -->
  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button id="sidebar-toggle" class="btn btn-outline btn-sm" style="display:none"><i class="fas fa-bars"></i></button>
        <span class="topbar-title">Dashboard</span>
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

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon navy"><i class="fas fa-file-alt"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $total ?></div>
            <div class="stat-label">Total Blogs</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $published ?></div>
            <div class="stat-label">Published</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="fas fa-pen"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $draft ?></div>
            <div class="stat-label">Drafts</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $scheduled ?></div>
            <div class="stat-label">Scheduled</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-eye"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= number_format($totalViews) ?></div>
            <div class="stat-label">Total Views</div>
          </div>
        </div>
      </div>

      <!-- Recent Blogs Table -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-list"></i> Recent Blog Posts</span>
          <a href="/admin/blogs.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Title</th>
                <th>Categories</th>
                <th>Status</th>
                <th>Views</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($recentBlogs)): ?>
              <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:40px;">
                No blogs yet. <a href="/admin/blog-edit.php" style="color:#2C3E50;font-weight:600;">Create your first blog →</a>
              </td></tr>
            <?php else: foreach ($recentBlogs as $b): ?>
              <tr>
                <td>
                  <a href="/admin/blog-edit.php?id=<?= $b['id'] ?>" style="font-weight:500;color:#2C3E50;">
                    <?= esc(mb_substr($b['title'], 0, 55)) . (mb_strlen($b['title']) > 55 ? '…' : '') ?>
                  </a>
                </td>
                <td style="font-size:12px;color:#6b7280;"><?= esc($b['categories'] ?? '—') ?></td>
                <td><span class="badge badge-<?= esc($b['status']) ?>"><?= esc($b['status']) ?></span></td>
                <td><?= number_format($b['views']) ?></td>
                <td style="font-size:12px;color:#6b7280;"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                <td>
                  <a href="/admin/blog-edit.php?id=<?= $b['id'] ?>" class="btn btn-outline btn-sm">
                    <i class="fas fa-edit"></i>
                  </a>
                  <?php if ($b['status'] === 'published'): ?>
                  <a href="/blog/<?= esc($b['slug']) ?>" target="_blank" class="btn btn-sm" style="background:#dcfce7;color:#166534;">
                    <i class="fas fa-external-link-alt"></i>
                  </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div>
<script src="/admin/assets/admin.js"></script>
</body>
</html>
