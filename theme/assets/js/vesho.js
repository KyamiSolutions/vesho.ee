/**
 * Vesho Theme JavaScript
 * - Mobile menu toggle
 * - Service request modal
 * - Contact form AJAX
 * - Smooth scroll
 */

(function () {
    'use strict';

    // ── Cached DOM refs ─────────────────────────────────────────────────────────
    var hamburger       = document.getElementById('hamburger-btn');
    var primaryNav      = document.getElementById('primary-nav');
    var mobileOverlay   = document.getElementById('mobile-nav-overlay');
    var modalBackdrop   = document.getElementById('modal-backdrop');
    var serviceModal    = document.getElementById('service-modal');
    var modalCloseBtn   = document.getElementById('modal-close-btn');
    var serviceForm     = document.getElementById('service-request-form');
    var modalSuccess    = document.getElementById('modal-success');
    var modalError      = document.getElementById('modal-error');
    var modalSubmitBtn  = document.getElementById('modal-submit-btn');
    var modalNewRequest = document.getElementById('modal-new-request');
    var serviceSelect   = document.getElementById('sr-service');

    // ── Mobile Menu ─────────────────────────────────────────────────────────────
    function openMobileMenu() {
        if (!hamburger || !primaryNav) return;
        hamburger.setAttribute('aria-expanded', 'true');
        primaryNav.classList.add('is-open');
        if (mobileOverlay) {
            mobileOverlay.classList.add('is-open');
            mobileOverlay.removeAttribute('aria-hidden');
        }
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        if (!hamburger || !primaryNav) return;
        hamburger.setAttribute('aria-expanded', 'false');
        primaryNav.classList.remove('is-open');
        if (mobileOverlay) {
            mobileOverlay.classList.remove('is-open');
            mobileOverlay.setAttribute('aria-hidden', 'true');
        }
        document.body.style.overflow = '';
    }

    function toggleMobileMenu() {
        var isOpen = hamburger && hamburger.getAttribute('aria-expanded') === 'true';
        isOpen ? closeMobileMenu() : openMobileMenu();
    }

    if (hamburger) {
        hamburger.addEventListener('click', toggleMobileMenu);
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMobileMenu);
    }

    // Close on resize to desktop
    window.addEventListener('resize', function () {
        if (window.innerWidth > 900) {
            closeMobileMenu();
        }
    });

    // Close mobile menu when a nav link is clicked
    if (primaryNav) {
        primaryNav.querySelectorAll('.nav__link').forEach(function (link) {
            link.addEventListener('click', closeMobileMenu);
        });
    }

    // Keyboard: Escape closes mobile menu
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
            closeModal();
        }
    });

    // ── Service Request Modal ───────────────────────────────────────────────────
    function openModal(serviceName) {
        if (!serviceModal || !modalBackdrop) return;

        // Pre-fill service if provided
        if (serviceName && serviceSelect) {
            var opts = serviceSelect.options;
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].value === serviceName) {
                    serviceSelect.selectedIndex = i;
                    break;
                }
            }
        }

        serviceModal.removeAttribute('aria-hidden');
        serviceModal.classList.add('is-open');
        modalBackdrop.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        // Focus management
        setTimeout(function () {
            var firstInput = serviceModal.querySelector('input, select, textarea');
            if (firstInput) firstInput.focus();
        }, 300);
    }

    function closeModal() {
        if (!serviceModal || !modalBackdrop) return;
        serviceModal.setAttribute('aria-hidden', 'true');
        serviceModal.classList.remove('is-open');
        modalBackdrop.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    // Open modal from any trigger with [aria-controls="service-modal"]
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[aria-controls="service-modal"]');
        if (trigger) {
            e.preventDefault();
            var serviceName = trigger.dataset.service || '';
            openModal(serviceName);
        }
    });

    // Close on backdrop click
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', closeModal);
    }

    // Close button
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeModal);
    }

    // New request button (after success)
    if (modalNewRequest) {
        modalNewRequest.addEventListener('click', function () {
            if (serviceForm) {
                serviceForm.reset();
                serviceForm.style.display = '';
            }
            if (modalSuccess) modalSuccess.style.display = 'none';
            if (modalError) modalError.style.display = 'none';
        });
    }

    // Focus trap inside modal
    if (serviceModal) {
        serviceModal.addEventListener('keydown', function (e) {
            if (e.key !== 'Tab') return;
            var focusable = serviceModal.querySelectorAll(
                'a, button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            );
            var first = focusable[0];
            var last  = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        });
    }

    // ── Service Request Form Submission ────────────────────────────────────────
    if (serviceForm) {
        serviceForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (modalError) modalError.style.display = 'none';

            var name  = serviceForm.querySelector('[name="name"]');
            var email = serviceForm.querySelector('[name="email"]');

            if (!name || !name.value.trim()) {
                showFormError('Nimi on kohustuslik'); return;
            }
            if (!email || !email.value.trim() || !isValidEmail(email.value)) {
                showFormError('Kehtiv e-posti aadress on kohustuslik'); return;
            }

            var originalBtnContent = modalSubmitBtn ? modalSubmitBtn.innerHTML : '';
            if (modalSubmitBtn) {
                modalSubmitBtn.disabled = true;
                modalSubmitBtn.innerHTML = '<span class="spinner" aria-hidden="true"></span> Saadan...';
            }

            var fd = new FormData(serviceForm);
            fd.append('action', 'vesho_guest_request');

            // Get nonce from VeshoData or hidden field
            var nonce = (window.VeshoData && window.VeshoData.nonce) ||
                        (document.querySelector('[name="vesho_nonce_field"]') || {}).value || '';
            fd.append('nonce', nonce);

            var ajaxUrl = (window.VeshoData && window.VeshoData.ajaxUrl) || '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        serviceForm.style.display = 'none';
                        if (modalSuccess) modalSuccess.style.display = 'block';
                        serviceForm.reset();
                    } else {
                        var msg = (data.data && data.data.message) || 'Saatmine ebaõnnestus. Palun proovige uuesti.';
                        showFormError(msg);
                        if (modalSubmitBtn) {
                            modalSubmitBtn.disabled = false;
                            modalSubmitBtn.innerHTML = originalBtnContent;
                        }
                    }
                })
                .catch(function () {
                    showFormError('Ühenduse viga. Kontrollige internetiühendust.');
                    if (modalSubmitBtn) {
                        modalSubmitBtn.disabled = false;
                        modalSubmitBtn.innerHTML = originalBtnContent;
                    }
                });
        });
    }

    function showFormError(msg) {
        if (!modalError) return;
        modalError.textContent = msg;
        modalError.style.display = 'block';
        modalError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // ── Smooth Scroll ────────────────────────────────────────────────────────────
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            var targetId = link.getAttribute('href');
            if (targetId === '#') return;
            var target = document.querySelector(targetId);
            if (!target) return;
            e.preventDefault();
            var headerHeight = document.querySelector('.site-header') ?
                document.querySelector('.site-header').offsetHeight : 0;
            var targetTop = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 16;
            window.scrollTo({ top: targetTop, behavior: 'smooth' });
            closeMobileMenu();
        });
    });

    // ── Sticky header shadow ────────────────────────────────────────────────────
    var siteHeader = document.getElementById('site-header');
    if (siteHeader) {
        var lastScroll = 0;
        window.addEventListener('scroll', function () {
            var currentScroll = window.pageYOffset;
            if (currentScroll > 10) {
                siteHeader.style.boxShadow = '0 2px 16px rgba(0, 50, 80, 0.14)';
            } else {
                siteHeader.style.boxShadow = '0 2px 8px rgba(0, 50, 80, 0.07)';
            }
            lastScroll = currentScroll;
        }, { passive: true });
    }

    // ── Animate elements on scroll ──────────────────────────────────────────────
    function initScrollAnimations() {
        var animEls = document.querySelectorAll('.service-card, .service-full-card, .stat-item, .process-step, .why-us__card, .contact-card');
        if (!('IntersectionObserver' in window)) {
            animEls.forEach(function (el) { el.style.opacity = '1'; });
            return;
        }
        animEls.forEach(function (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(16px)';
            el.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
        });
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
        animEls.forEach(function (el) { observer.observe(el); });
    }

    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollAnimations);
    } else {
        initScrollAnimations();
    }

    // Cookie notice disabled

    // ── Dynamic top height: announcement bar + header ──────────────────────────
    // Called on load and when announcement bar is dismissed
    window.veshoAdjustTopHeight = function () {
        var wrapper = document.querySelector('.site-top-wrapper');
        if (wrapper) {
            document.documentElement.style.setProperty('--site-top-height', wrapper.offsetHeight + 'px');
        }
    };
    veshoAdjustTopHeight();
    window.addEventListener('resize', veshoAdjustTopHeight, { passive: true });

})();
