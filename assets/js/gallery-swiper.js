var gallerySwiper = new Swiper(".gallerySwiper", {
  loop: true,
  spaceBetween: 20,

  autoplay: {
    delay: 3000, // هیچ توقفی بین اسلایدها نباشد
    disableOnInteraction: false, // این خط باعث می‌شود با کلیک یا درگ، اتوپلی خاموش نشود
    pauseOnMouseEnter: false,
  },
  // breakPoints: {
  //   60: {
  //     slidesPerView: 1,
  //     spaceBetween: 20,
  //   },
  //   1009: {
  //     slidesPerView: 2,
  //     spaceBetween: 10,
  //   },
  // },

  slidesPerView: 1,
  centeredSlides: true,
  watchSlidesProgress: true,
});

export default gallerySwiper;
