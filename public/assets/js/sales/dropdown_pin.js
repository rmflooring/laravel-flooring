document.addEventListener('DOMContentLoaded', () => {

  const DROPDOWN_SELECTOR =
    '[data-product-type-dropdown],' +
    '[data-manufacturer-dropdown],' +
    '[data-style-dropdown],' +
    '[data-color-dropdown],' +
    '[data-freight-desc-dropdown],' +
    '[data-labour-type-dropdown],' +
    '[data-labour-desc-dropdown]';

  const INPUT_SELECTOR =
    'input[data-product-type-input],' +
    'input[data-manufacturer-input],' +
    'input[data-style-input],' +
    'input[data-color-input],' +
    'input[data-freight-desc-input],' +
    'input[data-labour-type-input],' +
    'input[data-labour-desc-input]';

  function findOwnerInput(dropdown) {
    const cell = dropdown.closest('td, .relative');
    if (!cell) return null;
    return cell.querySelector(INPUT_SELECTOR);
  }

  function pinDropdown(dropdown) {
    const input = findOwnerInput(dropdown);
    if (!input) return;

    const r = input.getBoundingClientRect();

    dropdown.dataset.pinned = '1';
    dropdown.style.position = 'fixed';
    dropdown.style.left = r.left + 'px';
    dropdown.style.top = (r.bottom + 6) + 'px';
    dropdown.style.width = r.width + 'px';
    dropdown.style.zIndex = 9999;
  }

  function unpinDropdown(dropdown) {
    if (dropdown.dataset.pinned !== '1') return;

    dropdown.dataset.pinned = '0';
    dropdown.style.position = '';
    dropdown.style.left = '';
    dropdown.style.top = '';
    dropdown.style.width = '';
    dropdown.style.zIndex = '';
  }

  function attach(dropdown) {
    if (!dropdown || dropdown.dataset.pinObserverAttached) return;
    dropdown.dataset.pinObserverAttached = '1';

    const obs = new MutationObserver(() => {
      if (!dropdown.classList.contains('hidden')) pinDropdown(dropdown);
      else unpinDropdown(dropdown);
    });

    obs.observe(dropdown, { attributes: true, attributeFilter: ['class'] });

    if (!dropdown.classList.contains('hidden')) pinDropdown(dropdown);
  }

  // Existing dropdowns
  document.querySelectorAll(DROPDOWN_SELECTOR).forEach(attach);

  // Dropdowns added later (new rows/rooms)
  const docObs = new MutationObserver((muts) => {
    muts.forEach((m) => {
      m.addedNodes.forEach((n) => {
        if (!(n instanceof HTMLElement)) return;

        if (n.matches && n.matches(DROPDOWN_SELECTOR)) attach(n);
        n.querySelectorAll?.(DROPDOWN_SELECTOR).forEach(attach);
      });
    });
  });

  docObs.observe(document.body, { childList: true, subtree: true });

  function repin() {
    document.querySelectorAll(DROPDOWN_SELECTOR).forEach((d) => {
      if (d.dataset.pinned === '1' && !d.classList.contains('hidden')) {
        pinDropdown(d);
      }
    });
  }

  window.addEventListener('scroll', repin, true);
  window.addEventListener('resize', repin);
});
