<?php
/**
 * Public API — Get Single Blog Post
 * GET /api/get-blog-post.php?slug=my-post-slug
 *
 * Also increments view counter (once per session per post).
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Slug is required.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Auto-publish scheduled
    $pdo->exec(
        "UPDATE blogs SET status='published'
          WHERE status='scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()"
    );

    $stmt = $pdo->prepare(
        "SELECT b.*,
                GROUP_CONCAT(DISTINCT c.name  ORDER BY c.name  SEPARATOR ', ') AS category_names,
                GROUP_CONCAT(DISTINCT c.slug  ORDER BY c.slug  SEPARATOR ',')  AS category_slugs,
                GROUP_CONCAT(DISTINCT t.name  ORDER BY t.name  SEPARATOR ',')  AS tags
           FROM blogs b
           LEFT JOIN blog_category_relations bcr ON b.id = bcr.blog_id
           LEFT JOIN blog_categories c ON bcr.category_id = c.id
           LEFT JOIN blog_tag_relations btr ON b.id = btr.blog_id
           LEFT JOIN blog_tags t ON btr.tag_id = t.id
          WHERE b.slug = ? AND b.status = 'published'
          LIMIT 1"
    );
    $stmt->execute([$slug]);
    $blog = $stmt->fetch();

    if (!$blog) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Post not found.']);
        exit;
    }

    // Increment views (session-gated to prevent reload inflation)
    if (session_status() === PHP_SESSION_NONE) session_start();
    $viewKey = 'viewed_' . $blog['id'];
    if (empty($_SESSION[$viewKey])) {
        $pdo->prepare('UPDATE blogs SET views = views + 1 WHERE id = ?')->execute([$blog['id']]);
        $_SESSION[$viewKey] = true;
        $blog['views']++;
    }

    // Related posts (same category, exclude current)
    $related = $pdo->prepare(
        "SELECT DISTINCT b2.id, b2.title, b2.slug, b2.feature_image,
                         b2.feature_image_alt, b2.excerpt,
                         b2.publish_date, b2.created_at, b2.read_time
           FROM blogs b2
           JOIN blog_category_relations bcr2 ON b2.id = bcr2.blog_id
          WHERE bcr2.category_id IN (
                    SELECT category_id FROM blog_category_relations WHERE blog_id = ?
                )
            AND b2.id != ?
            AND b2.status = 'published'
          ORDER BY b2.created_at DESC
          LIMIT 3"
    );
    $related->execute([$blog['id'], $blog['id']]);
    $relatedPosts = $related->fetchAll();

    $date = $blog['publish_date'] ?: $blog['created_at'];

    echo json_encode([
        'success' => true,
        'data'    => [
            'id'                  => (int)$blog['id'],
            'title'               => $blog['title'],
            'slug'                => $blog['slug'],
            'excerpt'             => $blog['excerpt'],
            'content'             => $blog['content'],
            'feature_image'       => $blog['feature_image'],
            'feature_image_alt'   => $blog['feature_image_alt'],
            'feature_image_title' => $blog['feature_image_title'],
            'meta_title'          => $blog['meta_title']       ?: $blog['title'],
            'meta_description'    => $blog['meta_description'] ?: $blog['excerpt'],
            'focus_keyword'       => $blog['focus_keyword'],
            'author_name'         => $blog['author_name'],
            'author_bio'          => $blog['author_bio'],
            'author_image'        => $blog['author_image'],
            'author_page'         => $blog['author_page'],
            'category_names'      => $blog['category_names'] ?: '',
            'category_slugs'      => $blog['category_slugs'] ? explode(',', $blog['category_slugs']) : [],
            'tags'                => $blog['tags']            ? explode(',', $blog['tags'])            : [],
            'status'              => $blog['status'],
            'date'                => $date ? date('d M Y', strtotime($date)) : '',
            'date_iso'            => $date ?: '',
            'read_time'           => (int)$blog['read_time'],
            'views'               => (int)$blog['views'],
        ],
        'related' => array_map(function ($r) {
            $d = $r['publish_date'] ?: $r['created_at'];
            return [
                'id'                => (int)$r['id'],
                'title'             => $r['title'],
                'slug'              => $r['slug'],
                'feature_image'     => $r['feature_image'],
                'feature_image_alt' => $r['feature_image_alt'],
                'excerpt'           => $r['excerpt'],
                'date'              => $d ? date('d M Y', strtotime($d)) : '',
                'read_time'         => (int)$r['read_time'],
                'url'               => '/blog/' . rawurlencode($r['slug']),
            ];
        }, $relatedPosts),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
