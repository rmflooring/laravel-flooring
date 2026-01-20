// public/assets/js/estimates/estimate_mock.js
document.addEventListener("DOMContentLoaded", () => {
  console.log("[estimate_mock.js] Loaded successfully");

  const roomsContainer = document.getElementById("rooms-container");
  const addRoomBtn = document.getElementById("add-room-btn");
  const roomTemplate = document.getElementById("room-template");

  if (!roomsContainer || !addRoomBtn || !roomTemplate) {
    console.error("[estimate_mock.js] Missing critical DOM elements");
    return;
  }

  // Wide/Compact mode toggle (optional)
  const toggleWideBtn = document.getElementById("toggle-wide-mode");
  if (toggleWideBtn) {
    const saved = localStorage.getItem("estimateWideMode") === "1";
    applyWideMode(saved);

    toggleWideBtn.addEventListener("click", () => {
      const isWide = !document.body.classList.contains("estimate-wide-mode");
      applyWideMode(isWide);
    });
  }

  function applyWideMode(isWide) {
    document.body.classList.toggle("estimate-wide-mode", isWide);
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
    const roomCards = roomsContainer.querySelectorAll(".room-card");
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
  const input = roomCard.querySelector('[data-product-type-input]');
  const dropdown = roomCard.querySelector('[data-product-type-dropdown]');
  const list = roomCard.querySelector('[data-product-type-options]');
  if (!input || !dropdown || !list) return;

  const row = input.closest('tr');
  const unitInput = row ? row.querySelector('input[name*="[unit]"]') : null;

  let productTypes = [];

  function openDropdown() { dropdown.classList.remove('hidden'); }
  function closeDropdown() { dropdown.classList.add('hidden'); }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

function render(items) {
  list.innerHTML = (items || []).map(pt => {
    const unitCode  = (pt.sold_by_unit && pt.sold_by_unit.code)  ? pt.sold_by_unit.code  : '';
	const unitLabel = (pt.sold_by_unit && pt.sold_by_unit.label) ? pt.sold_by_unit.label : '';
    return `
	  <li>
		<button type="button"
		  class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
		  data-pt-name="${escapeHtml(pt.name)}"
		  data-pt-unit="${escapeHtml(unitCode)}">
		  <div class="flex justify-between items-center gap-3">
			<span class="truncate">${escapeHtml(pt.name)}</span>
			<span class="text-gray-500 text-xs whitespace-nowrap">${escapeHtml(unitLabel)}</span>
		  </div>
		</button>
	  </li>`;
  }).join('');
}

		  // --- Keyboard navigation (ArrowUp/ArrowDown/Enter/Escape) ---
  let activeIndex = -1;

  function getOptionButtons() {
    return Array.from(list.querySelectorAll('button[data-pt-name]'));
  }

  function setActiveIndex(nextIndex) {
    const opts = getOptionButtons();
    if (!opts.length) {
      activeIndex = -1;
      return;
    }

    // clamp
    if (nextIndex < 0) nextIndex = opts.length - 1;
    if (nextIndex >= opts.length) nextIndex = 0;

    activeIndex = nextIndex;

    // update styles
    opts.forEach((btn, i) => {
      if (i === activeIndex) {
        btn.classList.add('bg-gray-100');
        btn.setAttribute('aria-selected', 'true');
        // keep visible
        btn.scrollIntoView({ block: 'nearest' });
      } else {
        btn.classList.remove('bg-gray-100');
        btn.setAttribute('aria-selected', 'false');
      }
    });
  }

  function selectFromButton(btn) {
    if (!btn) return;

    const name = btn.dataset.ptName || '';
    const unit = btn.dataset.ptUnit || '';

    input.value = name;
    if (unitInput) unitInput.value = unit;

    closeDropdown();
    activeIndex = -1;
  }

  // Click selection (use mousedown so it selects before blur)
  list.addEventListener('mousedown', (e) => {
    const btn = e.target.closest('button[data-pt-name]');
    if (!btn) return;
    e.preventDefault();
    selectFromButton(btn);
  });

  // Keyboard control on the input field
  input.addEventListener('keydown', (e) => {
    const opts = getOptionButtons();

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      openDropdown();
      // if nothing active yet, start at first
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
      // only intercept if dropdown open and we have an active item
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
        activeIndex = -1;
      }
      return;
    }
  });
  // --- end keyboard navigation ---

  // fetch once per room for now (we’ll optimize later)
  fetch('/admin/estimates/api/product-types', { headers: { Accept: 'application/json' }})
    .then(r => r.json())
    .then(data => {
      productTypes = data || [];
      render(productTypes);
    });

  // Open immediately on click (mousedown happens before "click" handlers)
input.addEventListener('mousedown', () => {
  openDropdown();
  // optional: if you want it filtered instantly based on current text:
  const q = (input.value || '').toLowerCase();
  render(productTypes.filter(pt => (pt.name || '').toLowerCase().includes(q)));
});

// Still open when focused (Tab)
input.addEventListener('focus', () => {
  openDropdown();
});
  input.addEventListener('input', () => {
    const q = (input.value || '').toLowerCase();
    render(productTypes.filter(pt => (pt.name || '').toLowerCase().includes(q)));
    openDropdown();
  });

  list.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-pt-name]');
    if (!btn) return;
    input.value = btn.getAttribute('data-pt-name') || '';
    const unit = btn.getAttribute('data-pt-unit') || '';
    if (unitInput && unit) unitInput.value = unit;
    closeDropdown();
  });

  document.addEventListener('click', (e) => {
    if (roomCard.contains(e.target)) return;
    closeDropdown();
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
        const nm = el.getAttribute("name") || "";
        if (!nm.endsWith("[id]") && !nm.endsWith("[_delete]")) {
          el.disabled = true;
        }
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
    const tax = 0;
    const grand = pretax + tax;

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
      reindexAllRooms();
      updateRoomTotals(room);
      return;
    }

    if (t.closest(".add-freight-row")) {
      const room = t.closest(".room-card");
      appendRowFromTemplate(room, ".freight-tbody", ".freight-row-template");
      reindexAllRooms();
      updateRoomTotals(room);
      return;
    }

    if (t.closest(".add-labour-row")) {
      const room = t.closest(".room-card");
      appendRowFromTemplate(room, ".labour-tbody", ".labour-row-template");
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
    updateRoomTotals(card);
  });

  renumberRooms();
  reindexAllRooms();
			

