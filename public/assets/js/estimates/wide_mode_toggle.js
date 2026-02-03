document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('toggle-wide-mode');
  if (!btn) return;

  const normalContainers = document.querySelectorAll('.estimate-normal-container');

  function setWideMode(isWide) {

    normalContainers.forEach(el => {
      // compact mode
      el.classList.toggle('max-w-7xl', !isWide);
      el.classList.toggle('mx-auto', !isWide);

      // wide mode
      el.classList.toggle('max-w-none', isWide);
      el.classList.toggle('w-full', isWide);
    });

    btn.textContent = isWide ? 'Compact Mode' : 'Wide Mode';
    localStorage.setItem('fm_estimate_wide_mode', isWide ? '1' : '0');
  }

  // Load saved mode (default compact)
  const saved = localStorage.getItem('fm_estimate_wide_mode') === '1';
  setWideMode(saved);

  // Toggle on click
  btn.addEventListener('click', () => {
    const current = localStorage.getItem('fm_estimate_wide_mode') === '1';
    setWideMode(!current);
  });
});
