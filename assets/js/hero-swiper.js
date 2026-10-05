var swiper = new Swiper(".heroSwiper", {
  loop: true,
  watchSlidesProgress: true,
  speed: 700,
  breakpoints: {
    60: {
      slidesPerView: 1.2,
      spaceBetween: 20,
    },
    1009: {
      slidesPerView: 1,
      spaceBetween: 20,
    },
  },
  pagination: {
    el: ".swiper-pagination",
    dynamicBullets: true,
  },
  autoplay: {
    delay: 3000,
    disableOnInteraction: false,
    // reverseDirection: true,
  },
});

export default swiper;
