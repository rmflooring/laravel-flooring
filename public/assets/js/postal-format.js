document.addEventListener("DOMContentLoaded", function () {
  const postalInputs = document.querySelectorAll('.postal-input');

  postalInputs.forEach((input) => {
    input.addEventListener("blur", function () {
      const clean = (this.value || "").replace(/\s/g, "").toUpperCase();

      if (/^[A-Z]\d[A-Z]\d[A-Z]\d$/.test(clean)) {
        this.value = clean.slice(0, 3) + " " + clean.slice(3);
        this.dispatchEvent(new Event('input'));
      }
    });
  });
});
