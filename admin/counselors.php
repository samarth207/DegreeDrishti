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
        if($id>0) $pdo->prepare('DELETE FROM counselors WHERE id=?')->execute([$id]);
        setFlash('success','Counselor deleted.');
        header('Location: /admin/counselors.php'); exit;
    }

    if (in_array($act,['create','update'])) {
        $p=$_POST; $id=(int)($p['id']??0);
        
        // Handle image upload if file was submitted
        $imagePath = trim($p['image']??'');
        if (!empty($_FILES['image_upload']['name'])) {
            $uploadResult = handleCounselorImageUpload('image_upload');
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['url'];
            } else {
                setFlash('danger', 'Image upload failed: ' . $uploadResult['error']);
                header('Location: /admin/counselors.php' . ($id>0?'?edit='.$id:'?add=1'));
                exit;
            }
        }
        
        $data=[
            'name'=>trim($p['name']??''),
            'qualification'=>trim($p['qualification']??''),
            'students_counselled'=>trim($p['students_counselled']??''),
            'experience_years'=>trim($p['experience_years']??''),
            'bio'=>trim($p['bio']??''),
            'image'=>$imagePath,
            'image_alt'=>trim($p['image_alt']??''),
            'active'=>!empty($p['active'])?1:0,
            'sort_order'=>(int)($p['sort_order']??0)
        ];
        
        if($id>0){
            $sets=implode(', ',array_map(fn($k)=>"`$k`=:$k",array_keys($data)));
            $data['id']=$id;
            $pdo->prepare("UPDATE counselors SET $sets WHERE id=:id")->execute($data);
            setFlash('success','Counselor updated.');
        } else {
            $cols=implode(',',array_map(fn($k)=>"`$k`",array_keys($data)));
            $vals=implode(',',array_map(fn($k)=>":$k",array_keys($data)));
            $pdo->prepare("INSERT INTO counselors ($cols) VALUES ($vals)")->execute($data);
            setFlash('success','Counselor added.');
        }
        header('Location: /admin/counselors.php'); exit;
    }
}

$editRow=null;
if(isset($_GET['edit'])){
    $s2=$pdo->prepare('SELECT * FROM counselors WHERE id=?');
    $s2->execute([(int)$_GET['edit']]);
    $editRow=$s2->fetch()?:null;
}
$counselors=$pdo->query('SELECT id,name,qualification,students_counselled,experience_years,active,sort_order FROM counselors ORDER BY sort_order ASC,id ASC')->fetchAll();

