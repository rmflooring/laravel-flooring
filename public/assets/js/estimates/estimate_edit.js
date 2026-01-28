document.addEventListener("DOMContentLoaded", () => {
  // Kick calculations for existing rows on Edit page:
  // estimate_mock.js updates totals when inputs fire, but edit loads with values already set.
  setTimeout(() => {
    document.querySelectorAll(".room-card input[type='number']").forEach((el) => {
      // Trigger the same listeners estimate_mock.js uses for recalculation
      el.dispatchEvent(new Event("input", { bubbles: true }));
    });
  }, 0);
});