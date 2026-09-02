const DEBUG = false;

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}


// =========================
// Load Header
// =========================
fetch("Header&Footer/header.html")
    .then(response => {
        if (!response.ok) {
            throw new Error("ไม่พบไฟล์ header.html");
        }

        return response.text();
    })
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
fetch("Header&Footer/footer.html")
    .then(response => {
        if (!response.ok) {
            throw new Error("ไม่พบไฟล์ footer.html");
        }

        return response.text();
    })
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
// Toggle Mobile Menu
// =========================
function toggleMenu() {
    const navMenu = document.getElementById("navMenu");

    if (navMenu) {
        navMenu.classList.toggle("active");
    }
}
