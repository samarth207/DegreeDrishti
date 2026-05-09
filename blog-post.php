<?php
/**
 * Individual Blog Post — DegreeDrishti
 * Full SEO-optimised, server-side rendered blog post
 */
require_once __DIR__ . '/api/config.php';

$slug = trim($_GET['slug'] ?? '');

// Redirect to blog listing if no slug
if ($slug === '') {
    header('Location: /blog', true, 301);
    exit;
}

// -------------------------------------------------------
// Load blog post
// -------------------------------------------------------
$blog         = null;
$relatedPosts = [];

try {
    $pdo = getDBConnection();

    // Auto-publish scheduled
    $pdo->exec("UPDATE blogs SET status='published'
                 WHERE status='scheduled' AND scheduled_at IS NOT NULL AND scheduled_at<=NOW()");

    $stmt = $pdo->prepare(
        "SELECT b.*,
                GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS category_names,
                GROUP_CONCAT(DISTINCT c.slug ORDER BY c.slug SEPARATOR ',')  AS category_slugs,
                GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',')  AS tags
           FROM blogs b
           LEFT JOIN blog_category_relations bcr ON b.id=bcr.blog_id
           LEFT JOIN blog_categories c ON bcr.category_id=c.id
           LEFT JOIN blog_tag_relations btr ON b.id=btr.blog_id
           LEFT JOIN blog_tags t ON btr.tag_id=t.id
          WHERE b.slug=? AND b.status='published'
          LIMIT 1"
    );
    $stmt->execute([$slug]);
    $blog = $stmt->fetch();

    if (!$blog) {
        http_response_code(404);
    } else {
        // Increment views (session-gated)
        if (session_status() === PHP_SESSION_NONE) session_start();
        $viewKey = 'viewed_' . $blog['id'];
        if (empty($_SESSION[$viewKey])) {
            $pdo->prepare('UPDATE blogs SET views=views+1 WHERE id=?')->execute([$blog['id']]);
            $_SESSION[$viewKey] = true;
        }

        // Related posts
        $rel = $pdo->prepare(
            "SELECT DISTINCT b2.title, b2.slug, b2.feature_image, b2.feature_image_alt,
                             b2.excerpt, b2.publish_date, b2.created_at, b2.read_time
               FROM blogs b2
               JOIN blog_category_relations bcr2 ON b2.id=bcr2.blog_id
              WHERE bcr2.category_id IN (
                        SELECT category_id FROM blog_category_relations WHERE blog_id=?
                    )
                AND b2.id != ?
                AND b2.status='published'
              ORDER BY b2.publish_date DESC
              LIMIT 3"
        );
        $rel->execute([$blog['id'], $blog['id']]);
        $relatedPosts = $rel->fetchAll();
    }

} catch (Exception $e) {
    http_response_code(500);
}

// -------------------------------------------------------
// If not found, show 404
// -------------------------------------------------------
if (!$blog): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><title>Post Not Found — DegreeDrishti</title>
  <link rel="stylesheet" href="/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div style="text-align:center;padding:100px 20px;">
    <h1 style="font-size:72px;color:#2C3E50;">404</h1>
    <p style="font-size:20px;margin:16px 0;">Blog post not found.</p>
    <a href="/blog" style="color:#2C3E50;font-weight:600;text-decoration:underline;">← Back to Blog</a>
  </div>
</body>
</html>
<?php exit; endif;

// -------------------------------------------------------
// Process content: generate TOC & render lead form blocks
// -------------------------------------------------------

/**
 * Extract H2/H3 headings, add ID anchors, build TOC array
 */
function processContent(string $html): array {
    if (empty($html)) return ['toc' => [], 'html' => ''];

    // Add IDs to h2/h3/h4 headings
    $usedSlugs = [];
    $toc       = [];

    $html = preg_replace_callback(
        '/<h([234])([^>]*)>(.*?)<\/h[234]>/si',
        function ($m) use (&$usedSlugs, &$toc) {
            $level   = (int)$m[1];
            $attrs   = $m[2];
            $text    = strip_tags($m[3]);
            $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
            $baseSlug = trim($baseSlug, '-');
            $baseSlug = mb_substr($baseSlug, 0, 60);
            $slug     = $baseSlug;

            // Ensure unique
            $i = 1;
            while (in_array($slug, $usedSlugs)) {
                $slug = $baseSlug . '-' . $i++;
            }
            $usedSlugs[] = $slug;

            if ($level <= 3) {
                $toc[] = ['level' => $level, 'text' => $text, 'id' => $slug];
            }

            // Preserve existing id or set new one
            if (!str_contains($attrs, 'id=')) {
                $attrs .= ' id="' . htmlspecialchars($slug) . '"';
            }
            return "<h{$level}{$attrs}>{$m[3]}</h{$level}>";
        },
        $html
    );

    return ['toc' => $toc, 'html' => $html];
}

