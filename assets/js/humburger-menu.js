const menuToggle = document.querySelector("#menuToggle");
const mobileMenu = document.querySelector("#mobileMenu");
const menuOverlay = document.querySelector("#menuOverlay");
const menuLinks = document.querySelectorAll(".menu-links a");

function openMenu() {
  menuToggle.classList.add("active");
  mobileMenu.classList.add("open");
  menuOverlay.classList.add("show");
  menuToggle.setAttribute("aria-expanded", "true");
  menuToggle.setAttribute("aria-label", "بستن منو");
  mobileMenu.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
  const firstLink = mobileMenu.querySelector("a");
  if (firstLink) firstLink.focus();
}

function closeMenu() {
  menuToggle.classList.remove("active");
  mobileMenu.classList.remove("open");
  menuOverlay.classList.remove("show");
  menuToggle.setAttribute("aria-expanded", "false");
  menuToggle.setAttribute("aria-label", "باز کردن منو");
  mobileMenu.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
  menuToggle.focus();
}

function toggleMenu() {
  const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
  isOpen ? closeMenu() : openMenu();
}

menuToggle.addEventListener("click", toggleMenu);
menuOverlay.addEventListener("click", closeMenu);

menuLinks.forEach((link) => {
  link.addEventListener("click", closeMenu);
});

document.addEventListener("keydown", (event) => {
  if (
    event.key === "Escape" &&
    menuToggle.getAttribute("aria-expanded") === "true"
  ) {
    closeMenu();
  }
});

export default menuToggle;