/**
 * Blog Carousel - Imporlan Premium Content
 * Smooth touch-enabled carousel with autoplay
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var wrappers = document.querySelectorAll('.ipc-carousel-wrapper');

        wrappers.forEach(function (wrapper) {
            initCarousel(wrapper);
        });
    });

    function initCarousel(wrapper) {
        var track = wrapper.querySelector('.ipc-carousel-track');
        var cards = wrapper.querySelectorAll('.ipc-carousel-card');
        var prevBtn = wrapper.querySelector('.ipc-prev');
        var nextBtn = wrapper.querySelector('.ipc-next');
        var dotsContainer = wrapper.querySelector('.ipc-carousel-dots');

        if (!track || cards.length === 0) return;

        var autoplay = wrapper.dataset.autoplay === 'true';
        var speed = parseInt(wrapper.dataset.speed, 10) || 4000;
        var currentIndex = 0;
        var autoplayTimer = null;
        var isDragging = false;
        var startX = 0;
        var currentTranslate = 0;
        var prevTranslate = 0;

        function getVisibleCount() {
            var w = wrapper.offsetWidth;
            if (w <= 640) return 1;
            if (w <= 1024) return 2;
            return parseInt(wrapper.dataset.columns, 10) || 3;
        }

        function getMaxIndex() {
            var visible = getVisibleCount();
            return Math.max(0, cards.length - visible);
        }

        function getSlideWidth() {
            if (cards.length === 0) return 0;
            var card = cards[0];
            var style = window.getComputedStyle(card);
            var gap = parseInt(window.getComputedStyle(track).gap, 10) || 24;
            return card.offsetWidth + gap;
        }

        function slideTo(index) {
            var maxIndex = getMaxIndex();
            currentIndex = Math.max(0, Math.min(index, maxIndex));
            var offset = -(currentIndex * getSlideWidth());
            track.style.transform = 'translateX(' + offset + 'px)';
            prevTranslate = offset;
            updateDots();
            updateButtons();
        }

        function updateButtons() {
            if (prevBtn) prevBtn.disabled = currentIndex <= 0;
            if (nextBtn) nextBtn.disabled = currentIndex >= getMaxIndex();
        }

        function createDots() {
            if (!dotsContainer) return;
            dotsContainer.innerHTML = '';
            var total = getMaxIndex() + 1;
            for (var i = 0; i < total; i++) {
                var dot = document.createElement('button');
                dot.className = 'ipc-carousel-dot' + (i === currentIndex ? ' active' : '');
                dot.setAttribute('aria-label', 'Ir a slide ' + (i + 1));
                dot.dataset.index = i;
                dot.addEventListener('click', function () {
                    slideTo(parseInt(this.dataset.index, 10));
                    resetAutoplay();
                });
                dotsContainer.appendChild(dot);
            }
        }

        function updateDots() {
            if (!dotsContainer) return;
            var dots = dotsContainer.querySelectorAll('.ipc-carousel-dot');
            dots.forEach(function (dot, i) {
                dot.classList.toggle('active', i === currentIndex);
            });
        }

        // Navigation buttons
        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                slideTo(currentIndex - 1);
                resetAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                slideTo(currentIndex + 1);
                resetAutoplay();
            });
        }

        // Touch / Drag support
        function onPointerDown(e) {
            isDragging = true;
            startX = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
            track.style.transition = 'none';
        }

        function onPointerMove(e) {
            if (!isDragging) return;
            var x = e.type.includes('mouse') ? e.pageX : e.touches[0].clientX;
            var diff = x - startX;
            currentTranslate = prevTranslate + diff;
            track.style.transform = 'translateX(' + currentTranslate + 'px)';
        }

        function onPointerUp(e) {
            if (!isDragging) return;
            isDragging = false;
            track.style.transition = '';
            var x = e.type.includes('mouse') ? e.pageX : (e.changedTouches ? e.changedTouches[0].clientX : startX);
            var diff = x - startX;
            var threshold = getSlideWidth() * 0.2;

            if (diff < -threshold) {
                slideTo(currentIndex + 1);
            } else if (diff > threshold) {
                slideTo(currentIndex - 1);
            } else {
                slideTo(currentIndex);
            }
            resetAutoplay();
        }

        track.addEventListener('mousedown', onPointerDown);
        track.addEventListener('mousemove', onPointerMove);
        track.addEventListener('mouseup', onPointerUp);
        track.addEventListener('mouseleave', function () {
            if (isDragging) onPointerUp({ pageX: startX, type: 'mouse' });
        });

        track.addEventListener('touchstart', onPointerDown, { passive: true });
        track.addEventListener('touchmove', onPointerMove, { passive: true });
        track.addEventListener('touchend', onPointerUp);

        // Prevent drag on links/images
        track.addEventListener('dragstart', function (e) { e.preventDefault(); });

        // Autoplay
        function startAutoplay() {
            if (!autoplay) return;
            stopAutoplay();
            autoplayTimer = setInterval(function () {
                if (currentIndex >= getMaxIndex()) {
                    slideTo(0);
                } else {
                    slideTo(currentIndex + 1);
                }
            }, speed);
        }

        function stopAutoplay() {
            if (autoplayTimer) {
                clearInterval(autoplayTimer);
                autoplayTimer = null;
            }
        }

        function resetAutoplay() {
            stopAutoplay();
            startAutoplay();
        }

        // Pause on hover
        wrapper.addEventListener('mouseenter', stopAutoplay);
        wrapper.addEventListener('mouseleave', startAutoplay);

        // Resize handler
        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                createDots();
                slideTo(Math.min(currentIndex, getMaxIndex()));
            }, 200);
        });

        // Init
        createDots();
        slideTo(0);
        startAutoplay();
    }
})();
