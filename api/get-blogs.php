<?php
/**
 * Public API — Get Blog Listing
 * GET /api/get-blogs.php
 *
 * Query params:
 *   page      int    (default 1)
 *   limit     int    (default 10, max 50)
 *   category  string slug
 *   search    string
 *   status    string (default 'published' — use 'all' only internally)
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/config.php';

$page     = max(1, (int)($_GET['page']  ?? 1));
$limit    = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset   = ($page - 1) * $limit;
$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search']   ?? '');
$status   = 'published'; // public API always returns published only

try {
    $pdo = getDBConnection();

    // Auto-publish any scheduled blogs whose time has passed
    $pdo->exec(
        "UPDATE blogs
            SET status = 'published'
          WHERE status = 'scheduled'
            AND scheduled_at IS NOT NULL
            AND scheduled_at <= NOW()"
    );

    $where  = ["b.status = 'published'"];
    $params = [];

    if ($category !== '') {
        $where[]  = 'EXISTS (
            SELECT 1 FROM blog_category_relations bcr
            JOIN blog_categories c ON bcr.category_id = c.id
            WHERE bcr.blog_id = b.id AND c.slug = ?
        )';
        $params[] = $category;
    }

    if ($search !== '') {
        $where[]  = '(b.title LIKE ? OR b.excerpt LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

    // Total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blogs b $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Blogs
    $stmt = $pdo->prepare(
        "SELECT b.id, b.title, b.slug, b.excerpt,
                b.feature_image, b.feature_image_alt,
                b.author_name, b.publish_date, b.created_at,
                b.read_time, b.views,
                GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS categories,
                GROUP_CONCAT(DISTINCT c.slug ORDER BY c.slug SEPARATOR ',')  AS category_slugs
           FROM blogs b
           LEFT JOIN blog_category_relations bcr ON b.id = bcr.blog_id
           LEFT JOIN blog_categories c ON bcr.category_id = c.id
         $whereSQL
          GROUP BY b.id
          ORDER BY b.publish_date DESC, b.created_at DESC
          LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Format dates
    $blogs = array_map(function ($row) {
        $date = $row['publish_date'] ?: $row['created_at'];
        return [
            'id'                => (int)$row['id'],
            'title'             => $row['title'],
            'slug'              => $row['slug'],
            'excerpt'           => $row['excerpt'],
            'feature_image'     => $row['feature_image'],
            'feature_image_alt' => $row['feature_image_alt'],
            'author_name'       => $row['author_name'],
            'date'              => $date ? date('d M Y', strtotime($date)) : '',
            'date_iso'          => $date ?: '',
            'read_time'         => (int)$row['read_time'],
            'views'             => (int)$row['views'],
            'categories'        => $row['categories'] ?: '',
            'category_slugs'    => $row['category_slugs'] ? explode(',', $row['category_slugs']) : [],
            'url'               => '/blog/' . rawurlencode($row['slug']),
        ];
    }, $rows);

    // Load all categories for filter bar
    $allCats = $pdo->query(
        "SELECT c.name, c.slug, COUNT(bcr.blog_id) AS cnt
           FROM blog_categories c
           LEFT JOIN blog_category_relations bcr ON c.id = bcr.category_id
           LEFT JOIN blogs b ON bcr.blog_id = b.id AND b.status = 'published'
          GROUP BY c.id
          ORDER BY cnt DESC, c.name"
    )->fetchAll();

    echo json_encode([
        'success'    => true,
        'data'       => $blogs,
        'categories' => $allCats,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / $limit),
            'has_next'    => $page * $limit < $total,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error.']);
}
