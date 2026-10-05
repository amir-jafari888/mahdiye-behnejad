console.log("form.js loaded");

document.addEventListener("DOMContentLoaded", () => {
  console.log("DOM فرم آماده شد");

  const choiceSection = document.querySelector(".auth-choice");
  const loginSection = document.querySelector(".auth-panel--login");
  const signupSection = document.querySelector(".auth-panel--signup");

  const loginButton = document.querySelector(".auth-button--login");
  const signupButton = document.querySelector(".auth-button--signup");
  const backButtons = document.querySelectorAll(".auth-back");

  console.log({
    choiceSection,
    loginSection,
    signupSection,
    loginButton,
    signupButton,
    backButtons
  });

  if (
    !choiceSection ||
    !loginSection ||
    !signupSection ||
    !loginButton ||
    !signupButton
  ) {
    console.error("عناصر فرم احراز هویت کامل پیدا نشدند");
    return;
  }

  function hideAllPanels() {
    choiceSection.classList.add("is-hidden");
    loginSection.classList.add("is-hidden");
    signupSection.classList.add("is-hidden");
  }

  loginButton.addEventListener("click", (event) => {
    event.preventDefault();

    hideAllPanels();
    loginSection.classList.remove("is-hidden");
  });

  signupButton.addEventListener("click", (event) => {
    event.preventDefault();

    hideAllPanels();
    signupSection.classList.remove("is-hidden");
  });

  backButtons.forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();

      hideAllPanels();
      choiceSection.classList.remove("is-hidden");
    });
  });
});
