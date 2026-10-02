<?php
/**
 * GET /api/get-course-universities.php
 * Returns universities that offer a specific course (e.g., MBA, MCA, BBA)
 * Uses the courses_json field to filter universities by course name
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

try {
    $pdo = getDBConnection();

    // Get course parameter from URL (e.g., ?course=MBA)
    $courseFilter = isset($_GET['course']) ? trim($_GET['course']) : '';

    if (empty($courseFilter)) {
        echo json_encode(['success' => false, 'message' => 'Course parameter is required', 'data' => []]);
        exit;
    }

    // Fetch all active universities
    $rows = $pdo->query(
        "SELECT id, slug, name, short_name, logo, location, page_url, website_url,
                naac_grade, type, min_fee, max_fee, rating, featured, active,
                placement_rate, avg_salary, top_recruiters, rank_nirf, rank_outlook,
                ugc_approved, courses_json
         FROM universities
         WHERE active = 1
         ORDER BY sort_order ASC, featured DESC, rating DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // Filter universities that offer the specified course
    $filteredUniversities = [];
    foreach ($rows as $r) {
        $courses = $r['courses_json'] ? json_decode($r['courses_json'], true) : [];

        // Check if any course matches the filter (case-insensitive)
        $offersCourse = false;
        $courseData = null;

        foreach ($courses as $course) {
            if (isset($course['name']) && stripos($course['name'], $courseFilter) !== false) {
                $offersCourse = true;
                $courseData = $course;
                break;
            }
        }

        if ($offersCourse) {
            $filteredUniversities[] = [
                'id' => (int)$r['id'],
                'slug' => $r['slug'],
                'name' => $r['name'],
                'shortName' => $r['short_name'],
                'logo' => $r['logo'],
                'location' => $r['location'],
                'pageUrl' => $r['page_url'],
                'websiteUrl' => $r['website_url'],
                'naacGrade' => $r['naac_grade'],
                'type' => $r['type'],
                'minFee' => (int)$r['min_fee'],
                'maxFee' => (int)$r['max_fee'],
                'rating' => (float)$r['rating'],
                'featured' => (bool)$r['featured'],
                'ugcApproved' => (bool)$r['ugc_approved'],
                'placementRate' => $r['placement_rate'] ? (int)$r['placement_rate'] : null,
                'avgSalary' => $r['avg_salary'] ? (float)$r['avg_salary'] : null,
                'topRecruiters' => $r['top_recruiters'] ? array_map('trim', explode(',', $r['top_recruiters'])) : [],
                'ranking' => [
                    'nirf' => $r['rank_nirf'] ? (int)$r['rank_nirf'] : null,
                    'outlook' => $r['rank_outlook'] ? (int)$r['rank_outlook'] : null
                ],
                'course' => $courseData // Include the specific course data (fee, duration, specializations)
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $filteredUniversities, 'count' => count($filteredUniversities)]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