// Product Type searchable dropdown (Flowbite-style)
(async function initProductTypeDropdown() {
  console.log('[pt] initProductTypeDropdown start');
  const input = document.querySelector('[data-product-type-input]');
  const dropdown = document.querySelector('[data-product-type-dropdown]');
  const list = document.querySelector('[data-product-type-options]');

  if (!input || !dropdown || !list) return;

  // Find the Unit input in the same row
  const wrapper = input.closest('td') || input.parentElement;
const row = input.closest('tr') || input.closest('.material-row') || input.closest('tbody');
  const unitInput = row ? row.querySelector('input[name*="[unit]"]') : null;

  let productTypes = [];
  let filtered = [];

  function openDropdown() {
    dropdown.classList.remove('hidden');
  }

  function closeDropdown() {
    dropdown.classList.add('hidden');
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function render(items) {
    if (!items || items.length === 0) {
      list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
      return;
    }

    list.innerHTML = items
      .map((pt) => {
        const unitLabel = (pt.sold_by_unit && (pt.sold_by_unit.code || pt.sold_by_unit.label)) || '';
        return `
          <li>
            <button type="button"
              class="w-full text-left px-3 py-2 hover:bg-gray-100"
              data-pt-id="${pt.id}"
              data-pt-name="${escapeHtml(pt.name)}"
              data-pt-unit="${escapeHtml(unitLabel)}"
            >
              <div class="flex justify-between gap-2">
                <span class="truncate">${escapeHtml(pt.name)}</span>
                <span class="text-gray-400 text-xs">${escapeHtml(unitLabel)}</span>
              </div>
            </button>
          </li>
        `;
      })
      .join('');
  }

  function applyFilter() {
    const q = (input.value || '').trim().toLowerCase();
    filtered = productTypes.filter((pt) => pt.name.toLowerCase().includes(q));
    render(filtered);
  }

  // Fetch data once
  try {
	  console.log('[pt] about to fetch product types');
    const resp = await fetch('/admin/estimates/api/product-types', {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    });
    if (!resp.ok) throw new Error(`Product types fetch failed (${resp.status})`);
    productTypes = await resp.json();
	  console.log('[pt] fetched productTypes:', productTypes.length);
  } catch (e) {
    console.error('[estimate_mock] Failed to load product types', e);
    list.innerHTML = `<li class="px-3 py-2 text-red-600">Failed to load product types</li>`;
    return;
  }

  // Initial render
  render(productTypes);
console.log('[pt] rendered items:', list.children.length);

  // Open + filter behavior
  // Open on pointerdown so it happens before the document pointerdown close handler
input.addEventListener('mousedown', (e) => {
  openDropdown();
  applyFilter();
});

// Keep filtering while typing
input.addEventListener('input', () => {
  openDropdown();
  applyFilter();
});

  // Click on an option
  list.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-pt-id]');
    if (!btn) return;

    const name = btn.getAttribute('data-pt-name') || '';
    const unit = btn.getAttribute('data-pt-unit') || '';

    input.value = name;

    // Auto-fill unit but keep editable
    if (unitInput && unit) {
      unitInput.value = unit;
    }

    closeDropdown();
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

// Close on outside click
document.addEventListener('click', (e) => {
  // ignore clicks inside the product-type cell (input + dropdown)
  if (wrapper && wrapper.contains(e.target)) return;
  closeDropdown();
});

  // Close on Escape
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeDropdown();
  });
})();

  console.log("[estimate_mock.js] Initialization complete");
});
							