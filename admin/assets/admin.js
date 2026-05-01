/**
 * DegreeDrishti Admin Panel - JavaScript
 * Handles: slug generation, char counters, tag chips,
 *          image preview, SEO score, TinyMCE setup
 */

/* ============================================================
   UTILITY: Slug from string
   ============================================================ */
function toSlug(str) {
  return str
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s]+/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

/* ============================================================
   CHARACTER COUNTER
   input: element, max, counterEl
   ============================================================ */
function attachCounter(inputEl, max, counterEl, ideal) {
  function update() {
    const len = inputEl.value.length;
    counterEl.textContent = len + ' / ' + max + ' characters';
    counterEl.className = 'char-counter';
    if (ideal) {
      if (len >= ideal[0] && len <= ideal[1]) counterEl.classList.add('good');
      else if (len > max)                     counterEl.classList.add('over');
      else                                    counterEl.classList.add('warn');
    } else {
      if (len > max)       counterEl.classList.add('over');
      else if (len > max * 0.8) counterEl.classList.add('warn');
    }
  }
  inputEl.addEventListener('input', update);
  update();
}

/* ============================================================
   TAG CHIP INPUT
   ============================================================ */
function initTagInput(wrapId, hiddenId) {
  const wrap   = document.getElementById(wrapId);
  const hidden = document.getElementById(hiddenId);
  if (!wrap || !hidden) return;

  const input = wrap.querySelector('.tag-real-input');
  let tags = hidden.value ? hidden.value.split(',').filter(Boolean) : [];

  function renderTags() {
    wrap.querySelectorAll('.tag-chip').forEach(c => c.remove());
    tags.forEach((tag, i) => {
      const chip = document.createElement('span');
      chip.className = 'tag-chip';
      chip.innerHTML = tag + '<span class="remove-tag" data-i="' + i + '">&times;</span>';
      wrap.insertBefore(chip, input);
    });
    hidden.value = tags.join(',');
  }

  function addTag(val) {
    const t = val.trim();
    if (t && !tags.includes(t)) {
      tags.push(t);
      renderTags();
    }
  }

  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      addTag(this.value);
      this.value = '';
    }
    if (e.key === 'Backspace' && !this.value && tags.length) {
      tags.pop();
      renderTags();
    }
  });

  input.addEventListener('blur', function () {
    if (this.value.trim()) { addTag(this.value); this.value = ''; }
  });

  wrap.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-tag')) {
      tags.splice(parseInt(e.target.dataset.i), 1);
      renderTags();
    }
    input.focus();
  });

  renderTags();
}

/* ============================================================
   IMAGE PREVIEW (feature image / author image)
   ============================================================ */
function initImagePreview(inputId, previewId, uploadAreaId) {
  const input    = document.getElementById(inputId);
  const preview  = document.getElementById(previewId);
  const uploadArea = document.getElementById(uploadAreaId);
  if (!input || !preview) return;

  input.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      alert('Only JPG, PNG and WebP images are allowed.');
      this.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      alert('File size must be under 5 MB.');
      this.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.classList.add('show');
      if (uploadArea) uploadArea.style.padding = '10px';
    };
    reader.readAsDataURL(file);
  });
}

/* ============================================================
   SLUG AUTO-GENERATION FROM BLOG TITLE
   ============================================================ */
function initSlugGen(titleId, slugId) {
  const titleEl = document.getElementById(titleId);
  const slugEl  = document.getElementById(slugId);
  if (!titleEl || !slugEl) return;

  let userEdited = slugEl.value !== '';

  titleEl.addEventListener('input', function () {
    if (!userEdited) {
      slugEl.value = toSlug(this.value);
    }
  });

  slugEl.addEventListener('input', function () {
    userEdited = true;
    this.value = toSlug(this.value);
  });
}

/* ============================================================
   SEO SCORE INDICATOR
   ============================================================ */
