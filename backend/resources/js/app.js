import './bootstrap';
import './chart-component';

// ==================== DARK MODE ====================
function initDarkMode() {
    const darkModeToggle = document.getElementById('dark-mode-toggle');
    const html = document.documentElement;

    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }

    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            localStorage.theme = html.classList.contains('dark') ? 'dark' : 'light';
        });
    }
}

// ==================== MOBILE MENU ====================
function initMobileMenu() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('-translate-x-full');
            mobileMenuOverlay?.classList.toggle('hidden');
        });

        mobileMenuOverlay?.addEventListener('click', () => {
            mobileMenu.classList.add('-translate-x-full');
            mobileMenuOverlay.classList.add('hidden');
        });
    }
}

// ==================== DROPDOWNS ====================
function initDropdowns() {
    document.querySelectorAll('[data-dropdown-toggle]').forEach(button => {
        const targetId = button.getAttribute('data-dropdown-toggle');
        const target = document.getElementById(targetId);

        if (target) {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                target.classList.toggle('hidden');
            });
        }
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    });
}

// ==================== FLASH MESSAGES ====================
function initFlashMessages() {
    document.querySelectorAll('[data-flash-message]').forEach(message => {
        const duration = parseInt(message.getAttribute('data-duration')) || 5000;

        // Entrance animation
        message.style.animation = 'slide-down 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both';

        setTimeout(() => {
            message.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            message.style.opacity = '0';
            message.style.transform = 'translateY(-8px)';
            setTimeout(() => message.remove(), 300);
        }, duration);

        const dismissButton = message.querySelector('[data-dismiss]');
        if (dismissButton) {
            dismissButton.addEventListener('click', () => {
                message.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                message.style.opacity = '0';
                message.style.transform = 'translateY(-8px)';
                setTimeout(() => message.remove(), 200);
            });
        }
    });
}

// ==================== MODALS ====================
function initModals() {
    document.querySelectorAll('[data-modal-toggle]').forEach(button => {
        const targetId = button.getAttribute('data-modal-toggle');
        const target = document.getElementById(targetId);

        if (target) {
            button.addEventListener('click', () => {
                target.classList.toggle('hidden');
            });
        }
    });

    document.querySelectorAll('[data-modal-close]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('[data-modal]');
            if (modal) modal.classList.add('hidden');
        });
    });

    document.querySelectorAll('[data-modal-overlay]').forEach(overlay => {
        overlay.addEventListener('click', () => {
            const modal = overlay.closest('[data-modal]');
            if (modal) modal.classList.add('hidden');
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[data-modal]:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    });
}

// ==================== CURRENCY INPUTS ====================
function initCurrencyInputs() {
    document.querySelectorAll('[data-currency]').forEach(input => {
        input.addEventListener('blur', (e) => {
            const value = parseFloat(e.target.value.replace(/[^0-9.-]/g, ''));
            if (!isNaN(value)) {
                e.target.value = value.toFixed(2);
            }
        });
    });
}

// ==================== NUMBER COUNTER ANIMATION ====================
function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseFloat(el.getAttribute('data-counter'));
                const prefix = el.getAttribute('data-prefix') || '';
                const suffix = el.getAttribute('data-suffix') || '';
                const decimals = parseInt(el.getAttribute('data-decimals') || '0');
                const duration = 800;
                const start = performance.now();

                function tick(now) {
                    const elapsed = now - start;
                    const progress = Math.min(elapsed / duration, 1);
                    // Ease out cubic
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = target * eased;
                    el.textContent = prefix + current.toLocaleString('es-EC', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals,
                    }) + suffix;
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(el => observer.observe(el));
}

// ==================== STAGGER ANIMATIONS ON SCROLL ====================
function initScrollAnimations() {
    const targets = document.querySelectorAll('[data-animate]');
    if (!targets.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const type = entry.target.getAttribute('data-animate') || 'slide-up';
                const delay = entry.target.getAttribute('data-delay') || '0';
                entry.target.style.animationDelay = delay + 'ms';
                entry.target.classList.add(`animate-${type}`);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    targets.forEach(el => observer.observe(el));
}

// ==================== KEYBOARD SHORTCUTS ====================
function initKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
        // Cmd/Ctrl + K → focus search
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[data-search-input]');
            if (searchInput) {
                searchInput.focus();
            } else {
                // Trigger search modal event for Livewire
                window.dispatchEvent(new CustomEvent('open-search'));
            }
        }
    });
}

// ==================== PROGRESS BAR ANIMATION ====================
function initProgressBars() {
    document.querySelectorAll('[data-progress]').forEach(bar => {
        const fill = bar.querySelector('.progress-fill');
        if (fill) {
            const width = bar.getAttribute('data-progress') + '%';
            requestAnimationFrame(() => {
                fill.style.width = width;
            });
        }
    });
}

// ==================== INITIALIZE ====================
function initAll() {
    initDarkMode();
    initMobileMenu();
    initDropdowns();
    initFlashMessages();
    initModals();
    initCurrencyInputs();
    initCounters();
    initScrollAnimations();
    initKeyboardShortcuts();
    initProgressBars();
}

document.addEventListener('DOMContentLoaded', initAll);
document.addEventListener('livewire:navigated', initAll);
