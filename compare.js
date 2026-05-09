/* =============================================
   compare.js — DegreeDrishti University Comparison
   ============================================= */

const API_BASE = '/api/compare-universities.php';
const MAX_COMPARE = 4;

let allUniversities = [];
let filteredUniversities = [];
let selectedIds = [];
let selectedCourse = '';       // course filter for comparison
let currentPage = 1;
const PER_PAGE = 12;
let currentView = 'grid';
let searchDebounceTimer = null;

// ---- Init ----
document.addEventListener('DOMContentLoaded', () => {
  initRangeTrack();
  fetchUniversities();
  setupHamburger();
});

// ---- Filters ----
function debounceSearch() {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(applyFilters, 350);
}

function onRangeChange() {
  updateRangeTrack();
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(applyFilters, 200);
}

function applyFilters() {
  const search   = document.getElementById('searchInput').value.toLowerCase().trim();
  const minFee   = parseInt(document.getElementById('minFeeRange').value);
  const maxFee   = parseInt(document.getElementById('maxFeeRange').value);
  const sortBy   = document.getElementById('sortSelect').value;
  const naac     = document.getElementById('naacSelect').value;
  const type     = document.getElementById('typeSelect').value;
  const ugcOnly  = document.getElementById('ugcToggle').checked;

  let results = allUniversities.filter(u => {
    if (search && !u.name.toLowerCase().includes(search) &&
        !(u.shortName || '').toLowerCase().includes(search) &&
        !(u.location || '').toLowerCase().includes(search)) return false;
    if (u.minFee < minFee || u.minFee > maxFee) return false;
    if (naac && u.naacGrade !== naac) return false;
    if (type && u.type !== type) return false;
    if (ugcOnly && !u.ugcApproved) return false;
    return true;
  });

  // Sort
  const sortFns = {
    fee_asc:  (a,b) => a.minFee - b.minFee,
    fee_desc: (a,b) => b.minFee - a.minFee,
    rating:   (a,b) => b.rating - a.rating,
    ranking:  (a,b) => (a.ranking?.nirf || 999) - (b.ranking?.nirf || 999),
    name:     (a,b) => a.name.localeCompare(b.name),
  };
  if (sortFns[sortBy]) results.sort(sortFns[sortBy]);
  else results.sort((a,b) => (b.featured ? 1 : 0) - (a.featured ? 1 : 0) || b.rating - a.rating);

  filteredUniversities = results;
  currentPage = 1;
  renderGrid();
}

function clearFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('minFeeRange').value = 0;
  document.getElementById('maxFeeRange').value = 200000;
  document.getElementById('sortSelect').value = '';
  document.getElementById('naacSelect').value = '';
  document.getElementById('typeSelect').value = '';
  document.getElementById('ugcToggle').checked = false;
  updateRangeTrack();
  applyFilters();
}

// ---- Range Track ----
function initRangeTrack() {
  const track = document.createElement('div');
  track.className = 'dual-range-track';
  track.id = 'rangeTrack';
  document.querySelector('.dual-range').appendChild(track);
  updateRangeTrack();

  // Label update
  const minR = document.getElementById('minFeeRange');
  const maxR = document.getElementById('maxFeeRange');
  minR.addEventListener('input', () => {
    if (parseInt(minR.value) > parseInt(maxR.value) - 5000)
      minR.value = parseInt(maxR.value) - 5000;
    updateFeeLabels();
  });
  maxR.addEventListener('input', () => {
    if (parseInt(maxR.value) < parseInt(minR.value) + 5000)
      maxR.value = parseInt(minR.value) + 5000;
    updateFeeLabels();
  });
  updateFeeLabels();
}

function updateRangeTrack() {
  const minR = document.getElementById('minFeeRange');
  const maxR = document.getElementById('maxFeeRange');
  if (!minR || !maxR) return;
  const min = parseInt(minR.value);
  const max = parseInt(maxR.value);
  const range = parseInt(minR.max) - parseInt(minR.min);
  const leftPct  = ((min - parseInt(minR.min)) / range) * 100;
  const rightPct = ((parseInt(maxR.max) - max) / range) * 100;
  const track = document.getElementById('rangeTrack');
  if (track) {
    track.style.left  = leftPct  + '%';
    track.style.right = rightPct + '%';
  }
  updateFeeLabels();
}

