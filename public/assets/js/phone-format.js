document.addEventListener("DOMContentLoaded", function () {
  const phoneInputs = document.querySelectorAll('.phone-input');

  phoneInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      const digits = (this.value || "").replace(/\D/g, "");

      if (digits.length === 11 && digits.startsWith("1")) {
        this.value =
          "1-" +
          digits.slice(1, 4) +
          "-" +
          digits.slice(4, 7) +
          "-" +
          digits.slice(7, 11);
      } else if (digits.length === 10) {
        this.value =
          digits.slice(0, 3) +
          "-" +
          digits.slice(3, 6) +
          "-" +
          digits.slice(6, 10);
      }
    });
  });
});

