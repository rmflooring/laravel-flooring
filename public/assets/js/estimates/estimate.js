// public/assets/js/estimates/estimate.js
document.addEventListener("DOMContentLoaded", () => {
  console.log("[estimate.js] Loaded successfully");

  const roomsContainer = document.getElementById("rooms-container");
  const addRoomBtn = document.getElementById("add-room-btn");
  const roomTemplate = document.getElementById("room-template");

  if (!roomsContainer || !addRoomBtn || !roomTemplate) {
    console.error("[estimate.js] Missing critical DOM elements");
    return;
  }

  // ── Unsaved changes warning (Create Estimate) ───────────────────────────
  let fmHasUnsavedChanges = false;

  const form = roomsContainer.closest("form");
  if (form) {
    form.addEventListener("input", () => { fmHasUnsavedChanges = true; }, true);
    form.addEventListener("change", () => { fmHasUnsavedChanges = true; }, true);
    form.addEventListener("submit", () => { fmHasUnsavedChanges = false; });
  }

  window.addEventListener("beforeunload", (e) => {
    if (!fmHasUnsavedChanges) return;
    e.preventDefault();
    e.returnValue = "";
  });

  // ✅ INIT EXISTING ROOMS (EDIT MODE FIX)
  document.querySelectorAll('.room-card').forEach((roomCard) => {
    initProductTypeDropdownForRoom(roomCard);
    initManufacturerDropdownForRoom(roomCard);
    initStyleDropdownForRoom(roomCard);
    initColorDropdownForRoom(roomCard);
    initManualPriceOverrideForRoom(roomCard);
    initFreightDropdownForRoom(roomCard);

    roomCard.querySelectorAll('.labour-tbody tr').forEach((row) => {
      initLabourTypeDropdownForRow(row);
      initLabourDescriptionDropdownForRow(row);
    });
  });

	
// Default tax group on page load
const taxGroupInputEl = document.getElementById('tax_group_id_input');
if (taxGroupInputEl?.value) {
  const defaultId = String(taxGroupInputEl.value);

  // Grab the label from the modal button list (so we display the real group name)
  const btn = document.querySelector(`[data-tax-group-id="${defaultId}"]`);
  if (btn?.dataset?.taxGroupName) {
    window.FM_CURRENT_TAX_GROUP_LABEL = btn.dataset.taxGroupName;
  }

  loadTaxGroupRate(defaultId);
}


function applyWideMode(isWide) {
  document.body.classList.toggle("estimate-wide-mode", isWide);

  // Grab all the page width containers (top, rooms, bottom summary)
  const containers = document.querySelectorAll(
    ".estimate-normal-container, .max-w-7xl.mx-auto"
  );

  containers.forEach((el) => {
    // Record original state once so we can restore correctly
    if (el.dataset.fmInitWidth !== "1") {
      el.dataset.fmInitWidth = "1";
      el.dataset.fmHadMaxW = el.classList.contains("max-w-7xl") ? "1" : "0";
      el.dataset.fmHadMxAuto = el.classList.contains("mx-auto") ? "1" : "0";
    }

    if (isWide) {
      // GO WIDE
      el.classList.add("max-w-none", "w-full");
      if (el.dataset.fmHadMaxW === "1") el.classList.remove("max-w-7xl");
      if (el.dataset.fmHadMxAuto === "1") el.classList.remove("mx-auto");
    } else {
      // GO COMPACT (restore original)
      el.classList.remove("max-w-none", "w-full");
      if (el.dataset.fmHadMaxW === "1") el.classList.add("max-w-7xl");
      if (el.dataset.fmHadMxAuto === "1") el.classList.add("mx-auto");
    }
  });

  if (toggleWideBtn) toggleWideBtn.textContent = isWide ? "Compact Mode" : "Wide Mode";
  localStorage.setItem("estimateWideMode", isWide ? "1" : "0");
}

  // ── Helpers ──────────────────────────────────────────────────────────────

  function formatMoney(value) {
    const n = Number(value);
    return isNaN(n) ? "$0.00" : n.toLocaleString(undefined, { style: "currency", currency: "USD" });
  }

  function parseNumber(value) {
    const n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }

  function isDeletedRoom(card) {
    if (!card) return false;
    const flag = card.querySelector(".room-delete-flag");
    return flag && flag.value === "1";
  }

  function getActiveRoomCards() {
    return Array.from(roomsContainer.querySelectorAll(".room-card")).filter(
      c => !isDeletedRoom(c) && c.style.display !== "none"
    );
  }

  function renumberRooms() {
    const roomCards = getActiveRoomCards();
    roomCards.forEach((card, index) => {
      const roomNumber = index + 1;

      const title = card.querySelector(".room-title");
      if (title) title.textContent = `Room ${roomNumber}`;

      // Optional: keep Room Summary labels in sync too
      const matLabel = card.querySelector(".room-material-label");
      const frLabel = card.querySelector(".room-freight-label");
      const labLabel = card.querySelector(".room-labour-label");
      const totLabel = card.querySelector(".room-total-label");

      if (matLabel) matLabel.textContent = `Room ${roomNumber} Material Total`;
      if (frLabel) frLabel.textContent = `Room ${roomNumber} Freight Total`;
      if (labLabel) labLabel.textContent = `Room ${roomNumber} Labour Total`;
      if (totLabel) totLabel.textContent = `Room ${roomNumber} Total`;

      const moveUp = card.querySelector(".move-up");
      const moveDown = card.querySelector(".move-down");
      if (moveUp) moveUp.classList.toggle("hidden", index === 0);
      if (moveDown) moveDown.classList.toggle("hidden", index === roomCards.length - 1);
    });
  }

	// ── Tax (default group) ──────────────────────────────────────────────
window.FM_CURRENT_TAX_GROUP_ID = null;
window.FM_CURRENT_TAX_GROUP_LABEL = 'Tax';

window.FM_CURRENT_GST_PERCENT = 0;
window.FM_CURRENT_PST_PERCENT = 0;
window.FM_CURRENT_OTHER_TAXES = [];
// fallback

async function loadTaxGroupRate(groupId) {
  if (!groupId) return;

  try {
    const resp = await fetch(`/estimates/api/tax-groups/${encodeURIComponent(groupId)}/rate`, {
      headers: { Accept: 'application/json' }
    });
    if (!resp.ok) throw new Error(`Tax group rate fetch failed (${resp.status})`);

    const data = await resp.json();
	  
	  window.FM_CURRENT_TAX_GROUP_RATE_PERCENT = Number(data.tax_rate_percent || 0);

    // Basic group info
    window.FM_CURRENT_TAX_GROUP_ID = String(groupId);
    window.FM_CURRENT_TAX_GROUP_LABEL =
      (data?.group_name || data?.group_label || data?.name || '').toString().trim() || `#${groupId}`;

// ✅ Update the summary label AFTER we have the real name + percent
const taxLabel = document.querySelector('.estimate-tax-label');
if (taxLabel) {
  const groupRate = parseNumber(window.FM_CURRENT_TAX_GROUP_RATE_PERCENT || 0);
  const namePart = window.FM_CURRENT_TAX_GROUP_LABEL
    ? `Tax (${window.FM_CURRENT_TAX_GROUP_LABEL})`
    : 'Tax';
  taxLabel.textContent = `${namePart} ${groupRate}%`;
}


    // Reset split taxes
    window.FM_CURRENT_GST_PERCENT = 0;
    window.FM_CURRENT_PST_PERCENT = 0;
    window.FM_CURRENT_OTHER_TAXES = [];

    // ---- Try to read a breakdown (preferred) ----
    // Support a few common shapes:
    // data.taxes: [{name, rate_percent}] OR [{tax_name, tax_rate_sales}]
    // data.tax_rates: same
    // data.items: same
    const rawList =
      (Array.isArray(data?.taxes) && data.taxes) ||
      (Array.isArray(data?.tax_rates) && data.tax_rates) ||
      (Array.isArray(data?.items) && data.items) ||
      [];

    if (rawList.length) {
      rawList.forEach((t) => {
        const name = String(t?.name ?? t?.tax_name ?? '').trim();
        const rate = Number(t?.rate_percent ?? t?.tax_rate_sales ?? t?.rate ?? 0) || 0;

        if (/gst/i.test(name)) {
          window.FM_CURRENT_GST_PERCENT += rate;
          return;
        }
        if (/(pst|provincial)/i.test(name)) {
          window.FM_CURRENT_PST_PERCENT += rate;
          return;
        }

        // keep anything else (for later, if you ever add other taxes)
        if (rate) window.FM_CURRENT_OTHER_TAXES.push({ name, rate });
      });
    } else {
      // ---- Fallback: old endpoint only returns a single total percent ----
      // For now we treat this as GST (so totals still work),
      // and PST stays 0 until the API returns a breakdown.
      const total = Number(data?.tax_rate_percent ?? 0) || 0;
      window.FM_CURRENT_GST_PERCENT = total;
      window.FM_CURRENT_PST_PERCENT = 0;
    }

    updateEstimateTotals();
  } catch (err) {
    console.error('[tax] Failed to load tax group rate', err);

    window.FM_CURRENT_TAX_GROUP_ID = null;
    window.FM_CURRENT_TAX_GROUP_LABEL = 'Tax';
    window.FM_CURRENT_GST_PERCENT = 0;
    window.FM_CURRENT_PST_PERCENT = 0;
    window.FM_CURRENT_OTHER_TAXES = [];

    updateEstimateTotals();
  }
}



const selectTaxGroupBtn = document.getElementById('select-tax-group-btn');

if (selectTaxGroupBtn) {
  selectTaxGroupBtn.addEventListener('click', () => {
    const groupId = document.getElementById('tax_group_id_input')?.value;

    if (!groupId) return;

    // For now: just re-emit selection using the default group id.
    // (Later, the modal will emit this with the *chosen* group id + name.)
    document.dispatchEvent(new CustomEvent('fm:tax-group-selected', {
      detail: { id: groupId, name: '' }
    }));
  });
}

// 1) Modal buttons dispatch the selection event (bind ONCE)
document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-tax-group-id]');
  if (!btn) return;

  const groupId = btn.dataset.taxGroupId || btn.getAttribute('data-tax-group-id');
  const groupName = btn.dataset.taxGroupName || btn.getAttribute('data-tax-group-name') || '';

  if (!groupId) return;

  document.dispatchEvent(new CustomEvent('fm:tax-group-selected', {
    detail: { id: String(groupId), name: String(groupName) }
  }));
});

