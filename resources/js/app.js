import './bootstrap';
import Alpine from 'alpinejs';

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

// HOHO Platform Apple-Style Interactive System

// Sound effect URLs - you can replace these with actual sound files
const SOUNDS = {
    click: 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0PLYiTYIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsI=',
    success: 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0PLYiTYIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsI=',
    error: 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0PLYiTYIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsI=',
    notify: 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0PLYiTYIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsIGGm98tqOOQYNTaTe9LNmGgU5ltryxnkpBSl+zPLaizsI='
};

// User preferences
let userPreferences = {
    soundEnabled: localStorage.getItem('hoho-sound-enabled') !== 'false',
    animationsEnabled: localStorage.getItem('hoho-animations-enabled') !== 'false',
    reducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches
};

// Alpine.js stores and utilities
Alpine.store('ui', {
    soundEnabled: userPreferences.soundEnabled,
    animationsEnabled: userPreferences.animationsEnabled,
    darkMode: localStorage.getItem('hoho-dark-mode') === 'true',

    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('hoho-sound-enabled', this.soundEnabled);
        userPreferences.soundEnabled = this.soundEnabled;
        if (this.soundEnabled) {
            playSfx('click');
        }
    },

    toggleAnimations() {
        this.animationsEnabled = !this.animationsEnabled;
        localStorage.setItem('hoho-animations-enabled', this.animationsEnabled);
        userPreferences.animationsEnabled = this.animationsEnabled;
        document.documentElement.style.setProperty('--animation-enabled', this.animationsEnabled ? '1' : '0');
    },

    toggleDarkMode() {
        this.darkMode = !this.darkMode;
        localStorage.setItem('hoho-dark-mode', this.darkMode);
        document.documentElement.classList.toggle('dark', this.darkMode);
        playSfx('click');
    }
});

// Alpine.js directives
Alpine.directive('sound', (el, { expression }) => {
    el.addEventListener('click', () => {
        if (userPreferences.soundEnabled) {
            playSfx(expression || 'click');
        }
    });
});

Alpine.directive('reveal', (el, { value, expression }) => {
    const delay = parseInt(expression) || 0;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    if (userPreferences.animationsEnabled && !userPreferences.reducedMotion) {
                        entry.target.classList.add('active');
                    } else {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'none';
                    }
                }, delay);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    observer.observe(el);
});

// Sound effect function
function playSfx(name) {
    if (!userPreferences.soundEnabled || userPreferences.reducedMotion) return;

    try {
        const audio = new Audio(SOUNDS[name] || SOUNDS.click);
        audio.volume = 0.3;
        audio.play().catch(() => {}); // Ignore play errors
    } catch (error) {
        // Silent fail for audio errors
    }
}

// Reveal animation function
function reveal(el, delay = 0) {
    if (!userPreferences.animationsEnabled || userPreferences.reducedMotion) {
        el.style.opacity = '1';
        el.style.transform = 'none';
        return;
    }

    setTimeout(() => {
        el.classList.add('scroll-reveal', 'active');
    }, delay);
}

// Initialize system
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeAnimations();
    initializeInteractions();
    initializeScrollEffects();
    initializeSkeletonLoading();
    initializeToastSystem();
});

// Initialize theme
function initializeTheme() {
    const darkMode = localStorage.getItem('hoho-dark-mode') === 'true';
    document.documentElement.classList.toggle('dark', darkMode);
    Alpine.store('ui').darkMode = darkMode;
}

// Initialize animations with stagger effect
function initializeAnimations() {
    if (!userPreferences.animationsEnabled || userPreferences.reducedMotion) return;

    // Stagger animations for grouped elements
    const staggerContainers = document.querySelectorAll('.stagger-children');
    staggerContainers.forEach(container => {
        const children = container.children;
        Array.from(children).forEach((child, index) => {
            child.style.animationDelay = `${index * 0.1}s`;
        });
    });

    // Initialize reveal elements
    const fadeElements = document.querySelectorAll('.fade-in-up, .fade-in-scale, .scroll-reveal');
    fadeElements.forEach((element, index) => {
        if (!element.classList.contains('scroll-reveal')) {
            element.style.animationDelay = `${index * 0.1}s`;
        }
    });

    // Parallax elements
    const parallaxElements = document.querySelectorAll('.parallax');
    if (parallaxElements.length > 0) {
        initializeParallax();
    }
}

