// Course Data
const coursesData = {
    masters: [
        {
            name: 'MBA',
            duration: '2 Years',
            badge: 'trending'
        },
        {
            name: 'MBA (Dual Specification)',
            duration: '2 Years',
            badge: 'few-seats'
        },
        {
            name: 'MBA (WX)',
            duration: '2 Years',
            badge: 'trending'
        },
        {
            name: 'Executive MBA',
            duration: '1 Year',
            badge: 'few-seats'
        },
        {
            name: 'MCA',
            duration: '2 Years',
            badge: 'trending'
        },
        {
            name: 'MCom',
            duration: '2 Years',
            badge: null
        },
        {
            name: 'MSc (Data Science)',
            duration: '2 Years',
            badge: 'trending'
        },
        {
            name: 'MA (Journalism & Mass Communication)',
            duration: '2 Years',
            badge: null
        },
        {
            name: 'MA (Public Policy & Governance)',
            duration: '2 Years',
            badge: 'few-seats'
        }
    ],
    bachelors: [
        {
            name: 'BBA',
            duration: '3 Years',
            badge: 'trending'
        },
        {
            name: 'BCA',
            duration: '3 Years',
            badge: 'trending'
        },
        {
            name: 'BCom',
            duration: '3 Years',
            badge: null
        },
        {
            name: 'BA',
            duration: '3 Years',
            badge: 'few-seats'
        }
    ],
    integrated: [
        {
            name: 'BCA + MCA',
            duration: '5 Years',
            badge: 'trending'
        },
        {
            name: 'BBA + MBA',
            duration: '5 Years',
            badge: 'trending'
        },
        {
            name: 'B.Com + MBA',
            duration: '5 Years',
            badge: 'few-seats'
        },
        {
            name: 'B.Com + ACCA',
            duration: '5 Years',
            badge: 'few-seats'
        }
    ],
    diploma: [
        {
            name: 'Diploma in Digital Marketing',
            duration: '1 Year',
            badge: 'trending'
        },
        {
            name: 'Diploma in Financial Management',
            duration: '1 Year',
            badge: null
        },
        {
            name: 'Diploma in Business Analytics',
            duration: '1 Year',
            badge: 'few-seats'
        }
    ]
};

// Hamburger Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    if (hamburger) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
    }

    // Close menu when clicking on a link
    const navItems = document.querySelectorAll('.nav-links a');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navLinks.classList.remove('active');
            if (hamburger) {
                hamburger.classList.remove('active');
            }
        });
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
        applicationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const countryCode = document.getElementById('countryCode').value;
            const phone = document.getElementById('phone').value;
            const state = document.getElementById('state').value;
            const course = document.getElementById('course').value;

            alert(`Thank you ${firstName} ${lastName}!\n\nYour application for ${course} has been submitted successfully.\n\nWe will contact you soon at ${email} or ${countryCode} ${phone}.`);
            
            applicationForm.reset();
        });
    }

    // Contact Form Submission
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('contactFirstName').value;
            const lastName = document.getElementById('contactLastName').value;
            const email = document.getElementById('contactEmail').value;
            const subject = document.getElementById('contactSubject').value;

            alert(`Thank you ${firstName} ${lastName}!\n\nYour message regarding "${subject}" has been sent successfully.\n\nWe will reply to you at ${email} soon.`);
            
            contactForm.reset();
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
                    <p class="course-duration">
                        <i class="fas fa-clock"></i> ${course.duration}
                    </p>
                    <div class="course-actions">
                        <button class="btn-apply-course" onclick="applyForCourse('${course.name}')">Apply Now</button>
                        <button class="btn-more-info" onclick="showMoreInfo('${course.name}')">More Info</button>
                    </div>
                </div>
            </div>
        `;

        courseCardsContainer.innerHTML += cardHTML;
    });
}

// Function to apply for a course
function applyForCourse(courseName) {
    // Scroll to the form
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Wait for scroll to complete, then select the course
    setTimeout(() => {
        const courseSelect = document.getElementById('course');
        if (courseSelect) {
            // Find and select the matching option
            const options = courseSelect.options;
            for (let i = 0; i < options.length; i++) {
                if (options[i].value === courseName || options[i].text === courseName) {
                    courseSelect.selectedIndex = i;
                    // Highlight the select field
                    courseSelect.focus();
                    courseSelect.style.border = '2px solid #FFD700';
                    setTimeout(() => {
                        courseSelect.style.border = '';
                    }, 2000);
                    break;
                }
            }
        }
    }, 800);
}

// Function to show more info about a course
function showMoreInfo(courseName) {
    // Create a mapping of course names to their page URLs
    const coursePages = {
        'MBA': 'courses/mba.html',
        'MBA (Dual Specification)': 'courses/mba-dual.html',
        'MBA (WX)': 'courses/mba-wx.html',
        'Executive MBA': 'courses/executive-mba.html',
        'MCA': 'courses/mca.html',
        'MCom': 'courses/mcom.html',
        'MSc (Data Science)': 'courses/msc-data-science.html',
        'MA (Journalism & Mass Communication)': 'courses/ma-journalism.html',
        'MA (Public Policy & Governance)': 'courses/ma-public-policy.html',
        'BBA': 'courses/bba.html',
        'BCA': 'courses/bca.html',
        'BCom': 'courses/bcom.html',
        'BA': 'courses/ba.html',
        'BCA + MCA': 'courses/bca-mca.html',
        'BBA + MBA': 'courses/bba-mba.html',
        'B.Com + MBA': 'courses/bcom-mba.html',
        'B.Com + ACCA': 'courses/bcom-acca.html'
    };
    
    const pageUrl = coursePages[courseName];
    if (pageUrl) {
        window.location.href = pageUrl;
    } else {
        alert(`More information about ${courseName} will be available soon!`);
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