(() => {
    'use strict';

    const body = document.body;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const basePath = body.dataset.base || (window.location.pathname.includes('/pages/') ? '../' : './');

    function initHeader() {
        const header = document.querySelector('.site-header');
        const menuButton = document.querySelector('.menu-toggle');
        const nav = document.querySelector('#navMenu');
        if (!header || !menuButton || !nav) return;

        const closeMenu = () => {
            nav.classList.remove('active');
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'เปิดเมนู');
            body.classList.remove('menu-open');
        };

        const openMenu = () => {
            nav.classList.add('active');
            menuButton.setAttribute('aria-expanded', 'true');
            menuButton.setAttribute('aria-label', 'ปิดเมนู');
            body.classList.add('menu-open');
        };

        menuButton.addEventListener('click', () => {
            nav.classList.contains('active') ? closeMenu() : openMenu();
        });

        nav.addEventListener('click', (event) => {
            if (event.target.closest('a')) closeMenu();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMenu();
        });

        document.addEventListener('click', (event) => {
            if (window.innerWidth > 768 || !nav.classList.contains('active')) return;
            if (!header.contains(event.target)) closeMenu();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) closeMenu();
        }, { passive: true });

        const setHeaderState = () => header.classList.toggle('scrolled', window.scrollY > 24);
        setHeaderState();
        window.addEventListener('scroll', setHeaderState, { passive: true });

        initActiveNavigation(nav);
    }

    function initActiveNavigation(nav) {
        const page = body.dataset.page || 'home';
        const links = [...nav.querySelectorAll('[data-nav]')];
        const activate = (key) => {
            links.forEach(link => {
                const active = link.dataset.nav === key;
                link.classList.toggle('active', active);
                if (active) link.setAttribute('aria-current', ['about', 'contact'].includes(key) ? 'location' : 'page');
                else link.removeAttribute('aria-current');
            });
        };

        activate(page === '404' ? '' : page);

        if (page !== 'home') return;
        const about = document.querySelector('#about');
        if (!about || !('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) activate('about');
                else if (window.scrollY < about.offsetTop + about.offsetHeight * 0.4) activate('home');
            });
        }, { threshold: 0.45 });
        observer.observe(about);
    }

    function initSlider() {
        document.querySelectorAll('[data-slider]').forEach(slider => {
            const slides = [...slider.querySelectorAll('[data-slide]')];
            const dotsContainer = slider.querySelector('[data-slider-dots]');
            if (!slides.length) return;

            let current = Math.max(0, slides.findIndex(s => s.classList.contains('is-selected')));
            let timer = null;
            const interval = Number(slider.dataset.autoplay) || 5000;

            const select = (index, focusDot = false) => {
                current = (index + slides.length) % slides.length;
                slides.forEach((slide, i) => {
                    const selected = i === current;
                    slide.classList.toggle('is-selected', selected);
                    slide.setAttribute('aria-hidden', String(!selected));
                });
                if (dotsContainer) {
                    [...dotsContainer.children].forEach((dot, i) => {
                        dot.classList.toggle('is-selected', i === current);
                        dot.setAttribute('aria-pressed', String(i === current));
                    });
                    if (focusDot && dotsContainer.children[current]) dotsContainer.children[current].focus();
                }
            };

            if (dotsContainer && slides.length > 1) {
                dotsContainer.innerHTML = '';
                slides.forEach((_, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'slider-dot';
                    button.setAttribute('aria-label', `ไปสไลด์ที่ ${index + 1}`);
                    button.addEventListener('click', () => {
                        select(index);
                        restart();
                    });
                    dotsContainer.appendChild(button);
                });
            } else if (dotsContainer) {
                dotsContainer.hidden = true;
            }

            const stop = () => {
                if (timer) window.clearInterval(timer);
                timer = null;
            };
            const start = () => {
                if (slides.length <= 1 || prefersReducedMotion) return;
                stop();
                timer = window.setInterval(() => select(current + 1), interval);
            };
            const restart = () => { stop(); start(); };

            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);
            slider.addEventListener('focusin', stop);
            slider.addEventListener('focusout', start);
            document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());

            select(current);
            start();
        });
    }

    function initReveal() {
        const grids = document.querySelectorAll('.dentists-grid, .services-grid, .reviews-grid, .team-grid');
        grids.forEach(grid => [...grid.children].forEach((card, index) => {
            card.classList.add('reveal');
            card.style.setProperty('--reveal-delay', `${Math.min(index, 5) * 0.08}s`);
        }));

        const items = document.querySelectorAll('.reveal');
        if (!items.length) return;
        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            items.forEach(item => item.classList.add('reveal-visible'));
            return;
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });
        items.forEach(item => observer.observe(item));
    }

    function initHeroAnimation() {
        if (prefersReducedMotion) return;
        requestAnimationFrame(() => {
            document.querySelectorAll('.hero-content').forEach(el => el.classList.add('hero-in'));
        });
    }

    function initBackToTop() {
        const button = document.createElement('button');
        button.id = 'back-to-top';
        button.type = 'button';
        button.textContent = '↑';
        button.setAttribute('aria-label', 'กลับขึ้นด้านบน');
        body.appendChild(button);

        const toggle = () => button.classList.toggle('show', window.scrollY > 420);
        toggle();
        window.addEventListener('scroll', toggle, { passive: true });
        button.addEventListener('click', () => window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' }));
    }

    function initLineFloat() {
        if (document.querySelector('.line-float')) return;
        const link = document.createElement('a');
        link.className = 'line-float';
        link.href = 'https://line.me/R/ti/p/@583uqqdn?oat__id=7100175';
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.setAttribute('aria-label', 'แชทผ่าน LINE เพื่อนัดหมาย');
        link.innerHTML = `<img src="${basePath}images/LogoLine.webp" width="120" height="120" alt="" decoding="async">`;
        body.appendChild(link);
    }

    function initExternalLinks() {
        document.querySelectorAll('a[target="_blank"]').forEach(link => {
            const rel = new Set((link.getAttribute('rel') || '').split(/\s+/).filter(Boolean));
            rel.add('noopener');
            rel.add('noreferrer');
            link.setAttribute('rel', [...rel].join(' '));
        });
    }

    function initDoctorSchedule() {
        const section = document.querySelector('[data-doctor-schedule]');
        if (!section) return;

        const status = section.querySelector('[data-schedule-status]');
        const grid = section.querySelector('[data-schedule-grid]');
        const endpoint = section.dataset.endpoint;
        const weekdays = {
            1: 'วันจันทร์',
            2: 'วันอังคาร',
            3: 'วันพุธ',
            4: 'วันพฤหัสบดี',
            5: 'วันศุกร์',
            6: 'วันเสาร์',
            7: 'วันอาทิตย์'
        };

        const setStatus = (message, state = '') => {
            if (!status) return;
            status.textContent = message;
            status.className = `schedule-status${state ? ` ${state}` : ''}`;
            status.hidden = false;
        };

        const makeText = (tag, className, text) => {
            const el = document.createElement(tag);
            if (className) el.className = className;
            el.textContent = text;
            return el;
        };

        const render = (items) => {
            if (!grid) return;
            grid.replaceChildren();
            const grouped = new Map();
            items.forEach(item => {
                const day = Number(item.weekday);
                if (!grouped.has(day)) grouped.set(day, []);
                grouped.get(day).push(item);
            });

            Object.entries(weekdays).forEach(([dayKey, dayLabel]) => {
                const day = Number(dayKey);
                const card = document.createElement('article');
                card.className = 'schedule-day-card';
                card.appendChild(makeText('h3', '', dayLabel));
                const list = document.createElement('div');
                list.className = 'schedule-slot-list';
                const dayItems = grouped.get(day) || [];

                if (!dayItems.length) {
                    list.appendChild(makeText('p', 'schedule-empty-day', 'ยังไม่มีตาราง'));
                } else {
                    dayItems.forEach(item => {
                        const row = document.createElement('div');
                        row.className = 'schedule-slot';
                        const info = document.createElement('div');
                        info.className = 'schedule-slot-info';
                        info.appendChild(makeText('strong', '', item.doctor_name || 'ทันตแพทย์'));
                        if (item.note) info.appendChild(makeText('small', '', item.note));
                        row.appendChild(info);
                        row.appendChild(makeText('span', 'schedule-time', `${item.start_time}–${item.end_time} น.`));
                        list.appendChild(row);
                    });
                }
                card.appendChild(list);
                grid.appendChild(card);
            });

            grid.hidden = false;
            if (status) status.hidden = true;
        };

        if (!endpoint) {
            setStatus('ยังไม่ได้กำหนดแหล่งข้อมูลตารางหมอ', 'is-error');
            return;
        }

        fetch(endpoint, { headers: { Accept: 'application/json' } })
            .then(response => {
                const type = response.headers.get('content-type') || '';
                if (!response.ok || !type.includes('application/json')) throw new Error('schedule-api-unavailable');
                return response.json();
            })
            .then(data => {
                if (!data || data.ok !== true || !Array.isArray(data.items)) throw new Error('invalid-schedule-data');
                render(data.items);
            })
            .catch(() => {
                setStatus('ไม่สามารถโหลดตารางทันตแพทย์ได้ในขณะนี้ กรุณาติดต่อคลินิกเพื่อยืนยันตาราง', 'is-error');
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initHeader();
        initSlider();
        initReveal();
        initHeroAnimation();
        initBackToTop();
        initLineFloat();
        initExternalLinks();
        initDoctorSchedule();
    });
})();
