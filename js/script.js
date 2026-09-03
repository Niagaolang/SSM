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
