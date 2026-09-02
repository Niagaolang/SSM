const DEBUG = false;

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

// โหลด Header และผูก Event ให้ปุ่มเมนูทำงานได้ทันทีหลังโหลดเสร็จ
fetch("components/header.html")
    .then(response => response.text())
    .then(data => {
        document.querySelector("#header").innerHTML = data;
        
        // ผูกฟังก์ชันปุ่มเปิด-ปิดเมนูมือถือ
        const menuToggle = document.querySelector(".menu-toggle");
        const navMenu = document.getElementById("navMenu");

        if (menuToggle && navMenu) {
            menuToggle.addEventListener("click", () => {
                navMenu.classList.toggle("active");
            });
        }
    });

// โหลด Footer
fetch("components/footer.html")
    .then(response => response.text())
    .then(data => {
        document.querySelector("#footer").innerHTML = data;
    });

// ฟังก์ชันสำรองกรณีเรียกผ่าน onclick ใน HTML
function toggleMenu() {
    const navMenu = document.getElementById("navMenu");
    if (navMenu) {
        navMenu.classList.toggle("active");
    }
}