// 2) Handle selection event (bind ONCE)
document.addEventListener('fm:tax-group-selected', (e) => {
  const groupId = e?.detail?.id;
  const groupName = (e?.detail?.name || '').trim();

  if (!groupId) return;

  window.FM_CURRENT_TAX_GROUP_ID = String(groupId);
  window.FM_CURRENT_TAX_GROUP_LABEL = groupName || `#${groupId}`;

  const taxInput = document.getElementById('tax_group_id_input');
  if (taxInput) taxInput.value = String(groupId);

  const openBtn = document.getElementById('select-tax-group-btn');
  if (openBtn) openBtn.focus();

  loadTaxGroupRate(groupId);
});
											   
  // ── Row template insertion ───────────────────────────────────────────────

  function appendRowFromTemplate(roomCard, tbodySelector, templateSelector) {
    const tbody = roomCard.querySelector(tbodySelector);
    const tpl = roomCard.querySelector(templateSelector);
    if (!tbody || !tpl) return;

    const fragment = tpl.content.cloneNode(true);

    const roomCards = Array.from(roomsContainer.querySelectorAll(".room-card"));
    const roomIndex = roomCards.indexOf(roomCard);

    // Find next item index based on existing [<index>] within this tbody
    const used = Array.from(tbody.querySelectorAll('[name]'))
      .map(el => {
        const match = el.name.match(/\[(\d+)\]/);
        return match ? parseInt(match[1], 10) : -1;
      })
      .filter(v => v >= 0);

    const itemIndex = used.length ? Math.max(...used) + 1 : 0;

    fragment.querySelectorAll("[name]").forEach(el => {
      el.name = el.name
        .replaceAll("__ROOM_INDEX__", roomIndex)
        .replaceAll("__ITEM_INDEX__", itemIndex);
    });

    tbody.appendChild(fragment);
  }

  function ensureDefaultRows(roomCard) {
    const sections = [
      { tbody: ".materials-tbody", template: ".material-row-template" },
      { tbody: ".freight-tbody", template: ".freight-row-template" },
      { tbody: ".labour-tbody", template: ".labour-row-template" }
    ];

    sections.forEach(({ tbody, template }) => {
      const body = roomCard.querySelector(tbody);
      if (body && body.querySelectorAll("tr").length === 0) {
        appendRowFromTemplate(roomCard, tbody, template);
      }
    });
  }

  // ── Reindexing ───────────────────────────────────────────────────────────

  function reindexRoomCard(roomCard, newRoomIndex) {
    if (!roomCard) return;

    roomCard.querySelectorAll("[name]").forEach(el => {
      if (el.closest("template")) return;

      const name = el.getAttribute("name") || "";
      if (!name || name.includes("__ROOM_INDEX__") || name.includes("__ITEM_INDEX__")) return;

      const updated = name.replace(/^rooms\[\d+\]/, `rooms[${newRoomIndex}]`);
      el.setAttribute("name", updated);
    });
  }

  function reindexItemRows(roomCard, roomIndex) {
    const groups = [
      { tbody: ".materials-tbody", key: "materials" },
      { tbody: ".freight-tbody", key: "freight" },
      { tbody: ".labour-tbody", key: "labour" }
    ];

    groups.forEach(({ tbody, key }) => {
      const body = roomCard.querySelector(tbody);
      if (!body) return;

      const rows = Array.from(body.querySelectorAll("tr"));
      rows.forEach((row, itemIndex) => {
        row.querySelectorAll("[name]").forEach(el => {
          if (el.closest("template")) return;

          let name = el.getAttribute("name") || "";
          if (!name || name.includes("__ROOM_INDEX__") || name.includes("__ITEM_INDEX__")) return;

          name = name.replace(/^rooms\[\d+\]/, `rooms[${roomIndex}]`);
          const re = new RegExp(`\\[${key}\\]\\[\\d+\\]`, "g");
          name = name.replace(re, `[${key}][${itemIndex}]`);
          el.setAttribute("name", name);
        });
      });
    });
  }

  function reindexAllRooms() {
    const active = getActiveRoomCards();
    active.forEach((card, idx) => {
      reindexRoomCard(card, idx);
      reindexItemRows(card, idx);
    });

    renumberRooms();
    updateEstimateTotals();
  }