// Image upload helper function
function handleCounselorImageUpload(string $fileKey): array {
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
    $filename = 'counselor-' . uniqid() . '.' . $ext;
    $uploadDir = SITE_ROOT . '/images/counselors/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $destPath = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Could not save file.'];
    }
    return ['success' => true, 'url' => '/images/counselors/' . $filename];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Counselors — DegreeDrishti Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .counselor-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#667eea;padding:14px 18px 8px;border-top:1px solid #f0f2f5;margin-top:4px}
    .counselor-section-title:first-child{border-top:none;padding-top:4px}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:0 18px 4px}
    .form-grid.full{grid-template-columns:1fr}
    .form-group{margin-bottom:0}
    .form-group label{font-size:12px;font-weight:600;color:#555;display:flex;align-items:center;gap:5px;margin-bottom:5px}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;color:#333;background:#fafafa;transition:.2s}
    .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);background:#fff}
    .form-hint{font-size:11px;color:#9ca3af;margin-top:3px}
    .flag-grid{display:flex;flex-wrap:wrap;gap:16px;padding:12px 18px 16px}
    .flag-item{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:500;color:#2C3E50;cursor:pointer}
    .flag-item input{width:16px;height:16px;accent-color:#667eea;cursor:pointer}
    .form-actions{padding:18px;border-top:1px solid #f0f2f5;display:flex;gap:10px;background:#fafafa}
    .table-name-block strong{font-size:13.5px;color:#2C3E50;display:block}
    .table-name-block span{font-size:11px;color:#9ca3af}
    .image-preview{width:100px;height:100px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;margin-top:8px;display:none}
    .image-preview.show{display:block}
  </style>
</head>
<body>
<div class="admin-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button id="sidebar-toggle" class="btn btn-outline btn-sm" style="display:none"><i class="fas fa-bars"></i></button>
        <span class="topbar-title"><i class="fas fa-user-tie" style="color:#667eea"></i> Expert Career Counselors</span>
      </div>
      <div class="topbar-right">
        <?php if(!isset($_GET['edit'])&&!isset($_GET['add'])): ?>
        <a href="?add=1" class="btn btn-yellow btn-sm"><i class="fas fa-plus"></i> Add Counselor</a>
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
  $isEdit=!empty($f['id']);
?>
<!-- -- EDITOR LAYOUT --------------------------- -->
<div style="display:flex;gap:8px;align-items:center;margin-bottom:20px">
  <a href="/admin/counselors.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <h2 style="font-size:18px;font-weight:700;color:#2C3E50"><?= $isEdit?'Edit: '.esc($f['name']):'Add New Counselor' ?></h2>
</div>

<form method="POST" action="/admin/counselors.php" id="counselorForm" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= esc($csrf) ?>">
<input type="hidden" name="_action" value="<?= $isEdit?'update':'create' ?>">
<?php if($isEdit): ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><?php endif; ?>

<div class="editor-layout">
  <!-- Main Column -->
  <div class="editor-main">

    <!-- Basic Info -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-info-circle"></i> Basic Information</div>
      <div class="counselor-section-title">Identity</div>
      <div class="form-grid">
        <div class="form-group">
          <label><i class="fas fa-user"></i> Counselor Name <span style="color:#e53e3e">*</span></label>
          <input type="text" name="name" value="<?= esc($f['name']??'') ?>" required placeholder="e.g. Mr. Vidya Sagar">
        </div>
        <div class="form-group">
          <label><i class="fas fa-graduation-cap"></i> Qualification</label>
          <input type="text" name="qualification" value="<?= esc($f['qualification']??'') ?>" placeholder="e.g. MBA, Career Counselor">
        </div>
      </div>

      <div class="counselor-section-title">Statistics</div>
      <div class="form-grid">
        <div class="form-group">
          <label><i class="fas fa-users"></i> Students Counseled</label>
          <input type="text" name="students_counselled" value="<?= esc($f['students_counselled']??'') ?>" placeholder="e.g. 1500+">
        </div>
        <div class="form-group">
          <label><i class="fas fa-clock"></i> Experience</label>
          <input type="text" name="experience_years" value="<?= esc($f['experience_years']??'') ?>" placeholder="e.g. 5 Years">
        </div>
      </div>

      <div class="counselor-section-title">Bio</div>
      <div class="form-grid full" style="padding-bottom:16px">
        <div class="form-group">
          <label><i class="fas fa-align-left"></i> Short Biography</label>
          <textarea name="bio" rows="3" placeholder="Brief description of the counselor's expertise..."><?= esc($f['bio']??'') ?></textarea>
        </div>
      </div>
    </div>

  </div><!-- /editor-main -->

  <!-- Sidebar Column -->
  <div class="editor-sidebar">

    <!-- Publish -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-paper-plane"></i> Save</div>
      <div class="form-actions" style="border-top:none;flex-direction:column">
        <button type="submit" class="btn btn-yellow btn-full btn-lg">
          <i class="fas fa-<?= $isEdit?'save':'plus' ?>"></i>
          <?= $isEdit?'Save Changes':'Add Counselor' ?>
        </button>
        <a href="/admin/counselors.php" class="btn btn-outline btn-full" style="justify-content:center">Cancel</a>
      </div>
      <div class="counselor-section-title">Settings</div>
      <div class="flag-grid">
        <label class="flag-item">
          <input type="checkbox" name="active" value="1" <?=(!empty($f['active'])||(!$isEdit))?'checked':''?>>
          <i class="fas fa-eye" style="color:#667eea;width:14px"></i> Active (visible on website)
        </label>
      </div>
      <div class="counselor-section-title">Display Order</div>
      <div style="padding:12px 18px 16px">
        <div class="form-group">
          <label><i class="fas fa-sort-numeric-down"></i> Sort Order</label>
          <input type="number" name="sort_order" value="<?= esc($f['sort_order']??0) ?>" min="0" placeholder="0">
          <div class="form-hint">Lower numbers appear first. 0 = default order.</div>
        </div>
      </div>
    </div>

    <!-- Image -->
    <div class="editor-panel">
      <div class="panel-header"><i class="fas fa-image"></i> Profile Image</div>
      <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
        <div class="form-group">
          <label><i class="fas fa-upload"></i> Upload Image</label>
          <div style="display:flex;gap:8px;align-items:center">
            <input type="file" name="image_upload" accept="image/jpeg,image/png,image/webp" id="counselorImageUpload" style="flex:1">
            <button type="button" class="btn btn-outline btn-sm" onclick="uploadCounselorImage()" id="uploadCounselorBtn">
              <i class="fas fa-upload"></i> Upload
            </button>
          </div>
          <div class="form-hint">JPG, PNG, WebP (max 5MB)</div>
        </div>
        <div class="form-group">
          <label><i class="fas fa-link"></i> Image URL</label>
          <input type="text" name="image" id="imagePath" value="<?= esc($f['image']??'') ?>" placeholder="/images/counselors/photo.jpg">
          <?php if(!empty($f['image'])): ?>
          <img src="<?= esc($f['image']) ?>" alt="Preview" id="imagePreview" class="image-preview show">
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label><i class="fas fa-text-alt"></i> Alt Text</label>
          <input type="text" name="image_alt" value="<?= esc($f['image_alt']??'') ?>" placeholder="Description for screen readers">
        </div>
      </div>
    </div>

  </div><!-- /editor-sidebar -->
</div><!-- /editor-layout -->
</form>

<?php else: ?>
<!-- -- LIST VIEW --------------------------- -->
<div class="card">
  <div class="card-header">
    <span class="card-title"><i class="fas fa-list"></i> All Counselors</span>
  </div>
  <div style="overflow-x:auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Qualification</th>
          <th>Students</th>
          <th>Experience</th>
          <th>Order</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(empty($counselors)): ?>
          <tr><td colspan="7" style="text-align:center;color:#9ca3af;padding:40px;">
            No counselors yet. <a href="?add=1" style="color:#2C3E50;font-weight:600;">Add your first counselor →</a>
          </td></tr>
        <?php else: foreach($counselors as $c): ?>
          <tr>
            <td>
              <div class="table-name-block">
                <strong><?= esc($c['name']) ?></strong>
              </div>
            </td>
            <td style="font-size:12px;color:#6b7280;"><?= esc($c['qualification']??'—') ?></td>
            <td style="font-size:12px;"><?= esc($c['students_counselled']??'—') ?></td>
            <td style="font-size:12px;"><?= esc($c['experience_years']??'—') ?></td>
            <td style="font-size:12px;"><?= (int)$c['sort_order'] ?></td>
            <td>
              <span class="badge badge-<?= $c['active']?'published':'draft' ?>">
                <?= $c['active']?'Active':'Inactive' ?>
              </span>
            </td>
            <td style="white-space:nowrap;">
              <a href="?edit=<?= $c['id'] ?>" class="btn btn-outline btn-sm" title="Edit">
                <i class="fas fa-edit"></i>
              </a>
              <form id="del-<?= $c['id'] ?>" method="POST" action="/admin/counselors.php" style="display:inline;">
                <input type="hidden" name="_csrf" value="<?= getCsrfToken() ?>">
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                <button type="button" class="btn btn-danger btn-sm" title="Delete"
                        onclick="confirmDelete('<?= esc(addslashes($c['name'])) ?>', 'del-<?= $c['id'] ?>')">
                  <i class="fas fa-trash"></i>
                </button>
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
function uploadCounselorImage() {
    const fileInput = document.getElementById('counselorImageUpload');
    const uploadBtn = document.getElementById('uploadCounselorBtn');
    const imagePath = document.getElementById('imagePath');
    const imagePreview = document.getElementById('imagePreview');

    if (!fileInput.files || !fileInput.files[0]) {
        alert('Please select a file to upload.');
        return;
    }

    const formData = new FormData();
    formData.append('image', fileInput.files[0]);

    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    fetch('/api/upload-counselor-image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            imagePath.value = data.url;
            imagePreview.src = data.url;
            imagePreview.classList.add('show');
            fileInput.value = '';
            alert('Image uploaded successfully!');
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        alert('Upload failed: ' + error.message);
    })
    .finally(() => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload';
    });
}

// Update preview when URL changes manually
document.getElementById('imagePath').addEventListener('change', function() {
    const preview = document.getElementById('imagePreview');
    if (this.value) {
        preview.src = this.value;
        preview.classList.add('show');
    } else {
        preview.classList.remove('show');
    }
});
</script>
</body>
</html>
