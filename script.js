// Hero Carousel
let currentSlideIndex = 1;
let autoSlideInterval;

// Show slide function
function showSlide(n) {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    
    if (n > slides.length) { currentSlideIndex = 1; }
    if (n < 1) { currentSlideIndex = slides.length; }
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    slides[currentSlideIndex - 1].classList.add('active');
    dots[currentSlideIndex - 1].classList.add('active');
}

// Change slide function
function changeSlide(n) {
    clearInterval(autoSlideInterval);
    currentSlideIndex += n;
    showSlide(currentSlideIndex);
    startAutoSlide();
}

// Go to specific slide
function currentSlide(n) {
    clearInterval(autoSlideInterval);
    currentSlideIndex = n;
    showSlide(currentSlideIndex);
    startAutoSlide();
}

// Auto slide function
function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        currentSlideIndex++;
        showSlide(currentSlideIndex);
    }, 5000); // Change slide every 5 seconds
}

// Initialize carousel
document.addEventListener('DOMContentLoaded', function() {
    showSlide(currentSlideIndex);
    startAutoSlide();
});

// Course Data
const coursesData = {
    masters: [
        {
            name: 'Online MBA',
            fullName: 'Online Master of Business Administration',
            duration: '2 Years',
            specialization: 'Management & Leadership',
            benefit: 'High ROI Career',
            badge: 'trending'
        },
        {
            name: 'Online MBA (Dual Specification)',
            fullName: 'Online Master of Business Administration (Dual)',
            duration: '2 Years',
            specialization: 'Two Specializations',
            benefit: 'Enhanced Career Options',
            badge: 'few-seats'
        },
        {
            name: 'Online MBA (WX)',
            fullName: 'Online MBA for Working Executives',
            duration: '2 Years',
            specialization: 'Executive Management',
            benefit: 'Work While You Study',
            badge: 'trending'
        },
        {
            name: 'Online Executive MBA',
            fullName: 'Online Executive Master of Business Administration',
            duration: '1 Year',
            specialization: 'Fast-Track Program',
            benefit: 'Quick Career Boost',
            badge: 'few-seats'
        },
        {
            name: 'Online MCA',
            fullName: 'Online Master of Computer Applications',
            duration: '2 Years',
            specialization: 'Software & IT',
            benefit: 'High Demand Skills',
            badge: 'trending'
        },
        {
            name: 'Online MCom',
            fullName: 'Online Master of Commerce',
            duration: '2 Years',
            specialization: 'Commerce & Finance',
            benefit: 'Accounting Excellence',
            badge: null
        },
        {
            name: 'Online MSc (Data Science)',
            fullName: 'Online Master of Science in Data Science',
            duration: '2 Years',
            specialization: 'AI & Analytics',
            benefit: 'Future-Ready Career',
            badge: 'trending'
        },
        {
            name: 'Online MA (Journalism & Mass Communication)',
            fullName: 'Online Master of Arts in Journalism',
            duration: '2 Years',
            specialization: 'Media & Communication',
            benefit: 'Creative Industry',
            badge: null
        },
        {
            name: 'Online MA (Public Policy & Governance)',
            fullName: 'Online Master of Arts in Public Policy',
            duration: '2 Years',
            specialization: 'Policy & Governance',
            benefit: 'Social Impact Career',
            badge: 'few-seats'
        }
    ],
    bachelors: [
        {
            name: 'Online BBA',
            fullName: 'Online Bachelor of Business Administration',
            duration: '3 Years',
            specialization: 'Business Management',
            benefit: 'Career Foundation',
            badge: 'trending'
        },
        {
            name: 'Online BCA',
            fullName: 'Online Bachelor of Computer Applications',
            duration: '3 Years',
            specialization: 'Computer Science',
            benefit: 'Tech Career Launch',
            badge: 'trending'
        },
        {
            name: 'Online BCom',
            fullName: 'Online Bachelor of Commerce',
            duration: '3 Years',
            specialization: 'Commerce & Accounts',
            benefit: 'Industry Recognition',
            badge: null
        },
        {
            name: 'Online BA',
            fullName: 'Online Bachelor of Arts',
            duration: '3 Years',
            specialization: 'Liberal Arts',
            benefit: 'Diverse Opportunities',
            badge: 'few-seats'
        }
    ],
    integrated: [
        {
            name: 'Online BCA + MCA',
            fullName: 'Online Integrated BCA + MCA Program',
            duration: '5 Years',
            specialization: 'Complete IT Education',
            benefit: 'Save Time & Money',
            badge: 'trending'
        },
        {
            name: 'Online BBA + MBA',
            fullName: 'Online Integrated BBA + MBA Program',
            duration: '5 Years',
            specialization: 'Complete Management',
            benefit: 'Fast Track Success',
            badge: 'trending'
        },
        {
            name: 'Online B.Com + MBA',
            fullName: 'Online Integrated B.Com + MBA Program',
            duration: '5 Years',
            specialization: 'Commerce to Management',
            benefit: 'Dual Advantage',
            badge: 'few-seats'
        },
        {
            name: 'Online B.Com + ACCA',
            fullName: 'Online B.Com + ACCA Certification',
            duration: '5 Years',
            specialization: 'Global Accounting',
            benefit: 'International Career',
            badge: 'few-seats'
        }
    ],
    diploma: [
        {
            name: 'Online Diploma in Digital Marketing',
            fullName: 'Online Professional Diploma in Digital Marketing',
            duration: '1 Year',
            specialization: 'Online Marketing',
            benefit: 'High Demand Skill',
            badge: 'trending'
        },
        {
            name: 'Online Diploma in Financial Management',
            fullName: 'Online Professional Diploma in Finance',
            duration: '1 Year',
            specialization: 'Financial Planning',
            benefit: 'Quick Upskilling',
            badge: null
        },
        {
            name: 'Online Diploma in Business Analytics',
            fullName: 'Online Professional Diploma in Analytics',
            duration: '1 Year',
            specialization: 'Data Analytics',
            benefit: 'Career Advancement',
            badge: 'few-seats'
        }
    ]
};