function initProductTypeDropdownForRoom(roomCard) {
  const inputs = roomCard.querySelectorAll('[data-product-type-input]');
  if (!inputs.length) return;

  // Cache product types per room so we don’t refetch for every row
  if (!roomCard._fmProductTypesPromise) {
    roomCard._fmProductTypesPromise = fetch('/estimates/api/product-types', {
      headers: { Accept: 'application/json' }
    })
      .then(r => r.json())
      .catch(err => {
        console.error('[estimate_mock] Failed to load product types', err);
        return [];
      });
  }

  inputs.forEach((input) => {
    // Prevent double-binding if rows get re-initialized
    if (input.dataset.ptBound === '1') return;
    input.dataset.ptBound = '1';

    const row = input.closest('tr');
    if (!row) return;

    // IMPORTANT: dropdown/list are usually in the same <td> as the input
    const cell = input.closest('td') || input.parentElement;
    if (!cell) return;

    const dropdown = cell.querySelector('[data-product-type-dropdown]');
    const list = cell.querySelector('[data-product-type-options]');
    if (!dropdown || !list) return;

    const unitInput = row.querySelector('input[name*="[unit]"]');

    let productTypes = [];
    let activeIndex = -1;

    function openDropdown() { dropdown.classList.remove('hidden'); }
    function closeDropdown() { dropdown.classList.add('hidden'); activeIndex = -1; }

    function escapeHtml(str) {
      return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function getOptionButtons() {
      return Array.from(list.querySelectorAll('button[data-pt-id]'));
    }

    function setActiveIndex(nextIndex) {
      const opts = getOptionButtons();
      if (!opts.length) { activeIndex = -1; return; }

      if (nextIndex < 0) nextIndex = opts.length - 1;
      if (nextIndex >= opts.length) nextIndex = 0;
      activeIndex = nextIndex;

      opts.forEach((btn, i) => {
        if (i === activeIndex) {
          btn.classList.add('bg-gray-100');
          btn.setAttribute('aria-selected', 'true');
          btn.scrollIntoView({ block: 'nearest' });
        } else {
          btn.classList.remove('bg-gray-100');
          btn.setAttribute('aria-selected', 'false');
        }
      });
    }

    function render(items) {
      const arr = items || [];
      if (!arr.length) {
        list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
        activeIndex = -1;
        return;
      }

      list.innerHTML = arr.map(pt => {
        const unitCode  = (pt.sold_by_unit && pt.sold_by_unit.code)  ? pt.sold_by_unit.code  : '';
        const unitLabel = (pt.sold_by_unit && pt.sold_by_unit.label) ? pt.sold_by_unit.label : '';

        return `
          <li>
            <button type="button"
              class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
              data-pt-id="${pt.id}"
              data-pt-name="${escapeHtml(pt.name)}"
              data-pt-unit="${escapeHtml(unitCode)}">
              <div class="flex justify-between items-center gap-3">
                <span class="truncate">${escapeHtml(pt.name)}</span>
                <span class="text-gray-500 text-xs whitespace-nowrap">${escapeHtml(unitLabel)}</span>
              </div>
            </button>
          </li>
        `;
      }).join('');

      activeIndex = -1;
    }

    function applyFilter() {
      const q = (input.value || '').toLowerCase();
      const filtered = productTypes.filter(pt => (pt.name || '').toLowerCase().includes(q));
      render(filtered);
    }

    function selectFromButton(btn) {
      if (!btn) return;

      const name = btn.dataset.ptName || '';
      const unit = btn.dataset.ptUnit || '';

      input.value = name;

      // ✅ Unit field shows CODE (ex: "SF")
      if (unitInput) unitInput.value = unit;

      // ✅ store selected product type id for manufacturer filtering
      input.dataset.productTypeId = btn.dataset.ptId || '';

      // ✅ clear manufacturer in same row (so it reloads correctly)
      const manuInput = row.querySelector('[data-manufacturer-input]');
      if (manuInput) {
        manuInput.value = '';
        manuInput.dispatchEvent(new Event('change', { bubbles: true }));
      }

      closeDropdown();
    }

    // Selection by mouse (mousedown beats blur)
    list.addEventListener('mousedown', (e) => {
      const btn = e.target.closest('button[data-pt-id]');
      if (!btn) return;
      e.preventDefault();
      selectFromButton(btn);
    });

    // Keyboard nav on input
    input.addEventListener('keydown', (e) => {
      const opts = getOptionButtons();

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(0);
        else setActiveIndex(activeIndex + 1);
        return;
      }

      if (e.key === 'ArrowUp') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(opts.length - 1);
        else setActiveIndex(activeIndex - 1);
        return;
      }

      if (e.key === 'Enter') {
        if (!dropdown.classList.contains('hidden') && activeIndex >= 0 && opts[activeIndex]) {
          e.preventDefault();
          selectFromButton(opts[activeIndex]);
        }
        return;
      }

      if (e.key === 'Escape') {
        if (!dropdown.classList.contains('hidden')) {
          e.preventDefault();
          closeDropdown();
        }
      }
    });

    // Open on click/focus + filter
    input.addEventListener('mousedown', async () => {
  productTypes = await roomCard._fmProductTypesPromise;
  render(productTypes);     // ✅ show ALL options
  openDropdown();
});

input.addEventListener('focus', async () => {
  productTypes = await roomCard._fmProductTypesPromise;
  render(productTypes);     // ✅ show ALL options
  openDropdown();
});

    input.addEventListener('input', () => {
      applyFilter();
      openDropdown();
    });

    // Close on outside click (but allow clicks inside this cell)
    document.addEventListener('click', (e) => {
      if (cell.contains(e.target)) return;
      closeDropdown();
    });
  });
}


function initManufacturerDropdownForRoom(roomCard) {
  const inputs = roomCard.querySelectorAll('[data-manufacturer-input]');
  if (!inputs.length) return;

  inputs.forEach((input) => {
    // Prevent double-binding
    if (input.dataset.manuBound === '1') return;
    input.dataset.manuBound = '1';

    const row = input.closest('tr');
    if (!row) return;

    const cell = input.closest('td') || input.parentElement;
    if (!cell) return;

    const dropdown = cell.querySelector('[data-manufacturer-dropdown]');
    const list = cell.querySelector('[data-manufacturer-options]');
    if (!dropdown || !list) return;

    const productTypeInput = row.querySelector('[data-product-type-input]');

    let manufacturers = [];
    let activeIndex = -1;

    function openDropdown() { dropdown.classList.remove('hidden'); }
    function closeDropdown() { dropdown.classList.add('hidden'); activeIndex = -1; }

    function escapeHtml(str) {
      return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function render(items) {
      const arr = items || [];
      if (!arr.length) {
        list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
        return;
      }

      list.innerHTML = arr.map(name => `
        <li>
          <button type="button"
            class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
            data-manu-name="${escapeHtml(name)}">
            <span class="truncate">${escapeHtml(name)}</span>
          </button>
        </li>
      `).join('');
    }

    function getOptionButtons() {
      return Array.from(list.querySelectorAll('button[data-manu-name]'));
    }

    function setActiveIndex(nextIndex) {
      const opts = getOptionButtons();
      if (!opts.length) { activeIndex = -1; return; }

      if (nextIndex < 0) nextIndex = opts.length - 1;
      if (nextIndex >= opts.length) nextIndex = 0;

      activeIndex = nextIndex;

      opts.forEach((btn, i) => {
        if (i === activeIndex) {
          btn.classList.add('bg-gray-100');
          btn.setAttribute('aria-selected', 'true');
          btn.scrollIntoView({ block: 'nearest' });
        } else {
          btn.classList.remove('bg-gray-100');
          btn.setAttribute('aria-selected', 'false');
        }
      });
    }

	function selectFromButton(btn) {
	  if (!btn) return;

	  input.value = btn.getAttribute('data-manu-name') || '';
	  closeDropdown();
	  input.dispatchEvent(new Event('change', { bubbles: true }));

	  // After selecting manufacturer, clear + pre-load style options for this row
	  const styleInput = row.querySelector('[data-style-input]');
	  if (styleInput) {
		styleInput.value = '';
		styleInput.dataset.productLineId = '';
		initStyleDropdownForRoom(roomCard); // ensures binding if needed
//		styleInput.focus();                 // optional
	  }
	}
	  
    async function loadManufacturersForSelectedProductType() {
      const ptId = productTypeInput ? (productTypeInput.dataset.productTypeId || '') : '';
      manufacturers = [];
      render([]);

      if (!ptId) return;

      const url = `${window.FM_CATALOG_MANUFACTURERS_URL}?product_type_id=${encodeURIComponent(ptId)}`;

      try {
        const resp = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!resp.ok) throw new Error(`Manufacturers fetch failed (${resp.status})`);
        const data = await resp.json();
        manufacturers = Array.isArray(data?.manufacturers) ? data.manufacturers : [];
        render(manufacturers);
      } catch (e) {
        console.error('[estimate_mock] Failed to load manufacturers', e);
        list.innerHTML = `<li class="px-3 py-2 text-red-600">Failed to load manufacturers</li>`;
      }
    }

    function applyFilter() {
      const q = (input.value || '').trim().toLowerCase();
      const filtered = manufacturers.filter(n => String(n).toLowerCase().includes(q));
      render(filtered);
    }

    // Click selection (mousedown beats blur)
    list.addEventListener('mousedown', (e) => {
      const btn = e.target.closest('button[data-manu-name]');
      if (!btn) return;
      e.preventDefault();
      selectFromButton(btn);
    });

    // Keyboard nav
    input.addEventListener('keydown', (e) => {
      const opts = getOptionButtons();

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(0);
        else setActiveIndex(activeIndex + 1);
        return;
      }

      if (e.key === 'ArrowUp') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(opts.length - 1);
        else setActiveIndex(activeIndex - 1);
        return;
      }

      if (e.key === 'Enter') {
        if (!dropdown.classList.contains('hidden') && activeIndex >= 0 && opts[activeIndex]) {
          e.preventDefault();
          selectFromButton(opts[activeIndex]);
        }
        return;
      }

      if (e.key === 'Escape') {
        if (!dropdown.classList.contains('hidden')) {
          e.preventDefault();
          closeDropdown();
        }
      }
    });

    // Open on click/focus
    input.addEventListener('mousedown', async () => {
      await loadManufacturersForSelectedProductType();
      openDropdown();
      applyFilter();
    });

    input.addEventListener('focus', async () => {
      await loadManufacturersForSelectedProductType();
      openDropdown();
      applyFilter();
    });

    input.addEventListener('input', () => {
      openDropdown();
      applyFilter();
    });

    // If product type changes, clear manufacturer
    if (productTypeInput) {
      productTypeInput.addEventListener('change', () => {
        input.value = '';
        manufacturers = [];
        render([]);
      });
    }

    // Close on outside click (outside this cell)
    document.addEventListener('click', (e) => {
      if (cell.contains(e.target)) return;
      closeDropdown();
    });
  });
}

