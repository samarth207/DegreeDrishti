<?php
/**
 * Admin — Universities Management
 * List, Add, Edit, Delete universities from the MySQL DB.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$admin = getCurrentAdmin();
$flash = getFlash();
$pdo   = getDBConnection();

// ── DELETE ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    if (!validateCsrf($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM universities WHERE id = ?')->execute([$id]);
        setFlash('success', 'University deleted successfully.');
    }
    header('Location: /admin/universities.php'); exit;
}

// ── SAVE (insert / update) ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['_action'] ?? '', ['create','update'])) {
    if (!validateCsrf($_POST['_csrf'] ?? '')) { die('Invalid CSRF'); }

    $p = $_POST;
    $id = (int)($p['id'] ?? 0);

    // Build courses JSON from dynamic rows
    $courses = [];
    $cNames  = $p['course_name']  ?? [];
    $cDur    = $p['course_dur']   ?? [];
    $cFee    = $p['course_fee']   ?? [];
    $cTotal  = $p['course_total'] ?? [];
    $cSpecs  = $p['course_specs'] ?? [];
    foreach ($cNames as $i => $cn) {
        if (trim($cn) === '') continue;
        $specs = array_filter(array_map('trim', explode(',', $cSpecs[$i] ?? '')));
        $courses[] = [
            'name'            => trim($cn),
            'duration'        => trim($cDur[$i]   ?? ''),
            'fee'             => (int)($cFee[$i]   ?? 0),
            'totalFee'        => (int)($cTotal[$i] ?? 0),
            'specializations' => array_values($specs),
        ];
    }

    $data = [
        'slug'           => makeSlug($p['slug'] ?? $p['name'] ?? ''),
        'name'           => trim($p['name']            ?? ''),
        'short_name'     => trim($p['short_name']      ?? ''),
        'logo'           => trim($p['logo']            ?? ''),
        'established'    => $p['established']   ? (int)$p['established']   : null,
        'location'       => trim($p['location']        ?? ''),
        'type'           => $p['type']          ?? 'Private',
        'naac_grade'     => trim($p['naac_grade']      ?? ''),
        'ugc_approved'   => !empty($p['ugc_approved'])  ? 1 : 0,
        'rank_nirf'      => $p['rank_nirf']     ? (int)$p['rank_nirf']     : null,
        'rank_outlook'   => $p['rank_outlook']  ? (int)$p['rank_outlook']  : null,
        'min_fee'        => (int)($p['min_fee']   ?? 0),
        'max_fee'        => (int)($p['max_fee']   ?? 0),
        'courses_json'   => !empty($courses) ? json_encode($courses, JSON_UNESCAPED_UNICODE) : null,
        'admission_mode' => trim($p['admission_mode']  ?? 'Online'),
        'exams_accepted' => trim($p['exams_accepted']  ?? ''),
        'highlights'     => trim($p['highlights']      ?? ''),
        'placement_rate' => $p['placement_rate'] ? (int)$p['placement_rate'] : null,
        'avg_salary'     => $p['avg_salary']    ? (float)$p['avg_salary']    : null,
        'top_recruiters' => trim($p['top_recruiters']  ?? ''),
        'emi_available'  => !empty($p['emi_available'])  ? 1 : 0,
        'scholarship'    => !empty($p['scholarship'])    ? 1 : 0,
        'lms_type'       => trim($p['lms_type']        ?? ''),
        'support_types'  => trim($p['support_types']   ?? ''),
        'website_url'    => trim($p['website_url']     ?? ''),
        'page_url'       => trim($p['page_url']        ?? ''),
        'rating'         => $p['rating']        ? (float)$p['rating']        : 0,
        'review_count'   => (int)($p['review_count'] ?? 0),
        'featured'       => !empty($p['featured'])       ? 1 : 0,
        'active'         => !empty($p['active'])         ? 1 : 0,
        'sort_order'     => (int)($p['sort_order'] ?? 0),
    ];

    if ($id > 0) {
        // Update
        $sets = implode(', ', array_map(fn($k) => "`$k` = :$k", array_keys($data)));
        $stmt = $pdo->prepare("UPDATE universities SET $sets WHERE id = :id");
        $data['id'] = $id;
        $stmt->execute($data);
        setFlash('success', 'University updated successfully.');
    } else {
        // Insert
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($data)));
        $pdo->prepare("INSERT INTO universities ($cols) VALUES ($vals)")->execute($data);
        setFlash('success', 'University added successfully.');
    }
    header('Location: /admin/universities.php'); exit;
}

// ── LOAD for edit ────────────────────────────────────────────────────────────
$editRow = null;
if (isset($_GET['edit'])) {
    $editRow = $pdo->prepare('SELECT * FROM universities WHERE id = ?');
    $editRow->execute([(int)$_GET['edit']]);
    $editRow = $editRow->fetch() ?: null;
}

// ── LIST ─────────────────────────────────────────────────────────────────────
$universities = $pdo->query(
    'SELECT id, name, short_name, type, naac_grade, min_fee, max_fee, rating,
            featured, active, sort_order, location
     FROM universities ORDER BY sort_order ASC, id ASC'
)->fetchAll();

$totalUnis = count($universities);
$csrf = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Universities — DegreeDrishti Admin</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .uni-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .uni-form-grid .full { grid-column:1/-1; }
    .course-row { display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr 2fr auto; gap:8px; align-items:center; background:#f8f9ff; padding:10px 12px; border-radius:8px; margin-bottom:8px; }
    .course-row input { padding:7px 10px; border:1.5px solid #e0e0e0; border-radius:7px; font-size:13px; font-family:inherit; width:100%; }
    .btn-remove-course { background:#fee2e2; border:none; color:#dc2626; width:30px; height:30px; border-radius:6px; cursor:pointer; font-size:14px; }
    .tag-hint { font-size:11px; color:#9ca3af; margin-top:3px; }
    .toggle-row { display:flex; align-items:center; gap:10px; }
    .form-section-title { font-size:13px; font-weight:700; color:#2C3E50; text-transform:uppercase; letter-spacing:0.5px; margin:20px 0 10px; padding-bottom:6px; border-bottom:2px solid #eef0ff; }
  </style>
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <button id="sidebar-toggle" class="btn btn-outline btn-sm" style="display:none"><i class="fas fa-bars"></i></button>
        <span class="topbar-title"><i class="fas fa-university"></i> Universities</span>
      </div>
      <div class="topbar-right">
        <a href="?add=1" class="btn btn-yellow btn-sm"><i class="fas fa-plus"></i> Add University</a>
      </div>
    </div>

    <div class="page-body">
      <?php if ($flash): ?>
        <div class="alert alert-<?= esc($flash['type']) ?>" style="margin-bottom:18px"><?= esc($flash['message']) ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['add']) || $editRow): ?>
      <!-- ═══════════════════════════════════════════
           ADD / EDIT FORM
      ════════════════════════════════════════════ -->
      <?php
        $f = $editRow ?? [];
        $courses = !empty($f['courses_json']) ? json_decode($f['courses_json'], true) : [['name'=>'','duration'=>'','fee'=>'','totalFee'=>'','specializations'=>[]]];
        $isEdit  = !empty($f['id']);
      ?>
      <div class="card" style="margin-bottom:30px;">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? 'Edit: '.esc($f['name']) : 'Add New University' ?></span>
          <a href="/admin/universities.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        <div style="padding:24px;">
          <form method="POST" action="/admin/universities.php" id="uniForm">
            <input type="hidden" name="_csrf"   value="<?= esc($csrf) ?>">
            <input type="hidden" name="_action" value="<?= $isEdit ? 'update' : 'create' ?>">
            <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><?php endif; ?>

            <!-- Basic Info -->
            <div class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</div>
            <div class="uni-form-grid">
              <div class="form-group">
                <label>University Name *</label>
                <input type="text" name="name" value="<?= esc($f['name'] ?? '') ?>" required placeholder="e.g. Amity University Online">
              </div>
              <div class="form-group">
                <label>Short Name</label>
                <input type="text" name="short_name" value="<?= esc($f['short_name'] ?? '') ?>" placeholder="e.g. Amity">
              </div>
              <div class="form-group">
                <label>Slug (URL-safe)</label>
                <input type="text" name="slug" id="slugField" value="<?= esc($f['slug'] ?? '') ?>" placeholder="auto-generated from name">
              </div>
              <div class="form-group">
                <label>Established Year</label>
                <input type="number" name="established" value="<?= esc($f['established'] ?? '') ?>" min="1800" max="2030" placeholder="e.g. 2005">
              </div>
              <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?= esc($f['location'] ?? '') ?>" placeholder="e.g. Noida, UP">
              </div>
              <div class="form-group">
                <label>University Type</label>
                <select name="type">
                  <?php foreach(['Private','Government','Deemed'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($f['type'] ?? 'Private') === $t ? 'selected' : '' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>NAAC Grade</label>
                <select name="naac_grade">
                  <option value="">— Select —</option>
                  <?php foreach(['A++','A+','A','B++','B+','B'] as $g): ?>
                    <option value="<?= $g ?>" <?= ($f['naac_grade'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Logo Path / URL</label>
                <input type="text" name="logo" value="<?= esc($f['logo'] ?? '') ?>" placeholder="/images/university-logos/amity.png">
              </div>
              <div class="form-group">
                <label>Website URL</label>
                <input type="url" name="website_url" value="<?= esc($f['website_url'] ?? '') ?>" placeholder="https://www.university.com">
              </div>
              <div class="form-group">
                <label>Internal Page URL</label>
                <input type="text" name="page_url" value="<?= esc($f['page_url'] ?? '') ?>" placeholder="/amity-online-university">
              </div>
            </div>

            <!-- Rankings & Fees -->
            <div class="form-section-title"><i class="fas fa-trophy"></i> Rankings & Fees</div>
            <div class="uni-form-grid">
              <div class="form-group">
                <label>NIRF Rank</label>
                <input type="number" name="rank_nirf" value="<?= esc($f['rank_nirf'] ?? '') ?>" min="1" placeholder="e.g. 46">
              </div>
              <div class="form-group">
                <label>Outlook Rank</label>
                <input type="number" name="rank_outlook" value="<?= esc($f['rank_outlook'] ?? '') ?>" min="1" placeholder="e.g. 12">
              </div>
              <div class="form-group">
                <label>Min Annual Fee (₹)</label>
                <input type="number" name="min_fee" value="<?= esc($f['min_fee'] ?? 0) ?>" min="0" placeholder="e.g. 40000">
              </div>
              <div class="form-group">
                <label>Max Annual Fee (₹)</label>
                <input type="number" name="max_fee" value="<?= esc($f['max_fee'] ?? 0) ?>" min="0" placeholder="e.g. 75000">
              </div>
            </div>

            <!-- Courses -->
            <div class="form-section-title"><i class="fas fa-book"></i> Courses Offered</div>
            <div id="coursesWrap">
              <?php foreach($courses as $i => $c): ?>
              <div class="course-row">
                <input type="text"   name="course_name[]"  value="<?= esc($c['name'] ?? '') ?>"                        placeholder="Course name (e.g. MBA)">
                <input type="text"   name="course_dur[]"   value="<?= esc($c['duration'] ?? '') ?>"                    placeholder="Duration (e.g. 2 Years)">
                <input type="number" name="course_fee[]"   value="<?= esc($c['fee'] ?? '') ?>"                         placeholder="Annual Fee (₹)">
                <input type="number" name="course_total[]" value="<?= esc($c['totalFee'] ?? '') ?>"                    placeholder="Total Fee (₹)">
                <input type="text"   name="course_specs[]" value="<?= esc(implode(', ', $c['specializations'] ?? [])) ?>" placeholder="Specs (comma-separated)">
                <button type="button" class="btn-remove-course" onclick="removeCourse(this)" title="Remove"><i class="fas fa-times"></i></button>
              </div>
              <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline btn-sm" onclick="addCourse()" style="margin-bottom:4px;">
              <i class="fas fa-plus"></i> Add Course
            </button>

            <!-- Admission -->
            <div class="form-section-title"><i class="fas fa-file-alt"></i> Admission Details</div>
            <div class="uni-form-grid">
              <div class="form-group">
                <label>Admission Mode</label>
                <select name="admission_mode">
                  <?php foreach(['Online','Offline','Both'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($f['admission_mode'] ?? 'Online') === $m ? 'selected' : '' ?>><?= $m ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Exams Accepted <span class="tag-hint">(comma-separated)</span></label>
                <input type="text" name="exams_accepted" value="<?= esc($f['exams_accepted'] ?? '') ?>" placeholder="CAT, MAT, XAT, Direct">
              </div>
              <div class="form-group full">
                <label>Highlights <span class="tag-hint">(comma-separated)</span></label>
                <input type="text" name="highlights" value="<?= esc($f['highlights'] ?? '') ?>" placeholder="UGC-DEB Approved, NAAC A+, IBM Partnership">
              </div>
            </div>

            <!-- Placements -->
            <div class="form-section-title"><i class="fas fa-briefcase"></i> Placements</div>
            <div class="uni-form-grid">
              <div class="form-group">
                <label>Placement Rate (%)</label>
                <input type="number" name="placement_rate" value="<?= esc($f['placement_rate'] ?? '') ?>" min="0" max="100">
              </div>
              <div class="form-group">
                <label>Avg. Salary (LPA)</label>
                <input type="number" name="avg_salary" value="<?= esc($f['avg_salary'] ?? '') ?>" min="0" step="0.1">
              </div>
              <div class="form-group full">
                <label>Top Recruiters <span class="tag-hint">(comma-separated)</span></label>
                <input type="text" name="top_recruiters" value="<?= esc($f['top_recruiters'] ?? '') ?>" placeholder="TCS, Infosys, Amazon, Google">
              </div>
            </div>

            <!-- Other Details -->
            <div class="form-section-title"><i class="fas fa-cog"></i> Other Details</div>
            <div class="uni-form-grid">
              <div class="form-group">
                <label>LMS Platform</label>
                <input type="text" name="lms_type" value="<?= esc($f['lms_type'] ?? '') ?>" placeholder="e.g. Amity LMS">
              </div>
              <div class="form-group">
                <label>Support Types <span class="tag-hint">(comma-separated)</span></label>
                <input type="text" name="support_types" value="<?= esc($f['support_types'] ?? '') ?>" placeholder="Email, Phone, Chat, WhatsApp">
              </div>
              <div class="form-group">
                <label>Rating (out of 5)</label>
                <input type="number" name="rating" value="<?= esc($f['rating'] ?? '0') ?>" min="0" max="5" step="0.1">
              </div>
              <div class="form-group">
                <label>Review Count</label>
                <input type="number" name="review_count" value="<?= esc($f['review_count'] ?? 0) ?>" min="0">
              </div>
              <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?= esc($f['sort_order'] ?? 0) ?>" min="0">
              </div>
            </div>

            <!-- Toggles -->
            <div class="form-section-title"><i class="fas fa-toggle-on"></i> Flags</div>
            <div style="display:flex;flex-wrap:wrap;gap:24px;margin-bottom:24px;">
              <?php
              $flags = ['ugc_approved'=>'UGC Approved','emi_available'=>'EMI Available','scholarship'=>'Scholarship Available','featured'=>'Featured','active'=>'Active (visible)'];
              foreach($flags as $fname => $flabel):
                $checked = !empty($f[$fname]) ? 'checked' : '';
                // For new form, active defaults to checked
                if (!$isEdit && $fname === 'active') $checked = 'checked';
              ?>
              <label class="toggle-row" style="cursor:pointer;font-size:14px;font-weight:500;">
                <input type="checkbox" name="<?= $fname ?>" value="1" <?= $checked ?> style="width:16px;height:16px;accent-color:#667eea;">
                <?= $flabel ?>
              </label>
              <?php endforeach; ?>
            </div>

            <div style="display:flex;gap:12px;">
              <button type="submit" class="btn btn-yellow"><?= $isEdit ? '<i class="fas fa-save"></i> Save Changes' : '<i class="fas fa-plus"></i> Add University' ?></button>
              <a href="/admin/universities.php" class="btn btn-outline">Cancel</a>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <!-- ═══════════════════════════════════════════
           UNIVERSITIES LIST TABLE
      ════════════════════════════════════════════ -->
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-list"></i> All Universities (<?= $totalUnis ?>)</span>
          <a href="/compare" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-external-link-alt"></i> View Compare Page</a>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th><th>University</th><th>Type</th><th>NAAC</th>
                <th>Min Fee</th><th>Rating</th><th>Featured</th><th>Active</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($universities)): ?>
              <tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:40px;">
                No universities yet. <a href="?add=1" style="color:#2C3E50;font-weight:600;">Add the first one →</a>
              </td></tr>
            <?php else: foreach($universities as $u): ?>
              <tr>
                <td style="color:#9ca3af;font-size:13px;"><?= (int)$u['id'] ?></td>
                <td>
                  <div style="font-weight:600;color:#2C3E50;"><?= esc($u['name']) ?></div>
                  <div style="font-size:12px;color:#9ca3af;"><?= esc($u['location'] ?? '') ?></div>
                </td>
                <td><span class="badge badge-draft"><?= esc($u['type']) ?></span></td>
                <td><span class="badge badge-published"><?= esc($u['naac_grade'] ?? '—') ?></span></td>
                <td style="font-size:13px;">₹<?= number_format($u['min_fee']) ?></td>
                <td>
                  <span style="color:#FFD700;">★</span>
                  <strong><?= number_format((float)$u['rating'],1) ?></strong>
                </td>
                <td><?= $u['featured'] ? '<span class="badge badge-published">Yes</span>' : '<span style="color:#9ca3af;">—</span>' ?></td>
                <td><?= $u['active'] ? '<span class="badge badge-published">Live</span>' : '<span class="badge badge-draft">Hidden</span>' ?></td>
                <td>
                  <a href="?edit=<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?= esc(addslashes($u['name'])) ?>?')">
                    <input type="hidden" name="_csrf"   value="<?= esc($csrf) ?>">
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id"      value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:none;" title="Delete"><i class="fas fa-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div>

<script src="/admin/assets/admin.js"></script>
<script>
// Auto-generate slug from name
const nameInput = document.querySelector('input[name="name"]');
const slugField = document.getElementById('slugField');
if (nameInput && slugField && !slugField.value) {
  nameInput.addEventListener('input', () => {
    slugField.value = nameInput.value.toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/[\s]+/g, '-')
      .replace(/-+/g, '-').trim();
  });
}

// Add course row
function addCourse() {
  const wrap = document.getElementById('coursesWrap');
  const div  = document.createElement('div');
  div.className = 'course-row';
  div.innerHTML = `
    <input type="text"   name="course_name[]"  placeholder="Course name (e.g. MBA)">
    <input type="text"   name="course_dur[]"   placeholder="Duration (e.g. 2 Years)">
    <input type="number" name="course_fee[]"   placeholder="Annual Fee (₹)">
    <input type="number" name="course_total[]" placeholder="Total Fee (₹)">
    <input type="text"   name="course_specs[]" placeholder="Specs (comma-separated)">
    <button type="button" class="btn-remove-course" onclick="removeCourse(this)"><i class="fas fa-times"></i></button>`;
  wrap.appendChild(div);
}
function removeCourse(btn) {
  btn.closest('.course-row').remove();
}
</script>
</body>
</html>
