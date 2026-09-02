const DEBUG = false;

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}


// =========================
// ตรวจว่าอยู่ในโฟลเดอร์ pages หรือไม่
// =========================

const isPages = window.location.pathname.includes("/pages/");

const path = isPages ? "../" : "";


// =========================
// Load Header
// =========================

fetch(`${path}Header&Footer/header.html`)
    .then(response => response.text())
    .then(data => {
        const header = document.querySelector("#header");

        if (header) {
            header.innerHTML = data;
        }
    })
    .catch(error => {
        console.error("Header Error:", error);
    });


// =========================
// Load Footer
// =========================

fetch(`${path}Header&Footer/footer.html`)
    .then(response => response.text())
    .then(data => {
        const footer = document.querySelector("#footer");

        if (footer) {
            footer.innerHTML = data;
        }
    })
    .catch(error => {
        console.error("Footer Error:", error);
    });


// =========================
// Mobile Menu
// =========================

function toggleMenu() {

    const navMenu = document.getElementById("navMenu");

    if (navMenu) {
        navMenu.classList.toggle("active");
    }

}