///style dropdown
	function initStyleDropdownForRoom(roomCard) {
  const inputs = roomCard.querySelectorAll('[data-style-input]');
  if (!inputs.length) return;

  inputs.forEach((styleInput) => {
    if (styleInput.dataset.styleBound === '1') return;
    styleInput.dataset.styleBound = '1';

    const row = styleInput.closest('tr');
    if (!row) return;

    const cell = styleInput.closest('td') || styleInput.parentElement;
    if (!cell) return;

    const dropdown = cell.querySelector('[data-style-dropdown]');
    const list = cell.querySelector('[data-style-options]');
    if (!dropdown || !list) return;

    const productTypeInput = row.querySelector('[data-product-type-input]');
    const manufacturerInput = row.querySelector('[data-manufacturer-input]');

    let productLines = [];
    let activeIndex = -1;

    function openDropdown() { 
  console.log('[freight] openDropdown fired');
  dropdown.classList.remove('hidden'); 
}
    function closeDropdown() { dropdown.classList.add('hidden'); activeIndex = -1; }

    function escapeHtml(str) {
      return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function getOptionButtons() {
      return Array.from(list.querySelectorAll('button[data-line-id]'));
    }

    function setActiveIndex(nextIndex) {
      const opts = getOptionButtons();
      if (!opts.length) { activeIndex = -1; return; }

      if (nextIndex < 0) nextIndex = opts.length - 1;
      if (nextIndex >= opts.length) nextIndex = 0;

      activeIndex = nextIndex;

      opts.forEach((btn, i) => {
        if (i === activeIndex) {
          btn.classList.add('bg-gray-100');
          btn.setAttribute('aria-selected', 'true');
          btn.scrollIntoView({ block: 'nearest' });
        } else {
          btn.classList.remove('bg-gray-100');
          btn.setAttribute('aria-selected', 'false');
        }
      });
    }

    function render(items) {
      const arr = items || [];
      if (!arr.length) {
        list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
        activeIndex = -1;
        return;
      }

      list.innerHTML = arr.map(line => `
        <li>
          <button type="button"
            class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
            data-line-id="${line.id}"
            data-line-name="${escapeHtml(line.name)}">
            <span class="truncate">${escapeHtml(line.name)}</span>
          </button>
        </li>
      `).join('');

      activeIndex = -1;
    }

    function selectFromButton(btn) {
      if (!btn) return;

      const name = btn.getAttribute('data-line-name') || '';
      const id = btn.getAttribute('data-line-id') || '';

      styleInput.value = name;
      styleInput.dataset.productLineId = id;

      closeDropdown();
      styleInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async function loadProductLines() {
      const ptId = productTypeInput ? (productTypeInput.dataset.productTypeId || '') : '';
      const manu = manufacturerInput ? (manufacturerInput.value || '').trim() : '';

      productLines = [];
      render([]);

      if (!ptId || !manu) return;

      try {
        const url = new URL('/estimates/api/product-lines', window.location.origin);
        url.searchParams.set('product_type_id', ptId);
        url.searchParams.set('manufacturer', manu);

        const resp = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
        if (!resp.ok) throw new Error(`Product lines fetch failed (${resp.status})`);

        productLines = await resp.json();
        render(productLines);
      } catch (err) {
        console.error('[style] Failed to load product lines', err);
        render([]);
      }
    }

    function applyFilter() {
      const q = (styleInput.value || '').trim().toLowerCase();
      const filtered = (productLines || []).filter(l => (l.name || '').toLowerCase().includes(q));
      render(filtered);
    }

    list.addEventListener('mousedown', (e) => {
      const btn = e.target.closest('button[data-line-id]');
      if (!btn) return;
      e.preventDefault();
      selectFromButton(btn);
    });

    styleInput.addEventListener('keydown', (e) => {
      const opts = getOptionButtons();

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(0);
        else setActiveIndex(activeIndex + 1);
        return;
      }

      if (e.key === 'ArrowUp') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(opts.length - 1);
        else setActiveIndex(activeIndex - 1);
        return;
      }

      if (e.key === 'Enter') {
        if (!dropdown.classList.contains('hidden') && activeIndex >= 0 && opts[activeIndex]) {
          e.preventDefault();
          selectFromButton(opts[activeIndex]);
        }
        return;
      }

      if (e.key === 'Escape') {
        if (!dropdown.classList.contains('hidden')) {
          e.preventDefault();
          closeDropdown();
        }
      }
    });

    styleInput.addEventListener('mousedown', async () => {
      if (!productLines.length) await loadProductLines();
      openDropdown();
      applyFilter();
    });

    styleInput.addEventListener('focus', async () => {
      if (!productLines.length) await loadProductLines();
      openDropdown();
      applyFilter();
    });

    styleInput.addEventListener('input', () => {
      openDropdown();
      applyFilter();
    });

    document.addEventListener('click', (e) => {
      if (cell.contains(e.target)) return;
      closeDropdown();
    });

    if (productTypeInput) {
      productTypeInput.addEventListener('change', () => {
        styleInput.value = '';
        styleInput.dataset.productLineId = '';
        productLines = [];
        render([]);
      });
    }

    if (manufacturerInput) {
      manufacturerInput.addEventListener('change', () => {
        styleInput.value = '';
        styleInput.dataset.productLineId = '';
        productLines = [];
        render([]);
      });
    }
  });
}