// Initialize enhanced interactions
function initializeInteractions() {
    // Enhanced button interactions with ripple effect
    const buttons = document.querySelectorAll('.btn, button, [role="button"]');

    buttons.forEach(button => {
        // Add ripple effect
        button.addEventListener('click', function(e) {
            if (!userPreferences.animationsEnabled) return;

            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.className = 'absolute rounded-full bg-white/30 pointer-events-none';
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple-animation 0.6s linear';

            this.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);

            // Play sound
            playSfx('click');
        });

        // Add focus handling
        button.addEventListener('focus', function() {
            if (userPreferences.animationsEnabled) {
                this.style.transform = 'scale(1.02)';
            }
        });

        button.addEventListener('blur', function() {
            if (userPreferences.animationsEnabled) {
                this.style.transform = 'scale(1)';
            }
        });
    });

    // Enhanced card interactions
    const cards = document.querySelectorAll('.card-interactive, .card-hover');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (userPreferences.animationsEnabled) {
                this.style.transform = 'translateY(-4px) scale(1.02)';
            }
            playSfx('click');
        });

        card.addEventListener('mouseleave', function() {
            if (userPreferences.animationsEnabled) {
                this.style.transform = 'translateY(0) scale(1)';
            }
        });
    });

    // Form interactions
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            playSfx('click');
            if (userPreferences.animationsEnabled) {
                this.parentElement?.classList.add('focused');
            }
        });

        input.addEventListener('blur', function() {
            this.parentElement?.classList.remove('focused');
        });
    });

    // Link interactions
    const links = document.querySelectorAll('a, .nav-link, .mobile-nav-link');
    links.forEach(link => {
        link.addEventListener('click', function() {
            playSfx('click');
        });
    });

    // Dropdown interactions
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
        const trigger = dropdown.querySelector('[data-dropdown-trigger]');
        const content = dropdown.querySelector('.dropdown-content');

        if (trigger && content) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
                playSfx('click');
            });

            document.addEventListener('click', function() {
                dropdown.classList.remove('open');
            });
        }
    });
}

// Enhanced scroll effects
function initializeScrollEffects() {
    if (!userPreferences.animationsEnabled || userPreferences.reducedMotion) {
        // Just make elements visible without animation
        const scrollElements = document.querySelectorAll('.scroll-reveal');
        scrollElements.forEach(el => {
            el.style.opacity = '1';
            el.style.transform = 'none';
        });
        return;
    }

    // Intersection Observer for reveal animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');

                // Trigger stagger animation for children
                const staggerChildren = entry.target.querySelectorAll('.stagger-children > *');
                staggerChildren.forEach((child, index) => {
                    setTimeout(() => {
                        child.classList.add('active');
                    }, index * 100);
                });
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    const scrollElements = document.querySelectorAll('.scroll-reveal');
    scrollElements.forEach(el => observer.observe(el));
}

// Initialize parallax effects
function initializeParallax() {
    if (userPreferences.reducedMotion) return;

    const parallaxElements = document.querySelectorAll('.parallax');
    let ticking = false;

    function updateParallax() {
        const scrolled = window.pageYOffset;

        parallaxElements.forEach(element => {
            const rate = scrolled * (element.dataset.speed || -0.5);
            element.style.transform = `translate3d(0, ${rate}px, 0)`;
        });

        ticking = false;
    }

    function requestParallaxUpdate() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }

    window.addEventListener('scroll', requestParallaxUpdate);
}

