/**
 * DegreeDrishti - Popup Forms Handler
 * Handles: Enquire Now popup, Timed Counselling popup, PHP backend submission
 */

(function () {
    'use strict';

    // ===== CONFIGURATION =====
    // PHP API endpoint (auto-detects path based on page location)
    function getApiPath() {
        if (window.location.pathname.includes('/courses/')) {
            return '../api/save-popup-enquiry.php';
        }
        return 'api/save-popup-enquiry.php';
    }

    // ===== ENQUIRE NOW POPUP =====
    const enquireOverlay = document.getElementById('ddEnquirePopup');
    const enquireForm = document.getElementById('ddEnquireForm');
    const enquireFormWrap = document.getElementById('ddEnquireFormWrap');
    const enquireSuccess = document.getElementById('ddEnquireSuccess');

    function openEnquirePopup() {
        if (!enquireOverlay) return;
        enquireOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        // Reset to form view
        if (enquireFormWrap) enquireFormWrap.style.display = 'block';
        if (enquireSuccess) enquireSuccess.classList.remove('active');
        if (enquireForm) enquireForm.reset();
    }

    function closeEnquirePopup() {
        if (!enquireOverlay) return;
        enquireOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Expose globally for inline onclick if needed
    window.openEnquirePopup = openEnquirePopup;
    window.closeEnquirePopup = closeEnquirePopup;

    // Close buttons
    if (enquireOverlay) {
        enquireOverlay.querySelectorAll('.dd-popup-close').forEach(btn => {
            btn.addEventListener('click', closeEnquirePopup);
        });
        // Close on overlay click
        enquireOverlay.addEventListener('click', function (e) {
            if (e.target === enquireOverlay) closeEnquirePopup();
        });
    }

    // ===== INTERCEPT "Enquire Now" / CTA BUTTONS =====
    // All links pointing to /contact except those in navbar, footer, sticky-icons
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href="/contact"]');
        if (!link) return;

        // Skip nav, footer, sticky icons — those should still navigate
        if (link.closest('.navbar') || link.closest('.nav-links') ||
            link.closest('.footer') || link.closest('.sticky-icons') ||
            link.closest('.footer-section') || link.closest('.footer-content')) {
            return;
        }

        e.preventDefault();
        openEnquirePopup();
    });

    // ===== ENQUIRE FORM SUBMISSION =====
    if (enquireForm) {
        enquireForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm(enquireForm, 'enquire', enquireFormWrap, enquireSuccess);
        });
    }

    // ===== COUNSELLING TIMED POPUP =====
    const counsellingOverlay = document.getElementById('ddCounsellingPopup');
    const counsellingForm = document.getElementById('ddCounsellingForm');
    const counsellingFormWrap = document.getElementById('ddCounsellingFormWrap');
    const counsellingSuccess = document.getElementById('ddCounsellingSuccess');

    function openCounsellingPopup() {
        if (!counsellingOverlay) return;
        counsellingOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeCounsellingPopup() {
        if (!counsellingOverlay) return;
        counsellingOverlay.classList.remove('active');
        document.body.style.overflow = '';
        // Mark as shown — suppress for 24 hours
        localStorage.setItem('dd_counselling_shown', Date.now().toString());
    }

    window.closeCounsellingPopup = closeCounsellingPopup;

    if (counsellingOverlay) {
        counsellingOverlay.querySelectorAll('.dd-popup-close').forEach(btn => {
            btn.addEventListener('click', closeCounsellingPopup);
        });
        counsellingOverlay.addEventListener('click', function (e) {
            if (e.target === counsellingOverlay) closeCounsellingPopup();
        });
    }

    // Trigger timed popup after 8 seconds (once per 24 hours)
    const lastShown = localStorage.getItem('dd_counselling_shown');
    const lastShownTs = parseInt(lastShown, 10);
    const oneDayMs = 24 * 60 * 60 * 1000;
    const shouldShow = !lastShown || isNaN(lastShownTs) || (Date.now() - lastShownTs) > oneDayMs;

    if (shouldShow) {
        setTimeout(function () {
            // Don't show if enquire popup is already open
            if (enquireOverlay && enquireOverlay.classList.contains('active')) return;
            openCounsellingPopup();
        }, 8000);
    }

    // Counselling form submission
    if (counsellingForm) {
        counsellingForm.addEventListener('submit', function (e) {
            e.preventDefault();
            submitForm(counsellingForm, 'counselling', counsellingFormWrap, counsellingSuccess);
        });
    }

    // ===== CLOSE ON ESC KEY =====
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeEnquirePopup();
            closeCounsellingPopup();
        }
    });

    // ===== PHONE VALIDATION =====
    document.querySelectorAll('input[name="phone"]').forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    });

    // ===== FORM SUBMISSION TO PHP BACKEND =====
    function submitForm(form, formType, formWrap, successEl) {
        const submitBtn = form.querySelector('.dd-submit-btn');
        const originalText = submitBtn.textContent;

        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="dd-spinner"></span> Submitting...';

        // Collect form data
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Add metadata
        data.formType = formType;
        data.pageUrl = window.location.href;

        // Combine phone
        if (data.countryCode && data.phone) {
            data.fullPhone = data.countryCode + data.phone;
        }

        console.log('Submitting popup form:', data);

        // Submit to PHP backend
        const apiPath = getApiPath();

        fetch(apiPath, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            console.log('Response:', result);
            if (result.success) {
                showSuccess(formWrap, successEl, submitBtn, originalText);
                // Track conversion (optional - for analytics)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submission', {
                        'event_category': formType === 'counselling' ? 'Counselling Popup' : 'Enquiry Popup',
                        'event_label': data.university || data.courseInterest || ''
                    });
                }
            } else {
                alert(result.message || 'Something went wrong. Please try again.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            alert('Unable to submit form. Please try again or contact us directly.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    }

    function showSuccess(formWrap, successEl, submitBtn, originalText) {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        if (formWrap) formWrap.style.display = 'none';
        if (successEl) successEl.classList.add('active');

        // Auto-close after 4 seconds
        setTimeout(() => {
            closeEnquirePopup();
            closeCounsellingPopup();
            // Reset
            if (formWrap) formWrap.style.display = 'block';
            if (successEl) successEl.classList.remove('active');
        }, 4000);
    }

})();
