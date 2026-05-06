// public/assets/js/invoices/invoice_edit.js
document.addEventListener("DOMContentLoaded", () => {
  console.log("[invoice_edit.js] Loaded");

  const roomsContainer = document.getElementById("rooms-container");
  const addRoomBtn     = document.getElementById("add-room-btn");
  const roomTemplate   = document.getElementById("room-template");

  if (!roomsContainer || !addRoomBtn || !roomTemplate) {
    console.error("[invoice_edit.js] Missing critical DOM elements");
    return;
  }

  const form = roomsContainer.closest("form") || document.getElementById("invoice-edit-form");

  // Unsaved changes warning
  let fmHasUnsavedChanges = false;
  if (form) {
    form.addEventListener("input",  () => { fmHasUnsavedChanges = true; }, true);
    form.addEventListener("change", () => { fmHasUnsavedChanges = true; }, true);
    form.addEventListener("submit", () => { fmHasUnsavedChanges = false; });
  }
  window.addEventListener("beforeunload", (e) => {
    if (!fmHasUnsavedChanges) return;
    e.preventDefault();
    e.returnValue = "";
  });

  // ── Helpers ──────────────────────────────────────────────────────────────

  // Positions a dropdown panel using fixed positioning so it escapes
  // any overflow:auto/hidden ancestor (e.g. the overflow-x-auto table wrapper).
  function positionFixed(inputEl, dropdownEl, minWidth = 180) {
    const rect = inputEl.getBoundingClientRect();
    const w    = Math.max(rect.width, minWidth);
    dropdownEl.style.position = "fixed";
    dropdownEl.style.top      = (rect.bottom + 2) + "px";
    dropdownEl.style.left     = rect.left + "px";
    dropdownEl.style.width    = w + "px";
    dropdownEl.style.margin   = "0";
    dropdownEl.style.zIndex   = "9999";
  }

  // Close all open dropdown panels on scroll or resize so they don't
  // float detached from their inputs.
  document.addEventListener("scroll", () => {
    document.querySelectorAll(
      "[data-product-type-dropdown]:not(.hidden)," +
      "[data-manufacturer-dropdown]:not(.hidden)," +
      "[data-style-dropdown]:not(.hidden)," +
      "[data-color-dropdown]:not(.hidden)," +
      "[data-freight-desc-dropdown]:not(.hidden)," +
      "[data-labour-type-dropdown]:not(.hidden)," +
      "[data-labour-desc-dropdown]:not(.hidden)"
    ).forEach(d => d.classList.add("hidden"));
  }, true);

  function formatMoney(value) {
    const n = Number(value);
    return isNaN(n) ? "$0.00" : "$" + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  function parseNumber(value) {
    const n = parseFloat(value);
    return isNaN(n) ? 0 : n;
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function isDeletedRoom(card) {
    const flag = card.querySelector(".room-delete-flag");
    return flag && flag.value === "1";
  }

  function getActiveRoomCards() {
    return Array.from(roomsContainer.querySelectorAll(".room-card")).filter(
      c => !isDeletedRoom(c) && c.style.display !== "none"
    );
  }

  // ── Totals ───────────────────────────────────────────────────────────────

  function sumRowTotals(tbody) {
    if (!tbody) return 0;
    let sum = 0;
    tbody.querySelectorAll("tr").forEach(row => {
      const hidden = row.querySelector('input[name*="[line_total]"]');
      if (hidden) sum += parseNumber(hidden.value || 0);
    });
    return sum;
  }

  function updateRoomTotals(roomCard) {
    if (!roomCard) return;

    const matTotal    = sumRowTotals(roomCard.querySelector(".materials-tbody"));
    const freightTotal = sumRowTotals(roomCard.querySelector(".freight-tbody"));
    const labourTotal  = sumRowTotals(roomCard.querySelector(".labour-tbody"));
    const roomTotal    = matTotal + freightTotal + labourTotal;

    const q = (sel) => roomCard.querySelector(sel);

    const matVal = q(".room-material-value");
    const frVal  = q(".room-freight-value");
    const labVal = q(".room-labour-value");
    const totVal = q(".room-total-value");

    if (matVal) matVal.textContent = formatMoney(matTotal);
    if (frVal)  frVal.textContent  = formatMoney(freightTotal);
    if (labVal) labVal.textContent = formatMoney(labourTotal);
    if (totVal) totVal.textContent = formatMoney(roomTotal);

    const matHidden = q(".room-subtotal-materials-input");
    const frHidden  = q(".room-subtotal-freight-input");
    const labHidden = q(".room-subtotal-labour-input");
    const totHidden = q(".room-total-input");

    if (matHidden) matHidden.value = matTotal.toFixed(2);
    if (frHidden)  frHidden.value  = freightTotal.toFixed(2);
    if (labHidden) labHidden.value = labourTotal.toFixed(2);
    if (totHidden) totHidden.value = roomTotal.toFixed(2);

    updateInvoiceTotals();
  }

  function updateInvoiceTotals() {
    let subtotal = 0;

    getActiveRoomCards().forEach(card => {
      const h = card.querySelector(".room-total-input");
      subtotal += h ? parseNumber(h.value) : 0;
    });

    const taxRate  = parseNumber(window.FM_INVOICE_TAX_RATE || 0) / 100;
    const taxAmt   = round2(subtotal * taxRate);
    const grandTotal = round2(subtotal + taxAmt);

    const setText = (sel, val) => {
      const el = document.querySelector(sel);
      if (el) el.textContent = formatMoney(val);
    };
    setText(".invoice-subtotal-value", subtotal);
    setText(".invoice-tax-value", taxAmt);
    setText(".invoice-grand-total-value", grandTotal);

    const setHidden = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.value = val.toFixed(2);
    };
    setHidden("subtotal_input", subtotal);
    setHidden("tax_amount_input", taxAmt);
    setHidden("grand_total_input", grandTotal);
  }

  function round2(n) { return Math.round(n * 100) / 100; }

  // Live line total recalculation
  roomsContainer.addEventListener("input", e => {
    const input = e.target;
    if (!input.matches('input[name*="[quantity]"], input[name*="[sell_price]"]')) return;

    const row = input.closest("tr");
    if (!row) return;

    const qty   = parseNumber(row.querySelector('input[name*="[quantity]"]')?.value || 0);
    const price = parseNumber(row.querySelector('input[name*="[sell_price]"]')?.value || 0);
    const line  = qty * price;

    const span   = row.querySelector(".line-total-display");
    const hidden = row.querySelector('input[name*="[line_total]"]');

    if (span)   span.textContent = formatMoney(line);
    if (hidden) hidden.value     = line.toFixed(2);

    const roomCard = row.closest(".room-card");
    if (roomCard) updateRoomTotals(roomCard);
  });

  // ── Row template insertion ────────────────────────────────────────────────

  function appendRowFromTemplate(roomCard, tbodySelector, templateSelector) {
    const tbody = roomCard.querySelector(tbodySelector);
    const tpl   = roomCard.querySelector(templateSelector);
    if (!tbody || !tpl) return;

    const fragment = tpl.content.cloneNode(true);

    const allCards = Array.from(roomsContainer.querySelectorAll(".room-card"));
    const roomIndex = allCards.indexOf(roomCard);

    const used = Array.from(tbody.querySelectorAll("[name]"))
      .map(el => {
        const allMatches = [...el.name.matchAll(/\[(\d+)\]/g)];
        const match = allMatches[1];
        return match ? parseInt(match[1], 10) : -1;
      })
      .filter(v => v >= 0);

    const itemIndex = used.length ? Math.max(...used) + 1 : 0;

    fragment.querySelectorAll("[name]").forEach(el => {
      el.name = el.name
        .replaceAll("__ROOM_INDEX__", roomIndex)
        .replaceAll("__ITEM_INDEX__", itemIndex);
    });

    const ownerForm = form || document.getElementById("invoice-edit-form");
    if (ownerForm?.id) {
      fragment.querySelectorAll("input, select, textarea").forEach(el => {
        el.setAttribute("form", ownerForm.id);
      });
    }

    tbody.appendChild(fragment);
  }

  // ── Reindexing ────────────────────────────────────────────────────────────

  function reindexRoomCard(roomCard, newRoomIndex) {
    roomCard.querySelectorAll("[name]").forEach(el => {
      if (el.closest("template")) return;
      const name = el.getAttribute("name") || "";
      if (!name || name.includes("__ROOM_INDEX__") || name.includes("__ITEM_INDEX__")) return;
      el.setAttribute("name", name.replace(/^rooms\[\d+\]/, `rooms[${newRoomIndex}]`));
    });
  }

  function reindexItemRows(roomCard, roomIndex) {
    const groups = [
      { tbody: ".materials-tbody", key: "materials" },
      { tbody: ".freight-tbody",   key: "freight"   },
      { tbody: ".labour-tbody",    key: "labour"    },
    ];

    groups.forEach(({ tbody, key }) => {
      const body = roomCard.querySelector(tbody);
      if (!body) return;

      Array.from(body.querySelectorAll("tr")).forEach((row, itemIndex) => {
        row.querySelectorAll("[name]").forEach(el => {
          if (el.closest("template")) return;
          let name = el.getAttribute("name") || "";
          if (!name || name.includes("__ROOM_INDEX__") || name.includes("__ITEM_INDEX__")) return;
          name = name.replace(/^rooms\[\d+\]/, `rooms[${roomIndex}]`);
          name = name.replace(new RegExp(`\\[${key}\\]\\[\\d+\\]`, "g"), `[${key}][${itemIndex}]`);
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
    updateInvoiceTotals();
  }

  function renumberRooms() {
    const cards = getActiveRoomCards();
    cards.forEach((card, idx) => {
      const n = idx + 1;
      const title = card.querySelector(".room-title");
      if (title) title.textContent = `Room ${n}`;

      const matLbl = card.querySelector(".room-material-label");
      const frLbl  = card.querySelector(".room-freight-label");
      const labLbl = card.querySelector(".room-labour-label");
      const totLbl = card.querySelector(".room-total-label");

      if (matLbl) matLbl.textContent = `Room ${n} Material Total`;
      if (frLbl)  frLbl.textContent  = `Room ${n} Freight Total`;
      if (labLbl) labLbl.textContent = `Room ${n} Labour Total`;
      if (totLbl) totLbl.textContent = `Room ${n} Total`;

      const moveUp   = card.querySelector(".move-up");
      const moveDown = card.querySelector(".move-down");
      if (moveUp)   moveUp.classList.toggle("hidden", idx === 0);
      if (moveDown) moveDown.classList.toggle("hidden", idx === cards.length - 1);
    });
  }

  // ── Product Type dropdown ─────────────────────────────────────────────────

  function initProductTypeDropdownForRoom(roomCard) {
    if (!roomCard._fmProductTypesPromise) {
      roomCard._fmProductTypesPromise = fetch(window.FM_CATALOG_PRODUCT_TYPES_URL, {
        headers: { Accept: "application/json" },
      })
        .then(r => r.json())
        .catch(() => []);
    }

    roomCard._fmProductTypesPromise.then((types) => {
      roomCard.querySelectorAll("[data-product-type-input]").forEach((input) => {
        if (input.dataset.productTypeId) return;
        const text = (input.value || "").trim().toLowerCase();
        if (!text) return;
        const match = types.find(t => (t.name || "").toLowerCase() === text);
        if (match) input.dataset.productTypeId = match.id;
      });
    });

    roomCard.querySelectorAll("[data-product-type-input]").forEach((input) => {
      if (input.dataset.ptBound === "1") return;
      input.dataset.ptBound = "1";

      const row  = input.closest("tr");
      const cell = input.closest("td") || input.parentElement;
      if (!row || !cell) return;

      const dropdown = cell.querySelector("[data-product-type-dropdown]");
      const list     = cell.querySelector("[data-product-type-options]");
      if (!dropdown || !list) return;

      let productTypes = [];
      let activeIndex  = -1;

      const open  = () => { dropdown.classList.remove("hidden"); positionFixed(input, dropdown, 176); };
      const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

      function getOpts() { return Array.from(list.querySelectorAll("button[data-pt-id]")); }

      function setActive(next) {
        const opts = getOpts();
        if (!opts.length) { activeIndex = -1; return; }
        if (next < 0) next = opts.length - 1;
        if (next >= opts.length) next = 0;
        activeIndex = next;
        opts.forEach((b, i) => {
          b.classList.toggle("bg-gray-100", i === activeIndex);
          b.setAttribute("aria-selected", i === activeIndex ? "true" : "false");
          if (i === activeIndex) b.scrollIntoView({ block: "nearest" });
        });
      }

      function render(items) {
        if (!(items || []).length) {
          list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`;
          activeIndex = -1;
          return;
        }
        list.innerHTML = (items || []).map(pt => {
          const code = pt.sold_by_unit?.code || "";
          return `<li><button type="button"
            class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
            data-pt-id="${pt.id}" data-pt-name="${escapeHtml(pt.name)}" data-pt-unit="${escapeHtml(code)}">
            <div class="flex justify-between items-center gap-3">
              <span class="truncate">${escapeHtml(pt.name)}</span>
              <span class="text-gray-500 text-xs whitespace-nowrap">${escapeHtml(code.toUpperCase())}</span>
            </div></button></li>`;
        }).join("");
        activeIndex = -1;
      }

      function applyFilter() {
        const q = (input.value || "").toLowerCase();
        render(productTypes.filter(pt => (pt.name || "").toLowerCase().includes(q)));
      }

      function selectBtn(btn) {
        if (!btn) return;
        input.value = btn.dataset.ptName || "";
        input.dataset.productTypeId = btn.dataset.ptId || "";

        const unitInput = row.querySelector("input[name*=\"[unit]\"]");
        if (unitInput && btn.dataset.ptUnit) unitInput.value = btn.dataset.ptUnit;

        const manuInput = row.querySelector("[data-manufacturer-input]");
        if (manuInput) {
          manuInput.value = "";
          manuInput.dispatchEvent(new Event("change", { bubbles: true }));
        }
        close();
      }

      list.addEventListener("mousedown", (e) => {
        const btn = e.target.closest("button[data-pt-id]");
        if (!btn) return;
        e.preventDefault();
        selectBtn(btn);
      });

      input.addEventListener("keydown", (e) => {
        const opts = getOpts();
        if (e.key === "ArrowDown") { e.preventDefault(); open(); setActive(activeIndex === -1 ? 0 : activeIndex + 1); return; }
        if (e.key === "ArrowUp")   { e.preventDefault(); open(); setActive(activeIndex === -1 ? opts.length - 1 : activeIndex - 1); return; }
        if (e.key === "Enter" && !dropdown.classList.contains("hidden") && activeIndex >= 0 && opts[activeIndex]) { e.preventDefault(); selectBtn(opts[activeIndex]); return; }
        if (e.key === "Escape" && !dropdown.classList.contains("hidden")) { e.preventDefault(); close(); }
      });

      input.addEventListener("mousedown", async () => {
        productTypes = await roomCard._fmProductTypesPromise;
        render(productTypes);
        open();
      });

      input.addEventListener("focus", async () => {
        productTypes = await roomCard._fmProductTypesPromise;
        render(productTypes);
        open();
      });

      input.addEventListener("input", () => { applyFilter(); open(); });

      document.addEventListener("click", (e) => {
        if (cell.contains(e.target)) return;
        close();
      });
    });
  }

  // ── Manufacturer dropdown ─────────────────────────────────────────────────

  function initManufacturerDropdownForRoom(roomCard) {
    roomCard.querySelectorAll("[data-manufacturer-input]").forEach((input) => {
      if (input.dataset.manuBound === "1") return;
      input.dataset.manuBound = "1";

      const row  = input.closest("tr");
      const cell = input.closest("td") || input.parentElement;
      if (!row || !cell) return;

      const dropdown = cell.querySelector("[data-manufacturer-dropdown]");
      const list     = cell.querySelector("[data-manufacturer-options]");
      if (!dropdown || !list) return;

      const ptInput = row.querySelector("[data-product-type-input]");
      let manufacturers = [];
      let activeIndex   = -1;

      const open  = () => { dropdown.classList.remove("hidden"); positionFixed(input, dropdown, 176); };
      const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

      function getOpts() { return Array.from(list.querySelectorAll("button[data-manu-name]")); }

      function setActive(next) {
        const opts = getOpts();
        if (!opts.length) { activeIndex = -1; return; }
        if (next < 0) next = opts.length - 1;
        if (next >= opts.length) next = 0;
        activeIndex = next;
        opts.forEach((b, i) => {
          b.classList.toggle("bg-gray-100", i === activeIndex);
          b.setAttribute("aria-selected", i === activeIndex ? "true" : "false");
          if (i === activeIndex) b.scrollIntoView({ block: "nearest" });
        });
      }

      function render(items) {
        if (!(items || []).length) { list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`; return; }
        list.innerHTML = (items || []).map(name => `<li><button type="button"
          class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
          data-manu-name="${escapeHtml(name)}"><span class="truncate">${escapeHtml(name)}</span></button></li>`).join("");
      }

      function applyFilter() {
        const q = (input.value || "").trim().toLowerCase();
        render(manufacturers.filter(n => String(n).toLowerCase().includes(q)));
      }

      function selectBtn(btn) {
        if (!btn) return;
        input.value = btn.getAttribute("data-manu-name") || "";
        close();
        input.dispatchEvent(new Event("change", { bubbles: true }));
        const styleInput = row.querySelector("[data-style-input]");
        if (styleInput) { styleInput.value = ""; styleInput.dataset.productLineId = ""; }
      }

      async function loadManufacturers() {
        const ptId = ptInput ? (ptInput.dataset.productTypeId || "") : "";
        manufacturers = [];
        render([]);
        if (!ptId) return;
        try {
          const resp = await fetch(`${window.FM_CATALOG_MANUFACTURERS_URL}?product_type_id=${encodeURIComponent(ptId)}`, { headers: { Accept: "application/json" } });
          if (!resp.ok) return;
          const data = await resp.json();
          manufacturers = Array.isArray(data?.manufacturers) ? data.manufacturers : [];
          render(manufacturers);
        } catch (e) { console.error("[manu] load failed", e); }
      }

      list.addEventListener("mousedown", (e) => {
        const btn = e.target.closest("button[data-manu-name]");
        if (!btn) return;
        e.preventDefault();
        selectBtn(btn);
      });

      input.addEventListener("keydown", (e) => {
        const opts = getOpts();
        if (e.key === "ArrowDown") { e.preventDefault(); open(); setActive(activeIndex === -1 ? 0 : activeIndex + 1); return; }
        if (e.key === "ArrowUp")   { e.preventDefault(); open(); setActive(activeIndex === -1 ? opts.length - 1 : activeIndex - 1); return; }
        if (e.key === "Enter" && !dropdown.classList.contains("hidden") && activeIndex >= 0 && opts[activeIndex]) { e.preventDefault(); selectBtn(opts[activeIndex]); return; }
        if (e.key === "Escape" && !dropdown.classList.contains("hidden")) { e.preventDefault(); close(); }
      });

      input.addEventListener("mousedown", async () => { await loadManufacturers(); open(); render(manufacturers); });
      input.addEventListener("focus",     async () => { await loadManufacturers(); open(); render(manufacturers); });
      input.addEventListener("input",     ()       => { applyFilter(); open(); });

      if (ptInput) {
        ptInput.addEventListener("change", () => { input.value = ""; manufacturers = []; render([]); });
      }

      document.addEventListener("click", (e) => { if (cell.contains(e.target)) return; close(); });
    });
  }

  // ── Style dropdown ────────────────────────────────────────────────────────

  function initStyleDropdownForRoom(roomCard) {
    roomCard.querySelectorAll("[data-style-input]").forEach((styleInput) => {
      if (styleInput.dataset.styleBound === "1") return;
      styleInput.dataset.styleBound = "1";

      const row  = styleInput.closest("tr");
      const cell = styleInput.closest("td") || styleInput.parentElement;
      if (!row || !cell) return;

      const dropdown     = cell.querySelector("[data-style-dropdown]");
      const list         = cell.querySelector("[data-style-options]");
      if (!dropdown || !list) return;

      const ptInput   = row.querySelector("[data-product-type-input]");
      const manuInput = row.querySelector("[data-manufacturer-input]");
      let productLines = [];
      let activeIndex  = -1;

      const open  = () => { dropdown.classList.remove("hidden"); positionFixed(styleInput, dropdown, 176); };
      const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

      function getOpts() { return Array.from(list.querySelectorAll("button[data-line-id]")); }

      function setActive(next) {
        const opts = getOpts();
        if (!opts.length) { activeIndex = -1; return; }
        if (next < 0) next = opts.length - 1;
        if (next >= opts.length) next = 0;
        activeIndex = next;
        opts.forEach((b, i) => {
          b.classList.toggle("bg-gray-100", i === activeIndex);
          b.setAttribute("aria-selected", i === activeIndex ? "true" : "false");
          if (i === activeIndex) b.scrollIntoView({ block: "nearest" });
        });
      }

      function render(items) {
        if (!(items || []).length) { list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`; activeIndex = -1; return; }
        list.innerHTML = (items || []).map(line => `<li><button type="button"
          class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
          data-line-id="${line.id}" data-line-name="${escapeHtml(line.name)}"
          data-sell-price="${parseFloat(line.default_sell_price ?? 0).toFixed(4)}">
          <span class="truncate">${escapeHtml(line.name)}</span></button></li>`).join("");
        activeIndex = -1;
      }

      function applyFilter() {
        const q = (styleInput.value || "").trim().toLowerCase();
        render((productLines || []).filter(l => (l.name || "").toLowerCase().includes(q)));
      }

      function selectBtn(btn) {
        if (!btn) return;
        const name = btn.getAttribute("data-line-name") || "";
        const id   = btn.getAttribute("data-line-id")   || "";
        styleInput.value = name;
        styleInput.dataset.productLineId = id;

        const lineIdInput = row.querySelector(".js-product-line-id-input");
        if (lineIdInput) lineIdInput.value = id;

        const priceInput = row.querySelector("input[name$=\"[sell_price]\"]");
        if (priceInput && priceInput.dataset.userOverridden !== "1") {
          const sell = parseFloat(btn.getAttribute("data-sell-price") || 0);
          if (sell > 0) {
            priceInput.value = sell.toFixed(4);
            priceInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }

        close();
        styleInput.dispatchEvent(new Event("change", { bubbles: true }));
      }

      async function loadProductLines() {
        const ptId = ptInput   ? (ptInput.dataset.productTypeId || "")   : "";
        const manu = manuInput ? (manuInput.value || "").trim()           : "";
        productLines = [];
        render([]);
        if (!ptId || !manu) return;
        try {
          const url = new URL("/pages/estimates/api/product-lines", window.location.origin);
          url.searchParams.set("product_type_id", ptId);
          url.searchParams.set("manufacturer", manu);
          const resp = await fetch(url.toString(), { headers: { Accept: "application/json" } });
          if (!resp.ok) return;
          productLines = await resp.json();
          render(productLines);
        } catch (e) { console.error("[style] load failed", e); }
      }

      list.addEventListener("mousedown", (e) => {
        const btn = e.target.closest("button[data-line-id]");
        if (!btn) return;
        e.preventDefault();
        selectBtn(btn);
      });

      styleInput.addEventListener("keydown", (e) => {
        const opts = getOpts();
        if (e.key === "ArrowDown") { e.preventDefault(); open(); setActive(activeIndex === -1 ? 0 : activeIndex + 1); return; }
        if (e.key === "ArrowUp")   { e.preventDefault(); open(); setActive(activeIndex === -1 ? opts.length - 1 : activeIndex - 1); return; }
        if (e.key === "Enter" && !dropdown.classList.contains("hidden") && activeIndex >= 0 && opts[activeIndex]) { e.preventDefault(); selectBtn(opts[activeIndex]); return; }
        if (e.key === "Escape" && !dropdown.classList.contains("hidden")) { e.preventDefault(); close(); }
      });

      styleInput.addEventListener("mousedown", async () => { await loadProductLines(); open(); render(productLines); });
      styleInput.addEventListener("focus",     async () => { await loadProductLines(); open(); render(productLines); });
      styleInput.addEventListener("input",     ()       => { applyFilter(); open(); });

      document.addEventListener("click", (e) => { if (cell.contains(e.target)) return; close(); });

      if (ptInput)   ptInput.addEventListener("change",   () => { styleInput.value = ""; styleInput.dataset.productLineId = ""; productLines = []; render([]); });
      if (manuInput) manuInput.addEventListener("change", () => { styleInput.value = ""; styleInput.dataset.productLineId = ""; productLines = []; render([]); });
    });
  }

  // ── Color dropdown ────────────────────────────────────────────────────────

  function initColorDropdownForRoom(roomCard) {
    roomCard.querySelectorAll("[data-color-input]").forEach((colorInput) => {
      if (colorInput.dataset.colorBound === "1") return;
      colorInput.dataset.colorBound = "1";

      const row  = colorInput.closest("tr");
      const cell = colorInput.closest("td") || colorInput.parentElement;
      if (!row || !cell) return;

      const dropdown  = cell.querySelector("[data-color-dropdown]");
      const list      = cell.querySelector("[data-color-options]");
      if (!dropdown || !list) return;

      const styleInput = row.querySelector("[data-style-input]");
      let styles      = [];
      let activeIndex = -1;

      const open  = () => { dropdown.classList.remove("hidden"); positionFixed(colorInput, dropdown, 176); };
      const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

      function getOpts() { return Array.from(list.querySelectorAll("button[data-style-id]")); }

      function setActive(next) {
        const opts = getOpts();
        if (!opts.length) { activeIndex = -1; return; }
        if (next < 0) next = opts.length - 1;
        if (next >= opts.length) next = 0;
        activeIndex = next;
        opts.forEach((b, i) => {
          b.classList.toggle("bg-gray-100", i === activeIndex);
          b.setAttribute("aria-selected", i === activeIndex ? "true" : "false");
          if (i === activeIndex) b.scrollIntoView({ block: "nearest" });
        });
      }

      function render(items) {
        if (!(items || []).length) { list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`; activeIndex = -1; return; }
        list.innerHTML = (items || []).map(s => `<li><button type="button"
          class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
          data-style-id="${s.id}" data-style-name="${escapeHtml(s.name)}"
          data-sell-price="${parseFloat(s.sell_price ?? 0).toFixed(4)}">
          <span class="truncate">${escapeHtml(s.name)}</span></button></li>`).join("");
        activeIndex = -1;
      }

      function applyFilter() {
        const q = (colorInput.value || "").trim().toLowerCase();
        render((styles || []).filter(s => (s.name || "").toLowerCase().includes(q)));
      }

      function selectBtn(btn) {
        if (!btn) return;
        colorInput.value = btn.getAttribute("data-style-name") || "";
        colorInput.dataset.productStyleId = btn.getAttribute("data-style-id") || "";

        const styleIdInput = row.querySelector(".js-product-style-id-input");
        if (styleIdInput) styleIdInput.value = colorInput.dataset.productStyleId;

        const priceInput = row.querySelector("input[name$=\"[sell_price]\"]");
        if (priceInput) {
          delete priceInput.dataset.userOverridden;
          const sell = parseFloat(btn.getAttribute("data-sell-price") || 0);
          if (sell > 0) {
            priceInput.value = sell.toFixed(4);
            priceInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }

        close();
        colorInput.dispatchEvent(new Event("change", { bubbles: true }));
      }

      async function loadStyles() {
        const lineId = styleInput ? (styleInput.dataset.productLineId || "") : "";
        styles = [];
        render([]);
        if (!lineId) return;
        try {
          const url = `/pages/estimates/api/product-lines/${encodeURIComponent(lineId)}/product-styles`;
          const resp = await fetch(url, { headers: { Accept: "application/json" } });
          if (!resp.ok) return;
          styles = await resp.json();
          render(styles);
        } catch (e) { console.error("[color] load failed", e); }
      }

      list.addEventListener("mousedown", (e) => {
        const btn = e.target.closest("button[data-style-id]");
        if (!btn) return;
        e.preventDefault();
        selectBtn(btn);
      });

      colorInput.addEventListener("keydown", (e) => {
        const opts = getOpts();
        if (e.key === "ArrowDown") { e.preventDefault(); open(); setActive(activeIndex === -1 ? 0 : activeIndex + 1); return; }
        if (e.key === "ArrowUp")   { e.preventDefault(); open(); setActive(activeIndex === -1 ? opts.length - 1 : activeIndex - 1); return; }
        if (e.key === "Enter" && !dropdown.classList.contains("hidden") && activeIndex >= 0 && opts[activeIndex]) { e.preventDefault(); selectBtn(opts[activeIndex]); return; }
        if (e.key === "Escape" && !dropdown.classList.contains("hidden")) { e.preventDefault(); close(); }
      });

      colorInput.addEventListener("mousedown", async () => { await loadStyles(); open(); render(styles); });
      colorInput.addEventListener("focus",     async () => { await loadStyles(); open(); render(styles); });
      colorInput.addEventListener("input",     ()       => {
        colorInput.dataset.productStyleId = "";
        const si = row.querySelector(".js-product-style-id-input");
        if (si) si.value = "";
        applyFilter();
        open();
      });

      document.addEventListener("click", (e) => { if (cell.contains(e.target)) return; close(); });

      if (styleInput) {
        styleInput.addEventListener("change", () => {
          colorInput.value = "";
          colorInput.dataset.productStyleId = "";
          const si = row.querySelector(".js-product-style-id-input");
          if (si) si.value = "";
          styles = [];
          render([]);
        });
      }
    });
  }

  // ── Freight dropdown ──────────────────────────────────────────────────────

  function initFreightDropdownForRoom(roomCard) {
    if (!roomCard._fmFreightItemsPromise) {
      roomCard._fmFreightItemsPromise = fetch(window.FM_CATALOG_FREIGHT_ITEMS_URL, { headers: { Accept: "application/json" } })
        .then(async (r) => {
          const data = await r.json();
          if (Array.isArray(data)) return data;
          if (Array.isArray(data?.freight_items)) return data.freight_items;
          if (Array.isArray(data?.items)) return data.items;
          return [];
        })
        .catch(() => []);
    }

    roomCard.querySelectorAll("[data-freight-desc-input]").forEach((input) => {
      if (input.dataset.freightBound === "1") return;
      input.dataset.freightBound = "1";

      const row  = input.closest("tr");
      const cell = input.closest("td") || input.parentElement;
      if (!row || !cell) return;

      const dropdown = cell.querySelector("[data-freight-desc-dropdown]");
      const list     = cell.querySelector("[data-freight-desc-options]");
      if (!dropdown || !list) return;

      const priceInput = row.querySelector("input[name*=\"[sell_price]\"]");
      let freightItems = [];
      let activeIndex  = -1;

      function openDropdown() {
        dropdown.classList.remove("hidden");
        positionFixed(input, dropdown, 320);
      }
      const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

      function getOpts() { return Array.from(list.querySelectorAll("button[data-freight-name]")); }

      function setActive(next) {
        const opts = getOpts();
        if (!opts.length) { activeIndex = -1; return; }
        if (next < 0) next = opts.length - 1;
        if (next >= opts.length) next = 0;
        activeIndex = next;
        opts.forEach((b, i) => {
          b.classList.toggle("bg-gray-100", i === activeIndex);
          b.setAttribute("aria-selected", i === activeIndex ? "true" : "false");
          if (i === activeIndex) b.scrollIntoView({ block: "nearest" });
        });
      }

      function render(items) {
        if (!(items || []).length) { list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`; activeIndex = -1; return; }
        list.innerHTML = (items || []).map(item => {
          const name  = item.name ?? item.freight_description ?? item.description ?? String(item);
          const price = item.sell_price != null ? String(item.sell_price) : "";
          return `<li><button type="button"
            class="w-full text-left px-3 py-2 rounded-md hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
            data-freight-name="${escapeHtml(name)}" data-freight-price="${escapeHtml(price)}">
            <span class="truncate">${escapeHtml(name)}</span></button></li>`;
        }).join("");
        activeIndex = -1;
      }

      function applyFilter() {
        const q = (input.value || "").trim().toLowerCase();
        render(freightItems.filter(item => {
          const name = (item.name ?? item.freight_description ?? item.description ?? String(item));
          return String(name).toLowerCase().includes(q);
        }));
      }

      function selectBtn(btn) {
        if (!btn) return;
        input.value = btn.dataset.freightName || "";
        const p = btn.dataset.freightPrice;
        if (priceInput && p !== "" && p != null) {
          const n = Number(p);
          if (!isNaN(n)) {
            priceInput.value = n.toFixed(4);
            priceInput.dispatchEvent(new Event("input", { bubbles: true }));
          }
        }
        close();
        input.dispatchEvent(new Event("change", { bubbles: true }));
      }

      list.addEventListener("mousedown", (e) => {
        const btn = e.target.closest("button[data-freight-name]");
        if (!btn) return;
        e.preventDefault();
        selectBtn(btn);
      });

      input.addEventListener("keydown", (e) => {
        const opts = getOpts();
        if (e.key === "ArrowDown") { e.preventDefault(); openDropdown(); setActive(activeIndex === -1 ? 0 : activeIndex + 1); return; }
        if (e.key === "ArrowUp")   { e.preventDefault(); openDropdown(); setActive(activeIndex === -1 ? opts.length - 1 : activeIndex - 1); return; }
        if (e.key === "Enter" && !dropdown.classList.contains("hidden") && activeIndex >= 0 && opts[activeIndex]) { e.preventDefault(); selectBtn(opts[activeIndex]); return; }
        if (e.key === "Escape" && !dropdown.classList.contains("hidden")) { e.preventDefault(); close(); }
      });

      input.addEventListener("pointerdown", (e) => {
        e.stopPropagation();
        openDropdown();
        list.innerHTML = `<li class="px-3 py-2 text-gray-500">Loading...</li>`;
        input.focus();
        Promise.resolve(roomCard._fmFreightItemsPromise).then((items) => {
          freightItems = Array.isArray(items) ? items : [];
          render(freightItems);
          openDropdown();
        });
      });

      input.addEventListener("focus", async () => {
        freightItems = await roomCard._fmFreightItemsPromise;
        render(freightItems);
        openDropdown();
      });

      document.addEventListener("click", (e) => {
        if (cell.contains(e.target) || dropdown.contains(e.target)) return;
        setTimeout(() => close(), 0);
      });
    });
  }

  // ── Labour Type dropdown ──────────────────────────────────────────────────

  async function fetchLabourTypes() {
    const res = await fetch(window.FM_CATALOG_LABOUR_TYPES_URL, { headers: { Accept: "application/json" }, credentials: "same-origin" });
    if (!res.ok) throw new Error(`[labour-types] HTTP ${res.status}`);
    return await res.json();
  }

  function initLabourTypeDropdownForRow(rowEl) {
    const input    = rowEl.querySelector("[data-labour-type-input]");
    const dropdown = rowEl.querySelector("[data-labour-type-dropdown]");
    const list     = rowEl.querySelector("[data-labour-type-options]");
    if (!input || !dropdown || !list) return;
    if (input.dataset.ltBound === "1") return;
    input.dataset.ltBound = "1";

    let labourTypes = [];
    let activeIndex = -1;

    const open  = () => { dropdown.classList.remove("hidden"); positionFixed(input, dropdown, 176); };
    const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

    function getOpts() { return Array.from(list.querySelectorAll("button[data-labour-name]")); }

    function setActive(next) {
      const opts = getOpts();
      if (!opts.length) { activeIndex = -1; return; }
      if (next < 0) next = opts.length - 1;
      if (next >= opts.length) next = 0;
      activeIndex = next;
      opts.forEach((b, i) => {
        b.classList.toggle("bg-gray-100", i === activeIndex);
        b.setAttribute("aria-selected", i === activeIndex ? "true" : "false");
        if (i === activeIndex) b.scrollIntoView({ block: "nearest" });
      });
    }

    function render(items) {
      if (!(items || []).length) { list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`; activeIndex = -1; return; }
      list.innerHTML = (items || []).map(t => `<li><button type="button"
        class="w-full text-left px-3 py-2 hover:bg-gray-100"
        data-labour-id="${t.id}" data-labour-name="${escapeHtml(t.name)}">
        ${escapeHtml(t.name)}</button></li>`).join("");
      activeIndex = -1;
    }

    async function ensureLoaded() {
      if (labourTypes.length) return;
      labourTypes = await fetchLabourTypes();
    }

    function selectBtn(btn) {
      if (!btn) return;
      input.value = btn.dataset.labourName || "";
      rowEl.dataset.labourTypeId = btn.dataset.labourId || "";
      close();
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }

    list.addEventListener("mousedown", (e) => {
      const btn = e.target.closest("button[data-labour-name]");
      if (!btn) return;
      e.preventDefault();
      selectBtn(btn);
    });

    input.addEventListener("keydown", (e) => {
      const opts = getOpts();
      if (e.key === "ArrowDown") { e.preventDefault(); open(); setActive(activeIndex === -1 ? 0 : activeIndex + 1); return; }
      if (e.key === "ArrowUp")   { e.preventDefault(); open(); setActive(activeIndex === -1 ? opts.length - 1 : activeIndex - 1); return; }
      if (e.key === "Enter" && !dropdown.classList.contains("hidden") && activeIndex >= 0 && opts[activeIndex]) { e.preventDefault(); selectBtn(opts[activeIndex]); return; }
      if (e.key === "Escape" && !dropdown.classList.contains("hidden")) { e.preventDefault(); close(); }
    });

    input.addEventListener("focus", async () => { await ensureLoaded(); render(labourTypes); open(); });
    input.addEventListener("click", async () => { await ensureLoaded(); render(labourTypes); open(); });
    input.addEventListener("input", async () => {
      await ensureLoaded();
      const q = (input.value || "").toLowerCase();
      render(labourTypes.filter(t => (t.name || "").toLowerCase().includes(q)));
      open();
    });

    document.addEventListener("click", (e) => { if (e.target === input || rowEl.contains(e.target) && dropdown.contains(e.target)) return; if (!rowEl.contains(e.target)) close(); });
  }

  // ── Labour Description dropdown ───────────────────────────────────────────

  function initLabourDescriptionDropdownForRow(rowEl) {
    const typeInput  = rowEl.querySelector("[data-labour-type-input]");
    const descInput  = rowEl.querySelector("[data-labour-desc-input]");
    const dropdown   = rowEl.querySelector("[data-labour-desc-dropdown]");
    const list       = rowEl.querySelector("[data-labour-desc-options]");
    const unitInput  = rowEl.querySelector("[data-labour-unit-input]");
    const priceInput = rowEl.querySelector("input[name*=\"[labour]\"][name$=\"[sell_price]\"]");

    if (!typeInput || !descInput || !dropdown || !list || !unitInput) return;
    if (rowEl.dataset.labourDescBound === "1") return;
    rowEl.dataset.labourDescBound = "1";

    let items       = [];
    let activeIndex = -1;

    const open  = () => { dropdown.classList.remove("hidden"); positionFixed(descInput, dropdown, 256); };
    const close = () => { dropdown.classList.add("hidden"); activeIndex = -1; };

    function getOpts() { return Array.from(list.querySelectorAll("button[data-labour-desc]")); }

    function setActive(next) {
      const btns = getOpts();
      if (!btns.length) { activeIndex = -1; return; }
      if (next < 0) next = btns.length - 1;
      if (next >= btns.length) next = 0;
      activeIndex = next;
      btns.forEach((b, i) => b.classList.toggle("bg-gray-100", i === activeIndex));
      btns[activeIndex]?.scrollIntoView({ block: "nearest" });
    }

    function render(filtered) {
      if (!(filtered || []).length) { list.innerHTML = `<li class="px-3 py-2 text-gray-500">No matches</li>`; return; }
      list.innerHTML = (filtered || []).map(it => `<li><button type="button"
        class="w-full text-left px-3 py-2 hover:bg-gray-100"
        data-labour-desc="${escapeHtml(it.description)}"
        data-labour-unit="${escapeHtml(it.unit_code)}"
        data-labour-sell="${escapeHtml(it.sell ?? "")}">${escapeHtml(it.description)}</button></li>`).join("");

      getOpts().forEach(btn => {
        btn.addEventListener("click", () => {
          descInput.value = btn.getAttribute("data-labour-desc") || "";
          unitInput.value = btn.getAttribute("data-labour-unit") || "";
          const sell = btn.getAttribute("data-labour-sell") || "";
          if (priceInput && sell !== "") {
            const n = Number(sell);
            if (!isNaN(n)) {
              priceInput.value = n.toFixed(4);
              priceInput.dispatchEvent(new Event("input", { bubbles: true }));
            }
          }
          close();
          descInput.dispatchEvent(new Event("input", { bubbles: true }));
          unitInput.dispatchEvent(new Event("input", { bubbles: true }));
        });
      });
    }

    async function loadItems() {
      const ltId = rowEl.dataset.labourTypeId;
      if (!ltId) { items = []; render([]); return; }
      const url = new URL("/pages/estimates/api/labour-items", window.location.origin);
      url.searchParams.set("labour_type_id", ltId);
      const res = await fetch(url.toString(), { headers: { Accept: "application/json" } });
      if (!res.ok) throw new Error(`Labour items HTTP ${res.status}`);
      items = await res.json();
      render(items);
    }

    function filterAndRender() {
      const q = (descInput.value || "").toLowerCase().trim();
      if (!q) { render(items); return; }
      render(items.filter(it => String(it.description || "").toLowerCase().includes(q)));
    }

    descInput.addEventListener("focus", async () => { try { await loadItems(); open(); } catch (e) { console.error("[labour desc]", e); } });
    descInput.addEventListener("click", async () => { try { await loadItems(); open(); } catch (e) { console.error("[labour desc]", e); } });
    descInput.addEventListener("input", () => { filterAndRender(); open(); });

    descInput.addEventListener("keydown", (e) => {
      const btns = getOpts();
      if (!btns.length) return;
      if (e.key === "ArrowDown") { e.preventDefault(); setActive(activeIndex + 1); open(); }
      if (e.key === "ArrowUp")   { e.preventDefault(); setActive(activeIndex - 1); open(); }
      if (e.key === "Enter" && activeIndex >= 0) { e.preventDefault(); btns[activeIndex].click(); }
      if (e.key === "Escape") { e.preventDefault(); close(); }
    });

    document.addEventListener("click", (e) => { if (!rowEl.contains(e.target)) close(); });
  }

  // ── Room add/delete/move ──────────────────────────────────────────────────

  function initRoom(roomCard) {
    initProductTypeDropdownForRoom(roomCard);
    initManufacturerDropdownForRoom(roomCard);
    initStyleDropdownForRoom(roomCard);
    initColorDropdownForRoom(roomCard);
    initFreightDropdownForRoom(roomCard);
    roomCard.querySelectorAll(".labour-tbody tr").forEach(row => {
      initLabourTypeDropdownForRow(row);
      initLabourDescriptionDropdownForRow(row);
    });
    updateRoomTotals(roomCard);
  }

  function addRoom() {
    const fragment   = roomTemplate.content.cloneNode(true);
    const allCards   = roomsContainer.querySelectorAll(".room-card");
    const newIndex   = allCards.length;

    fragment.querySelectorAll("[name]").forEach(el => {
      el.name = el.name.replaceAll("__ROOM_INDEX__", newIndex);
    });

    roomsContainer.appendChild(fragment);

    const newCard = Array.from(roomsContainer.querySelectorAll(".room-card")).pop();
    if (!newCard) return;

    initRoom(newCard);
    renumberRooms();
    reindexAllRooms();
  }

  addRoomBtn.addEventListener("click", addRoom);

  function deleteRoom(card) {
    if (!card || !confirm("Delete this room?")) return;

    const idInput = card.querySelector("input[name$=\"[id]\"]");
    const hasId   = idInput && idInput.value?.trim();

    if (hasId) {
      const flag = card.querySelector(".room-delete-flag");
      if (flag) flag.value = "1";
      card.querySelectorAll("input, select, textarea").forEach(el => el.disabled = true);
      card.style.display = "none";
    } else {
      card.remove();
    }

    reindexAllRooms();
  }

  // ── Event delegation ──────────────────────────────────────────────────────

  roomsContainer.addEventListener("click", e => {
    const t = e.target;

    if (t.closest(".add-material-row")) {
      const room = t.closest(".room-card");
      appendRowFromTemplate(room, ".materials-tbody", ".material-row-template");
      initProductTypeDropdownForRoom(room);
      initManufacturerDropdownForRoom(room);
      initStyleDropdownForRoom(room);
      initColorDropdownForRoom(room);
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
      const room  = t.closest(".room-card");
      appendRowFromTemplate(room, ".labour-tbody", ".labour-row-template");
      const tbody  = room.querySelector(".labour-tbody");
      const newRow = tbody ? tbody.querySelector("tr:last-child") : null;
      if (newRow) { initLabourTypeDropdownForRow(newRow); initLabourDescriptionDropdownForRow(newRow); }
      reindexAllRooms();
      updateRoomTotals(room);
      return;
    }

    if (t.closest(".delete-room")) {
      deleteRoom(t.closest(".room-card"));
      return;
    }

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

    if (t.closest(".move-up")) {
      const card   = t.closest(".room-card");
      const active = getActiveRoomCards();
      const idx    = active.indexOf(card);
      if (idx > 0) { roomsContainer.insertBefore(card, active[idx - 1]); reindexAllRooms(); }
      return;
    }

    if (t.closest(".move-down")) {
      const card   = t.closest(".room-card");
      const active = getActiveRoomCards();
      const idx    = active.indexOf(card);
      if (idx !== -1 && idx < active.length - 1) { roomsContainer.insertBefore(active[idx + 1], card); reindexAllRooms(); }
      return;
    }
  });

  // ── Initialize existing rooms ─────────────────────────────────────────────

  document.querySelectorAll(".room-card").forEach(initRoom);
  renumberRooms();
  reindexAllRooms();
  updateInvoiceTotals();

  console.log("[invoice_edit.js] Init complete");
});
