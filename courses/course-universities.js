/**
 * Dynamic Course Universities Loader
 * Loads universities for a specific course from the API and populates the table
 */

// Get the course name from the page URL or data attribute
function getCourseName() {
    // Try to get from data attribute on the universities table
    const table = document.getElementById('universitiesTable');
    if (table && table.dataset.course) {
        return table.dataset.course;
    }

    // Fallback: extract from URL path
    const path = window.location.pathname;
    const match = path.match(/\/courses\/([^\/]+)/);
    if (match) {
        // Convert slug to course name (e.g., mba -> MBA)
        return match[1].toUpperCase().replace('-', ' ');
    }

    return '';
}

// Format fee for display
function formatFee(amount) {
    if (!amount) return 'N/A';
    return '₹' + number_format(amount);
}

function number_format(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Generate table row HTML for a university
function generateUniversityRow(uni) {
    const course = uni.course || {};
    const fee = course.totalFee || course.fee || uni.maxFee || 0;
    const duration = course.duration || 'N/A';
    const specializations = course.specializations || [];

    // Build approvals string
    const approvals = [];
    if (uni.ugcApproved) approvals.push('UGC');
    if (uni.naacGrade) approvals.push('NAAC ' + uni.naacGrade);
    approvals.push('AICTE'); // Most are AICTE approved

    // Build ranking string
    let ranking = 'N/A';
    if (uni.ranking && uni.ranking.nirf) {
        ranking = 'NIRF Rank: ' + uni.ranking.nirf;
    } else if (uni.ranking && uni.ranking.outlook) {
        ranking = 'Outlook Rank: ' + uni.ranking.outlook;
    }

    // Build placement string
    const placement = uni.avgSalary ? '₹' + uni.avgSalary + ' LPA' : 'N/A';

    // Logo path
    const logoPath = uni.logo || '../images/university-logos/default.png';

    return `
        <tr>
            <td data-label="University">
                <div class="university-info">
                    <img src="${logoPath}" alt="${uni.name}" onerror="this.src='../images/university-logos/default.png'">
                    <div class="uni-details">
                        <strong>${uni.name}</strong>
                        <span class="uni-location">${uni.location || ''}</span>
                    </div>
                </div>
            </td>
            <td data-label="Fees (Total)">${formatFee(fee)}</td>
            <td data-label="Duration">${duration}</td>
            <td data-label="Ranking">${ranking}</td>
            <td data-label="Average Placement">${placement}</td>
            <td data-label="Degree Approvals">${approvals.join(', ')}</td>
            <td data-label="Website">
                <a href="${uni.websiteUrl || '#'}" target="_blank" rel="noopener noreferrer" class="btn-website">Visit Website</a>
            </td>
            <td data-label="Action">
                <button onclick="openCounsellingPopup()" class="btn-apply-small">Apply Now</button>
            </td>
        </tr>
    `;
}

// Load universities from API
async function loadCourseUniversities() {
    const courseName = getCourseName();
    if (!courseName) {
        console.error('Could not determine course name');
        return;
    }

    const tableBody = document.querySelector('#universitiesTable tbody');
    if (!tableBody) {
        console.error('Universities table not found');
        return;
    }

    // Show loading state
    tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;"><i class="fas fa-spinner fa-spin"></i> Loading universities...</td></tr>';

    try {
        const response = await fetch(`/api/get-course-universities.php?course=${encodeURIComponent(courseName)}`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            // Clear loading state
            tableBody.innerHTML = '';

            // Generate rows for each university
            result.data.forEach(uni => {
                tableBody.innerHTML += generateUniversityRow(uni);
            });

            // Update count if exists
            const countElement = document.getElementById('universityCount');
            if (countElement) {
                countElement.textContent = result.data.length;
            }
        } else {
            tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#999;">No universities found for this course.</td></tr>';
        }
    } catch (error) {
        console.error('Error loading universities:', error);
        tableBody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:30px;color:#e74c3c;">Error loading universities. Please try again later.</td></tr>';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCourseUniversities();
});
