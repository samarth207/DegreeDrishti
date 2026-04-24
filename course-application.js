// Course Application Modal Handler

// Function to open application modal
function openApplicationModal(university, course) {
    const modal = document.getElementById('applicationModal');
    if (!modal) return;

    // Set university and course in the form
    document.getElementById('modalUniversity').value = university;
    document.getElementById('modalCourse').value = course;

    // Update modal header
    const modalTitle = modal.querySelector('.modal-header h2');
    modalTitle.textContent = `Apply for ${course}`;

    const modalSubtitle = modal.querySelector('.modal-header p');
    modalSubtitle.textContent = university;

    // Update course info box
    document.getElementById('displayCourse').textContent = course;
    document.getElementById('displayUniversity').textContent = university;

    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Function to close modal
function closeApplicationModal() {
    const modal = document.getElementById('applicationModal');
    if (!modal) return;

    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Reset form
    document.getElementById('applicationForm').reset();
}

// Initialize modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Create modal HTML if it doesn't exist
    if (!document.getElementById('applicationModal')) {
        createModalHTML();
    }

    // Close modal when clicking overlay
    const modal = document.getElementById('applicationModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeApplicationModal();
            }
        });
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeApplicationModal();
        }
    });

    // Handle form submission
    const form = document.getElementById('applicationForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('.btn-submit');
            const originalText = submitBtn.textContent;

            const firstName = document.getElementById('modalFirstName').value.trim();
            const lastName = document.getElementById('modalLastName').value.trim();
            const email = document.getElementById('modalEmail').value.trim();
            const countryCode = document.getElementById('modalCountryCode').value;
            const phone = document.getElementById('modalPhone').value.trim();
            const university = document.getElementById('modalUniversity').value;
            const course = document.getElementById('modalCourse').value;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            // Build payload matching save-counselling.php expected fields
            const payload = {
                name: `${firstName} ${lastName}`,
                email: email,
                phone: phone,
                course: course,
                preferred_time: university, // store university in preferred_time field
                message: `University: ${university} | Country Code: ${countryCode}`
            };

            // Determine API path
            const apiPath = window.location.pathname.includes('/courses/')
                ? '../api/save-counselling.php'
                : 'api/save-counselling.php';

            try {
                const response = await fetch(apiPath, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();

                if (result.success) {
                    // Track conversion
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submission', {
                            'event_category': 'Apply Now',
                            'event_label': course
                        });
                    }
                    // Redirect to thank you page
                    const params = new URLSearchParams({
                        name: payload.name,
                        course: course,
                        university: university,
                        email: email,
                        phone: `${countryCode} ${phone}`
                    });
                    window.location.href = `/thankyou?${params.toString()}`;
                } else {
                    alert(result.message || 'Something went wrong. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Submission error:', error);
                alert('Unable to submit. Please try again or contact us directly.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }

    // Update all Apply Now buttons to use the modal
    updateApplyButtons();
});

// Function to create modal HTML
function createModalHTML() {
    const modalHTML = `
    <div id="applicationModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Apply Now</h2>
                <p>University Name</p>
                <button type="button" class="modal-close" onclick="closeApplicationModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="course-info-box">
                    <h3>📚 Application Details</h3>
                    <p><strong>Course:</strong> <span id="displayCourse">MBA</span></p>
                    <p><strong>University:</strong> <span id="displayUniversity">University Name</span></p>
                </div>

                <form id="applicationForm" class="modal-form">
                    <input type="hidden" id="modalUniversity" name="university">
                    <input type="hidden" id="modalCourse" name="course">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="modalFirstName">First Name <span class="required">*</span></label>
                            <input type="text" id="modalFirstName" name="firstName" required>
                        </div>
                        <div class="form-group">
                            <label for="modalLastName">Last Name <span class="required">*</span></label>
                            <input type="text" id="modalLastName" name="lastName" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="modalEmail">Email Address <span class="required">*</span></label>
                        <input type="email" id="modalEmail" name="email" required>
                    </div>

                    <div class="form-row phone-row">
                        <div class="form-group">
                            <label for="modalCountryCode">Code <span class="required">*</span></label>
                            <select id="modalCountryCode" name="countryCode" required>
                                <option value="+91">+91</option>
                                <option value="+1">+1</option>
                                <option value="+44">+44</option>
                                <option value="+971">+971</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="modalPhone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="modalPhone" name="phone" pattern="[0-9]{10}" maxlength="10" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        🚀 Submit Application
                    </button>
                </form>
            </div>
        </div>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// Function to update all Apply Now buttons to open the application modal
function updateApplyButtons() {
    const applyButtons = document.querySelectorAll('.btn-apply-small');

    applyButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();

            // Detect university from the img alt in the first td of the same row
            const row = button.closest('tr');
            let uniName = 'University';
            if (row) {
                const img = row.querySelector('.university-info img');
                if (img && img.alt) {
                    uniName = img.alt;
                }
            }

            // Course name: read from page <h1> or <title>
            const h1 = document.querySelector('h1');
            const courseName = h1 ? h1.textContent.trim() : document.title.trim();

            openApplicationModal(uniName.trim(), courseName);
        };
    });
}
