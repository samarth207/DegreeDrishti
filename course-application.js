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
            
            const firstName = document.getElementById('modalFirstName').value;
            const lastName = document.getElementById('modalLastName').value;
            const email = document.getElementById('modalEmail').value;
            const countryCode = document.getElementById('modalCountryCode').value;
            const phone = document.getElementById('modalPhone').value;
            const university = document.getElementById('modalUniversity').value;
            const course = document.getElementById('modalCourse').value;

            // Submit to Google Sheets
            if (typeof submitToGoogleSheets === 'function') {
                await submitToGoogleSheets({
                    firstName,
                    lastName,
                    email,
                    countryCode,
                    phone,
                    course,
                    university
                }, 'application');
            }

            // Redirect to thank you page with parameters
            const params = new URLSearchParams({
                name: `${firstName} ${lastName}`,
                course: course,
                university: university,
                email: email,
                phone: `${countryCode} ${phone}`
            });

            window.location.href = `../thankyou.html?${params.toString()}`;
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

// Function to update all Apply Now buttons
function updateApplyButtons() {
    // Get all Apply Now buttons in the comparison table
    const applyButtons = document.querySelectorAll('.btn-apply-small');
    
    applyButtons.forEach(button => {
        // Get the university name and course from the row
        const row = button.closest('tr');
        if (row) {
            const universityCell = row.querySelector('.university-info span');
            const university = universityCell ? universityCell.textContent : 'University';
            
            // Get course name from page header
            const pageHeader = document.querySelector('.program-header h1');
            let course = 'Course';
            if (pageHeader) {
                course = pageHeader.textContent.replace(/📊|🎓|💼|📚|🏆|🎯|✨/g, '').trim();
            }

            // Replace the href with onclick
            button.href = 'javascript:void(0)';
            button.onclick = function(e) {
                e.preventDefault();
                openApplicationModal(university, course);
            };
        }
    });
}