function updateSeoScore() {
  const metaTitle = (document.getElementById('meta_title')   || {}).value || '';
  const metaDesc  = (document.getElementById('meta_description') || {}).value || '';
  const focusKw   = (document.getElementById('focus_keyword')    || {}).value || '';
  const altText   = (document.getElementById('feature_image_alt') || {}).value || '';
  const slug      = (document.getElementById('slug')             || {}).value || '';

  const items = [];
  let score = 0;

  // Meta title: 30-60 chars
  const mtGood = metaTitle.length >= 30 && metaTitle.length <= 60;
  items.push({ ok: mtGood, text: 'Meta title ' + (mtGood ? 'length is optimal' : '(ideal 30-60 chars)') });
  if (mtGood) score += 20;

  // Meta description: 150-250 chars
  const mdGood = metaDesc.length >= 150 && metaDesc.length <= 250;
  items.push({ ok: mdGood, text: 'Meta description ' + (mdGood ? 'length is optimal' : '(ideal 150-250 chars)') });
  if (mdGood) score += 20;

  // Focus keyword in meta title
  const kwInTitle = focusKw && metaTitle.toLowerCase().includes(focusKw.toLowerCase());
  items.push({ ok: kwInTitle, text: kwInTitle ? 'Focus keyword in title ✓' : 'Add focus keyword to meta title' });
  if (kwInTitle) score += 20;

  // Focus keyword in meta description
  const kwInDesc = focusKw && metaDesc.toLowerCase().includes(focusKw.toLowerCase());
  items.push({ ok: kwInDesc, text: kwInDesc ? 'Focus keyword in meta description ✓' : 'Add focus keyword to meta description' });
  if (kwInDesc) score += 20;

  // Feature image alt text
  const altGood = altText.trim().length > 0;
  items.push({ ok: altGood, text: altGood ? 'Feature image alt text ✓' : 'Feature image alt text is missing' });
  if (altGood) score += 10;

  // Slug not empty
  const slugGood = slug.trim().length > 0;
  items.push({ ok: slugGood, text: slugGood ? 'URL slug set ✓' : 'URL slug is empty' });
  if (slugGood) score += 10;

  // Update UI
  const fill  = document.getElementById('seo-score-fill');
  const label = document.getElementById('seo-score-label');
  const list  = document.getElementById('seo-item-list');

  if (fill) {
    fill.style.width = score + '%';
    fill.style.background = score >= 80 ? '#38a169' : score >= 50 ? '#d69e2e' : '#e53e3e';
  }
  if (label) {
    label.textContent = score + '/100 — ' + (score >= 80 ? 'Good' : score >= 50 ? 'Needs Improvement' : 'Poor');
  }
  if (list) {
    list.innerHTML = items.map(it =>
      '<div class="seo-item"><span class="dot ' + (it.ok ? 'dot-green' : 'dot-red') + '"></span>' +
      '<span>' + it.text + '</span></div>'
    ).join('');
  }
}

/* ============================================================
   TOC PREVIEW (from TinyMCE headings)
   ============================================================ */
function updateTocPreview() {
  const preview = document.getElementById('toc-preview');
  if (!preview || typeof tinymce === 'undefined') return;

  const editor = tinymce.get('blog-content');
  if (!editor) return;

  const content = editor.getContent();
  const headings = [];
  const regex = /<h([23])[^>]*>(.*?)<\/h[23]>/gi;
  let m;
  while ((m = regex.exec(content)) !== null) {
    headings.push({ level: parseInt(m[1]), text: m[2].replace(/<[^>]+>/g, '') });
  }

  if (!headings.length) {
    preview.innerHTML = '<em>No H2/H3 headings in content yet</em>';
    return;
  }

  let html = '<ol>';
  headings.forEach(h => {
    html += '<li class="' + (h.level === 3 ? 'toc-h3' : '') + '">' + h.text + '</li>';
  });
  html += '</ol>';
  preview.innerHTML = html;
}

/* ============================================================
   TINYMCE INIT
   ============================================================ */
