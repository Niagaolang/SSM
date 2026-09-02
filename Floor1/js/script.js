const DEBUG = false;

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

fetch("../Header&Footer/header.html")
    .then(response => response.text())
    .then(data => {
        document.querySelector("#header").innerHTML = data;
    });


fetch("../Header&Footer/footer.html")
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
