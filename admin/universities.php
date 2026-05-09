<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
$admin = getCurrentAdmin();
$flash = getFlash();
$pdo   = getDBConnection();
$csrf  = getCsrfToken();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!validateCsrf($_POST['_csrf']??'')) die('Invalid CSRF');
    $act = $_POST['_action']??'';

    if ($act==='delete') {
        $id=(int)($_POST['id']??0);
        if($id>0) $pdo->prepare('DELETE FROM universities WHERE id=?')->execute([$id]);
        setFlash('success','University deleted.');
        header('Location: /admin/universities.php'); exit;
    }

    if (in_array($act,['create','update'])) {
        $p=$_POST; $id=(int)($p['id']??0);
        $courses=[];
        foreach(($p['course_name']??[]) as $i=>$cn) {
            if(trim($cn)==='') continue;
            $specs=array_values(array_filter(array_map('trim',explode(',',$p['course_specs'][$i]??''))));
            $courses[]=['name'=>trim($cn),'duration'=>trim($p['course_dur'][$i]??''),'fee'=>(int)($p['course_fee'][$i]??0),'totalFee'=>(int)($p['course_total'][$i]??0),'specializations'=>$specs];
        }
        $slug=makeSlug($p['slug']??$p['name']??'');
        $data=['slug'=>$slug,'name'=>trim($p['name']??''),'short_name'=>trim($p['short_name']??''),
            'logo'=>trim($p['logo']??''),'established'=>$p['established']?(int)$p['established']:null,
            'location'=>trim($p['location']??''),'type'=>$p['type']??'Private','naac_grade'=>trim($p['naac_grade']??''),
            'ugc_approved'=>!empty($p['ugc_approved'])?1:0,'rank_nirf'=>$p['rank_nirf']?(int)$p['rank_nirf']:null,
            'rank_outlook'=>$p['rank_outlook']?(int)$p['rank_outlook']:null,'min_fee'=>(int)($p['min_fee']??0),
            'max_fee'=>(int)($p['max_fee']??0),'courses_json'=>!empty($courses)?json_encode($courses,JSON_UNESCAPED_UNICODE):null,
            'admission_mode'=>trim($p['admission_mode']??'Online'),'exams_accepted'=>trim($p['exams_accepted']??''),
            'highlights'=>trim($p['highlights']??''),'placement_rate'=>$p['placement_rate']?(int)$p['placement_rate']:null,
            'avg_salary'=>$p['avg_salary']?(float)$p['avg_salary']:null,'top_recruiters'=>trim($p['top_recruiters']??''),
            'emi_available'=>!empty($p['emi_available'])?1:0,'scholarship'=>!empty($p['scholarship'])?1:0,
            'lms_type'=>trim($p['lms_type']??''),'support_types'=>trim($p['support_types']??''),
            'website_url'=>trim($p['website_url']??''),'page_url'=>trim($p['page_url']??''),
            'rating'=>$p['rating']?(float)$p['rating']:0,'review_count'=>(int)($p['review_count']??0),
            'featured'=>!empty($p['featured'])?1:0,'active'=>!empty($p['active'])?1:0,'sort_order'=>(int)($p['sort_order']??0)];
        if($id>0){
            $sets=implode(', ',array_map(fn($k)=>"`$k`=:$k",array_keys($data)));
            $data['id']=$id;
            $pdo->prepare("UPDATE universities SET $sets WHERE id=:id")->execute($data);
            setFlash('success','University updated.');
        } else {
            $cols=implode(',',array_map(fn($k)=>"`$k`",array_keys($data)));
            $vals=implode(',',array_map(fn($k)=>":$k",array_keys($data)));
            $pdo->prepare("INSERT INTO universities ($cols) VALUES ($vals)")->execute($data);
            setFlash('success','University added.');
        }
        header('Location: /admin/universities.php'); exit;
    }
}