function updateFeeLabels() {
  const minVal = parseInt(document.getElementById('minFeeRange').value);
  const maxVal = parseInt(document.getElementById('maxFeeRange').value);
  document.getElementById('minFeeLabel').textContent = formatFeeShort(minVal);
  document.getElementById('maxFeeLabel').textContent = maxVal >= 200000 ? '₹2L+' : formatFeeShort(maxVal);
}

// ---- Render Grid ----
function renderGrid() {
  const grid = document.getElementById('universityGrid');
  const start = 0;
  const end = currentPage * PER_PAGE;
  const visible = filteredUniversities.slice(start, end);
  const total = filteredUniversities.length;

  document.getElementById('resultsCount').textContent = total;

  if (total === 0) {
    grid.innerHTML = '';
    document.getElementById('noResults').style.display = 'block';
    document.getElementById('loadMoreWrap').style.display = 'none';
    return;
  }

  document.getElementById('noResults').style.display = 'none';
  grid.innerHTML = visible.map(u => buildCard(u)).join('');

  // Load more
  const loadWrap = document.getElementById('loadMoreWrap');
  if (end < total) {
    loadWrap.style.display = 'block';
    document.getElementById('loadMoreBtn').textContent =
      `Show More (${total - end} remaining)`;
  } else {
    loadWrap.style.display = 'none';
  }
}

function loadMore() {
  currentPage++;
  renderGrid();
  // Smooth scroll to new cards
  setTimeout(() => {
    const grid = document.getElementById('universityGrid');
    grid.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }, 100);
}

// ---- Card Builder ----
function buildCard(u) {
  const isSelected = selectedIds.includes(u.id);
  const feeProgress = Math.min(100, (u.minFee / 200000) * 100);
  const stars = buildStars(u.rating || 0);
  const highlights = (u.highlights || []).slice(0, 3)
    .map(h => `<span class="highlight-tag">${h}</span>`).join('');

  return `
  <div class="uni-card${isSelected ? ' selected-card' : ''}" id="card-${u.id}" data-id="${u.id}">
    <div class="card-top">
      <img class="card-logo" src="${u.logo || '/images/university-logos/default.png'}"
           alt="${u.name}" onerror="this.src='/images/university-logos/default.png'">
      <div class="card-title-wrap">
        <div class="card-uni-name">${u.name}</div>
        <div class="card-badges">
          ${u.featured ? '<span class="badge badge-featured">⭐ Featured</span>' : ''}
          <span class="badge badge-naac">NAAC ${u.naacGrade || 'N/A'}</span>
          ${u.ugcApproved ? '<span class="badge badge-ugc">UGC ✓</span>' : ''}
          <span class="badge badge-type">${u.type || ''}</span>
        </div>
      </div>
    </div>

    <div class="card-body">
      <div class="card-stats-row">
        <div class="card-stat">
          <span class="card-stat-val">${formatFeeShort(u.minFee)}</span>
          <span class="card-stat-lbl">Min Fee/yr</span>
        </div>
        <div class="card-stat">
          <span class="card-stat-val">${u.placementRate || '–'}%</span>
          <span class="card-stat-lbl">Placement</span>
        </div>
        <div class="card-stat">
          <span class="card-stat-val">${u.ranking?.nirf ? '#' + u.ranking.nirf : 'N/A'}</span>
          <span class="card-stat-lbl">NIRF Rank</span>
        </div>
      </div>

      <div class="card-fee-bar">
        <div class="card-fee-label">
          <span><i class="fas fa-rupee-sign"></i> Annual Fee</span>
          <span>${formatFeeShort(u.minFee)} – ${formatFeeShort(u.maxFee)}</span>
        </div>
        <div class="fee-progress">
          <div class="fee-progress-fill" style="width:${feeProgress}%"></div>
        </div>
      </div>

      <div class="card-highlights">${highlights}</div>

      <div class="card-rating">
        <span class="stars">${stars}</span>
        <span>${u.rating || 0}</span>
        <span>(${(u.reviewCount || 0).toLocaleString()} reviews)</span>
      </div>
    </div>

    <div class="card-footer">
      <button class="btn-card-compare${isSelected ? ' active-compare' : ''}"
              id="cmpBtn-${u.id}"
              onclick="toggleCompare('${u.id}')">
        ${isSelected
          ? '<i class="fas fa-minus"></i> Remove'
          : '<i class="fas fa-plus"></i> + Compare'}
      </button>
      <a href="${u.websiteUrl || '#'}" target="_blank" class="btn-card-apply">
        <i class="fas fa-external-link-alt"></i> Apply
      </a>
    </div>
  </div>`;
}

