const DEBUG = false;

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

// คำนวณ "ระยะห่างจาก root" อัตโนมัติ จากตำแหน่งหน้าเว็บปัจจุบัน
// - ถ้าอยู่ในโฟลเดอร์ pages/ (เช่น pages/services.html) ต้องถอยออกมา 1 ชั้น -> "../"
// - ถ้าอยู่ที่ root (index.html) ไม่ต้องถอยเลย -> "./"
// วิธีนี้ทำให้ path ถูกต้องเสมอ ไม่ว่าจะรันในเครื่อง หรือ deploy บน GitHub Pages
// (ไม่ต้องมานั่งแก้ path เองทุกครั้งที่ deploy)
const isSubPage = window.location.pathname.includes('/pages/');
const basePath = isSubPage ? '../' : './';

// โหลด Header
fetch(basePath + 'components/header.html')
    .then(response => {
        if (!response.ok) {
            throw new Error(`โหลด header ไม่สำเร็จ: ${response.status}`);
        }
        return response.text();
    })
    .then(data => {
        // แทนที่ {{base}} ในไฟล์ header.html ด้วย path ที่คำนวณได้จริง
        data = data.split('{{base}}').join(basePath);
        document.querySelector('#header').innerHTML = data;
    })
    .catch(err => console.error(err));

// โหลด Footer
fetch(basePath + 'components/footer.html')
    .then(response => {
        if (!response.ok) {
            throw new Error(`โหลด footer ไม่สำเร็จ: ${response.status}`);
        }
        return response.text();
    })
    .then(data => {
        document.querySelector('#footer').innerHTML = data;
    })
    .catch(err => console.error(err));

// Toggle Mobile Menu
function toggleMenu() {
    const navMenu = document.getElementById("navMenu");

    if (navMenu) {
        navMenu.classList.toggle("active");
    }
}


/* =========================================================
   ✦ EFFECTS ADD-ON ✦
   ส่วนที่เพิ่มเข้ามาใหม่ทั้งหมดอยู่ด้านล่างนี้
   (ไม่แก้โค้ดเดิมด้านบนเลยแม้แต่บรรทัดเดียว)
   ========================================================= */

// เช็คว่าผู้ใช้ตั้งค่า "ลดการเคลื่อนไหว" ไว้ไหม (accessibility)
const prefersReducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
).matches;

// ---------------------------------------------------------
// 1) Reveal on scroll — section-heading (มี class "reveal"
//    อยู่ใน HTML แล้ว) + การ์ดใน dentists-grid / services-grid
//    (เติม class ให้อัตโนมัติ พร้อม stagger delay ทีละใบ)
// ---------------------------------------------------------
function initStaggerCards() {
    document.querySelectorAll('.dentists-grid, .services-grid, .reviews-grid')
        .forEach(grid => {
            [...grid.children].forEach((card, i) => {
                card.style.setProperty('--reveal-delay', `${i * 0.12}s`);
                card.classList.add('reveal');
            });
        });
}

