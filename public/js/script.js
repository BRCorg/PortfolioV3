// ==========================================
// PORTFOLIO V2 - SCRIPT PRINCIPAL
// ==========================================

// ==========================================
// BARRE DE PROGRESSION DE SCROLL
// ==========================================
function updateScrollProgress() {
    const scrollProgress = document.getElementById('scrollProgress');
    if (!scrollProgress) return;
    
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const scrollTop = window.scrollY;
    
    const scrollableHeight = documentHeight - windowHeight;
    const scrollPercentage = (scrollTop / scrollableHeight) * 100;
    
    scrollProgress.style.width = Math.min(scrollPercentage, 100) + '%';
}

// Initialiser et écouter le scroll
window.addEventListener('scroll', updateScrollProgress);
window.addEventListener('load', updateScrollProgress);

// ==========================================
// PAGE LOADER
// ==========================================
window.addEventListener('load', function() {
    const loader = document.getElementById('pageLoader');

    // Vérifier si le loader a déjà été affiché dans cette session
    const hasSeenLoader = sessionStorage.getItem('loaderShown');

    if (hasSeenLoader) {
        // Si déjà vu, cacher immédiatement
        if (loader) {
            loader.classList.add('hidden');
        }
    } else {
        // Première visite de la session, afficher le loader
        setTimeout(function() {
            if (loader) {
                loader.classList.add('hidden');
                // Marquer le loader comme affiché pour cette session
                sessionStorage.setItem('loaderShown', 'true');
            }
        }, 800);
    }
});

// ==========================================
// DARK MODE
// ==========================================
const darkModeToggle = document.getElementById('darkModeToggle');
const body = document.body;

// Charger la préférence depuis localStorage
if (localStorage.getItem('darkMode') === 'enabled') {
    body.classList.add('dark');
    if (darkModeToggle) {
        const icon = darkModeToggle.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-sun';
        } else {
            darkModeToggle.textContent = '☀️';
        }
    }
}

// Toggle dark mode
if (darkModeToggle) {
    darkModeToggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        const icon = darkModeToggle.querySelector('i');

        if (body.classList.contains('dark')) {
            if (icon) {
                icon.className = 'fas fa-sun';
            } else {
                darkModeToggle.textContent = '☀️';
            }
            localStorage.setItem('darkMode', 'enabled');
        } else {
            if (icon) {
                icon.className = 'fas fa-moon';
            } else {
                darkModeToggle.textContent = '🌙';
            }
            localStorage.setItem('darkMode', null);
        }
    });
}

// ==========================================
// MENU BURGER (MOBILE)
// ==========================================
let menuOpen = false;

function hamburg() {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown) {
        if (menuOpen) {
            // Fermer le menu - glisse vers la gauche
            dropdown.style.opacity = '0';
            dropdown.style.transform = 'translateX(-100px)';
            setTimeout(() => {
                dropdown.style.transform = 'translateX(-71.25rem)';
            }, 300);
            menuOpen = false;
        } else {
            // Ouvrir le menu - glisse depuis la gauche
            dropdown.style.transform = 'translateX(0)';
            dropdown.style.opacity = '1';
            menuOpen = true;
        }
    }
}

function cancel() {
    const dropdown = document.querySelector('.dropdown');
    if (dropdown) {
        dropdown.style.opacity = '0';
        dropdown.style.transform = 'translateX(-100px)';
        setTimeout(() => {
            dropdown.style.transform = 'translateX(-71.25rem)';
        }, 300);
        menuOpen = false;
    }
}

// Fermer le menu en cliquant sur un lien
document.querySelectorAll('.dropdown a').forEach(link => {
    link.addEventListener('click', cancel);
});

// ==========================================
// SMOOTH SCROLL NAVIGATION
// ==========================================
// Gérer la navigation smooth scroll pour tous les liens avec ancres
document.querySelectorAll('a[href*="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');

        // Vérifier si c'est un lien avec ancre
        if (href && href.includes('#')) {
            const parts = href.split('#');
            const hash = parts[1];

            // Ne pas traiter les liens vides ou sans hash
            if (!hash) return;

            // Si c'est un lien vers une section de la page principale
            if (href.startsWith('#') || href.startsWith('/#')) {
                e.preventDefault();

                // Vérifier si on est sur la page d'accueil
                const isOnHomePage = window.location.pathname === '/' || window.location.pathname === '';

                // Si on n'est pas sur la page d'accueil, rediriger vers l'accueil avec l'ancre
                if (!isOnHomePage) {
                    window.location.href = '/#' + hash;
                    return;
                }

                // On est sur la page principale, faire un smooth scroll
                const targetElement = document.getElementById(hash);
                if (targetElement) {
                    // Fermer le menu mobile si ouvert
                    cancel();

                    // Scroll smooth vers la section
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    // Mettre à jour l'URL sans recharger
                    history.pushState(null, null, '#' + hash);
                }
            }
        }
    });
});