// ---- Compare Logic ----
function toggleCompare(id) {
  if (selectedIds.includes(id)) {
    selectedIds = selectedIds.filter(x => x !== id);
  } else {
    if (selectedIds.length >= MAX_COMPARE) {
      showToast(`You can compare up to ${MAX_COMPARE} universities at a time.`, 'warning');
      return;
    }
    selectedIds.push(id);
    showToast('University added to comparison!', 'success');
  }
  refreshCardState(id);
  updateCompareBar();
  if (selectedIds.length >= 2) updateComparisonTable();
}

function refreshCardState(id) {
  const card = document.getElementById(`card-${id}`);
  const btn  = document.getElementById(`cmpBtn-${id}`);
  if (!card || !btn) return;
  const isSelected = selectedIds.includes(id);
  card.classList.toggle('selected-card', isSelected);
  btn.classList.toggle('active-compare', isSelected);
  btn.innerHTML = isSelected
    ? '<i class="fas fa-minus"></i> Remove'
    : '<i class="fas fa-plus"></i> + Compare';
}

function removeFromCompare(id) {
  selectedIds = selectedIds.filter(x => x !== id);
  refreshCardState(id);
  updateCompareBar();
  if (selectedIds.length >= 2) updateComparisonTable();
  else document.getElementById('comparisonSection').style.display = 'none';
}

// ---- Compare Bar ----
function updateCompareBar() {
  const bar   = document.getElementById('compareBar');
  const slots = document.getElementById('compareSlots');
  const count = document.getElementById('compareCount');
  const btn   = document.getElementById('compareNowBtn');

  count.textContent = selectedIds.length;
  btn.disabled = selectedIds.length < 2;

  const selected = selectedIds.map(id => allUniversities.find(u => u.id === id)).filter(Boolean);
  slots.innerHTML = selected.map(u => `
    <div class="compare-slot">
      <img src="${u.logo || '/images/university-logos/default.png'}"
           alt="${u.shortName || u.name}"
           onerror="this.src='/images/university-logos/default.png'">
      ${u.shortName || u.name.split(' ')[0]}
      <button class="compare-slot-remove" onclick="removeFromCompare('${u.id}')" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>`).join('');
}

