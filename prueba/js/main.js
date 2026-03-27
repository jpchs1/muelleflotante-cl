// Costa Colbún — Main JavaScript
(function() {
    'use strict';

    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    let lastScroll = 0;

    function handleScroll() {
        const currentScroll = window.pageYOffset;
        if (currentScroll > 80) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        lastScroll = currentScroll;
    }

    window.addEventListener('scroll', handleScroll, { passive: true });

    // Mobile menu toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');

    navToggle.addEventListener('click', function() {
        navToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
        document.body.classList.toggle('menu-open');
    });

    // Close menu on link click
    navMenu.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            navToggle.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) {
                var offset = navbar.offsetHeight;
                var pos = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: pos, behavior: 'smooth' });
            }
        });
    });

    // Scroll animations (Intersection Observer)
    var animElements = document.querySelectorAll(
        '.about-grid, .feature-card, .lakelife-card, .nearby-card, .gallery-item, .section-header, .pricing-highlight, .contact-grid'
    );

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    animElements.forEach(function(el) {
        el.classList.add('animate-ready');
        observer.observe(el);
    });

    // Gallery Lightbox
    var lightbox = document.getElementById('lightbox');
    var lightboxImg = lightbox.querySelector('.lightbox-img');
    var galleryItems = document.querySelectorAll('.gallery-item img');
    var currentIndex = 0;
    var images = Array.from(galleryItems);

    galleryItems.forEach(function(img, index) {
        img.addEventListener('click', function() {
            currentIndex = index;
            openLightbox(img.src, img.alt);
        });
    });

    function openLightbox(src, alt) {
        lightboxImg.src = src;
        lightboxImg.alt = alt;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    lightbox.querySelector('.lightbox-close').addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });

    lightbox.querySelector('.lightbox-prev').addEventListener('click', function(e) {
        e.stopPropagation();
        currentIndex = (currentIndex - 1 + images.length) % images.length;
        lightboxImg.src = images[currentIndex].src;
        lightboxImg.alt = images[currentIndex].alt;
    });

    lightbox.querySelector('.lightbox-next').addEventListener('click', function(e) {
        e.stopPropagation();
        currentIndex = (currentIndex + 1) % images.length;
        lightboxImg.src = images[currentIndex].src;
        lightboxImg.alt = images[currentIndex].alt;
    });

    document.addEventListener('keydown', function(e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lightbox.querySelector('.lightbox-prev').click();
        if (e.key === 'ArrowRight') lightbox.querySelector('.lightbox-next').click();
    });

    // Active nav link on scroll
    var sections = document.querySelectorAll('section[id]');
    function highlightNav() {
        var scrollPos = window.pageYOffset + navbar.offsetHeight + 100;
        sections.forEach(function(section) {
            var top = section.offsetTop;
            var height = section.offsetHeight;
            var id = section.getAttribute('id');
            var link = navbar.querySelector('a[href="#' + id + '"]');
            if (link) {
                if (scrollPos >= top && scrollPos < top + height) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            }
        });
    }
    window.addEventListener('scroll', highlightNav, { passive: true });

    // Blog Carousel
    var blogCarousel = document.getElementById('blogCarousel');
    var blogPrev = document.getElementById('blogPrev');
    var blogNext = document.getElementById('blogNext');

    if (blogCarousel && blogPrev && blogNext) {
        var scrollAmount = 0;

        function getBlogCardWidth() {
            var card = blogCarousel.querySelector('.blog-card');
            if (!card) return 340;
            return card.offsetWidth + 24; // card width + gap
        }

        blogNext.addEventListener('click', function() {
            var cardW = getBlogCardWidth();
            var maxScroll = blogCarousel.scrollWidth - blogCarousel.parentElement.offsetWidth;
            scrollAmount = Math.min(scrollAmount + cardW, maxScroll);
            blogCarousel.style.transform = 'translateX(-' + scrollAmount + 'px)';
        });

        blogPrev.addEventListener('click', function() {
            var cardW = getBlogCardWidth();
            scrollAmount = Math.max(scrollAmount - cardW, 0);
            blogCarousel.style.transform = 'translateX(-' + scrollAmount + 'px)';
        });

        // Touch/drag support
        var isDragging = false;
        var startX = 0;
        var dragStartScroll = 0;

        blogCarousel.addEventListener('mousedown', function(e) {
            isDragging = true;
            startX = e.pageX;
            dragStartScroll = scrollAmount;
        });

        blogCarousel.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            var diff = startX - e.pageX;
            var maxScroll = blogCarousel.scrollWidth - blogCarousel.parentElement.offsetWidth;
            scrollAmount = Math.max(0, Math.min(dragStartScroll + diff, maxScroll));
            blogCarousel.style.transform = 'translateX(-' + scrollAmount + 'px)';
        });

        document.addEventListener('mouseup', function() { isDragging = false; });

        blogCarousel.addEventListener('touchstart', function(e) {
            startX = e.touches[0].pageX;
            dragStartScroll = scrollAmount;
        }, { passive: true });

        blogCarousel.addEventListener('touchmove', function(e) {
            var diff = startX - e.touches[0].pageX;
            var maxScroll = blogCarousel.scrollWidth - blogCarousel.parentElement.offsetWidth;
            scrollAmount = Math.max(0, Math.min(dragStartScroll + diff, maxScroll));
            blogCarousel.style.transform = 'translateX(-' + scrollAmount + 'px)';
        }, { passive: true });
    }

    // Contact form handling
    var contactForm = document.getElementById('contactForm');
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = contactForm.querySelector('button[type="submit"]');
        var originalText = btn.textContent;
        btn.textContent = 'Enviando...';
        btn.disabled = true;

        // Simple mailto fallback
        var name = contactForm.querySelector('#name').value;
        var email = contactForm.querySelector('#email').value;
        var phone = contactForm.querySelector('#phone').value;
        var message = contactForm.querySelector('#message').value;

        var subject = encodeURIComponent('Consulta Costa Colbún - ' + name);
        var body = encodeURIComponent(
            'Nombre: ' + name + '\n' +
            'Email: ' + email + '\n' +
            'Teléfono: ' + phone + '\n\n' +
            'Mensaje:\n' + message
        );

        window.location.href = 'mailto:info@costacolbun.cl?subject=' + subject + '&body=' + body;

        setTimeout(function() {
            btn.textContent = originalText;
            btn.disabled = false;
        }, 2000);
    });

})();