// Hamburger Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');
    const dropdown = document.querySelector('.dropdown');

    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }

    // Handle dropdown on mobile
    if (dropdown) {
        const dropbtn = dropdown.querySelector('.dropbtn');
        if (dropbtn) {
            dropbtn.addEventListener('click', function(e) {
                if (window.innerWidth <= 968) {
                    e.preventDefault();
                    dropdown.classList.toggle('active');
                }
            });
        }
    }

    // Close menu when clicking on a link
    const navItems = document.querySelectorAll('.nav-links a:not(.dropbtn)');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navLinks.classList.remove('active');
            if (hamburger) {
                hamburger.classList.remove('active');
            }
            if (dropdown) {
                dropdown.classList.remove('active');
            }
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navLinks.contains(e.target) && !hamburger.contains(e.target)) {
            navLinks.classList.remove('active');
            if (hamburger) {
                hamburger.classList.remove('active');
            }
            if (dropdown) {
                dropdown.classList.remove('active');
            }
        }
    });

    // Course Category Filter
    const categoryButtons = document.querySelectorAll('.category-btn');
    const courseCardsContainer = document.getElementById('courseCards');

    if (categoryButtons.length > 0 && courseCardsContainer) {
        // Load initial courses (masters)
        loadCourses('masters');

        categoryButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get category
                const category = this.getAttribute('data-category');
                
                // Load courses
                loadCourses(category);
            });
        });
    }

    // Application Form Submission
    const applicationForm = document.getElementById('applicationForm');
    if (applicationForm) {
        applicationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Form submitted! Starting processing...');
            
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const countryCode = document.getElementById('countryCode').value;
            const phone = document.getElementById('phone').value;
            const state = document.getElementById('state').value;
            const course = document.getElementById('course').value;

            console.log('Form data collected:', { firstName, lastName, email, countryCode, phone, state, course });

            // Redirect to thank you page
            console.log('Redirecting to thank you page...');
            window.location.href = `/thankyou?name=${encodeURIComponent(firstName + ' ' + lastName)}&course=${encodeURIComponent(course)}`;
        });
    } else {
        console.log('Application form not found on this page');
    }

    // Contact Form Submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('contactFirstName').value;
            const lastName = document.getElementById('contactLastName').value;
            const email = document.getElementById('contactEmail').value;
            const countryCode = document.getElementById('contactCountryCode').value;
            const phone = document.getElementById('contactPhone').value;
            const subject = document.getElementById('contactSubject').value;
            const message = document.getElementById('contactMessage').value;

            // Redirect to thank you page
            window.location.href = `/thankyou?name=${encodeURIComponent(firstName + ' ' + lastName)}`;
        });
    }
});

// Function to load courses
function loadCourses(category) {
    const courseCardsContainer = document.getElementById('courseCards');
    const courses = coursesData[category] || [];

    courseCardsContainer.innerHTML = '';

    courses.forEach(course => {
        const badgeHTML = course.badge 
            ? `<span class="badge ${course.badge}">${course.badge === 'trending' ? '🔥 Trending' : '⚡ Few Seats Left'}</span>` 
            : '';

        const cardHTML = `
            <div class="course-card">
                <div class="course-image">
                    ${badgeHTML}
                </div>
                <div class="course-info">
                    <h3>${course.name}</h3>
                    <div class="course-details">
                        <div class="detail-item">
                            <span class="detail-icon">⏱️</span>
                            <span class="detail-text"><strong>Duration:</strong> ${course.duration}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">💼</span>
                            <span class="detail-text">${course.specialization}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-icon">💰</span>
                            <span class="detail-text">${course.benefit}</span>
                        </div>
                    </div>
                    <div class="course-actions">
                        <button class="btn-apply-course" onclick="applyForCourse('${course.name}')">Apply Now</button>
                        <button class="btn-compare" onclick="compareUniversities('${course.name}')">Compare Universities</button>
                    </div>
                </div>
            </div>
        `;

        courseCardsContainer.innerHTML += cardHTML;
    });
}