//color dropdown
	function initColorDropdownForRoom(roomCard) {
  const inputs = roomCard.querySelectorAll('[data-color-input]');
  if (!inputs.length) return;

  inputs.forEach((colorInput) => {
    if (colorInput.dataset.colorBound === '1') return;
    colorInput.dataset.colorBound = '1';

    const row = colorInput.closest('tr');
    if (!row) return;

    const cell = colorInput.closest('td') || colorInput.parentElement;
    if (!cell) return;

    const dropdown = cell.querySelector('[data-color-dropdown]');
    const list = cell.querySelector('[data-color-options]');
    if (!dropdown || !list) return;

    const styleInput = row.querySelector('[data-style-input]');

    let styles = [];
    let activeIndex = -1;

    function openDropdown() {
  console.log('[freight] openDropdown fired (FREIGHT)');
  dropdown.classList.remove('hidden');
}
    function closeDropdown() { dropdown.classList.add('hidden'); activeIndex = -1; }

    function escapeHtml(str) {
      return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function getOptionButtons() {
      return Array.from(list.querySelectorAll('button[data-style-id]'));
    }

    function setActiveIndex(nextIndex) {
      const opts = getOptionButtons();
      if (!opts.length) { activeIndex = -1; return; }

      if (nextIndex < 0) nextIndex = opts.length - 1;
      if (nextIndex >= opts.length) nextIndex = 0;

      activeIndex = nextIndex;

      opts.forEach((btn, i) => {
        if (i === activeIndex) {
          btn.classList.add('bg-gray-100');
          btn.setAttribute('aria-selected', 'true');
          btn.scrollIntoView({ block: 'nearest' });
        } else {
          btn.classList.remove('bg-gray-100');
          btn.setAttribute('aria-selected', 'false');
        }
      });
    }

    function render(items) {
      const arr = items || [];
      if (!arr.length) {
        list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
        activeIndex = -1;
        return;
      }

      list.innerHTML = arr.map(s => `
        <li>
          <button type="button"
            class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
            data-style-id="${s.id}"
            data-style-name="${escapeHtml(s.name)}">
            <span class="truncate">${escapeHtml(s.name)}</span>
          </button>
        </li>
      `).join('');

      activeIndex = -1;
    }

    function selectFromButton(btn) {
      if (!btn) return;

      const name = btn.getAttribute('data-style-name') || '';
      const id = btn.getAttribute('data-style-id') || '';

      // Fill Color / Item # with Product Style "name"
      colorInput.value = name;

    // Store id for later pricing lookup
		colorInput.dataset.productStyleId = id;

		// New color selected -> allow autofill again (clear any manual override)
		const priceInput = row.querySelector('input[name$="[sell_price]"]');
		if (priceInput) delete priceInput.dataset.userOverridden;

		// Autofill price now (style price, else line default)
		autofillSellPriceForRow(row);

		closeDropdown();
		colorInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    async function loadProductStyles() {
		  const productLineId = styleInput ? (styleInput.dataset.productLineId || '') : '';
	styles = [];
	render([]);

	if (!productLineId) return;

	try {
	  const url = new URL(`/estimates/api/product-lines/${encodeURIComponent(productLineId)}/product-styles`, window.location.origin);

	  const resp = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
	  if (!resp.ok) throw new Error(`Product styles fetch failed (${resp.status})`);

	  styles = await resp.json(); // should be [{id, name}, ...] or a resource array
	  render(styles);
	} catch (err) {
	  console.error('[color] Failed to load product styles', err);
	  render([]);
	}
    }

    function applyFilter() {
      const q = (colorInput.value || '').trim().toLowerCase();
      const filtered = (styles || []).filter(s => (s.name || '').toLowerCase().includes(q));
      render(filtered);
    }

	  async function autofillSellPriceForRow(row) {
  const colorInput = row.querySelector('[data-color-input]');
  const styleInput = row.querySelector('[data-style-input]');
  const priceInput = row.querySelector('input[name$="[sell_price]"]');

  if (!colorInput || !styleInput || !priceInput) return;

  const productStyleId = colorInput.dataset.productStyleId;
  const productLineId = styleInput.dataset.productLineId;

  if (!productStyleId || !productLineId) return;

  // If user manually overrode price AND hasn't changed color since, do nothing
  // (We will clear this flag when color changes — in the select handler)
  if (priceInput.dataset.userOverridden === '1') return;

  const url = `/api/product-pricing?product_style_id=${encodeURIComponent(productStyleId)}&product_line_id=${encodeURIComponent(productLineId)}`;

  const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
  if (!resp.ok) return;

  const data = await resp.json();
  if (typeof data.sell_price !== 'number') return;

  priceInput.value = data.sell_price.toFixed(2);

  // trigger totals recalculation if your script listens to input events
  priceInput.dispatchEvent(new Event('input', { bubbles: true }));
}

    // Click selection (mousedown beats blur)
    list.addEventListener('mousedown', (e) => {
      const btn = e.target.closest('button[data-style-id]');
      if (!btn) return;
      e.preventDefault();
      selectFromButton(btn);
    });

    // Keyboard navigation
    colorInput.addEventListener('keydown', (e) => {
      const opts = getOptionButtons();

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(0);
        else setActiveIndex(activeIndex + 1);
        return;
      }

      if (e.key === 'ArrowUp') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(opts.length - 1);
        else setActiveIndex(activeIndex - 1);
        return;
      }

      if (e.key === 'Enter') {
        if (!dropdown.classList.contains('hidden') && activeIndex >= 0 && opts[activeIndex]) {
          e.preventDefault();
          selectFromButton(opts[activeIndex]);
        }
        return;
      }

      if (e.key === 'Escape') {
        if (!dropdown.classList.contains('hidden')) {
          e.preventDefault();
          closeDropdown();
        }
      }
    });

    // Open on click/focus
    colorInput.addEventListener('mousedown', async () => {
      if (!styles.length) await loadProductStyles();
      openDropdown();
      applyFilter();
    });

    colorInput.addEventListener('focus', async () => {
      if (!styles.length) await loadProductStyles();
      openDropdown();
      applyFilter();
    });

    colorInput.addEventListener('input', () => {
      openDropdown();
      applyFilter();
    });

    // Close on outside click (outside this cell)
    document.addEventListener('click', (e) => {
      if (cell.contains(e.target)) return;
      closeDropdown();
    });

    // If Style changes → clear Color and reload next open
    if (styleInput) {
      styleInput.addEventListener('change', () => {
        colorInput.value = '';
        colorInput.dataset.productStyleId = '';
        styles = [];
        render([]);
      });
    }
  });
}

	//sell price function
	function initManualPriceOverrideForRoom(roomCard) {
  // Unit Price input for material rows (name ends with [sell_price])
  const priceInputs = roomCard.querySelectorAll('input[name*="[materials]"][name$="[sell_price]"]');

  priceInputs.forEach((input) => {
    if (input.dataset.priceOverrideBound === '1') return;
    input.dataset.priceOverrideBound = '1';

    input.addEventListener('input', () => {
      input.dataset.userOverridden = '1';
    });
  });
}

	//Freight dropdown
	function initFreightDropdownForRoom(roomCard) {
  const inputs = roomCard.querySelectorAll('[data-freight-desc-input]');
  if (!inputs.length) return;
		
		if (roomCard.dataset.freightClickDebug !== '1') {
  roomCard.dataset.freightClickDebug = '1';

  roomCard.addEventListener('click', (e) => {
    const clickedFreightInput = !!e.target.closest('[data-freight-desc-input]');
    const clickedFreightCell = !!e.target.closest('td')?.querySelector?.('[data-freight-desc-input]');
    console.log('[freight click debug]', { clickedFreightInput, clickedFreightCell, target: e.target });
  }, true); // CAPTURE
}
		

  // Cache freight items per room so we don’t refetch for every row
  if (!roomCard._fmFreightItemsPromise) {
  roomCard._fmFreightItemsPromise = fetch(window.FM_CATALOG_FREIGHT_ITEMS_URL, {
    headers: { Accept: 'application/json' }
  })
    .then(async (r) => {
      const data = await r.json();

      // Normalize common response shapes into a plain array
      if (Array.isArray(data)) return data;
      if (Array.isArray(data?.freight_items)) return data.freight_items;
      if (Array.isArray(data?.items)) return data.items;
      if (Array.isArray(data?.freightItems)) return data.freightItems;

      return [];
    })
    .catch(err => {
      console.error('[estimate_mock] Failed to load freight items', err);
      return [];
    });
}

  inputs.forEach((input) => {
    // Prevent double-binding
    if (input.dataset.freightBound === '1') return;
    input.dataset.freightBound = '1';

    const row = input.closest('tr');
    if (!row) return;

    const cell = input.closest('td') || input.parentElement;
    if (!cell) return;

    const dropdown = cell.querySelector('[data-freight-desc-dropdown]');
    const list = cell.querySelector('[data-freight-desc-options]');
    if (!dropdown || !list) return;
	dropdown.style.overflow = 'visible';
						
    // Optional: if your freight API returns unit + price, we can autofill these later
    const priceInput = row.querySelector('input[name*="[sell_price]"]');

    let freightItems = [];
    let activeIndex = -1;

function openDropdown() {
  dropdown.classList.remove('hidden');

  // Proper fix: anchor to the cell so it can overlay below the input
  cell.style.position = 'relative';
  cell.style.overflow = 'visible';

  dropdown.style.position = 'absolute';
  dropdown.style.left = '0';
  dropdown.style.top = '100%';
  dropdown.style.width = '100%';
  dropdown.style.zIndex = '999999';

  // remove temp debug styling if present
  dropdown.style.outline = '';
  dropdown.style.background = '';
}

	function closeDropdown() {
	  dropdown.classList.add('hidden');
	  activeIndex = -1;
	}

    function escapeHtml(str) {
      return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function getOptionButtons() {
      return Array.from(list.querySelectorAll('button[data-freight-name]'));
    }

    function setActiveIndex(nextIndex) {
      const opts = getOptionButtons();
      if (!opts.length) { activeIndex = -1; return; }

      if (nextIndex < 0) nextIndex = opts.length - 1;
      if (nextIndex >= opts.length) nextIndex = 0;
      activeIndex = nextIndex;

      opts.forEach((btn, i) => {
        if (i === activeIndex) {
          btn.classList.add('bg-gray-100');
          btn.setAttribute('aria-selected', 'true');
          btn.scrollIntoView({ block: 'nearest' });
        } else {
          btn.classList.remove('bg-gray-100');
          btn.setAttribute('aria-selected', 'false');
        }
      });
    }

    function render(items) {
      const arr = items || [];
      if (!arr.length) {
        list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
        activeIndex = -1;
        return;
      }

      list.innerHTML = arr.map(item => {
        const name = item.name ?? item.freight_description ?? item.description ?? String(item);
        const price = (item && item.sell_price != null) ? String(item.sell_price) : '';
        return `
          <li>
            <button type="button"
              class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
              data-freight-name="${escapeHtml(name)}"
              data-freight-price="${escapeHtml(price)}">
              <span class="truncate">${escapeHtml(name)}</span>
            </button>
          </li>
        `;
      }).join('');

      activeIndex = -1;
    }

    function applyFilter() {
      const q = (input.value || '').trim().toLowerCase();
      const filtered = freightItems.filter(item => {
        const name = (item.name ?? item.freight_description ?? item.description ?? String(item));
        return String(name).toLowerCase().includes(q);
      });
      render(filtered);
    }

    function selectFromButton(btn) {
      if (!btn) return;
      input.value = btn.dataset.freightName || '';

      // Optional autofill price if present
      const p = btn.dataset.freightPrice;
      if (priceInput && p !== '' && p != null) {
        const n = Number(p);
        if (!isNaN(n)) {
          priceInput.value = n.toFixed(2);
          priceInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
      }

      closeDropdown();
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Selection by mouse (mousedown beats blur)
    list.addEventListener('mousedown', (e) => {
      const btn = e.target.closest('button[data-freight-name]');
      if (!btn) return;
      e.preventDefault();
      selectFromButton(btn);
    });

    // Keyboard nav
    input.addEventListener('keydown', (e) => {
      const opts = getOptionButtons();

      if (e.key === 'ArrowDown') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(0);
        else setActiveIndex(activeIndex + 1);
        return;
      }

      if (e.key === 'ArrowUp') {
        e.preventDefault();
        openDropdown();
        if (activeIndex === -1) setActiveIndex(opts.length - 1);
        else setActiveIndex(activeIndex - 1);
        return;
      }

      if (e.key === 'Enter') {
        if (!dropdown.classList.contains('hidden') && activeIndex >= 0 && opts[activeIndex]) {
          e.preventDefault();
          selectFromButton(opts[activeIndex]);
        }
        return;
      }

      if (e.key === 'Escape') {
        if (!dropdown.classList.contains('hidden')) {
          e.preventDefault();
          closeDropdown();
        }
      }
    });

    // Open on click/focus + filter

	input.addEventListener('pointerdown', (e) => {
	  // open instantly on click/tap
	  e.stopPropagation();

	  // show dropdown immediately (even before data arrives)
	  openDropdown();
	  list.innerHTML = `<li class="px-3 py-2 text-gray-500">Loading...</li>`;

	  // focus the input without delaying the UI
	  input.focus();

	  // load + render async without blocking the open
	  Promise.resolve(roomCard._fmFreightItemsPromise).then((items) => {
  freightItems = Array.isArray(items) ? items : [];
  render(freightItems);   // ✅ show ALL options
  openDropdown();
});
	});
	
	input.addEventListener('focus', async () => {
	  console.log('[freight] input focus fired');

	  freightItems = await roomCard._fmFreightItemsPromise;
	  applyFilter();

	  console.log('[freight] focus about to openDropdown');
	  openDropdown();
	});

    // Close on outside click (capture) — delay so open logic can win the same click
document.addEventListener('click', (e) => {
  if (cell.contains(e.target)) return;        // clicked inside input/cell
  if (dropdown.contains(e.target)) return;    // clicked inside dropdown panel

  setTimeout(() => closeDropdown(), 0);
}); // ✅ NOT capture
  });
}

 //Labour type loader
async function fetchLabourTypes() {
  const res = await fetch('/estimates/api/labour-types', {
    headers: { 'Accept': 'application/json' },
    credentials: 'same-origin',
  });
  if (!res.ok) throw new Error(`[labour-types] HTTP ${res.status}`);
  return await res.json(); // [{id,name}]
}
	//Labour type dropdown function
function initLabourTypeDropdownForRow(rowEl) {
const input = rowEl.querySelector('[data-labour-type-input]');
const dropdown = rowEl.querySelector('[data-labour-type-dropdown]');
const list = rowEl.querySelector('[data-labour-type-options]');
  if (!input || !dropdown || !list) return;

  let labourTypes = [];
  let activeIndex = -1;

  function openDropdown() { dropdown.classList.remove('hidden'); }
  function closeDropdown() { dropdown.classList.add('hidden'); activeIndex = -1; }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function render(items) {
    const arr = items || [];
    if (!arr.length) {
      list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
      return;
    }

    list.innerHTML = arr.map((t, idx) => `
      <li>
        <button type="button"
          class="w-full text-left px-3 py-2 hover:bg-gray-100"
		  data-labour-id="${t.id}"
          data-labour-name="${escapeHtml(t.name)}"
          data-index="${idx}">
          ${escapeHtml(t.name)}
        </button>
      </li>
    `).join('');
	  activeIndex = -1; // ✅ add this
  }

  function showAll() {
  render(labourTypes);
  openDropdown();
}

function filterAndShow() {
  const q = (input.value || '').toLowerCase();
  const filtered = labourTypes.filter(t => (t.name || '').toLowerCase().includes(q));
  render(filtered);
  openDropdown();
}

  // Load once (lazy)
  async function ensureLoaded() {
    if (labourTypes.length) return;
    labourTypes = await fetchLabourTypes();
  }

	  function getOptionButtons() {
    return Array.from(list.querySelectorAll('button[data-labour-name]'));
  }

  function setActiveIndex(nextIndex) {
    const opts = getOptionButtons();
    if (!opts.length) { activeIndex = -1; return; }

    if (nextIndex < 0) nextIndex = opts.length - 1;
    if (nextIndex >= opts.length) nextIndex = 0;
    activeIndex = nextIndex;

    opts.forEach((btn, i) => {
      if (i === activeIndex) {
        btn.classList.add('bg-gray-100');
        btn.setAttribute('aria-selected', 'true');
        btn.scrollIntoView({ block: 'nearest' });
      } else {
        btn.classList.remove('bg-gray-100');
        btn.setAttribute('aria-selected', 'false');
      }
    });
  }

  function selectFromButton(btn) {
  if (!btn) return;

  input.value = btn.dataset.labourName || '';

  const row = rowEl; 
  row.dataset.labourTypeId = String(btn.dataset.labourId);

  closeDropdown();
  input.dispatchEvent(new Event('change', { bubbles: true }));
}

  input.addEventListener('keydown', (e) => {
    const opts = getOptionButtons();

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      openDropdown();
      if (activeIndex === -1) setActiveIndex(0);
      else setActiveIndex(activeIndex + 1);
      return;
    }

    if (e.key === 'ArrowUp') {
      e.preventDefault();
      openDropdown();
      if (activeIndex === -1) setActiveIndex(opts.length - 1);
      else setActiveIndex(activeIndex - 1);
      return;
    }

    if (e.key === 'Enter') {
      if (!dropdown.classList.contains('hidden') && activeIndex >= 0 && opts[activeIndex]) {
        e.preventDefault();
        selectFromButton(opts[activeIndex]);
      }
      return;
    }

    if (e.key === 'Escape') {
      if (!dropdown.classList.contains('hidden')) {
        e.preventDefault();
        closeDropdown();
      }
    }
  });
		  
  // Open on click/focus like your preferred behavior
  input.addEventListener('focus', async () => {
  await ensureLoaded();
  showAll();
});

input.addEventListener('click', async () => {
  await ensureLoaded();
  showAll();
});

  input.addEventListener('input', async () => {
    await ensureLoaded();
    filterAndShow();
  });  

	// Select (mousedown beats blur)
	list.addEventListener('mousedown', (e) => {
	const btn = e.target.closest('button[data-labour-name]');
	if (!btn) return;
	e.preventDefault(); // prevent focus change / blur timing issues
	selectFromButton(btn); // reuse the same selection function
	});

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (e.target === input) return;
    if (dropdown.contains(e.target)) return;
    closeDropdown();
  });
}							   
			
