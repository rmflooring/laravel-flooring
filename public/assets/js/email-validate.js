document.addEventListener("DOMContentLoaded", function () {
  const emailInputs = document.querySelectorAll(".email-input");

  emailInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      const val = (this.value || "").trim();
      const errorId = "email-error-" + (this.id || this.name);
      let errorEl = document.getElementById(errorId);

      if (!errorEl) {
        errorEl = document.createElement("p");
        errorEl.id = errorId;
        errorEl.className = "text-red-500 text-xs mt-1";
        this.parentNode.appendChild(errorEl);
      }

      if (val === "") {
        errorEl.textContent = "";
        this.classList.remove("border-red-500");
        return;
      }

      const valid = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);

      if (!valid) {
        errorEl.textContent = "Please enter a valid email address (e.g. name@example.com).";
        this.classList.add("border-red-500");
      } else {
        errorEl.textContent = "";
        this.classList.remove("border-red-500");
      }
    });

    input.addEventListener("focus", function () {
      const errorId = "email-error-" + (this.id || this.name);
      const errorEl = document.getElementById(errorId);
      if (errorEl) errorEl.textContent = "";
      this.classList.remove("border-red-500");
    });
  });
});
