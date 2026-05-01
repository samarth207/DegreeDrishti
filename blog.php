<?php
/**
 * Public Blog Listing Page — DegreeDrishti
 * Dynamic version loaded from MySQL database
 */
require_once __DIR__ . '/api/config.php';

// Auto-publish scheduled posts
try {
    $pdo = getDBConnection();
    $pdo->exec("UPDATE blogs SET status='published'
                 WHERE status='scheduled' AND scheduled_at IS NOT NULL AND scheduled_at<=NOW()");
} catch (Exception $e) { /* silent */ }

// Query params
$page        = max(1, (int)($_GET['page']     ?? 1));
$perPage     = 9;
$offset      = ($page - 1) * $perPage;
$catFilter   = trim($_GET['category'] ?? '');
$search      = trim($_GET['search']   ?? '');

$blogs       = [];
$allCats     = [];
$totalBlogs  = 0;

try {
    $where  = ["b.status='published'"];
    $params = [];

    if ($catFilter) {
        $where[]  = "EXISTS (SELECT 1 FROM blog_category_relations bcr
                              JOIN blog_categories c ON bcr.category_id=c.id
                             WHERE bcr.blog_id=b.id AND c.slug=?)";
        $params[] = $catFilter;
    }
    if ($search) {
        $where[]  = "(b.title LIKE ? OR b.excerpt LIKE ?)";
        $params[] = '%'.$search.'%';
        $params[] = '%'.$search.'%';
    }
    $whereSQL = 'WHERE '.implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b $whereSQL");
    $countStmt->execute($params);
    $totalBlogs = (int)$countStmt->fetchColumn();
    $totalPages  = (int)ceil($totalBlogs / $perPage);

    $stmt = $pdo->prepare(
        "SELECT b.id, b.title, b.slug, b.excerpt, b.feature_image, b.feature_image_alt,
                b.author_name, b.publish_date, b.created_at, b.read_time,
                GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS categories,
                (SELECT c2.name FROM blog_category_relations bcr2
                  JOIN blog_categories c2 ON bcr2.category_id=c2.id
                 WHERE bcr2.blog_id=b.id LIMIT 1) AS primary_cat
           FROM blogs b
           LEFT JOIN blog_category_relations bcr ON b.id=bcr.blog_id
           LEFT JOIN blog_categories c ON bcr.category_id=c.id
         $whereSQL
          GROUP BY b.id
          ORDER BY b.publish_date DESC, b.created_at DESC
          LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $blogs = $stmt->fetchAll();

    // All categories with published blog count
    $allCats = $pdo->query(
        "SELECT c.name, c.slug, COUNT(DISTINCT b.id) AS cnt
           FROM blog_categories c
           LEFT JOIN blog_category_relations bcr ON c.id=bcr.category_id
           LEFT JOIN blogs b ON bcr.blog_id=b.id AND b.status='published'
          GROUP BY c.id
          HAVING cnt > 0
          ORDER BY cnt DESC, c.name"
    )->fetchAll();

} catch (Exception $e) { /* render empty */ }

// Meta for this page
$pageTitle = 'Blog';
$metaDesc  = 'Stay updated with the latest education insights, career tips, online learning guides, and university news.';
if ($catFilter) {
    $catName   = '';
    foreach ($allCats as $c) { if ($c['slug'] === $catFilter) { $catName = $c['name']; break; } }
    $pageTitle = ($catName ?: ucwords(str_replace('-',' ',$catFilter))) . ' — Blog';
}
if ($search) $pageTitle = 'Search: ' . htmlspecialchars($search) . ' — Blog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> - DegreeDrishti | Education Insights &amp; Career Tips</title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta name="robots" content="index, follow">
  <link rel="canonical" href="https://www.degreedrishti.com/blog<?= $catFilter ? '?category='.urlencode($catFilter) : '' ?>">

  <meta property="og:title" content="DegreeDrishti Blog | Education &amp; Career Insights">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:url" content="https://www.degreedrishti.com/blog">
  <meta property="og:type" content="website">

  <link rel="icon" type="image/png" href="/images/favicon.png">
  <link rel="stylesheet" href="/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* Blog listing extra styles */
    .blog-post-url { color: inherit; text-decoration: none; }
    .blog-post-url:hover .blog-card { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,.15); }
    .blog-card { transition: transform .3s, box-shadow .3s; }
    .search-bar { display:flex; gap:10px; max-width:480px; margin:0 auto 28px; }
    .search-bar input { flex:1; padding:11px 16px; border:2px solid #e2e8f0; border-radius:50px; font-size:14px; outline:none; }
    .search-bar input:focus { border-color:#2C3E50; }
    .search-bar button { padding:11px 22px; background:#2C3E50; color:#fff; border:none; border-radius:50px; cursor:pointer; font-weight:500; }
    .blog-category-btn { cursor:pointer; border:2px solid #e2e8f0; background:#fff; padding:8px 20px; border-radius:50px; font-size:13px; font-weight:500; color:#666; transition:all .2s; }
    .blog-category-btn:hover, .blog-category-btn.active { background:#2C3E50; color:#fff; border-color:#2C3E50; }
    .no-blogs { text-align:center; padding:60px 20px; color:#9ca3af; }
    .no-blogs i { font-size:48px; display:block; margin-bottom:14px; }
    .pagination { display:flex; justify-content:center; gap:8px; margin-top:40px; }
    .pagination a, .pagination span {
      display:inline-flex; align-items:center; justify-content:center;
      width:38px; height:38px; border-radius:8px;
      font-size:14px; border:2px solid #e2e8f0;
      color:#666; background:#fff; text-decoration:none; transition:all .2s;
    }
    .pagination a:hover, .pagination a.active { background:#2C3E50; color:#fff; border-color:#2C3E50; }
    .result-count { text-align:center; color:#9ca3af; font-size:13px; margin-bottom:20px; }
  </style>
</head>
<body>
  <!-- Navigation (same as site-wide) -->
  <nav class="navbar">
    <div class="container">
      <a href="/" class="logo" aria-label="DegreeDrishti">
        <img src="/images/logo-icon.png" alt="DegreeDrishti Logo">
        <span>DegreeDrishti</span>
      </a>
      <ul class="nav-links">
        <li><a href="/">Home</a></li>
        <li class="dropdown">
          <a href="#" class="dropbtn">Programs <i class="fas fa-chevron-down"></i></a>
          <div class="dropdown-content">
            <div class="dropdown-category">Master's Programs</div>
            <a href="/courses/mba.html">MBA</a>
            <a href="/courses/mca.html">MCA</a>
            <a href="/courses/mcom.html">MCom</a>
            <a href="/courses/executive-mba.html">Executive MBA</a>
            <a href="/courses/msc-data-science.html">MSc Data Science</a>
            <a href="/courses/ma-journalism.html">MA Journalism</a>
            <div class="dropdown-category">Bachelor's Programs</div>
            <a href="/courses/bba.html">BBA</a>
            <a href="/courses/bca.html">BCA</a>
            <a href="/courses/bcom.html">BCom</a>
            <a href="/courses/ba.html">BA</a>
            <div class="dropdown-category">Integrated Programs</div>
            <a href="/courses/bca-mca.html">BCA + MCA</a>
            <a href="/courses/bba-mba.html">BBA + MBA</a>
            <div class="dropdown-category">Diploma Programs</div>
            <a href="/courses/diploma-digital-marketing.html">Diploma in Digital Marketing</a>
            <a href="/courses/diploma-financial-management.html">Diploma in Financial Management</a>
          </div>
        </li>
        <li><a href="/about.html">About Us</a></li>
        <li><a href="/blog" class="active">Blog</a></li>
        <li><a href="/contact.html">Contact Us</a></li>
      </ul>
      <div class="hamburger">
        <span></span><span></span><span></span>
      </div>
    </div>
  </nav>

  <!-- Page Header -->
  <section class="page-header" style="background:#fff;">
    <div class="container">
      <h1 style="color:#2C3E50;">Our Blog</h1>
      <p style="color:#666;">Education Insights, Career Tips &amp; Latest Updates</p>
    </div>
  </section>

  <!-- Blog Content -->
  <section class="blog-section">
    <div class="container">

      <!-- Search Bar -->
      <form method="GET" action="/blog" class="search-bar">
        <?php if ($catFilter): ?>
          <input type="hidden" name="category" value="<?= htmlspecialchars($catFilter) ?>">
        <?php endif; ?>
        <input type="search" name="search" placeholder="Search articles…"
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
      </form>

      <!-- Category Filter -->
      <div class="blog-categories">
        <a href="/blog" class="blog-category-btn <?= !$catFilter ? 'active' : '' ?>">All Posts</a>
        <?php foreach ($allCats as $cat): ?>
        <a href="/blog?category=<?= urlencode($cat['slug']) ?>"
           class="blog-category-btn <?= $catFilter === $cat['slug'] ? 'active' : '' ?>">
          <?= htmlspecialchars($cat['name']) ?>
          <small style="opacity:.6">(<?= $cat['cnt'] ?>)</small>
        </a>
        <?php endforeach; ?>
      </div>

      <!-- Results count -->
      <?php if ($search || $catFilter): ?>
        <p class="result-count">
          <?= $totalBlogs ?> <?= $totalBlogs === 1 ? 'result' : 'results' ?> found
          <?= $search   ? 'for "<strong>' . htmlspecialchars($search)   . '</strong>"' : '' ?>
          <?= $catFilter ? 'in <strong>'  . htmlspecialchars($catFilter) . '</strong>'  : '' ?>
          &nbsp;— <a href="/blog" style="color:#2C3E50;">Clear filters</a>
        </p>
      <?php endif; ?>

      <!-- Blog Grid -->
      <?php if (empty($blogs)): ?>
        <div class="no-blogs">
          <i class="fas fa-newspaper"></i>
          <p style="font-size:18px;font-weight:600;color:#374151;">No articles found</p>
          <p>Check back soon or <a href="/blog" style="color:#2C3E50;font-weight:600;">view all posts</a>.</p>
        </div>
      <?php else: ?>
      <div class="blog-grid">
        <?php foreach ($blogs as $b):
          $date = $b['publish_date'] ?: $b['created_at'];
          $dateStr = $date ? date('M j, Y', strtotime($date)) : '';
          $postUrl = '/blog-post.php?slug=' . urlencode($b['slug']);
        ?>
        <article class="blog-card">
          <a href="<?= $postUrl ?>" class="blog-post-url" aria-label="Read: <?= htmlspecialchars($b['title']) ?>">
            <div class="blog-image">
              <?php if ($b['feature_image']): ?>
                <img src="<?= htmlspecialchars($b['feature_image']) ?>"
                     alt="<?= htmlspecialchars($b['feature_image_alt'] ?: $b['title']) ?>"
                     loading="lazy" width="400" height="225">
              <?php else: ?>
                <img src="/images/blog/blog-default.jpg"
                     alt="<?= htmlspecialchars($b['title']) ?>" loading="lazy" width="400" height="225">
              <?php endif; ?>
              <?php if ($b['primary_cat']): ?>
                <span class="blog-category"><?= htmlspecialchars($b['primary_cat']) ?></span>
              <?php endif; ?>
            </div>
          </a>
          <div class="blog-content">
            <div class="blog-meta">
              <?php if ($dateStr): ?>
                <span><i class="far fa-calendar"></i> <?= $dateStr ?></span>
              <?php endif; ?>
              <span><i class="far fa-clock"></i> <?= (int)$b['read_time'] ?> min read</span>
            </div>
            <h3><a href="<?= $postUrl ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($b['title']) ?></a></h3>
            <?php if ($b['excerpt']): ?>
              <p><?= htmlspecialchars(mb_substr($b['excerpt'], 0, 140)) . (mb_strlen($b['excerpt']) > 140 ? '…' : '') ?></p>
            <?php endif; ?>
            <a href="<?= $postUrl ?>" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php if (isset($totalPages) && $totalPages > 1): ?>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>">
            <i class="fas fa-chevron-left"></i>
          </a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
             class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>">
            <i class="fas fa-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-content">
        <div class="footer-section">
          <h3>DegreeDrishti</h3>
          <p>Your gateway to quality education and a brighter future. India's most trusted online education platform.</p>
          <div class="footer-social">
            <a href="https://www.facebook.com/degreedrishti.online" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://www.linkedin.com/company/degree-drishti" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
            <a href="https://www.instagram.com/degreedrishti" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
        <div class="footer-section">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/contact.html">Contact Us</a></li>
            <li><a href="/about.html">About Us</a></li>
            <li><a href="/blog">Blog</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Popular Programs</h4>
          <ul>
            <li><a href="/courses/mba.html">Online MBA</a></li>
            <li><a href="/courses/mca.html">Online MCA</a></li>
            <li><a href="/courses/bba.html">Online BBA</a></li>
            <li><a href="/courses/bca.html">Online BCA</a></li>
          </ul>
        </div>
        <div class="footer-section">
          <h4>Contact Us</h4>
          <p><i class="fas fa-phone"></i> +91 92665 30366</p>
          <p><i class="fas fa-envelope"></i> info@degreedrishti.com</p>
          <p><i class="fas fa-map-marker-alt"></i> C Block, Sector 2, Noida</p>
          <p><i class="fas fa-clock"></i> Mon-Sat: 9 AM - 6 PM</p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 DegreeDrishti. All Rights Reserved. |
           <a href="#">Privacy Policy</a> | <a href="#">Terms &amp; Conditions</a>
        </p>
      </div>
    </div>
  </footer>

  <div class="sticky-icons">
    <a href="https://wa.me/919266530366" target="_blank" class="sticky-icon whatsapp">
      <i class="fab fa-whatsapp"></i>
    </a>
    <a href="/contact.html" class="sticky-icon contact">
      <i class="fas fa-envelope"></i>
    </a>
  </div>

  <script src="/script.js"></script>
</body>
</html>
