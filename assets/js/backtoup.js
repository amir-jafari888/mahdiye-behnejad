const backToTopButton = document.getElementById("backToTop");
const circle = document.querySelector(".progress-ring__circle");
const radius = circle.r.baseVal.value;
const circumference = 2 * Math.PI * radius;

// تنظیمات اولیه SVG
circle.style.strokeDasharray = `${circumference} ${circumference}`;
circle.style.strokeDashoffset = circumference;

function setProgress(percent) {
  // محاسبه چقدر از دایره باید پر شود
  const offset = circumference - (percent / 100) * circumference;
  circle.style.strokeDashoffset = offset;
}

window.onscroll = function () {
  // ۱. محاسبه درصد اسکرول
  const winScroll =
    document.body.scrollTop || document.documentElement.scrollTop;
  const height =
    document.documentElement.scrollHeight -
    document.documentElement.clientHeight;
  const scrolled = (winScroll / height) * 100;

  // ۲. آپدیت دایره پیشرفت
  setProgress(scrolled);

  // ۳. نمایش/مخفی کردن دکمه با افکت Fade-in
  if (winScroll > 300) {
    backToTopButton.classList.add("show");
  } else {
    backToTopButton.classList.remove("show");
  }
};

// ۴. اسکرول نرم به بالا
backToTopButton.addEventListener("click", function () {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
});

export default backToTopButton;
