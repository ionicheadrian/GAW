let mobileMenuOpen = false;
let currentPage = '';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Navbar JavaScript loaded successfully!');
    
    initCurrentPage();
    initMobileMenu();
    initActivePageMarking();
    initProfileDropdown();
    initScrollBehavior();
});
function initCurrentPage() {
    currentPage = window.location.pathname.split('/').pop();
    if (!currentPage) {
        currentPage = 'dashboard.php';
    }
}

function initMobileMenu() {
    const mobileBtn = document.querySelector('.mobile-menu');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', toggleMobileMenu);
        const navLinksItems = navLinks.querySelectorAll('.pagini');
        navLinksItems.forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

    }
}

function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const mobileBtn = document.querySelector('.mobile-menu');
    
    if (navLinks && mobileBtn) {
        mobileMenuOpen = !mobileMenuOpen;
        
        if (mobileMenuOpen) {
            navLinks.classList.add('mobile-open');
            mobileBtn.innerHTML = '✕';
            mobileBtn.setAttribute('aria-expanded', 'true');
            document.body.style.overflow = 'hidden';
        } else {
            navLinks.classList.remove('mobile-open');
            mobileBtn.innerHTML = '☰';
            mobileBtn.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        }
    }
}

function closeMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const mobileBtn = document.querySelector('.mobile-menu');
    
    if (navLinks && mobileBtn && mobileMenuOpen) {
        navLinks.classList.remove('mobile-open');
        mobileBtn.innerHTML = '☰';
        mobileBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        mobileMenuOpen = false;
    }
}

function initActivePageMarking() {
    const navLinks = document.querySelectorAll('.nav-links .pagini');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        const linkPage = link.getAttribute('href');
        if (linkPage === currentPage || 
            (currentPage === 'index.php' && linkPage === 'dashboard.php') ||
            (currentPage === '' && linkPage === 'dashboard.php')) {
            
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        }
    });
}

function initProfileDropdown() {
    const userProfile = document.querySelector('.userprofile');
    const dropdown = document.querySelector('.profile-dropdown');
    
    if (userProfile && dropdown) {
        let hoverTimeout;
        
        userProfile.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
            dropdown.style.opacity = '1';
            dropdown.style.visibility = 'visible';
            dropdown.style.transform = 'translateY(0)';
        });
        
        userProfile.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                dropdown.style.opacity = '0';
                dropdown.style.visibility = 'hidden';
                dropdown.style.transform = 'translateY(-10px)';
            }, 300);
        });
        
        dropdown.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
        });
        
        dropdown.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                dropdown.style.opacity = '0';
                dropdown.style.visibility = 'hidden';
                dropdown.style.transform = 'translateY(-10px)';
            }, 100);
        });
    }
}

function initScrollBehavior() {
    let lastScrollTop = 0;
    const navbar = document.querySelector('nav');
    
    if (navbar) {
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (window.innerWidth <= 768) {
                if (scrollTop > lastScrollTop && scrollTop > 100) {
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    navbar.style.transform = 'translateY(0)';
                }
            }
            
            if (scrollTop > 0) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            
            lastScrollTop = scrollTop;
        });

    }
}

document.addEventListener('click', function(event) {
    const navLinks = document.querySelector('.nav-links');
    const mobileBtn = document.querySelector('.mobile-menu');
    const userProfile = document.querySelector('.userprofile');
    if (navLinks && mobileBtn && mobileMenuOpen) {
        if (!navLinks.contains(event.target) && !mobileBtn.contains(event.target)) {
            closeMobileMenu();
        }
    }
    
    if (userProfile && !userProfile.contains(event.target)) {
        const dropdown = document.querySelector('.profile-dropdown');
        if (dropdown) {
            dropdown.style.opacity = '0';
            dropdown.style.visibility = 'hidden';
            dropdown.style.transform = 'translateY(-10px)';
        }
    }
});

window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && mobileMenuOpen) {
        closeMobileMenu();
    }
    
    const navbar = document.querySelector('nav');
    if (navbar && window.innerWidth > 768) {
        navbar.style.transform = 'translateY(0)';
    }
});


window.NavbarUtils = {
    markPageActive: function(href) {
        const navLinks = document.querySelectorAll('.nav-links .pagini');
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === href) {
                link.classList.add('active');
            }
        });
    },
    closeMobileMenu: closeMobileMenu,
    isMobileMenuOpen: function() {
        return mobileMenuOpen;
    }
};

const style = document.createElement('style');
style.textContent = `
    nav {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    nav.scrolled {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    @media (max-width: 768px) {
        nav {
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
        }
        
        body {
            padding-top: 70px; /* Spatiu pentru navbar fixed */
        }
    }
`;
document.head.appendChild(style);