// Au chargement de la page, scroller vers l'ancre si présente
window.addEventListener('load', function() {
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        const targetElement = document.getElementById(hash);

        if (targetElement) {
            // Attendre que le loader soit caché si c'est la première visite
            const hasSeenLoader = sessionStorage.getItem('loaderShown');
            const delay = hasSeenLoader ? 100 : 2200; // 2200ms si loader affiché (2s + 200ms buffer)

            setTimeout(() => {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, delay);
        }
    }
});

// ==========================================
// TYPEWRITER EFFECT
// ==========================================
const typewriterText = document.getElementById('typewriterText');

if (typewriterText) {
    const texts = [
        'développeur Web Full Stack',
        'créatif',
        'passionné',
        'toujours à l\'écoute',
        'toujours en apprentissage',
        'votre futur collaborateur ?'

    ];

    let textIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let typingSpeed = 150;

    function type() {
        const currentText = texts[textIndex];

        if (isDeleting) {
            typewriterText.textContent = currentText.substring(0, charIndex - 1);
            charIndex--;
            typingSpeed = 50;
        } else {
            typewriterText.textContent = currentText.substring(0, charIndex + 1);
            charIndex++;
            typingSpeed = 150;
        }

        if (!isDeleting && charIndex === currentText.length) {
            // Pause à la fin du mot
            typingSpeed = 2000;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            textIndex = (textIndex + 1) % texts.length;
            typingSpeed = 500;
        }

        setTimeout(type, typingSpeed);
    }

    // Démarrer l'effet
    setTimeout(type, 1000);
}

// ==========================================
// CAROUSEL DE PROJETS AVEC SWIPER
// ==========================================
// Note: Ce carrousel utilise la classe .emotions-slider du HTML
// Il n'y a pas besoin de code supplémentaire ici car le carrousel
// est initialisé par la section "PROJECTS SLIDER (SWIPER)" plus bas dans ce fichier

// ==========================================
// FORMULAIRE DE CONTACT
// ==========================================
const contactForm = document.querySelector('.contact-section form');

