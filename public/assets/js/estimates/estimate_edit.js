document.addEventListener("DOMContentLoaded", () => {
  // Kick calculations for existing rows on Edit page:
  // estimate.js updates totals when inputs fire, but edit loads with values already set.
  setTimeout(() => {
    document.querySelectorAll(".room-card input[type='number']").forEach((el) => {
      // Trigger the same listeners estimate.js uses for recalculation
      el.dispatchEvent(new Event("input", { bubbles: true }));
    });
  }, 0);
});

function renumberLineItems(tbody) {
  tbody.querySelectorAll('tr').forEach((row, index) => {
    const orderInput = row.querySelector('.js-line-item-order');
    if (orderInput) {
      orderInput.value = index + 1;
    }
  });
}

document.addEventListener('click', function (e) {

  if (e.target.closest('.js-move-row-up')) {
    const row = e.target.closest('tr');
    const tbody = row.parentElement;
    const prev = row.previousElementSibling;

    if (prev) {
      tbody.insertBefore(row, prev);
      renumberLineItems(tbody);
    }
  }

  if (e.target.closest('.js-move-row-down')) {
    const row = e.target.closest('tr');
    const tbody = row.parentElement;
    const next = row.nextElementSibling;

    if (next) {
      tbody.insertBefore(next, row);
      renumberLineItems(tbody);
    }
  }

});
