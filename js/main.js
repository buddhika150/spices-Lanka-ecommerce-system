document.addEventListener("DOMContentLoaded", function () {
    console.log("Spices Lanka script loaded.");

    const tabBtns = document.querySelectorAll(".tab-btn");
    tabBtns.forEach(btn => {
        btn.addEventListener("click", function () {
            tabBtns.forEach(b => b.classList.remove("active"));
            this.classList.add("active");
        });
    });
});