// Function to apply for a course
function applyForCourse(courseName) {
    // Open the counselling popup
    openCounsellingPopup();
    
    // Pre-select the course in the popup form
    setTimeout(() => {
        const courseSelect = document.getElementById('counselling-course');
        if (courseSelect) {
            // Map course names to select values
            const courseMap = {
                'Online MBA': 'MBA',
                'Online MBA (Dual Specification)': 'MBA',
                'Online MBA (WX)': 'MBA',
                'Online Executive MBA': 'Executive MBA',
                'Online MCA': 'MCA',
                'Online MCom': 'Other',
                'Online MSc (Data Science)': 'MSc Data Science',
                'Online MA (Journalism)': 'Other',
                'Online MA (Public Policy)': 'Other',
                'Online BBA': 'BBA',
                'Online BCA': 'BCA',
                'Online BCom': 'BCom',
                'Online BA': 'Other',
                'BCA + MCA (Integrated)': 'Other',
                'BBA + MBA (Integrated)': 'Other',
                'B.Com + MBA (Integrated)': 'Other',
                'B.Com + ACCA': 'Other',
                'Diploma in Digital Marketing': 'Other',
                'Diploma in Financial Management': 'Other',
                'Diploma in Business Analytics': 'Other'
            };
            
            const selectValue = courseMap[courseName] || 'Other';
            
            // Find and select the matching option
            const options = courseSelect.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === selectValue) {
                    courseSelect.selectedIndex = i;
                    // Highlight the select field
                    courseSelect.style.border = '2px solid #FFD700';
                    setTimeout(() => {
                        courseSelect.style.border = '';
                    }, 2000);
                    break;
                }
            }
        }
    }, 100);
}

// Function to show more info about a course
function showMoreInfo(courseName) {
    // Create a mapping of course names to their page URLs
    const coursePages = {
        'MBA': '/courses/mba',
        'MBA (Dual Specification)': '/courses/mba-dual',
        'MBA (WX)': '/courses/mba-wx',
        'Executive MBA': '/courses/executive-mba',
        'MCA': '/courses/mca',
        'MCom': '/courses/mcom',
        'MSc (Data Science)': '/courses/msc-data-science',
        'MA (Journalism & Mass Communication)': '/courses/ma-journalism',
        'MA (Public Policy & Governance)': '/courses/ma-public-policy',
        'BBA': '/courses/bba',
        'BCA': '/courses/bca',
        'BCom': '/courses/bcom',
        'BA': '/courses/ba',
        'BCA + MCA': '/courses/bca-mca',
        'BBA + MBA': '/courses/bba-mba',
        'B.Com + MBA': '/courses/bcom-mba',
        'B.Com + ACCA': '/courses/bcom-acca'
    };
    
    const pageUrl = coursePages[courseName];
    if (pageUrl) {
        window.location.href = pageUrl;
    } else {
        alert(`More information about ${courseName} will be available soon!`);
    }
}

// Function to compare universities for a course
function compareUniversities(courseName) {
    // Remove 'Online ' prefix if present for URL mapping
    const cleanCourseName = courseName.replace(/^Online /, '');
    
    // Create a mapping of course names to their comparison page URLs
    const coursePages = {
        'MBA': '/courses/mba',
        'MBA (Dual Specification)': '/courses/mba-dual',
        'MBA (WX)': '/courses/mba-wx',
        'Executive MBA': '/courses/executive-mba',
        'MCA': '/courses/mca',
        'MCom': '/courses/mcom',
        'MSc (Data Science)': '/courses/msc-data-science',
        'MA (Journalism & Mass Communication)': '/courses/ma-journalism',
        'MA (Public Policy & Governance)': '/courses/ma-public-policy',
        'BBA': '/courses/bba',
        'BCA': '/courses/bca',
        'BCom': '/courses/bcom',
        'BA': '/courses/ba',
        'BCA + MCA': '/courses/bca-mca',
        'BBA + MBA': '/courses/bba-mba',
        'B.Com + MBA': '/courses/bcom-mba',
        'B.Com + ACCA': '/courses/bcom-acca',
        'Diploma in Digital Marketing': '/courses/diploma-digital-marketing',
        'Diploma in Financial Management': '/courses/diploma-financial-management',
        'Diploma in Business Analytics': '/courses/diploma-business-analytics'
    };
    
    const pageUrl = coursePages[cleanCourseName];
    if (pageUrl) {
        // Navigate to the course page with comparison section
        window.location.href = pageUrl + '#comparison';
    } else {
        alert(`University comparison for ${courseName} will be available soon!`);
    }
}

