<?php
/**
 * DegreeDrishti — University Comparison API (MySQL backend)
 * Reads from the `universities` table in the shared MySQL DB.
 * Works on any shared PHP hosting (Hostinger, GoDaddy, etc.)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/config.php';   // getDBConnection()

// ── Params ─────────────────────────────────────────────────────────────────────
$search  = isset($_GET['search'])     ? trim($_GET['search'])   : '';
$minFee  = isset($_GET['minFee'])     ? (int)$_GET['minFee']    : 0;
$maxFee  = isset($_GET['maxFee'])     ? (int)$_GET['maxFee']    : PHP_INT_MAX;
$sortBy  = isset($_GET['sortBy'])     ? trim($_GET['sortBy'])    : '';
$naac    = isset($_GET['naac'])       ? trim($_GET['naac'])      : '';
$type    = isset($_GET['type'])       ? trim($_GET['type'])      : '';
$ugcOnly = isset($_GET['ugcApproved']) && $_GET['ugcApproved'] === 'true';
$ids     = isset($_GET['ids'])        ? array_filter(explode(',', $_GET['ids'])) : [];
$limit   = isset($_GET['limit'])      ? max(1,(int)$_GET['limit']) : 100;
$page    = isset($_GET['page'])       ? max(1,(int)$_GET['page'])  : 1;

// ── DB ─────────────────────────────────────────────────────────────────────────
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// ── Build WHERE ────────────────────────────────────────────────────────────────
$where  = ['active = 1'];
$params = [];

// Fetch by specific IDs (compare table)
if (!empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT * FROM universities WHERE id IN ($placeholders) AND active = 1"
    );
    $stmt->execute(array_values($ids));
    $rows = $stmt->fetchAll();
    echo json_encode(['success' => true, 'total' => count($rows), 'data' => array_map('mapRow', $rows)]);
    exit;
}

if ($search !== '') {
    $where[]  = '(name LIKE ? OR short_name LIKE ? OR location LIKE ?)';
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
if ($minFee > 0)          { $where[] = 'min_fee >= ?'; $params[] = $minFee; }
if ($maxFee < PHP_INT_MAX){ $where[] = 'min_fee <= ?'; $params[] = $maxFee; }
if ($naac !== '')         { $where[] = 'naac_grade = ?'; $params[] = $naac; }
if ($type !== '')         { $where[] = 'type = ?'; $params[] = $type; }
if ($ugcOnly)             { $where[] = 'ugc_approved = 1'; }

// ── Sort ───────────────────────────────────────────────────────────────────────
$orderMap = [
    'fee_asc'  => 'min_fee ASC',
    'fee_desc' => 'min_fee DESC',
    'rating'   => 'rating DESC',
    'ranking'  => 'rank_nirf ASC',
    'name'     => 'name ASC',
];
$orderBy = $orderMap[$sortBy] ?? 'featured DESC, sort_order ASC, rating DESC';

// ── Count ──────────────────────────────────────────────────────────────────────
$whereClause = implode(' AND ', $where);
$countStmt   = $pdo->prepare("SELECT COUNT(*) FROM universities WHERE $whereClause");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

// ── Fetch page ─────────────────────────────────────────────────────────────────
$offset = ($page - 1) * $limit;
$stmt   = $pdo->prepare(
    "SELECT * FROM universities WHERE $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?"
);
$stmt->execute(array_merge($params, [$limit, $offset]));
$rows = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'total'   => $total,
    'page'    => $page,
    'data'    => array_map('mapRow', $rows),
]);

// ── Row mapper (DB → frontend shape) ──────────────────────────────────────────
function mapRow(array $r): array {
    return [
        'id'                => (string)$r['id'],
        'slug'              => $r['slug'],
        'name'              => $r['name'],
        'shortName'         => $r['short_name'],
        'logo'              => $r['logo'],
        'established'       => $r['established'] ? (int)$r['established'] : null,
        'location'          => $r['location'],
        'type'              => $r['type'],
        'naacGrade'         => $r['naac_grade'],
        'ugcApproved'       => (bool)$r['ugc_approved'],
        'ranking'           => [
            'nirf'    => $r['rank_nirf']    ? (int)$r['rank_nirf']    : null,
            'outlook' => $r['rank_outlook'] ? (int)$r['rank_outlook'] : null,
        ],
        'courses'           => $r['courses_json'] ? json_decode($r['courses_json'], true) : [],
        'minFee'            => (int)$r['min_fee'],
        'maxFee'            => (int)$r['max_fee'],
        'admissionMode'     => $r['admission_mode'],
        'examAccepted'      => $r['exams_accepted'] ? array_map('trim', explode(',', $r['exams_accepted'])) : [],
        'highlights'        => $r['highlights']     ? array_map('trim', explode(',', $r['highlights']))     : [],
        'placementRate'     => $r['placement_rate'] ? (int)$r['placement_rate'] : null,
        'avgSalary'         => $r['avg_salary']     ? (float)$r['avg_salary']   : null,
        'topRecruiters'     => $r['top_recruiters'] ? array_map('trim', explode(',', $r['top_recruiters'])) : [],
        'emiAvailable'      => (bool)$r['emi_available'],
        'scholarshipAvailable' => (bool)$r['scholarship'],
        'lmsType'           => $r['lms_type'],
        'supportType'       => $r['support_types']  ? array_map('trim', explode(',', $r['support_types']))  : [],
        'websiteUrl'        => $r['website_url'],
        'pageUrl'           => $r['page_url'],
        'rating'            => $r['rating'] ? (float)$r['rating'] : 0,
        'reviewCount'       => (int)$r['review_count'],
        'featured'          => (bool)$r['featured'],
    ];
}