// Labour description dropdown (ROW-based)
function initLabourDescriptionDropdownForRow(rowEl) {
  const typeInput = rowEl.querySelector('[data-labour-type-input]');
  const descInput = rowEl.querySelector('[data-labour-desc-input]');
  const dropdown  = rowEl.querySelector('[data-labour-desc-dropdown]');
  const list      = rowEl.querySelector('[data-labour-desc-options]');
  const unitInput = rowEl.querySelector('[data-labour-unit-input]');
  const priceInput = rowEl.querySelector('input[name*="[labour]"][name$="[sell_price]"]');
  const notesInput = rowEl.querySelector('input[name*="[labour]"][name$="[notes]"], textarea[name*="[labour]"][name$="[notes]"]');

  if (!typeInput || !descInput || !dropdown || !list || !unitInput) return;
  if (rowEl.dataset.labourDescBound === '1') return;
  rowEl.dataset.labourDescBound = '1';

  let items = [];
  let activeIndex = -1;

  function openDropdown() { dropdown.classList.remove('hidden'); }
  function closeDropdown() { dropdown.classList.add('hidden'); activeIndex = -1; }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function getButtons() {
    return Array.from(list.querySelectorAll('button[data-labour-desc]'));
  }

  function setActiveIndex(next) {
    const btns = getButtons();
    if (!btns.length) { activeIndex = -1; return; }
    if (next < 0) next = btns.length - 1;
    if (next >= btns.length) next = 0;
    activeIndex = next;
    btns.forEach((b, i) => b.classList.toggle('bg-gray-100', i === activeIndex));
    btns[activeIndex]?.scrollIntoView({ block: 'nearest' });
  }

  function render(filtered) {
    const arr = filtered || [];
    if (!arr.length) {
      list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
      return;
    }

    list.innerHTML = arr.map((it) => `
      <li>
        <button
          type="button"
          class="w-full text-left px-3 py-2 hover:bg-gray-100"
          data-labour-desc="${escapeHtml(it.description)}"
          data-labour-unit="${escapeHtml(it.unit_code)}"
		  data-labour-sell="${escapeHtml(it.sell ?? '')}"
		  data-labour-notes="${escapeHtml(it.notes ?? '')}"
        >${escapeHtml(it.description)}
		</button>
      </li>
    `).join('');

    // click select
    getButtons().forEach((btn) => {
      btn.addEventListener('click', () => {
        const desc = btn.getAttribute('data-labour-desc') || '';
        const unit = btn.getAttribute('data-labour-unit') || '';
        descInput.value = desc;
        unitInput.value = unit;
	closeDropdown();
const sell = btn.getAttribute('data-labour-sell') || '';
const notes = btn.getAttribute('data-labour-notes') || '';

if (priceInput && sell !== '') {
  const n = Number(sell);
  if (!isNaN(n)) {
    priceInput.value = n.toFixed(2);
    priceInput.dispatchEvent(new Event('input', { bubbles: true }));
  }
}

if (notesInput) {
  notesInput.value = notes;
  notesInput.dispatchEvent(new Event('input', { bubbles: true }));
}
        
        descInput.dispatchEvent(new Event('input', { bubbles: true }));
        unitInput.dispatchEvent(new Event('input', { bubbles: true }));
      });
    });
  }

  async function loadItemsForSelectedType() {
    const labourTypeId = rowEl.dataset.labourTypeId;
    if (!labourTypeId) {
      items = [];
      render([]);
      return;
    }

    const url = new URL('/estimates/api/labour-items', window.location.origin);
    url.searchParams.set('labour_type_id', labourTypeId);

    const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error(`Failed to load labour items (${res.status})`);
    items = await res.json();
    render(items);
  }

  function filterAndRender() {
    const q = (descInput.value || '').toLowerCase().trim();
    if (!q) { render(items); return; }
    render(items.filter((it) => String(it.description || '').toLowerCase().includes(q)));
  }

  descInput.addEventListener('focus', async () => {
    try { await loadItemsForSelectedType(); openDropdown(); }
    catch (e) { console.error('[labour desc] load failed', e); }
  });

  descInput.addEventListener('click', async () => {
    try { await loadItemsForSelectedType(); openDropdown(); }
    catch (e) { console.error('[labour desc] load failed', e); }
  });

  descInput.addEventListener('input', () => {
    filterAndRender();
    openDropdown();
  });

  descInput.addEventListener('keydown', (e) => {
    const btns = getButtons();
    if (!btns.length) return;

    if (e.key === 'ArrowDown') { e.preventDefault(); setActiveIndex(activeIndex + 1); openDropdown(); }
    if (e.key === 'ArrowUp') { e.preventDefault(); setActiveIndex(activeIndex - 1); openDropdown(); }
    if (e.key === 'Enter') {
      if (activeIndex >= 0) { e.preventDefault(); btns[activeIndex].click(); }
    }
    if (e.key === 'Escape') { e.preventDefault(); closeDropdown(); }
  });

  document.addEventListener('click', (e) => {
    if (!rowEl.contains(e.target)) closeDropdown();
  });
}

  // ── Room add/delete/move ────────────────────────────────────────────────

