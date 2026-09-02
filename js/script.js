const DEBUG = false;

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

// โหลด Header (ปรับตามชื่อโฟลเดอร์จริงที่คุณเปลี่ยน เช่น components)
fetch("components/header.html")
    .then(response => response.text())
    .then(data => {
        document.querySelector("#header").innerHTML = data;
    });

// โหลด Footer
fetch("components/footer.html")
    .then(response => response.text())
    .then(data => {
        document.querySelector("#footer").innerHTML = data;
    });

// Toggle Mobile Menu
function toggleMenu() {
    const navMenu = document.getElementById("navMenu");

    if (navMenu) {
        navMenu.classList.toggle("active");
    }
}