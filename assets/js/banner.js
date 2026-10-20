document.addEventListener('DOMContentLoaded', () => {
  const banner = document.getElementById('bannerSlider');
  if (!banner) return;

  const slides = [...banner.querySelectorAll('.banner-slide')];
  const dots = [...banner.querySelectorAll('.slider-dots .dot')];
  const nextButton = banner.querySelector('.next-btn');
  const previousButton = banner.querySelector('.prev-btn');
  const intervalTime = 4000;
  let currentIndex = 0;
  let slideInterval;

  if (slides.length === 0) return;

  function showSlide(index) {
    currentIndex = (index + slides.length) % slides.length;

    slides.forEach((slide, slideIndex) => {
      const active = slideIndex === currentIndex;
      slide.classList.toggle('active', active);
      slide.setAttribute('aria-hidden', String(!active));

      const dot = dots[slideIndex];
      if (dot) {
        dot.classList.toggle('active', active);
        dot.setAttribute('aria-current', String(active));
      }
    });
  }

  function stopSlider() {
    clearInterval(slideInterval);
  }

  function startSlider() {
    stopSlider();
    slideInterval = setInterval(() => showSlide(currentIndex + 1), intervalTime);
  }

  function navigateTo(index) {
    showSlide(index);
    startSlider();
  }

  nextButton?.addEventListener('click', () => navigateTo(currentIndex + 1));
  previousButton?.addEventListener('click', () => navigateTo(currentIndex - 1));
  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => navigateTo(index));
  });

  showSlide(0);
  startSlider();
});