$editRow=null;
if(isset($_GET['edit'])){
    $s2=$pdo->prepare('SELECT * FROM universities WHERE id=?');
    $s2->execute([(int)$_GET['edit']]);
    $editRow=$s2->fetch()?:null;
}
$universities=$pdo->query('SELECT id,name,short_name,type,naac_grade,min_fee,rating,featured,active,location FROM universities ORDER BY sort_order ASC,id ASC')->fetchAll();
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Universities — DegreeDrishti Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .uni-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#667eea;padding:14px 18px 8px;border-top:1px solid #f0f2f5;margin-top:4px}
    .uni-section-title:first-child{border-top:none;padding-top:4px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:0 18px 4px}
    .form-grid.cols-3{grid-template-columns:1fr 1fr 1fr}
    .form-grid.full{grid-template-columns:1fr}
    .form-group{margin-bottom:0}
    .form-group label{font-size:12px;font-weight:600;color:#555;display:flex;align-items:center;gap:5px;margin-bottom:5px}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;color:#333;background:#fafafa;transition:.2s}
    .form-group input:focus,.form-group select:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);background:#fff}
    .form-hint{font-size:11px;color:#9ca3af;margin-top:3px}
    .course-table{width:100%;border-collapse:collapse;margin:0 18px;width:calc(100% - 36px)}
    .course-table th{font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;padding:6px 8px;border-bottom:2px solid #f0f2f5;text-align:left}
    .course-table td{padding:6px 4px;vertical-align:middle}
    .course-table input{width:100%;padding:7px 9px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:12px;font-family:inherit}
    .course-table input:focus{outline:none;border-color:#667eea}
    .btn-remove-row{background:#fee2e2;border:none;color:#dc2626;width:28px;height:28px;border-radius:6px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center}
    .flag-grid{display:flex;flex-wrap:wrap;gap:16px;padding:12px 18px 16px}
    .flag-item{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:#2C3E50;cursor:pointer}
    .flag-item input{width:16px;height:16px;accent-color:#667eea;cursor:pointer}
    .form-actions{padding:18px;border-top:1px solid #f0f2f5;display:flex;gap:10px;background:#fafafa}
    .sidebar-stat{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid #f0f2f5;font-size:13px}
    .sidebar-stat:last-child{border-bottom:none}
    .sidebar-stat-val{font-weight:700;color:#2C3E50}
    .rating-stars{color:#FFD700}
    .table-name-block strong{font-size:13.5px;color:#2C3E50;display:block}
    .table-name-block span{font-size:11px;color:#9ca3af}
  </style>
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebar-toggle" class="btn btn-outline btn-sm" style="display:none"><i class="fas fa-bars"></i></button>
        <span class="topbar-title"><i class="fas fa-university" style="color:#667eea"></i> Universities</span>
      </div>
      <div class="topbar-right">
        <?php if(!isset($_GET['edit'])&&!isset($_GET['add'])): ?>
        <a href="?add=1" class="btn btn-yellow btn-sm"><i class="fas fa-plus"></i> Add University</a>
        <?php endif; ?>
        <div class="topbar-user">
          <div class="avatar"><?= strtoupper(substr($admin['full_name'],0,1)) ?></div>
          <span><?= esc($admin['full_name']) ?></span>
        </div>
      </div>
    </div>

    <div class="page-body">
      <?php if($flash): ?>
        <div class="alert alert-<?= esc($flash['type']) ?>" style="margin-bottom:18px"><?= esc($flash['message']) ?></div>
      <?php endif; ?>

<?php if(isset($_GET['add'])||$editRow): ?>
<?php
  $f=$editRow??[];
  $courses=!empty($f['courses_json'])?json_decode($f['courses_json'],true):[['name'=>'','duration'=>'','fee'=>'','totalFee'=>'','specializations'=>[]]];
  $isEdit=!empty($f['id']);
?>
<!-- -- EDITOR LAYOUT --------------------------- -->
<div style="display:flex;gap:8px;align-items:center;margin-bottom:20px">
  <a href="/admin/universities.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <h2 style="font-size:18px;font-weight:700;color:#2C3E50"><?= $isEdit?'Edit: '.esc($f['name']):'Add New University' ?></h2>
</div>

<form method="POST" action="/admin/universities.php" id="uniForm">
<input type="hidden" name="_csrf" value="<?= esc($csrf) ?>">
<input type="hidden" name="_action" value="<?= $isEdit?'update':'create' ?>">
<?php if($isEdit): ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><?php endif; ?>

<div class="editor-layout">
  <!-- Main Column -->
  <div class="editor-main">

    <!-- Basic Info -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-info-circle"></i> Basic Information</div>
      <div class="uni-section-title">Identity</div>
      <div class="form-grid">
        <div class="form-group">
          <label><i class="fas fa-university"></i> University Name <span style="color:#e53e3e">*</span></label>
          <input type="text" name="name" id="nameInput" value="<?= esc($f['name']??'') ?>" required placeholder="e.g. Amity University Online">
        </div>
        <div class="form-group">
          <label><i class="fas fa-tag"></i> Short Name</label>
          <input type="text" name="short_name" value="<?= esc($f['short_name']??'') ?>" placeholder="e.g. Amity">
        </div>
        <div class="form-group">
          <label><i class="fas fa-link"></i> Slug</label>
          <input type="text" name="slug" id="slugField" value="<?= esc($f['slug']??'') ?>" placeholder="auto-generated">
          <div class="form-hint">Auto-fills from name. Used in URLs.</div>
        </div>
        <div class="form-group">
          <label><i class="fas fa-calendar"></i> Established Year</label>
          <input type="number" name="established" value="<?= esc($f['established']??'') ?>" min="1800" max="2030" placeholder="e.g. 2005">
        </div>
        <div class="form-group">
          <label><i class="fas fa-map-marker-alt"></i> Location</label>
          <input type="text" name="location" value="<?= esc($f['location']??'') ?>" placeholder="e.g. Noida, UP">
        </div>
        <div class="form-group">
          <label><i class="fas fa-building"></i> University Type</label>
          <select name="type">
            <?php foreach(['Private','Government','Deemed'] as $t): ?>
            <option value="<?=$t?>" <?=($f['type']??'Private')===$t?'selected':''?>><?=$t?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="uni-section-title">Accreditation</div>
      <div class="form-grid cols-3">
        <div class="form-group">
          <label><i class="fas fa-certificate"></i> NAAC Grade</label>
          <select name="naac_grade">
            <option value="">— Select —</option>
            <?php foreach(['A++','A+','A','B++','B+','B'] as $g): ?>
            <option value="<?=$g?>" <?=($f['naac_grade']??'')===$g?'selected':''?>><?=$g?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-trophy"></i> NIRF Rank</label>
          <input type="number" name="rank_nirf" value="<?= esc($f['rank_nirf']??'') ?>" min="1" placeholder="e.g. 46">
        </div>
        <div class="form-group">
          <label><i class="fas fa-medal"></i> Outlook Rank</label>
          <input type="number" name="rank_outlook" value="<?= esc($f['rank_outlook']??'') ?>" min="1" placeholder="e.g. 12">
        </div>
      </div>

      <div class="uni-section-title">Fees</div>
      <div class="form-grid">
        <div class="form-group">
          <label><i class="fas fa-rupee-sign"></i> Min Annual Fee (?)</label>
          <input type="number" name="min_fee" value="<?= esc($f['min_fee']??0) ?>" min="0" placeholder="40000">
        </div>
        <div class="form-group">
          <label><i class="fas fa-rupee-sign"></i> Max Annual Fee (?)</label>
          <input type="number" name="max_fee" value="<?= esc($f['max_fee']??0) ?>" min="0" placeholder="75000">
        </div>
      </div>
    </div>

    <!-- Courses -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-book-open"></i> Courses Offered</div>
      <div style="padding:12px 18px 4px">
        <p style="font-size:12px;color:#9ca3af;margin-bottom:10px"><i class="fas fa-info-circle"></i> Add each course the university offers. Specializations are comma-separated.</p>
      </div>
      <table class="course-table" id="coursesTable">
        <thead><tr>
          <th style="width:18%">Course</th>
          <th style="width:13%">Duration</th>
          <th style="width:14%">Annual Fee ?</th>
          <th style="width:14%">Total Fee ?</th>
          <th>Specializations (comma-separated)</th>
          <th style="width:36px"></th>
        </tr></thead>
        <tbody id="coursesBody">
        <?php foreach($courses as $c): ?>
        <tr>
          <td><input type="text" name="course_name[]" value="<?= esc($c['name']??'') ?>" placeholder="MBA"></td>
          <td><input type="text" name="course_dur[]" value="<?= esc($c['duration']??'') ?>" placeholder="2 Years"></td>
          <td><input type="number" name="course_fee[]" value="<?= esc($c['fee']??'') ?>" placeholder="75000"></td>
          <td><input type="number" name="course_total[]" value="<?= esc($c['totalFee']??'') ?>" placeholder="150000"></td>
          <td><input type="text" name="course_specs[]" value="<?= esc(implode(', ',$c['specializations']??[])) ?>" placeholder="Finance, Marketing, HR"></td>
          <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()" title="Remove"><i class="fas fa-times"></i></button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <div style="padding:12px 18px 16px">
        <button type="button" class="btn btn-outline btn-sm" onclick="addCourseRow()"><i class="fas fa-plus"></i> Add Course</button>
      </div>
    </div>

    <!-- Placements -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-briefcase"></i> Placements & Recruiters</div>
      <div class="form-grid cols-3">
        <div class="form-group">
          <label><i class="fas fa-chart-line"></i> Placement Rate (%)</label>
          <input type="number" name="placement_rate" value="<?= esc($f['placement_rate']??'') ?>" min="0" max="100" placeholder="92">
        </div>
        <div class="form-group">
          <label><i class="fas fa-coins"></i> Avg Salary (LPA)</label>
          <input type="number" name="avg_salary" value="<?= esc($f['avg_salary']??'') ?>" step="0.1" placeholder="8.5">
        </div>
        <div class="form-group">
          <label><i class="fas fa-star"></i> Rating (out of 5)</label>
          <input type="number" name="rating" value="<?= esc($f['rating']??'0') ?>" step="0.1" min="0" max="5" placeholder="4.5">
        </div>
      </div>
      <div class="form-grid full" style="padding-bottom:16px">
        <div class="form-group">
          <label><i class="fas fa-building"></i> Top Recruiters <span class="form-hint" style="margin:0">(comma-separated)</span></label>
          <input type="text" name="top_recruiters" value="<?= esc($f['top_recruiters']??'') ?>" placeholder="TCS, Infosys, Amazon, Google, Deloitte">
        </div>
      </div>
    </div>

    <!-- Admission -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-file-alt"></i> Admission Details</div>
      <div class="form-grid">
        <div class="form-group">
          <label><i class="fas fa-desktop"></i> Admission Mode</label>
          <select name="admission_mode">
            <?php foreach(['Online','Offline','Both'] as $m): ?>
            <option value="<?=$m?>" <?=($f['admission_mode']??'Online')===$m?'selected':''?>><?=$m?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label><i class="fas fa-pen-nib"></i> Exams Accepted <span class="form-hint" style="margin:0">(comma-separated)</span></label>
          <input type="text" name="exams_accepted" value="<?= esc($f['exams_accepted']??'') ?>" placeholder="CAT, MAT, XAT, Direct">
        </div>
      </div>
      <div class="form-grid full" style="padding-bottom:16px">
        <div class="form-group">
          <label><i class="fas fa-list-check"></i> Highlights <span class="form-hint" style="margin:0">(comma-separated)</span></label>
          <input type="text" name="highlights" value="<?= esc($f['highlights']??'') ?>" placeholder="UGC-DEB Approved, NAAC A+, IBM Partnership, 200+ Placement Partners">
        </div>
      </div>
    </div>

  </div><!-- /editor-main -->

  <!-- Sidebar Column -->
  <div class="editor-sidebar">

    <!-- Publish -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-paper-plane"></i> Publish</div>
      <div class="form-actions" style="border-top:none;flex-direction:column">
        <button type="submit" class="btn btn-yellow btn-full btn-lg">
          <i class="fas fa-<?= $isEdit?'save':'plus' ?>"></i>
          <?= $isEdit?'Save Changes':'Add University' ?>
        </button>
        <a href="/admin/universities.php" class="btn btn-outline btn-full" style="justify-content:center">Cancel</a>
      </div>
      <div class="uni-section-title">Visibility Flags</div>
      <div class="flag-grid">
        <?php
        $flags=['active'=>['Active (publicly visible)','fa-eye'],'featured'=>['Featured on homepage','fa-star'],'ugc_approved'=>['UGC Approved','fa-check-shield'],'emi_available'=>['EMI Available','fa-credit-card'],'scholarship'=>['Scholarship Available','fa-hand-holding-usd']];
        foreach($flags as $fn=>[$fl,$fi]):
          $chk=!empty($f[$fn])||(!$isEdit&&$fn==='active')?'checked':'';
        ?>
        <label class="flag-item">
          <input type="checkbox" name="<?=$fn?>" value="1" <?=$chk?>>
          <i class="fas <?=$fi?>" style="color:#667eea;width:14px"></i> <?=$fl?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Links & Media -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-link"></i> Links & Media</div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
        <div class="form-group">
          <label><i class="fas fa-image"></i> Logo Path / URL</label>
          <input type="text" name="logo" value="<?= esc($f['logo']??'') ?>" placeholder="/images/university-logos/amity.png">
          <?php if(!empty($f['logo'])): ?>
          <img src="<?= esc($f['logo']) ?>" alt="Logo" style="width:60px;height:60px;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:4px;margin-top:8px;background:#f8f8f8">
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label><i class="fas fa-globe"></i> Website URL</label>
          <input type="url" name="website_url" value="<?= esc($f['website_url']??'') ?>" placeholder="https://university.com">
        </div>
        <div class="form-group">
          <label><i class="fas fa-file-code"></i> Internal Page URL</label>
          <input type="text" name="page_url" value="<?= esc($f['page_url']??'') ?>" placeholder="/amity-online-university">
          <div class="form-hint">Link to the dedicated university page on this site</div>
        </div>
      </div>
    </div>

    <!-- Stats & Order -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-sliders-h"></i> Settings</div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
        <div class="form-group">
          <label><i class="fas fa-laptop"></i> LMS Platform</label>
          <input type="text" name="lms_type" value="<?= esc($f['lms_type']??'') ?>" placeholder="e.g. Amity LMS">
        </div>
        <div class="form-group">
          <label><i class="fas fa-headset"></i> Support Types <span class="form-hint" style="margin:0">(comma-separated)</span></label>
          <input type="text" name="support_types" value="<?= esc($f['support_types']??'') ?>" placeholder="Email, Phone, Chat, WhatsApp">
        </div>
        <div class="form-group">
          <label><i class="fas fa-comments"></i> Review Count</label>
          <input type="number" name="review_count" value="<?= esc($f['review_count']??0) ?>" min="0">
        </div>
        <div class="form-group">
          <label><i class="fas fa-sort-numeric-up"></i> Sort Order</label>
          <input type="number" name="sort_order" value="<?= esc($f['sort_order']??0) ?>" min="0">
          <div class="form-hint">Lower number = shown first</div>
        </div>
      </div>
    </div>

    <?php if($isEdit): ?>
    <!-- Quick Stats -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-chart-bar"></i> Quick Info</div>
      <div class="panel-body">
        <div class="sidebar-stat"><span>ID</span><span class="sidebar-stat-val">#<?= (int)$f['id'] ?></span></div>
        <div class="sidebar-stat"><span>Rating</span><span class="sidebar-stat-val rating-stars">? <?= number_format((float)$f['rating'],1) ?></span></div>
        <div class="sidebar-stat"><span>Reviews</span><span class="sidebar-stat-val"><?= number_format($f['review_count']) ?></span></div>
        <div class="sidebar-stat"><span>Min Fee</span><span class="sidebar-stat-val">?<?= number_format($f['min_fee']) ?>/yr</span></div>
        <div class="sidebar-stat">
          <span>Status</span>
          <span><?= $f['active']?'<span class="badge badge-published">Live</span>':'<span class="badge badge-draft">Hidden</span>' ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /editor-sidebar -->
</div><!-- /editor-layout -->
</form>

<?php else: ?>
<!-- -- LIST VIEW ------------------------------- -->
<div class="card">
  <div class="card-header">
    <span class="card-title"><i class="fas fa-university" style="color:#667eea"></i> All Universities (<?= count($universities) ?>)</span>
    <div style="display:flex;gap:8px">
      <a href="/compare" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-balance-scale"></i> View Compare</a>
      <a href="?add=1" class="btn btn-yellow btn-sm"><i class="fas fa-plus"></i> Add New</a>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr>
        <th>#</th><th>University</th><th>Type</th><th>NAAC</th>
        <th>Min Fee/yr</th><th>Rating</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($universities)): ?>
        <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:50px">
          No universities yet. <a href="?add=1" style="color:#667eea;font-weight:600">Add the first one ?</a>
        </td></tr>
      <?php else: foreach($universities as $u): ?>
        <tr>
          <td style="color:#cbd5e0;font-size:12px"><?= (int)$u['id'] ?></td>
          <td>
            <div class="table-name-block">
              <strong><?= esc($u['name']) ?></strong>
              <span><?= esc($u['location']??'') ?></span>
            </div>
          </td>
          <td><span class="badge badge-draft"><?= esc($u['type']) ?></span></td>
          <td><span class="badge badge-published"><?= esc($u['naac_grade']??'—') ?></span></td>
          <td>?<?= number_format($u['min_fee']) ?></td>
          <td><span class="rating-stars">?</span> <strong><?= number_format((float)$u['rating'],1) ?></strong></td>
          <td><?= $u['active']?'<span class="badge badge-published">Live</span>':'<span class="badge badge-draft">Hidden</span>' ?></td>
          <td>
            <a href="?edit=<?= (int)$u['id'] ?>" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete <?= esc(addslashes($u['name'])) ?>?')">
              <input type="hidden" name="_csrf" value="<?= esc($csrf) ?>">
              <input type="hidden" name="_action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:none" title="Delete"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

    </div><!-- /page-body -->
  </div><!-- /main-content -->
</div>
<script src="/admin/assets/admin.js"></script>
<script>
// Auto-slug from name
const nameInput=document.getElementById('nameInput');
const slugField=document.getElementById('slugField');
if(nameInput&&slugField&&!slugField.value){
  nameInput.addEventListener('input',function(){
    slugField.value=this.value.toLowerCase().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-').trim();
  });
}
function addCourseRow(){
  const row=document.createElement('tr');
  row.innerHTML=`<td><input type="text" name="course_name[]" placeholder="MBA"></td>
    <td><input type="text" name="course_dur[]" placeholder="2 Years"></td>
    <td><input type="number" name="course_fee[]" placeholder="75000"></td>
    <td><input type="number" name="course_total[]" placeholder="150000"></td>
    <td><input type="text" name="course_specs[]" placeholder="Finance, Marketing, HR"></td>
    <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove()"><i class="fas fa-times"></i></button></td>`;
  document.getElementById('coursesBody').appendChild(row);
}
</script>
</body></html>