// Smooth Scrolling
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Scroll Animation
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.course-card, .counselor-card, .partner-logo');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// ================================
// Counselling Popup Functions
// ================================

// Open Counselling Popup
function openCounsellingPopup() {
    const popup = document.getElementById('counsellingPopup');
    const form = document.getElementById('counsellingForm');
    const success = document.getElementById('counsellingSuccess');
    
    // Reset form and show it
    if (form) {
        form.style.display = 'block';
        form.reset();
    }
    if (success) {
        success.style.display = 'none';
    }
    
    // Show popup with animation
    popup.style.display = 'flex';
    setTimeout(() => {
        popup.classList.add('active');
    }, 10);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

// Close Counselling Popup
function closeCounsellingPopup() {
    const popup = document.getElementById('counsellingPopup');
    
    popup.classList.remove('active');
    setTimeout(() => {
        popup.style.display = 'none';
    }, 300);
    
    // Restore body scroll
    document.body.style.overflow = 'auto';
}

// Close popup on overlay click
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('counsellingPopup');
    if (popup) {
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closeCounsellingPopup();
            }
        });
    }
    
    // Close popup on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCounsellingPopup();
        }
    });
    
    // Handle form submission
    const counsellingForm = document.getElementById('counsellingForm');
    if (counsellingForm) {
        counsellingForm.addEventListener('submit', handleCounsellingSubmit);
    }
    
    // Auto-open popup after 30 seconds (optional - uncomment to enable)
    // setTimeout(openCounsellingPopup, 30000);
});

// Handle Counselling Form Submission
async function handleCounsellingSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const submitBtn = document.getElementById('counsellingSubmitBtn');
    const successDiv = document.getElementById('counsellingSuccess');
    
    // Get form data
    const messageField = document.getElementById('counselling-message');
    const formData = {
        name: document.getElementById('counselling-name').value.trim(),
        email: document.getElementById('counselling-email').value.trim(),
        phone: document.getElementById('counselling-phone').value.trim(),
        course: document.getElementById('counselling-course').value,
        preferred_time: document.getElementById('counselling-time').value,
        message: messageField ? messageField.value.trim() : ''
    };
    
    // Validate phone number
    if (!/^[0-9]{10}$/.test(formData.phone)) {
        alert('Please enter a valid 10-digit phone number');
        return;
    }
    
    // Validate email
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
        alert('Please enter a valid email address');
        return;
    }
    
    // Show loading state
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Determine the correct API path based on current page location
    let apiPath = 'api/save-counselling.php';
    if (window.location.pathname.includes('/courses/')) {
        apiPath = '../api/save-counselling.php';
    }
    
    console.log('Submitting form data:', formData);
    console.log('API path:', apiPath);
    
    try {
        // Send data to PHP backend
        const response = await fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Response data:', result);
        
        if (result.success) {
            // Show success message
            form.style.display = 'none';
            successDiv.style.display = 'block';
            
            // Track conversion (optional - for analytics)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'form_submission', {
                    'event_category': 'Counselling',
                    'event_label': formData.course
                });
            }
        } else {
            alert(result.message || 'Something went wrong. Please try again.');
            console.error('Form submission failed:', result);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        alert('Unable to submit form. Please try again or contact us directly.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// ================================
// Dynamic University Nav Dropdown
// ================================
document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('navUniDropdown');
    if (!dropdown) return;

    fetch('/api/get-universities.php')
        .then(r => r.json())
        .then(json => {
            if (!json.success || !json.data.length) {
                dropdown.innerHTML = '<div style="padding:12px 20px;color:#999;font-size:13px;">No universities found.</div>';
                return;
            }
            dropdown.innerHTML = json.data.map(u => `
                <a href="${u.pageUrl || u.websiteUrl || '#'}" class="university-item">
                    <img src="${u.logo || '/images/university-logos/default.png'}"
                         alt="${u.name}"
                         onerror="this.src='/images/university-logos/default.png'"
                         style="width:50px;height:50px;object-fit:contain;border-radius:8px;background:#f5f5f5;padding:5px;">
                    <div class="university-info">
                        <span class="university-name">${u.shortName || u.name}</span>
                        <span class="university-courses">${u.courseCount || '10'}+ Courses</span>
                    </div>
                </a>`).join('');
        })
        .catch(() => {
            // Graceful fallback — keep loading spinner hidden
            dropdown.innerHTML = '';
        });
});