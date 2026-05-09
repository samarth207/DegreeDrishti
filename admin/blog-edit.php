<?php
/**
 * Blog Editor — Create & Edit
 * Full-featured CMS editor with TinyMCE
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$admin  = getCurrentAdmin();
$blogId = (int)($_GET['id'] ?? 0);
$isEdit = $blogId > 0;
$flash  = getFlash();
$errors = [];

// Defaults
$blog = [
    'id'                  => 0,
    'title'               => '',
    'slug'                => '',
    'excerpt'             => '',
    'content'             => '',
    'feature_image'       => '',
    'feature_image_alt'   => '',
    'feature_image_title' => '',
    'meta_title'          => '',
    'meta_description'    => '',
    'focus_keyword'       => '',
    'primary_keyword'     => '',
    'author_name'         => $admin['full_name'],
    'author_bio'          => '',
    'author_image'        => '',
    'author_page'         => '',
    'status'              => 'draft',
    'publish_date'        => date('Y-m-d'),
    'publish_time'        => '09:00',
];
$selectedCategories = [];
$selectedTags       = [];

try {
    $pdo = getDBConnection();

    // Load categories
    $allCategories = $pdo->query(
        'SELECT id, name, slug FROM blog_categories ORDER BY name'
    )->fetchAll();

    // If editing, load existing blog
    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT * FROM blogs WHERE id = ? LIMIT 1');
        $stmt->execute([$blogId]);
        $loaded = $stmt->fetch();
        if ($loaded) {
            $blog = array_merge($blog, $loaded);
        } else {
            setFlash('danger', 'Blog post not found.');
            header('Location: /admin/blogs.php');
            exit;
        }

        // Load selected categories
        $catStmt = $pdo->prepare(
            'SELECT category_id FROM blog_category_relations WHERE blog_id = ?'
        );
        $catStmt->execute([$blogId]);
        $selectedCategories = array_column($catStmt->fetchAll(), 'category_id');

        // Load tags
        $tagStmt = $pdo->prepare(
            'SELECT t.name FROM blog_tag_relations btr
               JOIN blog_tags t ON btr.tag_id = t.id
              WHERE btr.blog_id = ?
              ORDER BY t.name'
        );
        $tagStmt->execute([$blogId]);
        $selectedTags = array_column($tagStmt->fetchAll(), 'name');
    }

    // -------------------------------------------------------
    // Handle POST (save)
    // -------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCsrf($_POST['_token'] ?? '')) {
            $errors[] = 'Invalid security token. Please try again.';
        } else {
            // Collect & sanitise inputs
            $title         = trim($_POST['title']                ?? '');
            $slug          = makeSlug(trim($_POST['slug']        ?? ''));
            $excerpt       = trim($_POST['excerpt']              ?? '');
            $content       = $_POST['content']                   ?? '';
            $metaTitle     = trim(mb_substr($_POST['meta_title'] ?? '', 0, 60));
            $metaDesc      = trim(mb_substr($_POST['meta_description'] ?? '', 0, 250));
            $focusKw       = trim($_POST['focus_keyword']        ?? '');
            $primaryKw     = trim($_POST['primary_keyword']      ?? '');
            $featureAlt    = trim($_POST['feature_image_alt']    ?? '');
            $featureTitle  = trim($_POST['feature_image_title']  ?? '');
            $authorName    = trim($_POST['author_name']          ?? 'DegreeDrishti Team');
            $authorBio     = trim($_POST['author_bio']           ?? '');
            $authorPage    = trim($_POST['author_page']          ?? '');
            $status        = in_array($_POST['status'] ?? '', ['draft','pending','published','scheduled'])
                              ? $_POST['status'] : 'draft';
            $publishDate   = $_POST['publish_date'] ?? null;
            $publishTime   = $_POST['publish_time'] ?? null;
            $cats          = $_POST['categories']   ?? [];
            $tagsRaw       = trim($_POST['tags_raw'] ?? '');
            $readTime      = estimateReadTime($content);

            // Validate
            if (!$title)  $errors[] = 'Blog title is required.';
            if (!$slug)   $errors[] = 'URL slug is required.';
            if (empty($cats)) $errors[] = 'Select at least one category.';

            // Slug uniqueness check
            if (!$errors) {
                $slugCheck = $pdo->prepare(
                    'SELECT id FROM blogs WHERE slug = ? AND id != ? LIMIT 1'
                );
                $slugCheck->execute([$slug, $blogId]);
                if ($slugCheck->fetch()) {
                    $errors[] = 'URL slug "' . htmlspecialchars($slug) . '" is already in use. Choose a different slug.';
                }
            }

            // Handle feature image upload
            $featureImagePath = $blog['feature_image'];
            if (!empty($_FILES['feature_image_file']['name'])) {
                $uploadResult = handleImageUpload('feature_image_file', 'feature');
                if ($uploadResult['success']) {
                    $featureImagePath = $uploadResult['web_path'];
                } else {
                    $errors[] = 'Feature image: ' . $uploadResult['error'];
                }
            }

            // Handle author image upload
            $authorImagePath = $blog['author_image'];
            if (!empty($_FILES['author_image_file']['name'])) {
                $uploadResult = handleImageUpload('author_image_file', 'author');
                if ($uploadResult['success']) {
                    $authorImagePath = $uploadResult['web_path'];
                } else {
                    $errors[] = 'Author image: ' . $uploadResult['error'];
                }
            }

            // Scheduled datetime
            $scheduledAt = null;
            if ($status === 'scheduled' && $publishDate && $publishTime) {
                $scheduledAt = $publishDate . ' ' . $publishTime . ':00';
            }

            if (!$errors) {
                $pdo->beginTransaction();
                try {
                    if ($isEdit) {
                        $stmt = $pdo->prepare(
                            'UPDATE blogs SET
                               title=?, slug=?, excerpt=?, content=?,
                               feature_image=?, feature_image_alt=?, feature_image_title=?,
                               meta_title=?, meta_description=?, focus_keyword=?, primary_keyword=?,
                               author_name=?, author_bio=?, author_image=?, author_page=?,
                               status=?, publish_date=?, publish_time=?, scheduled_at=?,
                               read_time=?
                             WHERE id=?'
                        );
                        $stmt->execute([
                            $title, $slug, $excerpt, $content,
                            $featureImagePath, $featureAlt, $featureTitle,
                            $metaTitle, $metaDesc, $focusKw, $primaryKw,
                            $authorName, $authorBio, $authorImagePath, $authorPage,
                            $status, $publishDate ?: null, $publishTime ?: null, $scheduledAt,
                            $readTime, $blogId
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO blogs (
                               title, slug, excerpt, content,
                               feature_image, feature_image_alt, feature_image_title,
                               meta_title, meta_description, focus_keyword, primary_keyword,
                               author_name, author_bio, author_image, author_page,
                               status, publish_date, publish_time, scheduled_at,
                               read_time, created_by
                             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                        );
                        $stmt->execute([
                            $title, $slug, $excerpt, $content,
                            $featureImagePath, $featureAlt, $featureTitle,
                            $metaTitle, $metaDesc, $focusKw, $primaryKw,
                            $authorName, $authorBio, $authorImagePath, $authorPage,
                            $status, $publishDate ?: null, $publishTime ?: null, $scheduledAt,
                            $readTime, $admin['id']
                        ]);
                        $blogId = (int)$pdo->lastInsertId();
                        $isEdit = true;
                    }

                    // Sync categories
                    $pdo->prepare('DELETE FROM blog_category_relations WHERE blog_id=?')->execute([$blogId]);
                    $catIns = $pdo->prepare('INSERT IGNORE INTO blog_category_relations (blog_id,category_id) VALUES(?,?)');
                    foreach ($cats as $catId) {
                        $catId = (int)$catId;
                        if ($catId > 0) $catIns->execute([$blogId, $catId]);
                    }

                    // Sync tags
                    $pdo->prepare('DELETE FROM blog_tag_relations WHERE blog_id=?')->execute([$blogId]);
                    if ($tagsRaw) {
                        $tagNames = array_unique(array_filter(array_map('trim', explode(',', $tagsRaw))));
                        $tagIns   = $pdo->prepare('INSERT IGNORE INTO blog_tags (name, slug) VALUES(?,?)');
                        $tagRel   = $pdo->prepare(
                            'INSERT IGNORE INTO blog_tag_relations (blog_id, tag_id)
                             SELECT ?, id FROM blog_tags WHERE name=?'
                        );
                        foreach ($tagNames as $tagName) {
                            if ($tagName) {
                                $tagIns->execute([$tagName, makeSlug($tagName)]);
                                $tagRel->execute([$blogId, $tagName]);
                            }
                        }
                        $selectedTags = $tagNames;
                    }

                    $pdo->commit();

                    // Reload blog data
                    $blog['id'] = $blogId;
                    $blog['title']      = $title; $blog['slug'] = $slug;
                    $blog['excerpt']    = $excerpt; $blog['content'] = $content;
                    $blog['meta_title'] = $metaTitle; $blog['meta_description'] = $metaDesc;
                    $blog['focus_keyword'] = $focusKw; $blog['primary_keyword'] = $primaryKw;
                    $blog['feature_image'] = $featureImagePath;
                    $blog['feature_image_alt'] = $featureAlt;
                    $blog['feature_image_title'] = $featureTitle;
                    $blog['author_name'] = $authorName; $blog['author_bio'] = $authorBio;
                    $blog['author_image'] = $authorImagePath; $blog['author_page'] = $authorPage;
                    $blog['status'] = $status; $blog['publish_date'] = $publishDate;
                    $blog['publish_time'] = $publishTime;
                    $selectedCategories = array_map('intval', $cats);

                    setFlash('success', $status === 'published'
                        ? '🎉 Blog post published successfully!'
                        : 'Blog post saved as ' . $status . '.');
                    header('Location: /admin/blog-edit.php?id=' . $blogId);
                    exit;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }

} catch (Exception $e) {
    $errors[] = 'Error: ' . $e->getMessage();
    $allCategories = [];
}

// -------------------------------------------------------
// Image upload helper (used above)
// -------------------------------------------------------
function handleImageUpload(string $fileKey, string $prefix): array {
    $file = $_FILES[$fileKey] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed.'];
    }
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedTypes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, WebP allowed.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Max file size is 5 MB.'];
    }
    $ext      = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime];
    $filename = $prefix . '-' . uniqid() . '.' . $ext;
    $uploadDir = SITE_ROOT . '/images/blog/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $destPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Could not save file.'];
    }
    return ['success' => true, 'web_path' => '/images/blog/uploads/' . $filename];
}

$pageTitle = $isEdit ? 'Edit: ' . mb_substr($blog['title'] ?: 'Blog Post', 0, 40) : 'New Blog Post';
$csrfToken = getCsrfToken();
$tagsDefault = implode(',', $selectedTags);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= esc($pageTitle) ?> — Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- TinyMCE 6 via jsDelivr — no API key required -->
  <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.5/tinymce.min.js"></script>
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <!-- Top Bar -->
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button id="sidebar-toggle" class="btn btn-outline btn-sm"><i class="fas fa-bars"></i></button>
        <span class="topbar-title"><?= esc($pageTitle) ?></span>
      </div>
      <div class="topbar-right">
        <?php if ($isEdit && $blog['status'] === 'published'): ?>
        <a href="/blog/<?= esc($blog['slug']) ?>" target="_blank"
           class="btn btn-outline btn-sm">
          <i class="fas fa-eye"></i> View Post
        </a>
        <?php endif; ?>
        <div class="topbar-user">
          <div class="avatar"><?= strtoupper(substr($admin['full_name'], 0, 1)) ?></div>
          <span><?= esc($admin['full_name']) ?></span>
        </div>
      </div>
    </div>

    <div class="page-body">

      <!-- Flash / Errors -->
      <?php if ($flash): ?>
        <div class="alert alert-<?= esc($flash['type']) ?>" style="margin-bottom:18px">
          <?= esc($flash['message']) ?>
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert alert-danger" style="margin-bottom:18px">
          <strong>Please fix the following errors:</strong><br>
          <?php foreach ($errors as $e): ?>
            • <?= esc($e) ?><br>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="blog-form" enctype="multipart/form-data"
            onsubmit="return validateBlogForm('blog-form')">
        <input type="hidden" name="_token" value="<?= $csrfToken ?>">

        <div class="editor-layout">

          <!-- ================================================
               MAIN COLUMN
               ================================================ -->
          <div class="editor-main">

            <!-- 1. META / SEO DATA -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-search"></i> SEO & Meta Data</div>
              <div class="panel-body">

                <!-- URL Slug -->
                <div class="form-group">
                  <label for="slug">URL Slug <span class="req">*</span>
                    <small style="color:#9ca3af;font-weight:400;">(auto-generated from title, editable)</small>
                  </label>
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:13px;color:#9ca3af;white-space:nowrap;">degreedrishti.com/blog/</span>
                    <input type="text" id="slug" name="slug" class="form-control"
                           placeholder="amity-online-mba-fees-2026"
                           value="<?= esc($blog['slug']) ?>" required
                           pattern="[a-z0-9-]+" title="Lowercase letters, numbers and hyphens only">
                  </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                  <!-- Meta Title -->
                  <div class="form-group">
                    <label for="meta_title">Meta Title
                      <small style="color:#9ca3af;font-weight:400;">(max 60 chars)</small>
                    </label>
                    <input type="text" id="meta_title" name="meta_title" class="form-control"
                           maxlength="60"
                           placeholder="Online MBA Fees 2026 | DegreeDrishti"
                           value="<?= esc($blog['meta_title']) ?>">
                    <div id="meta-title-counter" class="char-counter"></div>
                  </div>

                  <!-- Focus Keyword -->
                  <div class="form-group">
                    <label for="focus_keyword">Focus Keyword <span class="req">*</span></label>
                    <input type="text" id="focus_keyword" name="focus_keyword" class="form-control"
                           placeholder="e.g. online MBA fees 2026"
                           value="<?= esc($blog['focus_keyword']) ?>" required>
                  </div>
                </div>

                <!-- Meta Description -->
                <div class="form-group">
                  <label for="meta_description">Meta Description
                    <small style="color:#9ca3af;font-weight:400;">(200–250 chars ideal)</small>
                  </label>
                  <textarea id="meta_description" name="meta_description" class="form-control"
                            rows="3" maxlength="250"
                            placeholder="Used in search engine results. Include focus keyword naturally…"><?= esc($blog['meta_description']) ?></textarea>
                  <div id="meta-desc-counter" class="char-counter"></div>
                </div>

              </div>
            </div><!-- /meta panel -->


            <!-- 2. FEATURE IMAGE -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-image"></i> Feature Image</div>
              <div class="panel-body">
                <img id="feature-img-preview"
                     class="image-preview <?= $blog['feature_image'] ? 'show' : '' ?>"
                     src="<?= esc($blog['feature_image']) ?>"
                     alt="Feature image preview">

                <div class="image-upload-area" id="feature-upload-area">
                  <input type="file" id="feature_image_file" name="feature_image_file"
                         accept="image/jpeg,image/png,image/webp">
                  <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                  <p><strong>Click to upload</strong> feature image<br>
                     <small>JPG / WebP recommended — 1200×628 px — Max 5 MB</small></p>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px;">
                  <div class="form-group" style="margin-bottom:0">
                    <label for="feature_image_alt">Image Alt Text <span class="req">*</span></label>
                    <input type="text" id="feature_image_alt" name="feature_image_alt"
                           class="form-control" placeholder="Describe the image for screen readers & SEO"
                           value="<?= esc($blog['feature_image_alt']) ?>">
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label for="feature_image_title">Image Title <small style="color:#9ca3af">(optional)</small></label>
                    <input type="text" id="feature_image_title" name="feature_image_title"
                           class="form-control" placeholder="Title attribute for the image"
                           value="<?= esc($blog['feature_image_title']) ?>">
                  </div>
                </div>
              </div>
            </div><!-- /feature image panel -->


            <!-- 3. BLOG TITLE & EXCERPT -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-heading"></i> Blog Title & Excerpt</div>
              <div class="panel-body">
                <div class="form-group">
                  <label for="title">Blog Title (H1) <span class="req">*</span>
                    <small style="color:#9ca3af;font-weight:400;">(max 70 chars)</small>
                  </label>
                  <input type="text" id="title" name="title" class="form-control"
                         maxlength="70" required
                         placeholder="Online MBA Fees 2026: Complete University Comparison"
                         value="<?= esc($blog['title']) ?>">
                  <div id="blog-title-counter" class="char-counter"></div>
                </div>

                <div class="form-group" style="margin-bottom:0">
                  <label for="excerpt">Short Description / Excerpt <span class="req">*</span>
                    <small style="color:#9ca3af;font-weight:400;">(max 250 chars — used in listing)</small>
                  </label>
                  <textarea id="excerpt" name="excerpt" class="form-control" rows="3"
                            maxlength="250" required
                            placeholder="A concise summary of the blog post shown in listing pages and meta description suggestions…"><?= esc($blog['excerpt']) ?></textarea>
                  <div id="excerpt-counter" class="char-counter"></div>
                </div>
              </div>
            </div><!-- /title panel -->


            <!-- 4. CONTENT EDITOR -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-align-left"></i> Blog Content
                <small style="font-weight:400;color:#9ca3af;">Use H2 / H3 headings for structure. Use FAQ Block and Lead Form buttons in toolbar.</small>
              </div>
              <div class="panel-body" style="padding:0;">
                <textarea id="blog-content" name="content"><?= htmlspecialchars($blog['content'], ENT_NOQUOTES, 'UTF-8') ?></textarea>
              </div>
            </div><!-- /content panel -->


            <!-- 5. AUTHOR SECTION -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-user"></i> Author</div>
              <div class="panel-body">
                <div style="display:grid;grid-template-columns:80px 1fr;gap:16px;align-items:start;">
                  <div>
                    <img id="author-img-preview"
                         class="author-img-preview <?= $blog['author_image'] ? 'show' : '' ?>"
                         src="<?= esc($blog['author_image']) ?>"
                         alt="Author avatar">
                    <div style="position:relative;">
                      <label class="btn btn-outline btn-sm" style="cursor:pointer;width:100%;justify-content:center;">
                        <i class="fas fa-camera"></i>
                        <input type="file" id="author_image_file" name="author_image_file"
                               accept="image/jpeg,image/png,image/webp"
                               style="display:none;">
                      </label>
                    </div>
                  </div>
                  <div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                      <div class="form-group">
                        <label for="author_name">Author Name <span class="req">*</span></label>
                        <input type="text" id="author_name" name="author_name" class="form-control"
                               value="<?= esc($blog['author_name']) ?>"
                               placeholder="DegreeDrishti Team" required>
                      </div>
                      <div class="form-group">
                        <label for="author_page">Author Page URL <small style="color:#9ca3af">(optional)</small></label>
                        <input type="url" id="author_page" name="author_page" class="form-control"
                               value="<?= esc($blog['author_page']) ?>"
                               placeholder="https://…">
                      </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                      <label for="author_bio">Author Bio <small style="color:#9ca3af">(optional)</small></label>
                      <textarea id="author_bio" name="author_bio" class="form-control" rows="2"
                                placeholder="Education counsellor at DegreeDrishti with 8+ years experience…"><?= esc($blog['author_bio']) ?></textarea>
                    </div>
                  </div>
                </div>
              </div>
            </div><!-- /author panel -->

          </div><!-- /editor-main -->


          <!-- ================================================
               RIGHT SIDEBAR
               ================================================ -->
          <div class="editor-sidebar">

            <!-- Publish Panel -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-rocket"></i> Publish</div>
              <div class="panel-body">
                <div class="form-group">
                  <label for="status">Status</label>
                  <select id="status" name="status" class="form-control">
                    <option value="draft"     <?= $blog['status']==='draft'     ? 'selected':'' ?>>📝 Draft</option>
                    <option value="pending"   <?= $blog['status']==='pending'   ? 'selected':'' ?>>⏳ Pending Review</option>
                    <option value="published" <?= $blog['status']==='published' ? 'selected':'' ?>>✅ Published</option>
                    <option value="scheduled" <?= $blog['status']==='scheduled' ? 'selected':'' ?>>📅 Scheduled</option>
                  </select>
                </div>

                <div id="schedule-row">
                  <div class="form-group">
                    <label for="publish_date">Publish Date</label>
                    <input type="date" id="publish_date" name="publish_date" class="form-control"
                           value="<?= esc($blog['publish_date'] ?? date('Y-m-d')) ?>">
                  </div>
                  <div class="form-group">
                    <label for="publish_time">Publish Time</label>
                    <input type="time" id="publish_time" name="publish_time" class="form-control"
                           value="<?= esc($blog['publish_time'] ?? '09:00') ?>">
                  </div>
                </div>

                <?php if ($isEdit && $blog['updated_at']): ?>
                <p style="font-size:11px;color:#9ca3af;margin-bottom:10px;">
                  Last updated: <?= date('d M Y, h:i A', strtotime($blog['updated_at'])) ?>
                </p>
                <?php endif; ?>

                <button type="submit" name="save_action" value="save" class="btn btn-yellow btn-full">
                  <i class="fas fa-save"></i>
                  <?= $blog['status'] === 'published' ? 'Update Post' : 'Save' ?>
                </button>
                <button type="submit" name="save_action" value="publish" class="btn btn-success btn-full"
                        style="margin-top:8px;"
                        onclick="document.getElementById('status').value='published'">
                  <i class="fas fa-rocket"></i> Publish Now
                </button>
              </div>
            </div>

            <!-- Categories -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-folder"></i> Categories <span class="req">*</span></div>
              <div class="panel-body">
                <div class="category-list">
                  <?php foreach ($allCategories as $cat): ?>
                  <label class="category-item">
                    <input type="checkbox" name="categories[]"
                           value="<?= $cat['id'] ?>"
                           <?= in_array($cat['id'], $selectedCategories) ? 'checked' : '' ?>>
                    <?= esc($cat['name']) ?>
                  </label>
                  <?php endforeach; ?>
                  <?php if (empty($allCategories)): ?>
                    <p style="font-size:12px;color:#9ca3af;">
                      <a href="/admin/categories.php" style="color:#2C3E50;">Add categories first →</a>
                    </p>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Tags -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-hashtag"></i> Tags <small style="font-weight:400;color:#9ca3af;">(Enter to add)</small></div>
              <div class="panel-body">
                <div class="tag-input-wrap" id="tag-input-wrap">
                  <input type="text" class="tag-real-input" placeholder="Type tag, press Enter…">
                </div>
                <input type="hidden" id="tags-hidden" name="tags_raw"
                       value="<?= esc($tagsDefault) ?>">
                <p style="font-size:11px;color:#9ca3af;margin-top:6px;">
                  Add comma-separated tags: MBA, Online, 2026
                </p>
              </div>
            </div>

            <!-- SEO Analytics -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-chart-line"></i> SEO Score</div>
              <div class="panel-body">
                <div class="seo-score">
                  <div class="seo-score-bar">
                    <div class="seo-score-fill" id="seo-score-fill" style="width:0%;background:#e53e3e;"></div>
                  </div>
                  <div class="seo-score-label" id="seo-score-label">0/100</div>
                </div>
                <div id="seo-item-list" style="margin-top:12px;"></div>

                <div class="form-group" style="margin-top:14px;margin-bottom:0">
                  <label for="primary_keyword">Primary Keyword</label>
                  <input type="text" id="primary_keyword" name="primary_keyword"
                         class="form-control" placeholder="Main target keyword"
                         value="<?= esc($blog['primary_keyword']) ?>">
                </div>
              </div>
            </div>

            <!-- TOC Preview -->
            <div class="editor-panel">
              <div class="panel-header"><i class="fas fa-list-ol"></i> Table of Contents Preview</div>
              <div class="panel-body">
                <div class="toc-preview" id="toc-preview">
                  <em style="font-size:12px;color:#9ca3af;">
                    TOC is auto-generated from H2 / H3 headings.<br>
                    Use the "TOC" button in the editor toolbar to insert.
                  </em>
                </div>
              </div>
            </div>

          </div><!-- /editor-sidebar -->

        </div><!-- /editor-layout -->
      </form>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div><!-- /admin-wrapper -->

<script src="/admin/assets/admin.js"></script>
</body>
</html>