/**
 * Replace blog-lead-form blocks with live form HTML
 */
function renderLeadForms(string $html): string {
    return preg_replace_callback(
        '/<div\s+class="blog-lead-form"\s+data-headline="([^"]*)"\s+data-btn-text="([^"]*)"[^>]*>.*?<\/div>/si',
        function ($m) {
            $headline = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $btnText  = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return buildLeadForm($headline, $btnText);
        },
        $html
    );
}

function buildLeadForm(string $headline, string $btnText): string {
    $h   = htmlspecialchars($headline);
    $btn = htmlspecialchars($btnText);
    return <<<HTML
<div class="blog-lead-form-rendered">
  <div class="blf-inner">
    <h3 class="blf-headline">{$h}</h3>
    <p class="blf-subtext">Fill in your details and our counsellors will guide you for free.</p>
    <form class="blf-form" method="POST" action="/api/save-popup-enquiry.php" onsubmit="return submitBlogForm(this)">
      <input type="hidden" name="form_type" value="blog_lead">
      <input type="hidden" name="source"    value="blog_inline_form">
      <div class="blf-fields">
        <div class="blf-field">
          <input type="text" name="full_name" placeholder="Your Name *" required autocomplete="name">
        </div>
        <div class="blf-field">
          <input type="tel"  name="phone"     placeholder="Phone Number *" required autocomplete="tel"
                 pattern="[6-9][0-9]{9}" title="10-digit mobile number">
        </div>
        <div class="blf-field">
          <select name="course_interest" required>
            <option value="">Select Course *</option>
            <option>MBA</option>
            <option>MCA</option>
            <option>BBA</option>
            <option>BCA</option>
            <option>BCom</option>
            <option>BA</option>
            <option>Executive MBA</option>
            <option>MSc Data Science</option>
            <option>Other</option>
          </select>
        </div>
      </div>
      <button type="submit" class="blf-btn">{$btn}</button>
    </form>
    <p class="blf-privacy"><i class="fas fa-lock"></i> 100% free, no spam. We respect your privacy.</p>
  </div>
</div>
HTML;
}

// Process the blog content
$processed   = processContent($blog['content'] ?? '');
$contentHtml = renderLeadForms($processed['html']);
$toc         = $processed['toc'];