// ---- Comparison Table ----
function scrollToComparison() {
  updateComparisonTable();
  setTimeout(() => {
    document.getElementById('comparisonSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }, 100);
}

function updateComparisonTable() {
  const section = document.getElementById('comparisonSection');
  const table   = document.getElementById('comparisonTable');

  if (selectedIds.length < 2) {
    section.style.display = 'none';
    return;
  }
  section.style.display = 'block';

  const unis = selectedIds.map(id => allUniversities.find(u => u.id === id)).filter(Boolean);
  if (!unis.length) return;

  // Find best values for highlighting
  const bestMinFee     = Math.min(...unis.map(u => u.minFee || Infinity));
  const bestRating     = Math.max(...unis.map(u => u.rating || 0));
  const bestPlacement  = Math.max(...unis.map(u => u.placementRate || 0));
  const bestNirf       = Math.min(...unis.map(u => u.ranking?.nirf || Infinity));
  const bestSalary     = Math.max(...unis.map(u => u.avgSalary || 0));

  // Course-specific rows (prepended when a course is selected)
  let courseRows = [];
  if (selectedCourse) {
    const getCourse = u => (u.courses || []).find(c =>
      c.name.toUpperCase().includes(selectedCourse.toUpperCase())
    );
    const courseFees    = unis.map(u => getCourse(u)?.fee || 0).filter(Boolean);
    const bestCourseFee = courseFees.length ? Math.min(...courseFees) : 0;
    const worstCourseFee= courseFees.length ? Math.max(...courseFees) : 0;

    courseRows = [
      { cat: `🎓 ${selectedCourse} — Course Details`, courseSection: true },
      { label: 'Available',        icon: 'fa-check-circle', val: u => getCourse(u) ? '<span class="check-yes">✓ Yes</span>' : '<span class="check-no">✗ Not Offered</span>', html: true },
      { label: 'Duration',         icon: 'fa-clock',        val: u => getCourse(u)?.duration || '—' },
      { label: 'Annual Fee',       icon: 'fa-rupee-sign',   val: u => getCourse(u) ? `<strong>${formatFee(getCourse(u).fee)}</strong>` : '—', html: true, best: u => getCourse(u)?.fee === bestCourseFee && bestCourseFee > 0, worst: u => getCourse(u)?.fee === worstCourseFee && worstCourseFee !== bestCourseFee },
      { label: 'Total Fee',        icon: 'fa-wallet',       val: u => getCourse(u) ? formatFee(getCourse(u).totalFee) : '—' },
      { label: 'Specializations',  icon: 'fa-list-ul',      val: u => (getCourse(u)?.specializations || []).join(', ') || '—' },
    ];
  }

  const rows = [

    { cat: '🏫 Overview' },
    { label: 'Location',       icon: 'fa-map-marker-alt', val: u => u.location || 'N/A' },
    { label: 'Established',    icon: 'fa-calendar',       val: u => u.established || 'N/A' },
    { label: 'Type',           icon: 'fa-university',     val: u => u.type || 'N/A' },
    { label: 'UGC Approved',   icon: 'fa-check-shield',   val: u => u.ugcApproved ? '<span class="check-yes">✓ Yes</span>' : '<span class="check-no">✗ No</span>', html: true },
    { cat: '📋 Accreditation & Ranking' },
    { label: 'NAAC Grade',     icon: 'fa-certificate',    val: u => `<strong>${u.naacGrade || 'N/A'}</strong>`, html: true },
    { label: 'NIRF Rank',      icon: 'fa-trophy',         val: u => u.ranking?.nirf ? '#' + u.ranking.nirf : 'N/A', best: u => u.ranking?.nirf === bestNirf },
    { label: 'Outlook Rank',   icon: 'fa-medal',          val: u => u.ranking?.outlook ? '#' + u.ranking.outlook : 'N/A' },
    { cat: '💰 Fees' },
    { label: 'Min Annual Fee', icon: 'fa-rupee-sign',     val: u => `<strong>${formatFee(u.minFee)}</strong>`, html: true, best: u => u.minFee === bestMinFee, worst: u => u.minFee === Math.max(...unis.map(x => x.minFee || 0)) },
    { label: 'Max Annual Fee', icon: 'fa-rupee-sign',     val: u => formatFee(u.maxFee) },
    { label: 'EMI Available',  icon: 'fa-credit-card',    val: u => u.emiAvailable ? '<span class="check-yes">✓ Yes</span>' : '<span class="check-no">✗ No</span>', html: true },
    { label: 'Scholarship',    icon: 'fa-hand-holding-usd', val: u => u.scholarshipAvailable ? '<span class="check-yes">✓ Yes</span>' : '<span class="check-no">✗ No</span>', html: true },
    { cat: '🎓 Programs' },
    { label: 'Courses Offered', icon: 'fa-book',          val: u => (u.courses || []).map(c => c.name).join(', ') || 'N/A' },
    { label: 'Admission Mode',  icon: 'fa-desktop',       val: u => u.admissionMode || 'N/A' },
    { label: 'Exams Accepted',  icon: 'fa-file-alt',      val: u => (u.examAccepted || []).join(', ') || 'N/A' },
    { cat: '🏆 Placements' },
    { label: 'Placement Rate',  icon: 'fa-chart-line',    val: u => `<strong>${u.placementRate || 'N/A'}%</strong>`, html: true, best: u => u.placementRate === bestPlacement },
    { label: 'Avg. Salary',     icon: 'fa-coins',         val: u => u.avgSalary ? `${u.avgSalary} LPA` : 'N/A', best: u => u.avgSalary === bestSalary },
    { label: 'Top Recruiters',  icon: 'fa-building',      val: u => (u.topRecruiters || []).slice(0, 4).join(', ') || 'N/A' },
    { cat: '⭐ Reviews' },
    { label: 'Rating',          icon: 'fa-star',          val: u => `${u.rating || 'N/A'} / 5 (${(u.reviewCount || 0).toLocaleString()} reviews)`, best: u => u.rating === bestRating },
    { cat: '🖥️ Learning' },
    { label: 'LMS Platform',    icon: 'fa-laptop',        val: u => u.lmsType || 'N/A' },
    { label: 'Support',         icon: 'fa-headset',       val: u => (u.supportType || []).join(' • ') || 'N/A' },
  ];

  // Build header row
  let headerRow = `<tr>
    <th class="row-label">Parameter</th>
    ${unis.map(u => `
      <th class="col-header">
        <img class="col-header-logo"
             src="${u.logo || '/images/university-logos/default.png'}"
             alt="${u.name}"
             onerror="this.src='/images/university-logos/default.png'">
        <div class="col-header-name">${u.name}</div>
        <div class="col-header-location"><i class="fas fa-map-marker-alt"></i> ${u.location || ''}</div>
        <button class="remove-col-btn" onclick="removeFromCompare('${u.id}')" title="Remove from comparison">
          <i class="fas fa-times-circle"></i>
        </button>
      </th>`).join('')}
  </tr>`;

  let bodyRows = [...courseRows, ...rows].map(row => {
    if (row.cat) {
      const cls = row.courseSection ? 'course-section-row' : 'category-row';
      return `<tr class="${cls}">
        <td colspan="${unis.length + 1}">${row.cat}</td>
      </tr>`;
    }
    const cells = unis.map(u => {
      const rawVal = row.val(u);
      const isBest  = row.best  && row.best(u);
      const isWorst = row.worst && row.worst(u);
      const cls = isBest ? 'best-val' : (isWorst ? 'worst-val' : '');
      if (row.html) return `<td class="${cls}">${rawVal}</td>`;
      return `<td class="${cls}">${escapeHtml(String(rawVal))}</td>`;
    }).join('');
    return `<tr>
      <td class="row-label"><i class="fas ${row.icon}"></i> ${row.label}</td>
      ${cells}
    </tr>`;
  }).join('');

  table.innerHTML = `<thead>${headerRow}</thead><tbody>${bodyRows}</tbody>`;
}

// ---- View Toggle ----
function setView(view) {
  currentView = view;
  const grid = document.getElementById('universityGrid');
  document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
  document.getElementById('listViewBtn').classList.toggle('active', view === 'list');
  grid.classList.toggle('list-view', view === 'list');
}

// ---- Course Filter ----
function setCourseFilter(course) {
  selectedCourse = course;
  // Update pill UI
  document.querySelectorAll('.course-pill').forEach(p => p.classList.remove('active'));
  const activeId = course ? `pill-${course}` : 'pill-all';
  const activePill = document.getElementById(activeId);
  if (activePill) activePill.classList.add('active');
  // Update label in comparison header
  const label = document.getElementById('courseCompareLabel');
  if (label) label.textContent = course ? `— ${course} Mode` : '';
  // Re-render table if open
  if (selectedIds.length >= 2) updateComparisonTable();
}

// ---- Utility ----
function formatFee(amount) {
  if (!amount) return 'N/A';
  if (amount >= 100000) return '₹' + (amount / 100000).toFixed(1) + ' L';
  return '₹' + (amount / 1000).toFixed(0) + 'K';
}
function formatFeeShort(amount) {
  if (!amount) return '₹0';
  if (amount >= 100000) return '₹' + (amount / 100000).toFixed(1) + 'L';
  if (amount >= 1000)   return '₹' + (amount / 1000).toFixed(0) + 'K';
  return '₹' + amount;
}
function buildStars(rating) {
  const full  = Math.floor(rating);
  const half  = rating % 1 >= 0.5 ? 1 : 0;
  const empty = 5 - full - half;
  return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
}
function escapeHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function showSkeletons() {
  const grid = document.getElementById('universityGrid');
  grid.innerHTML = Array(8).fill('<div class="card-skeleton"></div>').join('');
}

function showApiError() {
  document.getElementById('universityGrid').innerHTML = `
    <div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#e74c3c;">
      <i class="fas fa-exclamation-triangle" style="font-size:48px;margin-bottom:16px;display:block"></i>
      <h3>Unable to load universities</h3>
      <p style="color:#999;margin:10px 0 20px">Please make sure the file <code>api/compare-universities.php</code> is uploaded to your server.</p>
    </div>`;
}

function showToast(message, type = 'info') {
  const existing = document.querySelector('.dd-toast');
  if (existing) existing.remove();
  const toast = document.createElement('div');
  toast.className = 'dd-toast';
  const colors = { success: '#27ae60', warning: '#e67e22', info: '#667eea', error: '#e74c3c' };
  toast.style.cssText = `
    position:fixed;bottom:30px;right:30px;z-index:9999;
    background:${colors[type] || colors.info};color:#fff;
    padding:14px 22px;border-radius:12px;font-size:14px;font-weight:600;
    box-shadow:0 8px 30px rgba(0,0,0,0.2);
    animation:slideInToast 0.3s ease;font-family:'Poppins',sans-serif;
  `;
  toast.textContent = message;
  document.body.appendChild(toast);
  const style = document.createElement('style');
  style.textContent = '@keyframes slideInToast{from{transform:translateX(120%);opacity:0}to{transform:none;opacity:1}}';
  document.head.appendChild(style);
  setTimeout(() => toast.remove(), 3500);
}

// ---- Print & Share ----
function printComparison() { window.print(); }

function shareComparison() {
  const ids = selectedIds.join(',');
  const url = `${location.origin}${location.pathname}?compare=${ids}`;
  if (navigator.share) {
    navigator.share({ title: 'University Comparison | DegreeDrishti', url });
  } else {
    navigator.clipboard.writeText(url).then(() =>
      showToast('Comparison link copied to clipboard!', 'success')
    );
  }
}

// ---- Deep Link from URL ----
function loadFromUrl() {
  const params = new URLSearchParams(location.search);
  const ids = params.get('compare');
  if (ids) {
    selectedIds = ids.split(',');
    selectedIds.forEach(id => refreshCardState(id));
    updateCompareBar();
    if (selectedIds.length >= 2) {
      setTimeout(() => {
        updateComparisonTable();
        scrollToComparison();
      }, 800);
    }
  }
}

// ---- Hamburger ----
function setupHamburger() {
  const btn = document.getElementById('hamburger');
  if (!btn) return;
  btn.addEventListener('click', () => {
    btn.classList.toggle('active');
    const nav = document.querySelector('.nav-links');
    if (nav) nav.classList.toggle('open');
  });
}

async function fetchUniversities() {
  try {
    showSkeletons();
    const res = await fetch(`${API_BASE}?limit=100`);
    const json = await res.json();
    if (json.success) {
      allUniversities = json.data;
      filteredUniversities = [...allUniversities];
      document.getElementById('totalCount').textContent = allUniversities.length + '+';
      renderGrid();
      loadFromUrl();
    }
  } catch (err) {
    console.error('API error:', err);
    showApiError();
  }
}