// Initialize skeleton loading system
function initializeSkeletonLoading() {
    // Auto-replace skeleton elements when content loads
    const skeletonElements = document.querySelectorAll('.skeleton');

    skeletonElements.forEach(skeleton => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    const hasRealContent = Array.from(mutation.addedNodes).some(node =>
                        node.nodeType === Node.ELEMENT_NODE && !node.classList.contains('skeleton')
                    );

                    if (hasRealContent) {
                        skeleton.classList.add('fade-out');
                        setTimeout(() => {
                            skeleton.remove();
                        }, 300);
                        observer.disconnect();
                    }
                }
            });
        });

        observer.observe(skeleton.parentElement, {
            childList: true,
            subtree: true
        });
    });
}

// Initialize toast notification system
function initializeToastSystem() {
    // Create toast container if it doesn't exist
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
}

// Enhanced utility functions
function smoothScrollTo(target, offset = 0) {
    const element = typeof target === 'string' ? document.querySelector(target) : target;
    if (element) {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: userPreferences.animationsEnabled ? 'smooth' : 'auto'
        });
    }
}

// Create skeleton placeholder
function createSkeleton(type = 'text', config = {}) {
    const skeleton = document.createElement('div');

    switch (type) {
        case 'text':
            skeleton.className = `skeleton-text ${config.size || ''} ${config.width || ''}`;
            break;
        case 'avatar':
            skeleton.className = `skeleton-avatar ${config.size || ''}`;
            break;
        case 'button':
            skeleton.className = 'skeleton-button';
            break;
        case 'card':
            skeleton.className = 'skeleton-card';
            break;
        case 'image':
            skeleton.className = `skeleton-image ${config.aspect || ''}`;
            break;
        default:
            skeleton.className = 'skeleton';
    }

    return skeleton;
}

// Replace content with skeleton
function showSkeleton(element, type = 'text', config = {}) {
    const skeleton = createSkeleton(type, config);
    const originalContent = element.innerHTML;

    element.dataset.originalContent = originalContent;
    element.innerHTML = '';
    element.appendChild(skeleton);

    return {
        restore: () => {
            element.innerHTML = originalContent;
            delete element.dataset.originalContent;
        }
    };
}

// Enhanced toast notification system
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    const toastId = `toast-${Date.now()}`;

    toast.id = toastId;
    toast.className = `toast toast-${type} transform translate-x-full opacity-0`;

    const iconMap = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold">
                    ${iconMap[type] || iconMap.info}
                </span>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${message}</p>
            </div>
            <button class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-600 focus:outline-none" onclick="hideToast('${toastId}')">
                <span class="sr-only">Close</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;

    container.appendChild(toast);

    // Play sound
    playSfx(type === 'error' ? 'error' : type === 'success' ? 'success' : 'notify');

    // Trigger show animation
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full', 'opacity-0');
        toast.classList.add('show');
    });

    // Auto-hide
    if (duration > 0) {
        setTimeout(() => {
            hideToast(toastId);
        }, duration);
    }

    return toastId;
}

// Hide toast
function hideToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.classList.add('translate-x-full', 'opacity-0');
        toast.classList.remove('show');

        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// Enhanced loading system
function showLoading(message = 'Loading...', type = 'overlay') {
    const existingLoading = document.getElementById('global-loading');
    if (existingLoading) return;

    const loading = document.createElement('div');
    loading.id = 'global-loading';

    if (type === 'overlay') {
        loading.className = 'fixed inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center';
        loading.innerHTML = `
            <div class="text-center">
                <div class="spinner lg mx-auto mb-4"></div>
                <p class="text-gray-600 dark:text-gray-400 font-medium">${message}</p>
            </div>
        `;
    } else {
        loading.className = 'inline-flex items-center space-x-2';
        loading.innerHTML = `
            <div class="spinner sm"></div>
            <span class="text-sm text-gray-600 dark:text-gray-400">${message}</span>
        `;
    }

    if (type === 'overlay') {
        document.body.appendChild(loading);
    }

    return loading;
}

function hideLoading() {
    const loading = document.getElementById('global-loading');
    if (loading) {
        if (userPreferences.animationsEnabled) {
            loading.style.opacity = '0';
            setTimeout(() => {
                loading.remove();
            }, 200);
        } else {
            loading.remove();
        }
    }
}

