<?php
/**
 * GET /api/get-universities.php
 * Returns active universities for the navbar dropdown and other site uses.
 * Compatible with MySQL 5.7+ (no JSON_TABLE used).
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $pdo  = getDBConnection();
    $rows = $pdo->query(
        "SELECT id, slug, name, short_name, logo, location, page_url, website_url,
                naac_grade, type, min_fee, max_fee, rating, featured, active, courses_json
         FROM universities
         WHERE active = 1
         ORDER BY sort_order ASC, featured DESC, rating DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function($r) {
        // Count courses in PHP — avoids MySQL 8 JSON_TABLE dependency
        $courses = $r['courses_json'] ? json_decode($r['courses_json'], true) : [];
        $courseCount = is_array($courses) ? count($courses) : 0;

        return [
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
            'courseCount' => $courseCount,
        ];
    }, $rows);

    echo json_encode(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    // Return empty array so nav gracefully hides — never breaks the page
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