function initRevealEffects() {
    const revealEls = document.querySelectorAll('.reveal');
    if (!revealEls.length) return;

    if (prefersReducedMotion) {
        revealEls.forEach(el => el.classList.add('reveal-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    revealEls.forEach(el => observer.observe(el));
}

// ---------------------------------------------------------
// 2) Header shadow ตอน scroll ลง
//    (header โหลดมาแบบ async จาก fetch ด้านบน จึงต้องรอ
//    ให้ #header มีเนื้อหาก่อน ค่อยผูก listener)
// ---------------------------------------------------------
function initHeaderScrollEffect() {
    const header = document.querySelector('.site-header');
    if (!header) return;

    const toggleScrolled = () => {
        header.classList.toggle('scrolled', window.scrollY > 30);
    };

    toggleScrolled();
    window.addEventListener('scroll', toggleScrolled);
}

// ---------------------------------------------------------
// Scroll spy — ไฮไลต์เมนู "เกี่ยวกับเรา" อัตโนมัติตอนเลื่อน
// มาเจอ section #about (ทำงานเฉพาะหน้าแรกที่มี section นี้)
// เรียกใช้หลังจาก header โหลดเสร็จ เพราะ .main-nav อยู่ใน header
// ---------------------------------------------------------
function initScrollSpy() {
    const aboutSection = document.querySelector('#about');
    const navMenu = document.querySelector('.main-nav');
    if (!aboutSection || !navMenu) return; // ไม่ใช่หน้าแรก ข้ามไป

    const navLinks = [...navMenu.querySelectorAll('a')];
    const aboutLink = navLinks.find(a => (a.getAttribute('href') || '').includes('#about'));
    const homeLink = navLinks.find(a => a.classList.contains('active')) || navLinks[0];
    if (!aboutLink || !homeLink) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                homeLink.classList.remove('active');
                aboutLink.classList.add('active');
            } else {
                aboutLink.classList.remove('active');
                homeLink.classList.add('active');
            }
        });
    }, { threshold: 0.4 });

    observer.observe(aboutSection);
}

function watchHeaderInjected() {
    const headerContainer = document.querySelector('#header');
    if (!headerContainer) return;

    // ถ้า header ถูก inject ไปแล้วก่อนโค้ดนี้ทำงาน
    if (document.querySelector('.site-header')) {
        initHeaderScrollEffect();
        initScrollSpy();
        return;
    }

    const observer = new MutationObserver(() => {
        if (document.querySelector('.site-header')) {
            initHeaderScrollEffect();
            initScrollSpy();
            observer.disconnect();
        }
    });

    observer.observe(headerContainer, { childList: true });
}

// ---------------------------------------------------------
// 3) Hero content ค่อยๆ เลื่อนเข้ามาตอนโหลดหน้าเสร็จ
// ---------------------------------------------------------
function initHeroAnimation() {
    if (prefersReducedMotion) return;

    document.querySelectorAll('.hero-content').forEach(el => {
        requestAnimationFrame(() => el.classList.add('hero-in'));
    });
}

// ---------------------------------------------------------
// 4) ปุ่ม Back-to-top (สร้าง element เองอัตโนมัติ)
// ---------------------------------------------------------
function initBackToTop() {
    if (document.querySelector('#back-to-top')) return; // กันสร้างซ้ำ

    const btn = document.createElement('button');
    btn.id = 'back-to-top';
    btn.type = 'button';
    btn.innerText = '↑';
    btn.setAttribute('aria-label', 'กลับขึ้นด้านบน');
    document.body.appendChild(btn);

    window.addEventListener('scroll', () => {
        btn.classList.toggle('show', window.scrollY > 300);
    });

    btn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: prefersReducedMotion ? 'auto' : 'smooth'
        });
    });
}

// ---------------------------------------------------------
// 5) Ripple effect ตอนคลิกปุ่ม (คล้าย Material Design)
//    ใช้กับปุ่มที่กดแล้ว "รู้สึกว่าเป็นปุ่ม" จริงๆ
// ---------------------------------------------------------
function addRipple(el) {
    if (!el || el.dataset.rippleReady) return; // กันผูก event ซ้ำ
    el.dataset.rippleReady = 'true';
    el.classList.add('ripple-container');

    el.addEventListener('click', (e) => {
        if (prefersReducedMotion) return;

        const rect = el.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);

        const span = document.createElement('span');
        span.className = 'ripple-effect';
        span.style.width = span.style.height = `${size}px`;
        span.style.left = `${e.clientX - rect.left - size / 2}px`;
        span.style.top = `${e.clientY - rect.top - size / 2}px`;

        el.appendChild(span);
        span.addEventListener('animationend', () => span.remove());
    });
}

function initRippleButtons() {
    document.querySelectorAll('.hero-button').forEach(addRipple);

    const backToTop = document.querySelector('#back-to-top');
    if (backToTop) addRipple(backToTop);
}