// Modal system
function showModal(content, options = {}) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.innerHTML = `
        <div class="modal-content">
            ${options.header ? `<div class="modal-header"><h3 class="text-lg font-semibold">${options.header}</h3></div>` : ''}
            <div class="modal-body">${content}</div>
            ${options.footer ? `<div class="modal-footer">${options.footer}</div>` : '<div class="modal-footer"><button class="btn btn-secondary" onclick="hideModal()">Close</button></div>'}
        </div>
    `;

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            hideModal();
        }
    });

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    playSfx('click');

    return modal;
}

function hideModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        document.body.style.overflow = '';
        modal.remove();
    }
}

// Global utility functions export
window.hoho = {
    // Core functions
    showToast,
    hideToast,
    showLoading,
    hideLoading,
    showModal,
    hideModal,
    smoothScrollTo,
    playSfx,
    reveal,

    // Skeleton functions
    createSkeleton,
    showSkeleton,

    // Settings
    toggleSound: () => Alpine.store('ui').toggleSound(),
    toggleAnimations: () => Alpine.store('ui').toggleAnimations(),
    toggleDarkMode: () => Alpine.store('ui').toggleDarkMode(),

    // State getters
    get soundEnabled() { return Alpine.store('ui').soundEnabled; },
    get animationsEnabled() { return Alpine.store('ui').animationsEnabled; },
    get darkMode() { return Alpine.store('ui').darkMode; }
};

// Add CSS for animations if missing
if (!document.querySelector('#hoho-animations')) {
    const style = document.createElement('style');
    style.id = 'hoho-animations';
    style.textContent = `
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        .focused {
            transform: scale(1.02);
        }

        .fade-out {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    `;
    document.head.appendChild(style);
}

// Initialize keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], .search-input');
        if (searchInput) {
            searchInput.focus();
            playSfx('click');
        }
    }

    // Escape to close modals
    if (e.key === 'Escape') {
        const modal = document.querySelector('.modal-overlay');
        if (modal) {
            hideModal();
        }
    }
});

// Add reduced motion detection
const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
mediaQuery.addListener((e) => {
    userPreferences.reducedMotion = e.matches;
    if (e.matches) {
        Alpine.store('ui').animationsEnabled = false;
    }
});

// Performance optimization: Debounce scroll events
function debounce(func, wait) {
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

// Add to exports
window.hoho.debounce = debounce;

// Alpine.js components
Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
        playSfx('click');
    },
    close() {
        this.open = false;
    }
}));

Alpine.data('modal', () => ({
    open: false,
    show() {
        this.open = true;
        document.body.style.overflow = 'hidden';
        playSfx('click');
    },
    hide() {
        this.open = false;
        document.body.style.overflow = '';
    }
}));

Alpine.data('tabs', (defaultTab = 0) => ({
    activeTab: defaultTab,
    setTab(index) {
        this.activeTab = index;
        playSfx('click');
    }
}));

Alpine.data('collapse', (defaultOpen = false) => ({
    open: defaultOpen,
    toggle() {
        this.open = !this.open;
        playSfx('click');
    }
}));

Alpine.data('form', () => ({
    loading: false,
    errors: {},

    async submit(formData, url, options = {}) {
        this.loading = true;
        this.errors = {};

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                }
            });

            const data = await response.json();

            if (response.ok) {
                playSfx('success');
                if (options.onSuccess) {
                    options.onSuccess(data);
                } else {
                    showToast(data.message || 'Success!', 'success');
                }
            } else {
                this.errors = data.errors || {};
                playSfx('error');
                showToast(data.message || 'An error occurred', 'error');
            }
        } catch (error) {
            playSfx('error');
            showToast('Network error occurred', 'error');
        } finally {
            this.loading = false;
        }
    }
}));

// Initialize everything when Alpine is ready
document.addEventListener('alpine:init', () => {
    console.log('🍎 HOHO Apple-Style UI System initialized');
});