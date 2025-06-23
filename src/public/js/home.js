let statsAnimated = false;
let observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};
document.addEventListener('DOMContentLoaded', function() {
    console.log('Home.js loaded successfully! 🏠');
    
    initSmoothScrolling();
    initScrollAnimations();
    initStatsCounter();
    initParallaxEffect();
    initLazyLoading();
    initInteractiveElements();
    initPerformanceOptimizations();
});

function initSmoothScrolling() {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', function() {
            const statsSection = document.querySelector('.stats-section');
            if (statsSection) {
                statsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}
function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                if (entry.target.classList.contains('stats-section') && !statsAnimated) {
                    animateStatsCounters();
                    statsAnimated = true;
                }
                
                if (entry.target.classList.contains('features-grid')) {
                    animateFeatureCards(entry.target);
                }
                
                if (entry.target.classList.contains('steps-container')) {
                    animateSteps(entry.target);
                }
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.stats-section, .features-section, .how-it-works, .cta-section').forEach(section => {
        observer.observe(section);
    });
    
    document.querySelectorAll('.features-grid, .steps-container').forEach(grid => {
        observer.observe(grid);
    });
    
    console.log('✅ Scroll animations initializat');
}

function animateFeatureCards(container) {
    const cards = container.querySelectorAll('.feature-card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease-out';
            
            card.offsetHeight;
            
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

function animateSteps(container) {
    const steps = container.querySelectorAll('.step');
    steps.forEach((step, index) => {
        setTimeout(() => {
            step.style.opacity = '0';
            step.style.transform = 'translateY(30px) scale(0.9)';
            step.style.transition = 'all 0.8s ease-out';
            
            step.offsetHeight;
            
            step.style.opacity = '1';
            step.style.transform = 'translateY(0) scale(1)';
        }, index * 150);
    });
}

function initStatsCounter() {
    const style = document.createElement('style');
    style.textContent = `
        .stat-card {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease-out;
        }
        
        .stat-card.animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .stat-number {
            display: inline-block;
        }
        
        .counting {
            background: linear-gradient(45deg, #4CAF50, #66BB6A, #81C784);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 2s ease-in-out;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    `;
    document.head.appendChild(style);
}

function animateStatsCounters() {
    const statNumbers = document.querySelectorAll('.stat-number');
    
    statNumbers.forEach((element, index) => {
        const text = element.textContent;
        const hasUnit = text.includes('kg');
        const number = parseFloat(text.replace(/[^\d.]/g, ''));
        
        if (isNaN(number)) return;
        
        setTimeout(() => {
            element.classList.add('counting');
            animateCounter(element, 0, number, 2000, hasUnit);
        }, index * 200);
    });
}

function animateCounter(element, start, end, duration, hasUnit = false) {
    const startTime = performance.now();
    const range = end - start;
    
    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easeProgress = 1 - Math.pow(1 - progress, 3);
        const currentValue = start + (range * easeProgress);
        
        let formattedValue = '';
        if (currentValue >= 1000) {
            formattedValue = (currentValue / 1000).toFixed(1).replace('.0', '');
            if (!hasUnit) {
                formattedValue = Number(formattedValue).toLocaleString('ro-RO');
            }
        } else {
            formattedValue = Math.floor(currentValue).toLocaleString('ro-RO');
        }
        
        element.textContent = formattedValue + (hasUnit ? 'kg' : '');
        
        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            element.classList.remove('counting');
            let finalValue = end.toLocaleString('ro-RO');
            if (hasUnit && end >= 1000) {
                finalValue = (end / 1000).toFixed(1) + 'k';
            }
            element.textContent = finalValue + (hasUnit ? 'kg' : '');
        }
    }
    
    requestAnimationFrame(updateCounter);
}

function initParallaxEffect() {
    const hero = document.querySelector('.hero');
    const floatingIcons = document.querySelectorAll('.floating-icon');
    
    if (!hero) return;
    
    let ticking = false;
    
    function updateParallax() {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        
        floatingIcons.forEach((icon, index) => {
            const speed = 0.2 + (index * 0.1);
            icon.style.transform = `translateY(${scrolled * speed}px) rotate(${scrolled * 0.1}deg)`;
        });
        
        ticking = false;
    }
    
    function requestParallaxUpdate() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestParallaxUpdate, { passive: true });
    
    console.log('✅ Parallax effect initializat');
}

function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    if (images.length > 0) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    }
    
    console.log('✅ Lazy loading initializat');
}

function initInteractiveElements() {
    document.querySelectorAll('.stat-card, .feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('mousedown', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.5);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                pointer-events: none;
            `;
            
            if (!document.getElementById('ripple-animation')) {
                const rippleStyle = document.createElement('style');
                rippleStyle.id = 'ripple-animation';
                rippleStyle.textContent = `
                    @keyframes ripple {
                        to {
                            transform: scale(2);
                            opacity: 0;
                        }
                    }
                `;
                document.head.appendChild(rippleStyle);
            }
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    document.querySelectorAll('.main-logo, .footer-logo').forEach(logo => {
        logo.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        logo.style.cursor = 'pointer';
    });
}

function initPerformanceOptimizations() {
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (window.innerWidth <= 768) {
                document.body.classList.add('mobile-optimized');
            } else {
                document.body.classList.remove('mobile-optimized');
            }
        }, 250);
    }, { passive: true });
    let scrollTimeout;
    window.addEventListener('scroll', function() {
        document.body.classList.add('scrolling');
        
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            document.body.classList.remove('scrolling');
        }, 150);
    }, { passive: true });
    
    document.querySelectorAll('a[href$="login.php"], a[href$="register.php"]').forEach(link => {
        link.addEventListener('mouseenter', function() {
            const preloadLink = document.createElement('link');
            preloadLink.rel = 'prefetch';
            preloadLink.href = this.href;
            document.head.appendChild(preloadLink);
        }, { once: true });
    });
}
function throttle(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

function animateIn(element, delay = 0) {
    setTimeout(() => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'all 0.6s ease-out';
        element.offsetHeight;
        
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';
    }, delay);
}

window.HomeUtils = {
    animateIn,
    isInViewport,
    throttle
};
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
    /* Optimizari de performanta */
    .scrolling * {
        pointer-events: none;
    }
    
    .mobile-optimized .floating-icon {
        animation: none;
    }
    
    /* Smooth transitions pentru toate elementele interactive */
    .stat-card,
    .feature-card,
    .step,
    .btn {
        transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
    }
    
    /* Focus states imbunatatite */
    .btn:focus-visible {
        outline: 3px solid #4CAF50;
        outline-offset: 2px;
    }
    
    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .floating-icon,
        .scroll-indicator {
            animation: none;
        }
        
        .stat-card,
        .feature-card,
        .step,
        .btn {
            transition: none;
        }
    }
    
    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .btn-primary {
            border: 2px solid #000;
        }
        
        .btn-secondary {
            border: 2px solid #fff;
        }
    }
`;
document.head.appendChild(additionalStyles);