// ---------------------------------------------------------
// 6) ปุ่ม LINE ลอยติดจอ (มุมซ้ายล่าง สวนกับ back-to-top)
// ---------------------------------------------------------
function initLineFloat() {
    if (document.querySelector('.line-float')) return; // กันสร้างซ้ำ

    const link = document.createElement('a');
    link.className = 'line-float ripple-dark';
    link.href = 'https://line.me/R/ti/p/@583uqqdn?oat__id=7100175';
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.setAttribute('aria-label', 'แชทผ่าน LINE เพื่อนัดหมาย');

    const img = document.createElement('img');
    img.src = basePath + 'images/LogoLine.png';
    img.alt = 'LINE';
    link.appendChild(img);

    document.body.appendChild(link);
    addRipple(link);
}

// ---------------------------------------------------------
// 7) Page transition — คลิกลิงก์ภายในเว็บแล้วจางออกก่อน
//    ค่อยเปลี่ยนหน้า (ไม่ยุ่งกับลิงก์ภายนอก / เบอร์โทร / LINE)
// ---------------------------------------------------------


// ---------------------------------------------------------
// 8) Parallax รูป Hero — เลื่อนช้ากว่าคอนเทนต์ตอน scroll
//    (ปิดบนมือถือเพื่อไม่ให้ scroll กระตุก)
// ---------------------------------------------------------
function initHeroParallax() {
    if (prefersReducedMotion) return;
    if (window.innerWidth < 768) return;

    const heroImgs = document.querySelectorAll('.carousel-cell > img');
    const heroSlider = document.querySelector('#slider');
    if (!heroImgs.length || !heroSlider) return;

    const updateParallax = () => {
        const rect = heroSlider.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > window.innerHeight) return; // ไม่อยู่ในจอ ข้าม

        const offset = window.scrollY * 0.2; // ปรับความแรงพารัลแลกซ์ตรงนี้
        heroImgs.forEach(img => {
            img.style.transform = `translateY(${offset}px) scale(1.12)`;
        });
    };

    window.addEventListener('scroll', updateParallax, { passive: true });
    updateParallax();
}

// ---------------------------------------------------------
// 9) Typing effect — ข้อความ hero พิมพ์ทีละตัว
//    เริ่มพิมพ์หลัง hero fade-in เสร็จ (~0.9s)
// ---------------------------------------------------------
function initHeroTyping() {
    if (prefersReducedMotion) return;

    document.querySelectorAll('.hero-content p').forEach(p => {
        const fullText = p.textContent.replace(/\s+/g, ' ').trim();
        p.textContent = '';
        p.classList.add('typing-cursor');

        let i = 0;
        const typeNext = () => {
            if (i <= fullText.length) {
                p.textContent = fullText.slice(0, i);
                i++;
                setTimeout(typeNext, 26);
            } else {
                p.classList.remove('typing-cursor');
            }
        };

        setTimeout(typeNext, 900);
    });
}

// ---------------------------------------------------------
// 10) Blur-up — รูปหมอฟันเบลอตอนโหลด แล้วค่อยๆ ชัดขึ้น
// ---------------------------------------------------------
function initBlurUpImages() {
    document.querySelectorAll('.dentist-image img').forEach(img => {
        img.classList.add('img-blur');

        const markLoaded = () => img.classList.add('img-loaded');

        if (img.complete) {
            markLoaded();
        } else {
            img.addEventListener('load', markLoaded);
        }
    });
}

// ---------------------------------------------------------
// เริ่มทำงานทั้งหมดตอน DOM พร้อม
// ---------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    initStaggerCards();
    initRevealEffects();
    initHeroAnimation();
    initBackToTop();
    initRippleButtons();
    initLineFloat();
    initPageTransition();
    initHeroParallax();
    initHeroTyping();
    initBlurUpImages();
    watchHeaderInjected();
});