function addRoom() {
  console.log("[addRoom] Adding new room");

  const fragment = roomTemplate.content.cloneNode(true);
  const currentCount = roomsContainer.querySelectorAll(".room-card").length;
  const newIndex = currentCount;

  fragment.querySelectorAll("[name]").forEach(el => {
    el.name = el.name.replaceAll("__ROOM_INDEX__", newIndex);
  });

  roomsContainer.appendChild(fragment);

  const allCards = roomsContainer.querySelectorAll(".room-card");
  const newRoomCard = allCards[allCards.length - 1];

  if (!newRoomCard) {
    console.error("[addRoom] Failed to find new room card");
    return;
  }

  ensureDefaultRows(newRoomCard);

  // ✅ now it exists, so init is safe
	initProductTypeDropdownForRoom(newRoomCard);
	initManufacturerDropdownForRoom(newRoomCard);
	initStyleDropdownForRoom(newRoomCard);
	initColorDropdownForRoom(newRoomCard);
	initManualPriceOverrideForRoom(newRoomCard);
	initFreightDropdownForRoom(newRoomCard);
	newRoomCard.querySelectorAll('.labour-tbody tr').forEach((row) => {
  initLabourTypeDropdownForRow(row);
  initLabourDescriptionDropdownForRow(row);
});

  renumberRooms();
  reindexAllRooms();
  updateRoomTotals(newRoomCard);
  console.log("[addRoom] Success - Room #" + (newIndex + 1) + " added");
}



  addRoomBtn.addEventListener("click", addRoom);

  function deleteRoom(card) {
    if (!card) return;
    if (!confirm("Delete this room?")) return;

    const idInput = card.querySelector('input[name$="[id]"]');
    const hasId = idInput && idInput.value?.trim();

    if (hasId) {
      const flag = card.querySelector(".room-delete-flag");
      if (flag) flag.value = "1";

      card.querySelectorAll("input, select, textarea").forEach(el => {
  el.disabled = true;
});

      card.style.display = "none";
    } else {
      card.remove();
    }

    reindexAllRooms();
  }

  // ── Totals calculation ──────────────────────────────────────────────────

  function sumRowTotals(tbody) {
    if (!tbody) return 0;
    let sum = 0;

    tbody.querySelectorAll("tr").forEach(row => {
      // Use the class if present, otherwise fallback to name contains line_total
      const hidden =
        row.querySelector(".material-line-total-input") ||
        row.querySelector(".freight-line-total-input") ||
        row.querySelector(".labour-line-total-input") ||
        row.querySelector('input[name*="line_total"]');

      if (hidden) sum += parseNumber(hidden.value || 0);
    });

    return sum;
  }

  function updateRoomTotals(roomCard) {
    if (!roomCard) return;

    const matTotal = sumRowTotals(roomCard.querySelector(".materials-tbody"));
    const freightTotal = sumRowTotals(roomCard.querySelector(".freight-tbody"));
    const labourTotal = sumRowTotals(roomCard.querySelector(".labour-tbody"));
    const roomTotal = matTotal + freightTotal + labourTotal;

    // ✅ Update ALL room total displays (header + room summary tile)
    roomCard.querySelectorAll(".room-total-value").forEach(el => {
      el.textContent = formatMoney(roomTotal);
    });

    // ✅ Update room summary tiles (materials/freight/labour)
    const matValue = roomCard.querySelector(".room-material-value");
    const frValue = roomCard.querySelector(".room-freight-value");
    const labValue = roomCard.querySelector(".room-labour-value");

    if (matValue) matValue.textContent = formatMoney(matTotal);
    if (frValue) frValue.textContent = formatMoney(freightTotal);
    if (labValue) labValue.textContent = formatMoney(labourTotal);

    // Hidden room totals (these exist in your Blade)
    const matHidden = roomCard.querySelector(".room-subtotal-materials-input");
    const labHidden = roomCard.querySelector(".room-subtotal-labour-input");
    const frHidden = roomCard.querySelector(".room-subtotal-freight-input");
    const roomHidden = roomCard.querySelector(".room-total-input");

    if (matHidden) matHidden.value = matTotal.toFixed(2);
    if (labHidden) labHidden.value = labourTotal.toFixed(2);
    if (frHidden) frHidden.value = freightTotal.toFixed(2);
    if (roomHidden) roomHidden.value = roomTotal.toFixed(2);

    updateEstimateTotals();
  }

  // ✅ FIX: Estimate totals now read from hidden room subtotal inputs
  function updateEstimateTotals() {
    let materials = 0, freight = 0, labour = 0;

    getActiveRoomCards().forEach(card => {
      const matHidden = card.querySelector(".room-subtotal-materials-input");
      const frHidden = card.querySelector(".room-subtotal-freight-input");
      const labHidden = card.querySelector(".room-subtotal-labour-input");

      materials += matHidden ? parseNumber(matHidden.value) : 0;
      freight += frHidden ? parseNumber(frHidden.value) : 0;
      labour += labHidden ? parseNumber(labHidden.value) : 0;
    });    

const pretax = materials + freight + labour;

// GST applies to ALL rows (pretax)
const gstPercent = parseNumber(window.FM_CURRENT_GST_PERCENT || 0);
const gst = pretax * (gstPercent / 100);

// PST applies to MATERIALS ONLY
const pstPercent = parseNumber(window.FM_CURRENT_PST_PERCENT || 0);
const pst = materials * (pstPercent / 100);

// Optional: other taxes (default to pretax unless you later add bases)
let otherTax = 0;
if (Array.isArray(window.FM_CURRENT_OTHER_TAXES)) {
  window.FM_CURRENT_OTHER_TAXES.forEach(t => {
    const rate = parseNumber(t.rate || 0);
    otherTax += pretax * (rate / 100);
  });
}

const tax = gst + pst + otherTax;
const grand = pretax + tax;

// Effective percent for display/saving (because bases differ)
const effectivePercent = pretax > 0 ? (tax / pretax) * 100 : 0;
window.FM_CURRENT_EFFECTIVE_TAX_PERCENT = effectivePercent;


    // Visible spans in Estimate Summary (these exist)
    const setText = (sel, val) => {
      const el = document.querySelector(sel);
      if (el) el.textContent = formatMoney(val);
    };

    setText(".estimate-materials-value", materials);
    setText(".estimate-freight-value", freight);
    setText(".estimate-labour-value", labour);
    setText(".estimate-pretax-value", pretax);
    setText(".estimate-tax-value", tax);
    setText(".estimate-grand-total-value", grand);

    // Hidden totals for save (these exist)
    const setHidden = (id, val) => {
      const input = document.getElementById(id);
      if (input) input.value = val.toFixed(2);
    };

    setHidden("subtotal_materials_input", materials);
    setHidden("subtotal_freight_input", freight);
    setHidden("subtotal_labour_input", labour);
    setHidden("pretax_total_input", pretax);
    setHidden("tax_amount_input", tax);
    setHidden("grand_total_input", grand);
  }

  // ── Real-time line total calculation ────────────────────────────────────
  roomsContainer.addEventListener("input", e => {
    const input = e.target;
    if (!input.matches('input[name*="quantity"], input[name*="sell_price"]')) return;

    const row = input.closest("tr");
    if (!row) return;

    const qty = parseNumber(row.querySelector('input[name*="quantity"]')?.value || 0);
    const price = parseNumber(row.querySelector('input[name*="sell_price"]')?.value || 0);
    const lineTotal = qty * price;

    // Update visible line total (your spans exist in the 2nd last td)
    const totalSpan = row.querySelector("td:nth-last-child(2) span");
    if (totalSpan) totalSpan.textContent = formatMoney(lineTotal);

    // Update hidden line_total
    const hidden = row.querySelector('input[name*="line_total"]');
    if (hidden) hidden.value = lineTotal.toFixed(2);

    const roomCard = row.closest(".room-card");
    if (roomCard) updateRoomTotals(roomCard);
  });

  // ── Event delegation ────────────────────────────────────────────────────
  roomsContainer.addEventListener("click", e => {
    const t = e.target;

    // Add row
    if (t.closest(".add-material-row")) {
  const room = t.closest(".room-card");
  appendRowFromTemplate(room, ".materials-tbody", ".material-row-template");

  initProductTypeDropdownForRoom(room);
  initManufacturerDropdownForRoom(room);
  initStyleDropdownForRoom(room); 
  initColorDropdownForRoom(room);
  initManualPriceOverrideForRoom(room);

  reindexAllRooms();
  updateRoomTotals(room);
  return;
}

if (t.closest(".add-freight-row")) {
  const room = t.closest(".room-card");
  appendRowFromTemplate(room, ".freight-tbody", ".freight-row-template");

  initFreightDropdownForRoom(room);

  reindexAllRooms();
  updateRoomTotals(room);
  return;
}

    if (t.closest(".add-labour-row")) {
  const room = t.closest(".room-card");
  appendRowFromTemplate(room, ".labour-tbody", ".labour-row-template");

  // ✅ init labour dropdown on the newly added row
  const tbody = room.querySelector(".labour-tbody");
  const newRow = tbody ? tbody.querySelector("tr:last-child") : null;
  if (newRow) initLabourTypeDropdownForRow(newRow);
initLabourDescriptionDropdownForRow(newRow);

  reindexAllRooms();
  updateRoomTotals(room);
  return;
}

    // Delete room
    if (t.closest(".delete-room")) {
      deleteRoom(t.closest(".room-card"));
      return;
    }

    // Delete single row
    if (t.closest(".delete-material-row, .delete-freight-row, .delete-labour-row")) {
      const row = t.closest("tr");
      if (row && confirm("Delete this row?")) {
        const roomCard = row.closest(".room-card");
        row.remove();
        if (roomCard) {
          const idx = Array.from(roomsContainer.querySelectorAll(".room-card")).indexOf(roomCard);
          reindexItemRows(roomCard, idx);
          updateRoomTotals(roomCard);
        }
      }
      return;
    }

    // Move up
    if (t.closest(".move-up")) {
      const card = t.closest(".room-card");
      const active = getActiveRoomCards();
      const idx = active.indexOf(card);
      if (idx > 0) {
        roomsContainer.insertBefore(card, active[idx - 1]);
        reindexAllRooms();
      }
      return;
    }

    // Move down
    if (t.closest(".move-down")) {
      const card = t.closest(".room-card");
      const active = getActiveRoomCards();
      const idx = active.indexOf(card);
      if (idx !== -1 && idx < active.length - 1) {
        roomsContainer.insertBefore(active[idx + 1], card);
        reindexAllRooms();
      }
      return;
    }
  });

  // ── Initialize existing rooms ───────────────────────────────────────────
  roomsContainer.querySelectorAll(".room-card").forEach(card => {
  ensureDefaultRows(card);

  // bind dropdowns for existing rows
  initFreightDropdownForRoom(card);

  updateRoomTotals(card);
});

  renumberRooms();
  reindexAllRooms();

  console.log("[estimate.js] Initialization complete");
});
							