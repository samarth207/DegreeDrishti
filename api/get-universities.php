<?php
/**
 * GET /api/get-universities.php
 * Returns active universities for the navbar dropdown and other site uses.
 * Used by index.php nav, compare page, and any JS that needs the list.
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $pdo  = getDBConnection();
    $rows = $pdo->query(
        "SELECT id, slug, name, short_name, logo, location, page_url, website_url,
                naac_grade, type, min_fee, max_fee, rating, featured, active,
                (SELECT COUNT(*) FROM JSON_TABLE(courses_json, '$[*]' COLUMNS(n VARCHAR(50) PATH '$.name')) j) AS course_count
         FROM universities
         WHERE active = 1
         ORDER BY sort_order ASC, featured DESC, rating DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(fn($r) => [
        'id'          => (int)$r['id'],
        'slug'        => $r['slug'],
        'name'        => $r['name'],
        'shortName'   => $r['short_name'],
        'logo'        => $r['logo'],
        'location'    => $r['location'],
        'pageUrl'     => $r['page_url'],
        'websiteUrl'  => $r['website_url'],
        'naacGrade'   => $r['naac_grade'],
        'type'        => $r['type'],
        'minFee'      => (int)$r['min_fee'],
        'maxFee'      => (int)$r['max_fee'],
        'rating'      => (float)$r['rating'],
        'featured'    => (bool)$r['featured'],
        'courseCount' => (int)$r['course_count'],
    ], $rows);

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    // Fallback: return static list so the site never breaks
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
