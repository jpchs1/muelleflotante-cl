/**
 * Auto-Carousel: Convierte la sección "Últimas entradas del blog" de Elementor
 * en un carrusel deslizable automáticamente, sin modificar plantillas de Elementor.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Buscar la sección de "Ultimas entradas del blog" por su título
        var headings = document.querySelectorAll('.elementor-widget-heading .elementor-heading-title, .elementor-heading-title, h2, h3');
        var blogSection = null;

        headings.forEach(function (h) {
            var text = h.textContent.trim().toLowerCase();
            if (text.indexOf('ltimas entradas') > -1 || text.indexOf('ultimas entradas') > -1 || text.indexOf('últimas entradas') > -1) {
                // Subir hasta la sección padre de Elementor
                blogSection = h.closest('.elementor-section') || h.closest('.elementor-element') || h.closest('section');
            }
        });

        if (!blogSection) return;

        // Encontrar el contenedor de posts dentro de esa sección
        var postsContainer = blogSection.querySelector('.elementor-posts-container, .elementor-posts, .elementor-grid');
        if (!postsContainer) {
            // Buscar en la siguiente sección hermana (a veces el título y posts están en secciones separadas)
            var nextSection = blogSection.nextElementSibling;
            if (nextSection) {
                postsContainer = nextSection.querySelector('.elementor-posts-container, .elementor-posts, .elementor-grid');
                if (postsContainer) blogSection = nextSection;
            }
        }

        if (!postsContainer) return;

        // Obtener las tarjetas de posts
        var cards = postsContainer.querySelectorAll('.elementor-post, .elementor-grid-item, article');
        if (cards.length < 2) return;

        initAutoCarousel(postsContainer, cards);
    });

    function initAutoCarousel(container, cards) {
        // Estilos del carrusel
        var wrapper = document.createElement('div');
        wrapper.className = 'ipc-auto-carousel';

        // Insertar wrapper
        container.parentNode.insertBefore(wrapper, container);
        wrapper.appendChild(container);

        // Aplicar estilos al contenedor
        container.style.display = 'flex';
        container.style.flexWrap = 'nowrap';
        container.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        container.style.gap = '24px';
        container.style.willChange = 'transform';

        // Aplicar estilos a las tarjetas
        cards.forEach(function (card) {
            card.style.flex = '0 0 calc(33.333% - 16px)';
            card.style.maxWidth = 'calc(33.333% - 16px)';
            card.style.minWidth = '0';
            card.style.boxSizing = 'border-box';
        });

        // Wrapper overflow
        wrapper.style.overflow = 'hidden';
        wrapper.style.position = 'relative';
        wrapper.style.padding = '0 0 20px';

        // Crear controles de navegación
        var navHtml = '<div class="ipc-auto-nav">' +
            '<button class="ipc-auto-btn ipc-auto-prev" aria-label="Anterior">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>' +
            '</button>' +
            '<div class="ipc-auto-dots"></div>' +
            '<button class="ipc-auto-btn ipc-auto-next" aria-label="Siguiente">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>' +
            '</button>' +
            '</div>';
        wrapper.insertAdjacentHTML('beforeend', navHtml);

        var prevBtn = wrapper.querySelector('.ipc-auto-prev');
        var nextBtn = wrapper.querySelector('.ipc-auto-next');
        var dotsContainer = wrapper.querySelector('.ipc-auto-dots');

        var currentIndex = 0;
        var autoplayTimer = null;

        function getVisible() {
            var w = wrapper.offsetWidth;
            if (w <= 640) return 1;
            if (w <= 1024) return 2;
            return 3;
        }

        function getMaxIndex() {
            return Math.max(0, cards.length - getVisible());
        }

        function getSlideWidth() {
            if (cards.length === 0) return 0;
            return cards[0].offsetWidth + 24; // card width + gap
        }

        function slideTo(index) {
            currentIndex = Math.max(0, Math.min(index, getMaxIndex()));
            container.style.transform = 'translateX(-' + (currentIndex * getSlideWidth()) + 'px)';
            updateDots();
            updateButtons();
        }

        function updateButtons() {
            prevBtn.style.opacity = currentIndex <= 0 ? '0.3' : '1';
            prevBtn.style.pointerEvents = currentIndex <= 0 ? 'none' : 'auto';
            nextBtn.style.opacity = currentIndex >= getMaxIndex() ? '0.3' : '1';
            nextBtn.style.pointerEvents = currentIndex >= getMaxIndex() ? 'none' : 'auto';
        }

        function createDots() {
            dotsContainer.innerHTML = '';
            var total = getMaxIndex() + 1;
            for (var i = 0; i < total; i++) {
                var dot = document.createElement('button');
                dot.className = 'ipc-auto-dot' + (i === 0 ? ' active' : '');
                dot.dataset.index = i;
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', function () {
                    slideTo(parseInt(this.dataset.index));
                    resetAutoplay();
                });
                dotsContainer.appendChild(dot);
            }
        }

        function updateDots() {
            var dots = dotsContainer.querySelectorAll('.ipc-auto-dot');
            dots.forEach(function (d, i) {
                d.classList.toggle('active', i === currentIndex);
            });
        }

        prevBtn.addEventListener('click', function () {
            slideTo(currentIndex - 1);
            resetAutoplay();
        });

        nextBtn.addEventListener('click', function () {
            slideTo(currentIndex + 1);
            resetAutoplay();
        });

        // Touch support
        var startX = 0, isDragging = false;
        wrapper.addEventListener('touchstart', function (e) {
            isDragging = true;
            startX = e.touches[0].clientX;
            container.style.transition = 'none';
        }, { passive: true });

        wrapper.addEventListener('touchmove', function (e) {
            if (!isDragging) return;
            var diff = e.touches[0].clientX - startX;
            var offset = -(currentIndex * getSlideWidth()) + diff;
            container.style.transform = 'translateX(' + offset + 'px)';
        }, { passive: true });

        wrapper.addEventListener('touchend', function (e) {
            if (!isDragging) return;
            isDragging = false;
            container.style.transition = '';
            var diff = e.changedTouches[0].clientX - startX;
            if (diff < -50) slideTo(currentIndex + 1);
            else if (diff > 50) slideTo(currentIndex - 1);
            else slideTo(currentIndex);
            resetAutoplay();
        });

        // Autoplay
        function startAutoplay() {
            stopAutoplay();
            autoplayTimer = setInterval(function () {
                if (currentIndex >= getMaxIndex()) slideTo(0);
                else slideTo(currentIndex + 1);
            }, 4500);
        }

        function stopAutoplay() {
            if (autoplayTimer) clearInterval(autoplayTimer);
        }

        function resetAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        wrapper.addEventListener('mouseenter', stopAutoplay);
        wrapper.addEventListener('mouseleave', startAutoplay);

        // Responsive
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                // Actualizar tamaño de tarjetas según viewport
                var visible = getVisible();
                var pct = (100 / visible) - (16 * (visible - 1) / visible);
                cards.forEach(function (card) {
                    card.style.flex = '0 0 calc(' + (100 / visible) + '% - 16px)';
                    card.style.maxWidth = 'calc(' + (100 / visible) + '% - 16px)';
                });
                createDots();
                slideTo(Math.min(currentIndex, getMaxIndex()));
            }, 200);
        });

        // Responsive inicial
        var visible = getVisible();
        if (visible < 3) {
            cards.forEach(function (card) {
                card.style.flex = '0 0 calc(' + (100 / visible) + '% - 16px)';
                card.style.maxWidth = 'calc(' + (100 / visible) + '% - 16px)';
            });
        }

        // Init
        createDots();
        slideTo(0);
        startAutoplay();
    }
})();