// -------------------------------------------------------
// Meta
// -------------------------------------------------------
$metaTitle   = $blog['meta_title']       ?: mb_substr($blog['title'], 0, 60) . ' | DegreeDrishti';
$metaDesc    = $blog['meta_description'] ?: mb_substr(strip_tags($blog['excerpt']), 0, 160);
$canonicalUrl= 'https://www.degreedrishti.com/blog/' . rawurlencode($blog['slug']);
$pubDate     = $blog['publish_date'] ?: date('Y-m-d', strtotime($blog['created_at']));
$pubDateFmt  = date('F j, Y', strtotime($pubDate));
$tags        = $blog['tags'] ? explode(',', $blog['tags']) : [];
$categories  = $blog['category_names'] ?: '';
$primaryCat  = $categories ? explode(',', $categories)[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($metaTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
  <?php if ($blog['focus_keyword']): ?>
  <meta name="keywords" content="<?= htmlspecialchars($blog['focus_keyword'] . ', ' . implode(', ', $tags)) ?>">
  <?php endif; ?>
  <meta name="robots" content="index, follow">
  <meta name="author" content="<?= htmlspecialchars($blog['author_name']) ?>">
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

  <!-- Open Graph -->
  <meta property="og:title"       content="<?= htmlspecialchars($blog['title']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <meta property="og:url"         content="<?= htmlspecialchars($canonicalUrl) ?>">
  <meta property="og:type"        content="article">
  <meta property="og:site_name"   content="DegreeDrishti">
  <?php if ($blog['feature_image']): ?>
  <meta property="og:image"       content="https://www.degreedrishti.com<?= htmlspecialchars($blog['feature_image']) ?>">
  <meta property="og:image:alt"   content="<?= htmlspecialchars($blog['feature_image_alt']) ?>">
  <?php endif; ?>
  <meta property="article:published_time" content="<?= htmlspecialchars($pubDate) ?>">
  <meta property="article:author"         content="<?= htmlspecialchars($blog['author_name']) ?>">
  <?php if ($primaryCat): ?>
  <meta property="article:section"        content="<?= htmlspecialchars(trim($primaryCat)) ?>">
  <?php endif; ?>

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image">
  <meta name="twitter:title"       content="<?= htmlspecialchars($blog['title']) ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
  <?php if ($blog['feature_image']): ?>
  <meta name="twitter:image"       content="https://www.degreedrishti.com<?= htmlspecialchars($blog['feature_image']) ?>">
  <?php endif; ?>

  <!-- JSON-LD Article Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BlogPosting",
    "headline": <?= json_encode($blog['title']) ?>,
    "description": <?= json_encode($metaDesc) ?>,
    "url": <?= json_encode($canonicalUrl) ?>,
    "datePublished": <?= json_encode($pubDate) ?>,
    "dateModified": <?= json_encode($blog['updated_at'] ? date('Y-m-d', strtotime($blog['updated_at'])) : $pubDate) ?>,
    "author": {
      "@type": "Person",
      "name": <?= json_encode($blog['author_name']) ?>
      <?php if ($blog['author_page']): ?>, "url": <?= json_encode($blog['author_page']) ?><?php endif; ?>
    },
    "publisher": {
      "@type": "Organization",
      "name": "DegreeDrishti",
      "logo": { "@type": "ImageObject", "url": "https://www.degreedrishti.com/images/logo-icon.png" }
    }
    <?php if ($blog['feature_image']): ?>,
    "image": <?= json_encode('https://www.degreedrishti.com' . $blog['feature_image']) ?>
    <?php endif; ?>
    <?php if ($tags): ?>,
    "keywords": <?= json_encode(implode(', ', $tags)) ?>
    <?php endif; ?>
  }
  </script>

  <!-- BreadcrumbList Schema -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home",  "item": "https://www.degreedrishti.com/" },
      { "@type": "ListItem", "position": 2, "name": "Blog",  "item": "https://www.degreedrishti.com/blog" },
      { "@type": "ListItem", "position": 3, "name": <?= json_encode($blog['title']) ?>, "item": <?= json_encode($canonicalUrl) ?> }
    ]
  }
  </script>

  <!-- FAQ Schema (auto-generated if FAQ blocks present) -->
  <?php
  if (preg_match_all('/<h4\s+class="faq-question">(.*?)<\/h4>\s*<div\s+class="faq-answer"><p>(.*?)<\/p><\/div>/si',
                      $contentHtml, $faqMatches, PREG_SET_ORDER)):
  ?>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
      <?= implode(",\n      ", array_map(function($faq) {
          return json_encode([
              '@type'          => 'Question',
              'name'           => strip_tags($faq[1]),
              'acceptedAnswer' => ['@type'=>'Answer','text'=>strip_tags($faq[2])],
          ]);
      }, $faqMatches)) ?>
    ]
  }
  </script>
  <?php endif; ?>

  <link rel="icon" type="image/png" href="/images/favicon.png">
  <link rel="stylesheet" href="/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ---- Blog Post Layout ---- */
    .blog-post-wrap { max-width: 1100px; margin: 0 auto; padding: 40px 20px 60px; display: grid; grid-template-columns: 1fr 280px; gap: 48px; align-items: start; }
    @media(max-width:900px){ .blog-post-wrap{ grid-template-columns:1fr; } .blog-toc-sidebar{ display:none; } }

    /* Breadcrumb */
    .breadcrumb { font-size: 13px; color: #9ca3af; margin-bottom: 24px; }
    .breadcrumb a { color: #2C3E50; text-decoration: none; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb span { margin: 0 6px; }

    /* Feature image */
    article figure { margin: 0; padding: 0; }
    .blog-feature-img { width:100%; max-height:480px; object-fit:cover; border-radius:14px; margin-bottom:24px; }

    /* Post meta bar */
    .post-meta { display:flex; flex-wrap:wrap; align-items:center; gap:16px; margin-bottom:24px; font-size:13px; color:#6b7280; }
    .post-meta .cat-badge { background:#2C3E50; color:#fff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; text-decoration:none; }
    .post-meta .cat-badge:hover { background:#FFD700; color:#2C3E50; }

    /* Post content typography */
    .post-content { font-size:16.5px; line-height:1.9; color:#374151; }
    .post-content h2 { font-size:1.65em; color:#2C3E50; margin:2.2em 0 .7em; border-bottom:3px solid #FFD700; padding-bottom:8px; }
    .post-content h3 { font-size:1.3em;  color:#2C3E50; margin:1.8em 0 .5em; }
    .post-content h4 { font-size:1.1em;  color:#2C3E50; margin:1.4em 0 .4em; font-weight:600; }
    .post-content p  { margin-bottom:1.2em; }
    .post-content a  { color:#2C3E50; text-decoration:underline; }
    .post-content a:hover { color:#FFD700; }
    .post-content ul, .post-content ol { padding-left:24px; margin-bottom:1.2em; }
    .post-content li { margin-bottom:.5em; }
    .post-content blockquote { border-left:4px solid #FFD700; background:#fffbeb; padding:16px 22px; margin:24px 0; border-radius:0 10px 10px 0; font-style:italic; color:#374151; }
    .post-content img { max-width:100%; border-radius:10px; margin:16px 0; }
    .post-content table { border-collapse:collapse; width:100%; margin:24px 0; font-size:15px; }
    .post-content th { background:#2C3E50; color:#fff; padding:12px 14px; text-align:left; }
    .post-content td { padding:10px 14px; border:1px solid #dee2e6; }
    .post-content tr:nth-child(even) td { background:#f8fafc; }
    .post-content .blog-toc { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:22px 26px; margin:32px 0; }
    .post-content .toc-title { font-size:14px; font-weight:700; color:#2C3E50; margin-bottom:12px; }
    .post-content .toc-list { padding-left:20px; margin:0; }
    .post-content .toc-list li { padding:4px 0; font-size:14.5px; }
    .post-content .toc-list li a { color:#2C3E50; }

    /* FAQ Block */
    .post-content .faq-block { background:#f0f7ff; border-left:4px solid #FFD700; padding:24px 28px; border-radius:10px; margin:36px 0; }
    .post-content .faq-title { font-size:1.05em; font-weight:700; color:#2C3E50; margin-bottom:18px; }
    .post-content .faq-item { margin-bottom:20px; }
    .post-content .faq-item:last-child { margin-bottom:0; }
    .post-content .faq-question { font-weight:600; color:#2C3E50; font-size:.98em; margin-bottom:6px; }
    .post-content .faq-answer p { margin:0; color:#4b5563; }

    /* Lead Form Block */
    .blog-lead-form-rendered { background:linear-gradient(135deg,#2C3E50,#1a252f); color:#fff; border-radius:16px; padding:10px 10px; margin:14px 0; text-align:center; }
    .blf-inner { max-width:600px; margin:0 auto; }
    .blf-headline { font-size:1.5em; font-weight:700; margin-bottom:6px; color: white !important;}
    .blf-subtext { opacity:.75; font-size:14px; margin-bottom:24px; color: white; }
    .blf-fields { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:12px; margin-bottom:16px; }
    .blf-field input, .blf-field select { width:100%; padding:12px 16px; border:none; border-radius:8px; font-size:14px; color:#333; background:#fff; }
    .blf-field select { color:#333; }
    .blf-btn { background:#FFD700; color:#2C3E50; font-weight:700; font-size:15px; padding:13px 36px; border:none; border-radius:50px; cursor:pointer; transition:all .2s; }
    .blf-btn:hover { background:#e6c200; transform:translateY(-2px); }
    .blf-privacy { font-size:11px; opacity:.5; margin-top:12px; margin-bottom:0; }
    .blf-success { color:#4ade80; font-size:14px; margin-top:12px; display:none; }

    /* Author Box */
    .author-box { display:flex; align-items:flex-start; gap:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:26px; margin:44px 0 0; }
    .author-avatar { width:72px; height:72px; border-radius:50%; object-fit:cover; flex-shrink:0; border:3px solid #FFD700; }
    .author-initials { width:72px; height:72px; border-radius:50%; background:#2C3E50; color:#FFD700; display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:700; flex-shrink:0; }
    .author-name { font-size:15px; font-weight:700; color:#2C3E50; margin-bottom:4px; }
    .author-name a { color:#2C3E50; text-decoration:none; }
    .author-name a:hover { color:#FFD700; }
    .author-bio { font-size:13.5px; color:#6b7280; margin:0; line-height:1.6; }

    /* Tags */
    .post-tags { display:flex; flex-wrap:wrap; gap:8px; margin:28px 0; }
    .post-tag { padding:5px 14px; background:#f3f4f6; color:#4b5563; border-radius:20px; font-size:12px; text-decoration:none; }
    .post-tag:hover { background:#2C3E50; color:#fff; }

    /* Share buttons */
    .share-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin:32px 0; padding:20px; background:#f8fafc; border-radius:12px; }
    .share-bar span { font-size:13px; font-weight:600; color:#374151; }
    .share-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:500; color:#fff; text-decoration:none; transition:.2s; }
    .share-fb    { background:#1877f2; } .share-fb:hover    { background:#166fe5; }
    .share-tw    { background:#1da1f2; } .share-tw:hover    { background:#1a91da; }
    .share-wa    { background:#25d366; } .share-wa:hover    { background:#1db954; }
    .share-li    { background:#0a66c2; } .share-li:hover    { background:#004182; }
    .copy-link   { background:#6b7280; } .copy-link:hover   { background:#4b5563; cursor:pointer; border:none; }

    /* Related posts */
    .related-section { margin-top:52px; }
    .related-section h2 { font-size:1.4em; color:#2C3E50; margin-bottom:22px; border-bottom:2px solid #FFD700; padding-bottom:8px; }
    .related-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:20px; }
    .related-card { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.08); transition:.2s; text-decoration:none; color:inherit; }
    .related-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.12); }
    .related-img { width:100%; height:160px; object-fit:cover; }
    .related-body { padding:16px; }
    .related-body h4 { font-size:14px; font-weight:600; color:#2C3E50; margin-bottom:6px; line-height:1.4; }
    .related-body span { font-size:12px; color:#9ca3af; }

    /* TOC Sidebar */
    .blog-toc-sidebar { position:sticky; top:90px; }
    .blog-toc-sidebar .toc-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:22px; }
    .toc-box h3 { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#2C3E50; margin-bottom:14px; }
    .toc-box ol { padding-left:0; list-style:none; margin:0; }
    .toc-box li { margin-bottom:0; }
    .toc-box a { display:block; padding:6px 10px; font-size:13px; color:#4b5563; border-radius:6px; text-decoration:none; transition:.15s; border-left:2px solid transparent; }
    .toc-box a:hover, .toc-box a.active { background:#f0f4ff; color:#2C3E50; border-left-color:#FFD700; font-weight:500; }
    .toc-h3 a { padding-left:22px; font-size:12.5px; }
  </style>
</head>
<body>
  <!-- Navigation -->
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
            <div class="dropdown-category">Bachelor's Programs</div>
            <a href="/courses/bba.html">BBA</a>
            <a href="/courses/bca.html">BCA</a>
            <a href="/courses/bcom.html">BCom</a>
            <a href="/courses/ba.html">BA</a>
          </div>
        </li>
        <li><a href="/about.html">About Us</a></li>
        <li><a href="/blog" class="active">Blog</a></li>
        <li><a href="/contact.html">Contact Us</a></li>
      </ul>
      <div class="hamburger"><span></span><span></span><span></span></div>
    </div>
  </nav>

  <!-- Blog Post -->
  <main>
    <div class="blog-post-wrap">
      <!-- ===== ARTICLE ===== -->
      <article>
        <!-- Breadcrumb -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="/">Home</a><span>/</span>
          <a href="/blog">Blog</a><span>/</span>
          <?php if ($primaryCat): ?>
            <a href="/blog?category=<?= urlencode(strtolower(str_replace(' ','-',trim($primaryCat)))) ?>">
              <?= htmlspecialchars(trim($primaryCat)) ?>
            </a><span>/</span>
          <?php endif; ?>
          <span aria-current="page"><?= htmlspecialchars(mb_substr($blog['title'],0,50)).(mb_strlen($blog['title'])>50?'…':'') ?></span>
        </nav>

        <!-- Post Meta -->
        <div class="post-meta">
          <?php foreach (explode(',', $blog['category_names']) as $cat): $cat = trim($cat); if ($cat): ?>
          <a href="/blog?category=<?= urlencode(makeSlugSimple($cat)) ?>" class="cat-badge">
            <?= htmlspecialchars($cat) ?>
          </a>
          <?php endif; endforeach; ?>
          <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($pubDateFmt) ?></span>
          <span><i class="far fa-clock"></i> <?= (int)$blog['read_time'] ?> min read</span>
          <span><i class="far fa-eye"></i> <?= number_format((int)$blog['views']) ?> views</span>
          <?php if ($blog['author_name']): ?>
          <span><i class="far fa-user"></i> <?= htmlspecialchars($blog['author_name']) ?></span>
          <?php endif; ?>
        </div>

        <!-- Title H1 -->
        <h1 style="font-size:2em;line-height:1.3;color:#1a202c;margin-bottom:22px;">
          <?= htmlspecialchars($blog['title']) ?>
        </h1>

        <!-- Feature Image -->
        <?php if ($blog['feature_image']): ?>
        <figure>
          <img src="<?= htmlspecialchars($blog['feature_image']) ?>"
               alt="<?= htmlspecialchars($blog['feature_image_alt'] ?: $blog['title']) ?>"
               <?php if ($blog['feature_image_title']): ?>
               title="<?= htmlspecialchars($blog['feature_image_title']) ?>"
               <?php endif; ?>
               class="blog-feature-img"
               width="1200" height="628" loading="eager">
        </figure>
        <?php endif; ?>

        <!-- Content -->
        <div class="post-content" id="post-content">
          <?= $contentHtml ?>
        </div>

        <!-- Tags -->
        <?php if ($tags): ?>
        <div class="post-tags">
          <strong style="font-size:13px;color:#374151;margin-right:4px;"><i class="fas fa-hashtag"></i></strong>
          <?php foreach ($tags as $tag): $tag = trim($tag); if($tag): ?>
          <a href="/blog?search=<?= urlencode($tag) ?>" class="post-tag"><?= htmlspecialchars($tag) ?></a>
          <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Share Bar -->
        <?php
        $shareTitle = urlencode($blog['title']);
        $shareUrl   = urlencode($canonicalUrl);
        ?>
        <div class="share-bar">
          <span><i class="fas fa-share-alt"></i> Share:</span>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" class="share-btn share-fb">
            <i class="fab fa-facebook-f"></i> Facebook
          </a>
          <a href="https://twitter.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" target="_blank" rel="noopener" class="share-btn share-tw">
            <i class="fab fa-twitter"></i> Twitter
          </a>
          <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener" class="share-btn share-wa">
            <i class="fab fa-whatsapp"></i> WhatsApp
          </a>
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>" target="_blank" rel="noopener" class="share-btn share-li">
            <i class="fab fa-linkedin-in"></i> LinkedIn
          </a>
          <button class="share-btn copy-link" onclick="copyLink()">
            <i class="fas fa-link"></i> Copy Link
          </button>
        </div>

        <!-- Author Box -->
        <?php if ($blog['author_name']): ?>
        <div class="author-box">
          <?php if ($blog['author_image']): ?>
            <img src="<?= htmlspecialchars($blog['author_image']) ?>"
                 alt="<?= htmlspecialchars($blog['author_name']) ?>"
                 class="author-avatar">
          <?php else: ?>
            <div class="author-initials"><?= strtoupper(substr($blog['author_name'],0,1)) ?></div>
          <?php endif; ?>
          <div>
            <p class="author-name">
              <?php if ($blog['author_page']): ?>
                <a href="<?= htmlspecialchars($blog['author_page']) ?>" target="_blank" rel="noopener">
                  <?= htmlspecialchars($blog['author_name']) ?>
                </a>
              <?php else: ?>
                <?= htmlspecialchars($blog['author_name']) ?>
              <?php endif; ?>
            </p>
            <?php if ($blog['author_bio']): ?>
              <p class="author-bio"><?= htmlspecialchars($blog['author_bio']) ?></p>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Related Posts -->
        <?php if (!empty($relatedPosts)): ?>
        <section class="related-section">
          <h2>Related Articles</h2>
          <div class="related-grid">
            <?php foreach ($relatedPosts as $r):
              $rDate = $r['publish_date'] ?: $r['created_at'];
            ?>
            <a href="/blog/<?= urlencode($r['slug']) ?>" class="related-card">
              <?php if ($r['feature_image']): ?>
                <img src="<?= htmlspecialchars($r['feature_image']) ?>"
                     alt="<?= htmlspecialchars($r['feature_image_alt'] ?: $r['title']) ?>"
                     class="related-img" loading="lazy">
              <?php endif; ?>
              <div class="related-body">
                <h4><?= htmlspecialchars(mb_substr($r['title'],0,70)) ?></h4>
                <span><i class="far fa-clock"></i> <?= (int)$r['read_time'] ?> min
                  <?php if ($rDate): ?> · <?= date('M Y', strtotime($rDate)) ?><?php endif; ?>
                </span>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </section>
        <?php endif; ?>

      </article>

      <!-- ===== TOC SIDEBAR ===== -->
      <?php if (!empty($toc)): ?>
      <aside class="blog-toc-sidebar" aria-label="Table of contents">
        <div class="toc-box">
          <h3><i class="fas fa-list-ol" style="margin-right:6px;color:#FFD700;"></i> Table of Contents</h3>
          <ol>
            <?php foreach ($toc as $item): ?>
            <li class="<?= $item['level'] >= 3 ? 'toc-h3' : '' ?>">
              <a href="#<?= htmlspecialchars($item['id']) ?>"><?= htmlspecialchars($item['text']) ?></a>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>
      </aside>
      <?php endif; ?>

    </div><!-- /blog-post-wrap -->
  </main>

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
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 DegreeDrishti. All Rights Reserved. | <a href="#">Privacy Policy</a></p>
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
  <script>
  /* ---- TOC active highlight on scroll ---- */
  (function () {
    const links = document.querySelectorAll('.toc-box a');
    if (!links.length) return;
    const observer = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          links.forEach(l => l.classList.remove('active'));
          const active = document.querySelector('.toc-box a[href="#' + entry.target.id + '"]');
          if (active) active.classList.add('active');
        }
      });
    }, { rootMargin: '-20% 0px -70% 0px' });
    document.querySelectorAll('.post-content h2[id], .post-content h3[id]')
      .forEach(h => observer.observe(h));
  })();

  /* ---- Copy link button ---- */
  function copyLink() {
    navigator.clipboard.writeText(window.location.href).then(function () {
      const btn = document.querySelector('.copy-link');
      btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
      setTimeout(() => { btn.innerHTML = '<i class="fas fa-link"></i> Copy Link'; }, 2500);
    });
  }

  /* ---- Blog lead form AJAX submit ---- */
  function submitBlogForm(form) {
    const btn     = form.querySelector('.blf-btn');
    const origTxt = btn.textContent;
    btn.disabled    = true;
    btn.textContent = 'Sending…';

    const fd = new FormData(form);
    fd.append('page_url', window.location.href);

    fetch('/api/save-popup-enquiry.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        if (data.success || data.status === 'success') {
          form.innerHTML = '<p class="blf-success" style="display:block;font-size:15px;color:#4ade80;font-weight:600;">✅ Thank you! Our counsellor will contact you shortly.</p>';
        } else {
          btn.disabled = false;
          btn.textContent = origTxt;
          alert('Something went wrong. Please try again.');
        }
      })
      .catch(() => {
        btn.disabled    = false;
        btn.textContent = origTxt;
        alert('Network error. Please try again.');
      });

    return false;
  }
  </script>
</body>
</html>
<?php
// Helper: simple slug for category URL (used in template)
function makeSlugSimple(string $text): string {
    return trim(preg_replace('/[\s-]+/', '-', preg_replace('/[^a-z0-9\s-]/i', '', strtolower($text))), '-');
}
?>