function initTinyMCE() {
  if (typeof tinymce === 'undefined') return;

  tinymce.init({
    selector: '#blog-content',
    base_url: 'https://cdn.jsdelivr.net/npm/tinymce@6.8.5',
    suffix: '.min',
    height: 620,
    menubar: 'edit view insert format tools table',
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image',
      'charmap', 'preview', 'anchor', 'searchreplace',
      'visualblocks', 'code', 'fullscreen', 'insertdatetime',
      'media', 'table', 'wordcount', 'codesample'
    ],
    toolbar:
      'undo redo | blocks | ' +
      'bold italic underline strikethrough | forecolor backcolor | ' +
      'alignleft aligncenter alignright alignjustify | ' +
      'bullist numlist outdent indent | ' +
      'link image | ' +
      'table | ' +
      'blockquote codesample | ' +
      'removeformat | code | fullscreen | ' +
      'insertfaqblock insertleadform inserttoc',
    toolbar_mode: 'sliding',

    // Image plugin
    image_title: true,
    image_caption: true,
    image_advtab: true,
    automatic_uploads: true,
    images_upload_url: '../api/upload-media.php',
    images_upload_credentials: true,
    file_picker_types: 'image',

    // Link plugin
    link_default_target: '_blank',
    link_target_list: [
      { title: 'Same window', value: '' },
      { title: 'New tab (_blank)', value: '_blank' }
    ],

    // Table plugin
    table_style_by_css: true,
    table_cell_advtab: true,
    table_row_advtab: true,
    table_default_attributes: { border: '0' },
    table_default_styles: { 'border-collapse': 'collapse', width: '100%' },

    // Content styles (mirror public blog styles)
    content_style: `
      @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
      body { font-family: 'Poppins', sans-serif; font-size: 16px; line-height: 1.85; color: #333; max-width: 820px; margin: 0 auto; padding: 24px; }
      h2 { font-size: 1.7em; color: #2C3E50; margin-top: 2.2em; margin-bottom: .6em; border-bottom: 2px solid #FFD700; padding-bottom: 6px; }
      h3 { font-size: 1.3em; color: #2C3E50; margin-top: 1.8em; margin-bottom: .5em; }
      h4 { font-size: 1.1em; color: #2C3E50; margin-top: 1.4em; }
      a  { color: #2C3E50; text-decoration: underline; }
      blockquote { border-left: 4px solid #FFD700; margin: 20px 0; padding: 12px 20px; background: #fffbeb; border-radius: 0 8px 8px 0; font-style: italic; }
      table { border-collapse: collapse; width: 100%; margin: 24px 0; }
      th  { background: #2C3E50; color: #fff; padding: 12px 14px; text-align: left; }
      td  { padding: 10px 14px; border: 1px solid #dee2e6; }
      tr:nth-child(even) td { background: #f8fafc; }
      img { max-width: 100%; height: auto; border-radius: 8px; }
      .faq-block { background: #f0f7ff; border-left: 4px solid #FFD700; padding: 20px 24px; border-radius: 8px; margin: 32px 0; }
      .faq-title { color: #2C3E50; font-size: 1.1em; margin-bottom: 16px; }
      .faq-item  { margin-bottom: 18px; }
      .faq-question { color: #2C3E50; font-weight: 600; margin-bottom: 6px; }
      .blog-toc { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin: 28px 0; }
      .toc-title { font-size: 14px; font-weight: 600; color: #2C3E50; margin-bottom: 10px; }
      .toc-list { padding-left: 18px; }
      .toc-list li { padding: 3px 0; font-size: 14px; }
      .blog-lead-form { background: linear-gradient(135deg, #2C3E50, #1a252f); color: #fff; padding: 32px; border-radius: 12px; text-align: center; margin: 40px 0; }
    `,

    // Wordcount
    wordcount_countcharacters: true,

    // On-change callback
    setup: function (editor) {
      // Update SEO + TOC on every change
      editor.on('change input', function () {
        setTimeout(() => { updateSeoScore(); updateTocPreview(); }, 300);
      });

      /* ---- Custom: FAQ Block Button ---- */
      editor.ui.registry.addButton('insertfaqblock', {
        text: 'FAQ',
        icon: 'help',
        tooltip: 'Insert FAQ Section Block',
        onAction: function () {
          editor.windowManager.open({
            title: 'Insert FAQ Block',
            body: {
              type: 'panel',
              items: [
                { type: 'input', name: 'faqTitle', label: 'Section Title', placeholder: 'Frequently Asked Questions' },
                { type: 'input', name: 'faqCount', label: 'Number of Q&A Pairs', placeholder: '3' }
              ]
            },
            buttons: [
              { type: 'cancel', text: 'Cancel' },
              { type: 'submit', text: 'Insert FAQ', primary: true }
            ],
            onSubmit: function (api) {
              const d     = api.getData();
              const title = d.faqTitle || 'Frequently Asked Questions';
              const count = Math.min(parseInt(d.faqCount) || 3, 20);
              let html = '<div class="faq-block"><h3 class="faq-title">' + title + '</h3>';
              for (let i = 1; i <= count; i++) {
                html += '<div class="faq-item"><h4 class="faq-question">Question ' + i + '?</h4>' +
                        '<div class="faq-answer"><p>Answer to question ' + i + ' goes here.</p></div></div>';
              }
              html += '</div><p>&nbsp;</p>';
              editor.insertContent(html);
              api.close();
            }
          });
        }
      });

      /* ---- Custom: Lead Form Block Button ---- */
      editor.ui.registry.addButton('insertleadform', {
        text: 'Lead Form',
        icon: 'user',
        tooltip: 'Insert Lead Capture Form Block',
        onAction: function () {
          editor.windowManager.open({
            title: 'Insert Lead Capture Form',
            body: {
              type: 'panel',
              items: [
                { type: 'input', name: 'headline',   label: 'Form Headline',   placeholder: 'Ready to Start Your Journey?' },
                { type: 'input', name: 'buttonText', label: 'Button Text',     placeholder: 'Apply Now' }
              ]
            },
            buttons: [
              { type: 'cancel', text: 'Cancel' },
              { type: 'submit', text: 'Insert Form', primary: true }
            ],
            onSubmit: function (api) {
              const d    = api.getData();
              const hl   = (d.headline   || 'Ready to Start Your Journey?').replace(/"/g, '&quot;');
              const btn  = (d.buttonText || 'Apply Now').replace(/"/g, '&quot;');
              const html =
                '<div class="blog-lead-form" data-headline="' + hl + '" data-btn-text="' + btn + '">' +
                '<p style="font-size:18px;font-weight:700;margin:0 0 6px;">' + hl + '</p>' +
                '<p style="opacity:.75;font-size:13px;margin:0 0 4px;">📋 Name &nbsp;|&nbsp; Phone &nbsp;|&nbsp; Course fields</p>' +
                '<p style="opacity:.55;font-size:12px;margin:0;">Button: &ldquo;' + btn + '&rdquo; &mdash; renders as live form on the website</p>' +
                '</div><p>&nbsp;</p>';
              editor.insertContent(html);
              api.close();
            }
          });
        }
      });

      /* ---- Custom: Insert TOC Button ---- */
      editor.ui.registry.addButton('inserttoc', {
        text: 'TOC',
        icon: 'ordered-list',
        tooltip: 'Insert Table of Contents from headings',
        onAction: function () {
          const content = editor.getContent();
          const headings = [];
          const re = /<h([23])[^>]*>(.*?)<\/h[23]>/gi;
          let m;
          while ((m = re.exec(content)) !== null) {
            const lvl  = parseInt(m[1]);
            const text = m[2].replace(/<[^>]+>/g, '');
            const slug = text.toLowerCase().replace(/[^\w\s]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').substring(0, 60);
            headings.push({ level: lvl, text, slug });
          }
          if (!headings.length) {
            editor.notificationManager.open({ text: 'No H2/H3 headings found. Add headings first.', type: 'warning' });
            return;
          }
          let tocHtml = '<div class="blog-toc"><h4 class="toc-title">📋 Table of Contents</h4><ol class="toc-list">';
          headings.forEach(h => {
            const indent = h.level === 3 ? ' style="margin-left:20px"' : '';
            tocHtml += '<li' + indent + '><a href="#' + h.slug + '">' + h.text + '</a></li>';
          });
          tocHtml += '</ol></div><p>&nbsp;</p>';
          editor.insertContent(tocHtml);
        }
      });
    }
  });
}

/* ============================================================
   PUBLISH PANEL: toggle scheduled date/time
   ============================================================ */
function initPublishPanel() {
  const statusSel = document.getElementById('status');
  const schedRow  = document.getElementById('schedule-row');
  if (!statusSel || !schedRow) return;

  function toggle() {
    schedRow.style.display = statusSel.value === 'scheduled' ? 'block' : 'none';
  }
  statusSel.addEventListener('change', toggle);
  toggle();
}

/* ============================================================
   FORM VALIDATION (blog editor save)
   ============================================================ */
function validateBlogForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;

  const errors = [];
  const title  = form.querySelector('#title');
  const slug   = form.querySelector('#slug');
  const cats   = form.querySelectorAll('input[name="categories[]"]:checked');

  if (title && !title.value.trim())  errors.push('Blog title is required.');
  if (slug  && !slug.value.trim())   errors.push('URL slug is required.');
  if (!cats.length)                  errors.push('Please select at least one category.');

  // Sync TinyMCE content to textarea before submit
  if (typeof tinymce !== 'undefined') {
    const ed = tinymce.get('blog-content');
    if (ed) ed.save();
  }

  if (errors.length) {
    alert('Please fix the following:\n\n' + errors.join('\n'));
    return false;
  }
  return true;
}

/* ============================================================
   DELETE CONFIRMATION
   ============================================================ */
function confirmDelete(blogTitle, formId) {
  if (confirm('Delete "' + blogTitle + '"?\n\nThis cannot be undone.')) {
    document.getElementById(formId).submit();
  }
}

/* ============================================================
   SIDEBAR MOBILE TOGGLE
   ============================================================ */
function initSidebarToggle() {
  const btn     = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('.sidebar');
  if (!btn || !sidebar) return;
  btn.addEventListener('click', () => sidebar.classList.toggle('open'));
}

/* ============================================================
   BOOTSTRAP
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {
  initSidebarToggle();
  initPublishPanel();

  // SEO score live updates
  ['meta_title', 'meta_description', 'focus_keyword', 'feature_image_alt', 'slug']
    .forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', updateSeoScore);
    });

  // Character counters
  const metaTitleEl = document.getElementById('meta_title');
  const metaTitleCt = document.getElementById('meta-title-counter');
  if (metaTitleEl && metaTitleCt) attachCounter(metaTitleEl, 60, metaTitleCt, [30, 60]);

  const metaDescEl = document.getElementById('meta_description');
  const metaDescCt = document.getElementById('meta-desc-counter');
  if (metaDescEl && metaDescCt) attachCounter(metaDescEl, 250, metaDescCt, [150, 250]);

  const blogTitleEl = document.getElementById('title');
  const blogTitleCt = document.getElementById('blog-title-counter');
  if (blogTitleEl && blogTitleCt) attachCounter(blogTitleEl, 70, blogTitleCt, [40, 70]);

  const excerptEl = document.getElementById('excerpt');
  const excerptCt = document.getElementById('excerpt-counter');
  if (excerptEl && excerptCt) attachCounter(excerptEl, 250, excerptCt, [100, 250]);

  // Slug gen
  initSlugGen('title', 'slug');

  // Tag input
  initTagInput('tag-input-wrap', 'tags-hidden');

  // Image previews
  initImagePreview('feature_image_file', 'feature-img-preview', 'feature-upload-area');
  initImagePreview('author_image_file',  'author-img-preview',  null);

  // TinyMCE (loaded from CDN in the page)
  initTinyMCE();

  // Initial SEO score
  setTimeout(updateSeoScore, 500);
});
