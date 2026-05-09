<?php
/**
 * Sidebar include — shared by all admin pages
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$admin = getCurrentAdmin();
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div>
      <span>DegreeDrishti</span>
      <small>Admin CMS</small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Main</div>
    <a href="/admin/dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>

    <div class="nav-section-title">Blog</div>
    <a href="/admin/blog-edit.php" class="<?= $currentPage === 'blog-edit.php' && empty($_GET['id']) ? 'active' : '' ?>">
      <i class="fas fa-plus-circle"></i> New Blog Post
    </a>
    <a href="/admin/blogs.php" class="<?= $currentPage === 'blogs.php' ? 'active' : '' ?>">
      <i class="fas fa-list"></i> All Blogs
    </a>
    <a href="/admin/categories.php" class="<?= $currentPage === 'categories.php' ? 'active' : '' ?>">
      <i class="fas fa-tags"></i> Categories
    </a>

    <div class="nav-section-title">Universities</div>
    <a href="/admin/universities.php" class="<?= $currentPage === 'universities.php' && empty($_GET['edit']) && empty($_GET['add']) ? 'active' : '' ?>">
      <i class="fas fa-list"></i> All Universities
    </a>
    <a href="/admin/universities.php?add=1" class="<?= $currentPage === 'universities.php' && isset($_GET['add']) ? 'active' : '' ?>">
      <i class="fas fa-plus-circle"></i> Add University
    </a>
    <a href="/compare" target="_blank">
      <i class="fas fa-balance-scale"></i> Compare Page
    </a>

    <div class="nav-section-title">Website</div>
    <a href="/" target="_blank">
      <i class="fas fa-globe"></i> View Website
    </a>
    <a href="/blog.php" target="_blank">
      <i class="fas fa-newspaper"></i> View Blog
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="/admin/logout.php">
      <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>
  </div>
</aside>
