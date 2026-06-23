/**
 * CyberKavach Nexus - Global Application Scripts
 * Features: GSAP Loader, Intersection Observer, Global Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // 2. Netflix-style App Loader (GSAP)
    const initLoader = () => {
        const loader = document.getElementById('nexus-loader');
        const appContainer = document.querySelector('.app-container');
        
        // If loader doesn't exist, just fade in app
        if (!loader) {
            if (appContainer) appContainer.style.opacity = '1';
            return;
        }

        // Check if already seen in this session (optional, but good for UX)
        // For this demo, we play it every time or comment out session check
        /*
        if (sessionStorage.getItem('nexusLoaded')) {
            loader.style.display = 'none';
            if (appContainer) appContainer.style.opacity = '1';
            return;
        }
        sessionStorage.setItem('nexusLoaded', 'true');
        */

        const tl = gsap.timeline();

        // Logo scale in & fade in
        tl.to('.loader-logo', {
            duration: 0.8,
            scale: 1,
            opacity: 1,
            ease: "back.out(1.7)"
        })
        // Text typewriter/fade in
        .to('.loader-text', {
            duration: 1,
            opacity: 1,
            y: 0,
            ease: "power2.out"
        }, "-=0.4")
        // Hold for impact
        .to({}, { duration: 0.6 })
        // Fade out loader background and slide up content
        .to(loader, {
            duration: 0.8,
            opacity: 0,
            ease: "power2.inOut",
            onComplete: () => {
                loader.style.display = 'none';
            }
        })
        // Fade in main app
        .to(appContainer, {
            duration: 0.8,
            opacity: 1,
            ease: "power2.out"
        }, "-=0.4");
    };

    initLoader();

    // 3. Scroll Animations (Intersection Observer)
    const initScrollAnimations = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        if (!elements.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    // Optional: unobserve after animating once
                    // observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: "0px 0px -50px 0px"
        });

        elements.forEach(el => observer.observe(el));
    };

    initScrollAnimations();

    // 4. Global Toast Notification System
    window.showToast = (message, type = 'info') => {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let icon = 'info';
        if (type === 'success') icon = 'check-circle';
        if (type === 'warning') icon = 'alert-triangle';
        if (type === 'danger') icon = 'alert-circle';

        toast.innerHTML = `
            <i data-lucide="${icon}" class="text-${type}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(toast);
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: toast });

        // Animate in
        requestAnimationFrame(() => {
            toast.style.transition = 'all 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
            toast.classList.add('show');
        });

        // Remove after 4 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 4000);
    };

    // 5. Sidebar Toggle Logic (Mobile)
    const initSidebarToggle = () => {
        const toggleBtn = document.getElementById('mobileMenuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('is-open');
            });
        }
    };
    initSidebarToggle();
});
