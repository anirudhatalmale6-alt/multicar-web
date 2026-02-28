/**
 * MULTICAR — Main JavaScript
 * multicar.autos
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ═══════════════════════════════════════════
    // HEADER SCROLL EFFECT
    // ═══════════════════════════════════════════
    const header = document.getElementById('header');
    if (header && !header.classList.contains('header-solid')) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 40);
        });
        // Check on load
        header.classList.toggle('scrolled', window.scrollY > 40);
    }

    // ═══════════════════════════════════════════
    // MOBILE MENU TOGGLE
    // ═══════════════════════════════════════════
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    if (menuToggle && mainNav) {
        menuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('open');
            // Animate hamburger
            menuToggle.classList.toggle('active');
        });

        // Close menu when clicking a link
        mainNav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mainNav.classList.remove('open');
                menuToggle.classList.remove('active');
            });
        });
    }

    // ═══════════════════════════════════════════
    // SCROLL REVEAL
    // ═══════════════════════════════════════════
    const revealElements = document.querySelectorAll('.reveal');
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 80);
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        revealElements.forEach(el => revealObserver.observe(el));
    }

    // ═══════════════════════════════════════════
    // BACK TO TOP
    // ═══════════════════════════════════════════
    const backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            backToTop.classList.toggle('visible', window.scrollY > 600);
        });
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ═══════════════════════════════════════════
    // ANIMATED COUNTERS
    // ═══════════════════════════════════════════
    const counters = document.querySelectorAll('.stat-number');
    if (counters.length > 0) {
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const text = el.textContent;
                    const match = text.match(/(\d+)/);
                    if (match) {
                        const target = parseInt(match[1]);
                        const suffix = text.replace(match[1], '');
                        let current = 0;
                        const increment = target / 60;
                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                current = target;
                                clearInterval(timer);
                            }
                            el.innerHTML = Math.floor(current) +
                                suffix.replace('+', '<span class="accent">+</span>')
                                      .replace('%', '<span class="accent">%</span>');
                        }, 25);
                    }
                    counterObserver.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(c => counterObserver.observe(c));
    }

    // ═══════════════════════════════════════════
    // FLASH MESSAGES AUTO-DISMISS
    // ═══════════════════════════════════════════
    const flashMessages = document.querySelectorAll('.flash');
    flashMessages.forEach((flash, i) => {
        // Click to dismiss
        flash.addEventListener('click', () => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateX(60px)';
            setTimeout(() => flash.remove(), 350);
        });
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (flash.parentElement) {
                flash.style.opacity = '0';
                flash.style.transform = 'translateX(60px)';
                setTimeout(() => flash.remove(), 350);
            }
        }, 5000 + (i * 500));
    });

    // ═══════════════════════════════════════════
    // GALLERY CAROUSEL / LIGHTBOX (Vehicle detail page)
    // ═══════════════════════════════════════════
    const galleryMain = document.querySelector('.gallery-main img');
    const galleryThumbs = document.querySelectorAll('.gallery-thumb');
    const galleryPrev = document.querySelector('.gallery-prev');
    const galleryNext = document.querySelector('.gallery-next');
    const galleryCounter = document.getElementById('galleryCurrentIdx');
    const lightbox = document.getElementById('lightbox');

    // Build image list from thumbnails
    let galleryImages = [];
    let galleryIndex = 0;

    if (galleryThumbs.length > 0) {
        galleryThumbs.forEach(thumb => {
            const img = thumb.querySelector('img');
            if (img && img.src) galleryImages.push(img.src);
        });
    } else if (galleryMain && galleryMain.src) {
        galleryImages.push(galleryMain.src);
    }

    function showGalleryImage(idx) {
        if (!galleryMain || galleryImages.length === 0) return;
        galleryIndex = (idx + galleryImages.length) % galleryImages.length;
        galleryMain.src = galleryImages[galleryIndex];
        if (galleryCounter) galleryCounter.textContent = galleryIndex + 1;
        // Update active thumb
        galleryThumbs.forEach((t, i) => t.classList.toggle('active', i === galleryIndex));
        // Scroll active thumb into view
        const activeThumb = galleryThumbs[galleryIndex];
        if (activeThumb) activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    // Handle broken main image — skip to next
    if (galleryMain) {
        galleryMain.addEventListener('error', () => {
            if (galleryImages.length > 1) {
                galleryImages.splice(galleryIndex, 1);
                if (galleryIndex >= galleryImages.length) galleryIndex = 0;
                galleryMain.src = galleryImages[galleryIndex];
            }
        });
    }

    // Thumbnail click
    galleryThumbs.forEach((thumb, i) => {
        thumb.addEventListener('click', () => showGalleryImage(i));
    });

    // Prev/Next arrows on main image
    if (galleryPrev) galleryPrev.addEventListener('click', (e) => { e.stopPropagation(); showGalleryImage(galleryIndex - 1); });
    if (galleryNext) galleryNext.addEventListener('click', (e) => { e.stopPropagation(); showGalleryImage(galleryIndex + 1); });

    // Swipe support on main image (touch devices)
    if (galleryMain) {
        let touchStartX = 0;
        const mainContainer = document.querySelector('.gallery-main');
        if (mainContainer) {
            mainContainer.addEventListener('touchstart', (e) => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
            mainContainer.addEventListener('touchend', (e) => {
                const diff = e.changedTouches[0].screenX - touchStartX;
                if (Math.abs(diff) > 50) {
                    if (diff < 0) showGalleryImage(galleryIndex + 1);
                    else showGalleryImage(galleryIndex - 1);
                }
            }, { passive: true });
        }
    }

    // Lightbox
    if (lightbox) {
        const lightboxImg = lightbox.querySelector('img');
        const lightboxClose = lightbox.querySelector('.lightbox-close');
        const lightboxPrev = lightbox.querySelector('.lightbox-prev');
        const lightboxNext = lightbox.querySelector('.lightbox-next');
        let lbIndex = 0;

        // Open lightbox on main image click
        if (galleryMain) {
            const galleryMainContainer = document.querySelector('.gallery-main');
            if (galleryMainContainer) {
                galleryMainContainer.addEventListener('click', (e) => {
                    if (e.target.closest('.gallery-nav')) return; // Don't open lightbox when clicking arrows
                    lbIndex = galleryIndex;
                    lightboxImg.src = galleryImages[lbIndex] || galleryMain.src;
                    lightbox.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLightbox(); });

        if (lightboxPrev) {
            lightboxPrev.addEventListener('click', (e) => {
                e.stopPropagation();
                lbIndex = (lbIndex - 1 + galleryImages.length) % galleryImages.length;
                lightboxImg.src = galleryImages[lbIndex];
            });
        }

        if (lightboxNext) {
            lightboxNext.addEventListener('click', (e) => {
                e.stopPropagation();
                lbIndex = (lbIndex + 1) % galleryImages.length;
                lightboxImg.src = galleryImages[lbIndex];
            });
        }

        document.addEventListener('keydown', (e) => {
            if (lightbox.classList.contains('active')) {
                if (e.key === 'Escape') closeLightbox();
                else if (e.key === 'ArrowLeft' && lightboxPrev) lightboxPrev.click();
                else if (e.key === 'ArrowRight' && lightboxNext) lightboxNext.click();
            } else if (galleryImages.length > 1) {
                // Arrow keys also work on the carousel when lightbox is closed
                if (e.key === 'ArrowLeft') showGalleryImage(galleryIndex - 1);
                else if (e.key === 'ArrowRight') showGalleryImage(galleryIndex + 1);
            }
        });
    }

    // ═══════════════════════════════════════════
    // INVENTORY FILTER TOGGLE (Mobile)
    // ═══════════════════════════════════════════
    const filterToggle = document.getElementById('filterToggle');
    const filterSidebar = document.getElementById('filterSidebar');
    if (filterToggle && filterSidebar) {
        filterToggle.addEventListener('click', () => {
            filterSidebar.classList.toggle('open');
            filterToggle.textContent = filterSidebar.classList.contains('open')
                ? 'Ocultar filtros'
                : 'Mostrar filtros';
        });
    }

    // ═══════════════════════════════════════════
    // CONTACT & LEAD FORM SUBMISSION (AJAX)
    // ═══════════════════════════════════════════
    const contactForms = document.querySelectorAll('[data-ajax-form]');
    contactForms.forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Enviando...';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                });
                const data = await response.json();

                if (data.success) {
                    showFlash('success', data.message || 'Mensaje enviado correctamente.');
                    form.reset();
                } else {
                    showFlash('error', data.message || 'Error al enviar el mensaje.');
                }
            } catch (err) {
                showFlash('error', 'Error de conexión. Inténtalo de nuevo.');
            }

            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // ═══════════════════════════════════════════
    // FLASH MESSAGE HELPER
    // ═══════════════════════════════════════════
    function showFlash(type, message) {
        let container = document.querySelector('.flash-messages');
        if (!container) {
            container = document.createElement('div');
            container.className = 'flash-messages';
            document.body.appendChild(container);
        }

        const flash = document.createElement('div');
        flash.className = `flash flash-${type}`;
        flash.textContent = message;
        container.appendChild(flash);

        flash.addEventListener('click', () => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateX(60px)';
            setTimeout(() => flash.remove(), 350);
        });

        setTimeout(() => {
            if (flash.parentElement) {
                flash.style.opacity = '0';
                flash.style.transform = 'translateX(60px)';
                setTimeout(() => flash.remove(), 350);
            }
        }, 5000);
    }

    // ═══════════════════════════════════════════
    // INVENTORY SORT CHANGE
    // ═══════════════════════════════════════════
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('orden', sortSelect.value);
            url.searchParams.delete('pagina');
            window.location.href = url.toString();
        });
    }

    // ═══════════════════════════════════════════
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ═══════════════════════════════════════════
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const target = document.querySelector(link.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