if (contactForm) {
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        // Désactiver le bouton pendant l'envoi
        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';

        try {
            const response = await fetch('/contact/submit', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Message de succès
                showMessage('Message envoyé avec succès ! Je vous répondrai bientôt.', 'success');
                this.reset();
            } else {
                showMessage(result.message || 'Erreur lors de l\'envoi du message.', 'error');
            }
        } catch (error) {
            showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// ==========================================
// FONCTION UTILITAIRE: AFFICHER UN MESSAGE
// ==========================================
function showMessage(message, type = 'info') {
    // Créer un élément de notification
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 2rem;
        border-radius: 8px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideInRight 0.3s ease-out;
    `;

    document.body.appendChild(notification);

    // Retirer après 5 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 5000);
}

// Ajouter les animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ==========================================
// SMOOTH SCROLL POUR LES ANCRES
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Fermer le menu mobile si ouvert
                cancel();
            }
        }
    });
});

// ==========================================
// ANIMATION AU SCROLL
// ==========================================
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observer les éléments à animer
document.querySelectorAll('.project-card, .skill-tag, .contact-option').forEach(el => {
    observer.observe(el);
});

// ==========================================
// SCROLL TO TOP BUTTON
// ==========================================
const scrollToTopBtn = document.getElementById('scrollToTop');

if (scrollToTopBtn) {
    // Afficher/masquer le bouton selon la position de scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollToTopBtn.classList.add('visible');
        } else {
            scrollToTopBtn.classList.remove('visible');
        }
    });

    // Action de retour en haut
    scrollToTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// ==========================================
// TIMELINE FORMATION
// ==========================================
const timelineItems = document.querySelectorAll('.timeline-item');
const yearMarkers = document.querySelectorAll('.year-marker');
const prevYearBtn = document.getElementById('prevYear');
const nextYearBtn = document.getElementById('nextYear');
const progressBar = document.getElementById('progressBar');

if (timelineItems.length > 0) {
    // Récupérer toutes les années uniques et les trier
    const years = Array.from(timelineItems)
        .map(item => item.getAttribute('data-year'))
        .filter((year, index, self) => self.indexOf(year) === index)
        .sort();

    let currentYearIndex = 0; // Commencer à 2020 (la première année)
    let currentItemIndexInYear = 0; // Index de l'item dans l'année courante

    // Fonction pour afficher l'année courante
    function showYear(yearIndex, itemIndex = 0) {
        const selectedYear = years[yearIndex];

        // Cacher tous les items
        timelineItems.forEach(item => {
            item.classList.remove('active', 'prev');
        });

        // Afficher les items de l'année sélectionnée
        const itemsOfYear = Array.from(timelineItems).filter(
            item => item.getAttribute('data-year') === selectedYear
        );

        if (itemsOfYear.length > 0) {
            // S'assurer que l'index est valide
            currentItemIndexInYear = Math.max(0, Math.min(itemIndex, itemsOfYear.length - 1));

            // Afficher l'item sélectionné
            itemsOfYear[currentItemIndexInYear].classList.add('active');

            // Afficher des indicateurs si plusieurs items pour la même année
            updateYearItemIndicators(itemsOfYear.length, currentItemIndexInYear);
        }

        // Mettre à jour les marqueurs d'année
        yearMarkers.forEach(marker => {
            marker.classList.remove('active');
            if (marker.getAttribute('data-year') === selectedYear) {
                marker.classList.add('active');
            }
        });

        // Mettre à jour la barre de progression
        const progress = ((yearIndex + 1) / years.length) * 100;
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }

        // Gérer l'état des boutons
        const canGoPrev = yearIndex > 0 || currentItemIndexInYear > 0;
        const canGoNext = yearIndex < years.length - 1 || currentItemIndexInYear < itemsOfYear.length - 1;

        if (prevYearBtn) {
            prevYearBtn.disabled = !canGoPrev;
            prevYearBtn.style.opacity = canGoPrev ? '1' : '0.5';
        }

        if (nextYearBtn) {
            nextYearBtn.disabled = !canGoNext;
            nextYearBtn.style.opacity = canGoNext ? '1' : '0.5';
        }
    }

    // Fonction pour afficher les indicateurs d'items multiples
    function updateYearItemIndicators(totalItems, currentIndex) {
        // Chercher ou créer le conteneur d'indicateurs
        let indicatorContainer = document.querySelector('.timeline-item-indicators');

        if (totalItems <= 1) {
            // Supprimer les indicateurs s'il n'y a qu'un seul item
            if (indicatorContainer) {
                indicatorContainer.remove();
            }
            return;
        }

        if (!indicatorContainer) {
            indicatorContainer = document.createElement('div');
            indicatorContainer.className = 'timeline-item-indicators';
            document.querySelector('.timeline-content').appendChild(indicatorContainer);
        }

        // Créer les indicateurs
        indicatorContainer.innerHTML = '';
        for (let i = 0; i < totalItems; i++) {
            const dot = document.createElement('span');
            dot.className = 'indicator-dot' + (i === currentIndex ? ' active' : '');
            dot.addEventListener('click', () => {
                showYear(currentYearIndex, i);
            });
            indicatorContainer.appendChild(dot);
        }
    }

    // Bouton précédent
    if (prevYearBtn) {
        prevYearBtn.addEventListener('click', () => {
            const selectedYear = years[currentYearIndex];
            const itemsOfYear = Array.from(timelineItems).filter(
                item => item.getAttribute('data-year') === selectedYear
            );

            if (currentItemIndexInYear > 0) {
                // Aller à l'item précédent de la même année
                showYear(currentYearIndex, currentItemIndexInYear - 1);
            } else if (currentYearIndex > 0) {
                // Aller à la dernière expérience de l'année précédente
                currentYearIndex--;
                const prevYearItems = Array.from(timelineItems).filter(
                    item => item.getAttribute('data-year') === years[currentYearIndex]
                );
                showYear(currentYearIndex, prevYearItems.length - 1);
            }
        });
    }

    // Bouton suivant
    if (nextYearBtn) {
        nextYearBtn.addEventListener('click', () => {
            const selectedYear = years[currentYearIndex];
            const itemsOfYear = Array.from(timelineItems).filter(
                item => item.getAttribute('data-year') === selectedYear
            );

            if (currentItemIndexInYear < itemsOfYear.length - 1) {
                // Aller à l'item suivant de la même année
                showYear(currentYearIndex, currentItemIndexInYear + 1);
            } else if (currentYearIndex < years.length - 1) {
                // Aller à la première expérience de l'année suivante
                currentYearIndex++;
                showYear(currentYearIndex, 0);
            }
        });
    }

    // Clic sur les marqueurs d'année
    yearMarkers.forEach(marker => {
        marker.addEventListener('click', () => {
            const year = marker.getAttribute('data-year');
            currentYearIndex = years.indexOf(year);
            showYear(currentYearIndex);
        });
    });

    // Support du clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft' && currentYearIndex > 0) {
            currentYearIndex--;
            showYear(currentYearIndex);
        } else if (e.key === 'ArrowRight' && currentYearIndex < years.length - 1) {
            currentYearIndex++;
            showYear(currentYearIndex);
        }
    });

    // Initialiser l'affichage
    showYear(currentYearIndex);
}

// ==========================================
// CAROUSEL TÉMOIGNAGES
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const track = document.getElementById('testimonialsTrack');
    const prevBtn = document.getElementById('prevTestimonial');
    const nextBtn = document.getElementById('nextTestimonial');
    const dots = document.querySelectorAll('.dot');
    
    if (!track) return;
    
    let currentSlide = 0;
    const totalSlides = track.children.length;
    let autoSlideInterval;
    
    function updateCarousel() {
        // Calcul simple : chaque slide déplace de 100/6 = 16.666% de la largeur totale
        const translateX = -(currentSlide * 16.66666666);
        track.style.transform = `translateX(${translateX}%)`;
        
        // Mettre à jour les dots
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentSlide);
        });
    }
    
    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        updateCarousel();
    }
    
    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        updateCarousel();
    }
    
    function goToSlide(index) {
        currentSlide = index;
        updateCarousel();
    }
    
    // Event listeners pour les boutons
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAutoSlide();
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAutoSlide();
        });
    }
    
    // Event listeners pour les dots
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            goToSlide(index);
            resetAutoSlide();
        });
    });
    
    // Auto-slide fonctionnalité
    function startAutoSlide() {
        autoSlideInterval = setInterval(nextSlide, 5000); // Change toutes les 5 secondes
    }
    
    function stopAutoSlide() {
        if (autoSlideInterval) {
            clearInterval(autoSlideInterval);
        }
    }
    
    function resetAutoSlide() {
        stopAutoSlide();
        startAutoSlide();
    }
    
    // Pause auto-slide quand la souris survole le carrousel
    const testimonialsSection = document.querySelector('.testimonials-section');
    if (testimonialsSection) {
        testimonialsSection.addEventListener('mouseenter', stopAutoSlide);
        testimonialsSection.addEventListener('mouseleave', startAutoSlide);
    }
    
    // Support tactile pour mobile
    let startX = 0;
    let endX = 0;
    
    if (track) {
        track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });
        
        track.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            const threshold = 50; // Distance minimale pour déclencher le swipe
            
            if (startX - endX > threshold) {
                // Swipe left - next slide
                nextSlide();
                resetAutoSlide();
            } else if (endX - startX > threshold) {
                // Swipe right - previous slide
                prevSlide();
                resetAutoSlide();
            }
        });
    }
    
    // Support clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            resetAutoSlide();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            resetAutoSlide();
        }
    });
    
    // Initialiser le carrousel
    updateCarousel();
    startAutoSlide();
});

// ==========================================
// PROJECTS SLIDER (SWIPER)
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const sliders = document.querySelectorAll(".emotions-slider");

    if (!sliders.length) return;

    // Charger Swiper dynamiquement si pas déjà chargé
    if (typeof Swiper === 'undefined') {
        const swiperCSS = document.createElement('link');
        swiperCSS.rel = 'stylesheet';
        swiperCSS.href = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css';
        document.head.appendChild(swiperCSS);

        const swiperJS = document.createElement('script');
        swiperJS.src = 'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js';
        swiperJS.onload = initializeSliders;
        document.head.appendChild(swiperJS);
    } else {
        initializeSliders();
    }

    function initializeSliders() {
        sliders.forEach((element) => {
            const slider = element.querySelector(".swiper");
            const prevEl = element.querySelector(".slider-nav__item_prev");
            const nextEl = element.querySelector(".slider-nav__item_next");
            const pagination = element.querySelector(".slider-pagination");

            // Vérifier si le slider n'est pas déjà initialisé
            if (slider && !slider.swiper) {
                new Swiper(slider, {
                    loop: true,
                    speed: 700,
                    spaceBetween: 20,

                    pagination: {
                        el: pagination,
                        clickable: true,
                        dynamicBullets: true,
                        bulletClass: 'slider-pagination__item',
                        bulletActiveClass: 'active'
                    },

                    navigation: {
                        nextEl: nextEl,
                        prevEl: prevEl
                    },

                    breakpoints: {
                        0: {
                            slidesPerView: 1,
                            spaceBetween: 15,
                            centeredSlides: true
                        },
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 25,
                            centeredSlides: true
                        },
                        1024: {
                            slidesPerView: 3,
                            spaceBetween: 30,
                            centeredSlides: true
                        }
                    }
                });
            }
        });
    }
});

// ==========================================
// SCROLL INDICATOR
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const scrollIndicator = document.querySelector('.scroll-indicator');
    
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', () => {
            // Défilement vers la section projets
            const projectsSection = document.getElementById('projects');
            if (projectsSection) {
                projectsSection.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });

        // Masquer l'indicateur après défilement
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                if (window.scrollY > 100) {
                    scrollIndicator.style.opacity = '0';
                    scrollIndicator.style.pointerEvents = 'none';
                } else {
                    scrollIndicator.style.opacity = '1';
                    scrollIndicator.style.pointerEvents = 'auto';
                }
            }, 10);
        });
    }
});

// ==========================================
// ANIMATIONS SECTIONS AU SCROLL
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15 // 15% de la section visible
    };

    const observerCallback = (entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('section-visible');
            }
        });
    };

    const observer = new IntersectionObserver(observerCallback, observerOptions);

    // Observer les sections principales (pas la timeline)
    const sections = document.querySelectorAll('section:not(.timeline-section)');
    sections.forEach(section => {
        section.classList.add('section-animate');
        observer.observe(section);
    });

    // Observer les titres de section (h2) - y compris le titre de la timeline
    const titles = document.querySelectorAll('section h2');
    titles.forEach(title => {
        title.classList.add('section-animate');
        observer.observe(title);
    });

    // Observer la barre de compétences et les icônes de la section #home
    const skillBars = document.querySelectorAll('.progress-bar, .skill-progress');
    const homeIcons = document.querySelectorAll('#home .content .icons a');
    
    skillBars.forEach(bar => {
        bar.classList.add('section-animate');
        observer.observe(bar);
    });

    homeIcons.forEach(icon => {
        icon.classList.add('section-animate');
        observer.observe(icon);
    });
});

// ==========================================
// CAROUSEL PROJET DÉTAIL (SWIPER)
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const projectCarousel = document.querySelector('.project-detail-carousel');
    
    if (projectCarousel && typeof Swiper !== 'undefined') {
        new Swiper('.project-detail-carousel', {
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 800,
        });
    }
});

// ==========================================
// FIX POSITION FIXED MOBILE (URL BAR HIDE/SHOW)
// ==========================================
// Force le recalcul des éléments fixed quand la barre d'URL mobile se cache/affiche
let lastHeight = window.innerHeight;
window.addEventListener('resize', () => {
    const currentHeight = window.innerHeight;
    // Détecter uniquement les changements de hauteur (barre d'URL)
    if (Math.abs(currentHeight - lastHeight) > 100) {
        // Forcer le repaint des éléments fixed
        const fixedElements = [
            document.getElementById('darkModeToggle'),
            document.getElementById('scrollToTop'),
            document.getElementById('scrollProgress')
        ];

        fixedElements.forEach(el => {
            if (el) {
                el.style.transform = 'translateZ(0)';
                requestAnimationFrame(() => {
                    el.style.transform = '';
                });
            }
        });
    }
    lastHeight = currentHeight;
});
