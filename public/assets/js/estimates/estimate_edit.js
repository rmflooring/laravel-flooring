// temp log
console.log("[estimate_edit.js] loaded");

// ── Unsaved changes warning (Edit Estimate) ───────────────────────────────
(function () {
  let fmHasUnsavedChanges = false;
  let fmIsInitializing = true;

  function markDirty() {
    if (fmIsInitializing) return;
    fmHasUnsavedChanges = true;
  }

  document.addEventListener("input", markDirty, true);
  document.addEventListener("change", markDirty, true);
  document.addEventListener("fm:dirty", markDirty);

  document.addEventListener("submit", () => { fmHasUnsavedChanges = false; }, true);

  window.addEventListener("beforeunload", (e) => {
    if (!fmHasUnsavedChanges) return;
    e.preventDefault();
    e.returnValue = "";
  });

  document.addEventListener("DOMContentLoaded", () => {
    requestAnimationFrame(() => requestAnimationFrame(() => {
      fmIsInitializing = false;
    }));
  });
})();

// Helper: allow other code to mark the form "dirty"
function fmMarkDirty() {
  document.dispatchEvent(new Event("fm:dirty"));
}

// Mark dirty when delete a ROOM
document.addEventListener("click", function (e) {
  if (e.target.closest(".delete-room")) {
    fmMarkDirty();
  }
}, true);

// Mark dirty when moving a ROOM up/down
document.addEventListener("click", function (e) {
  if (e.target.closest(".move-up") || e.target.closest(".move-down")) {
    fmMarkDirty();
  }
}, true);

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

  // Move up
  if (e.target.closest('.js-move-row-up')) {
    const row = e.target.closest('tr');
    const tbody = row.parentElement;
    const prev = row.previousElementSibling;

    if (prev) {
      tbody.insertBefore(row, prev);
      renumberLineItems(tbody);
      fmMarkDirty(); // mark unsaved changes
    }
    return;
  }

  // Move down
  if (e.target.closest('.js-move-row-down')) {
    const row = e.target.closest('tr');
    const tbody = row.parentElement;
    const next = row.nextElementSibling;

    if (next) {
      tbody.insertBefore(next, row);
      renumberLineItems(tbody);
      fmMarkDirty(); // mark unsaved changes
    }
    return;
  }

}); 

function cleanupCopyModalBackdrop() {
  // Flowbite typically locks scrolling on body
  document.body.classList.remove("overflow-hidden");

  // Remove ONLY common Flowbite modal backdrops (z-40 overlay)
  document.querySelectorAll("div.fixed.inset-0.z-40").forEach((el) => {
    // extra safety: only remove overlays that look like the backdrop
    const cls = el.className || "";
    const looksLikeBackdrop =
      cls.includes("bg-gray") || cls.includes("bg-black") || cls.includes("backdrop");

    if (looksLikeBackdrop) el.remove();
  });
}


// ✅ close the move up/down click handler

// estimate_edit.js

(function () {
  let sourceRow = null;
  let sourceSection = null;

  function getRoomCards() {
    return Array.from(document.querySelectorAll(".room-card"));
  }

  function buildRoomOptions() {
    const select = document.getElementById("copy-target-room");
    if (!select) return;

    select.innerHTML = "";
    getRoomCards().forEach((card, idx) => {
      const titleEl = card.querySelector(".room-title");
      const label = titleEl ? titleEl.textContent.trim() : `Room ${idx + 1}`;
      const opt = document.createElement("option");
      opt.value = String(idx);
      opt.textContent = label;
      select.appendChild(opt);
    });
  }

function getCopyModalInstance() {
  const modalEl = document.getElementById("copy-line-item-modal");
  if (!modalEl) return null;

  // Prefer Flowbite v2 global
  let ModalClass = null;

  if (window.Flowbite && typeof window.Flowbite.Modal === "function") {
    ModalClass = window.Flowbite.Modal;
  } else if (typeof window.Modal === "function") {
    ModalClass = window.Modal;
  }

  if (!ModalClass) return null;

  const existing = ModalClass.getInstance?.(modalEl);
  return existing || new ModalClass(modalEl);
}

function openCopyModal(defaultRoomIndex, defaultSection) {
  buildRoomOptions();

  const roomSelect = document.getElementById("copy-target-room");
  const sectionSelect = document.getElementById("copy-target-section");

  if (roomSelect) roomSelect.value = String(defaultRoomIndex);
  if (sectionSelect) sectionSelect.value = defaultSection;

  const inst = getCopyModalInstance();
  if (inst && typeof inst.show === "function") {
    inst.show();
    return;
  }

  // fallback (should not be needed if Flowbite is loaded)
  const modalEl = document.getElementById("copy-line-item-modal");
  if (!modalEl) return;
  modalEl.classList.remove("hidden");
  modalEl.setAttribute("aria-hidden", "false");
}

function closeCopyModal() {
  const modalEl = document.getElementById("copy-line-item-modal");
  if (!modalEl) return;

  // ✅ Fix: if focus is inside the modal, move it back to the trigger (or blur)
  const active = document.activeElement;
  if (active && modalEl.contains(active)) {
    if (window.__fmLastCopyTrigger && typeof window.__fmLastCopyTrigger.focus === "function") {
      window.__fmLastCopyTrigger.focus();
    } else if (typeof active.blur === "function") {
      active.blur();
    }
  }

  const inst = getCopyModalInstance();
  if (inst && typeof inst.hide === "function") {
    inst.hide();
	setTimeout(cleanupCopyModalBackdrop, 0);
    return;
  }

  // fallback
  modalEl.classList.add("hidden");
  modalEl.setAttribute("aria-hidden", "true");
}



  // Copy inputs/selects/textarea values from one row to another
  function copyRowValues(fromRow, toRow) {
    const fromFields = Array.from(fromRow.querySelectorAll("input, select, textarea"));
    const toFields = Array.from(toRow.querySelectorAll("input, select, textarea"));

    // Map by "name" attribute after stripping the item index
    const normalize = (name) => (name || "").replace(/\[\d+\]/g, "[__]");
    const fromMap = new Map();

    fromFields.forEach((el) => {
      if (!el.name) return;
      fromMap.set(normalize(el.name), el);
    });

    toFields.forEach((el) => {
      if (!el.name) return;

      // Never copy DB ids
      if (/\[id\]$/.test(el.name)) return;

      const match = fromMap.get(normalize(el.name));
      if (!match) return;

      // Copy value
      el.value = match.value;

      // Trigger recalcs/autofill listeners
      el.dispatchEvent(new Event("input", { bubbles: true }));
      el.dispatchEvent(new Event("change", { bubbles: true }));
    });
  }



  function clickAddButton(roomCard, section) {
    if (!roomCard) return null;

    const map = {
      materials: ".add-material-row",
      freight: ".add-freight-row",
      labour: ".add-labour-row",
    };

    const btn = roomCard.querySelector(map[section]);
    if (!btn) return null;

    btn.click();

    // New row should be the last row in that tbody
    const tbody = roomCard.querySelector(`.${section}-tbody`);
    if (!tbody) return null;

    const newRow = tbody.querySelector("tr:last-child");
    return { tbody, newRow };
  }

  // Event delegation: Copy button click
  document.addEventListener("click", function (e) {
    const copyBtn = e.target.closest(".js-copy-line-item");
    if (!copyBtn) return;
	  
window.__fmLastCopyTrigger = copyBtn;


    sourceRow = copyBtn.closest("tr");
    sourceSection = copyBtn.dataset.section;

    if (!sourceRow || !sourceSection) return;

    const roomCard = copyBtn.closest(".room-card");
    const roomIndex = roomCard ? Number(roomCard.dataset.roomIndex || 0) : 0;

    openCopyModal(roomIndex, sourceSection);
  });

  // Modal confirm
  document.addEventListener("click", function (e) {
    if (e.target && e.target.id === "confirm-copy-line-item") {
      const roomIndex = Number(document.getElementById("copy-target-room")?.value || 0);
      const section = document.getElementById("copy-target-section")?.value || sourceSection;

      const roomCard = getRoomCards()[roomIndex];
      if (!roomCard || !sourceRow) {
        closeCopyModal();
        return;
      }

      const result = clickAddButton(roomCard, section);
      if (!result || !result.newRow) {
        closeCopyModal();
        return;
      }

      copyRowValues(sourceRow, result.newRow);
renumberLineItems(result.tbody);
if (typeof fmMarkDirty === "function") fmMarkDirty();

if (window.FM_RECALC_ESTIMATE_FROM_ROW) {
  window.FM_RECALC_ESTIMATE_FROM_ROW(result.newRow);
}

fmMarkDirty();
closeCopyModal();
    }
  });

  
})();
							   
(function () {
  function toNumber(val) {
    const n = parseFloat(val);
    return Number.isFinite(n) ? n : 0;
  }

  function money(n) {
    try {
      return new Intl.NumberFormat("en-CA", { style: "currency", currency: "CAD" }).format(n);
    } catch (e) {
      return "$" + n.toFixed(2);
    }
  }

  function setText(el, text) {
    if (el) el.textContent = text;
  }

  function updateRowTotal(row, type) {
    const qtyInput = row.querySelector('input[name$="[quantity]"]');
    const sellInput = row.querySelector('input[name$="[sell_price]"]');

    // Some labour rows might use unit_price in other versions, but your Blade uses sell_price.
    const qty = qtyInput ? toNumber(qtyInput.value) : 0;
    const sell = sellInput ? toNumber(sellInput.value) : 0;
    const total = qty * sell;

    if (type === "materials") {
      setText(row.querySelector(".material-line-total"), money(total));
      const hidden = row.querySelector(".material-line-total-input");
      if (hidden) hidden.value = total.toFixed(2);
    }

    if (type === "freight") {
      setText(row.querySelector(".freight-line-total"), money(total));
      const hidden = row.querySelector(".freight-line-total-input");
      if (hidden) hidden.value = total.toFixed(2);
    }

    if (type === "labour") {
      setText(row.querySelector(".labour-line-total"), money(total));
      const hidden = row.querySelector(".labour-line-total-input");
      if (hidden) hidden.value = total.toFixed(2);
    }
  }

  function sumHiddenTotals(roomCard, selector) {
    let sum = 0;
    roomCard.querySelectorAll(selector).forEach((el) => {
      sum += toNumber(el.value);
    });
    return sum;
  }

  function updateRoomTotals(roomCard) {
    const mat = sumHiddenTotals(roomCard, ".material-line-total-input");
    const frt = sumHiddenTotals(roomCard, ".freight-line-total-input");
    const lab = sumHiddenTotals(roomCard, ".labour-line-total-input");
    const roomTotal = mat + frt + lab;

    // Visible room summary values
    setText(roomCard.querySelector(".room-material-value"), money(mat));
    setText(roomCard.querySelector(".room-freight-value"), money(frt));
    setText(roomCard.querySelector(".room-labour-value"), money(lab));
    setText(roomCard.querySelector(".room-total-value"), money(roomTotal));

    // Hidden room totals (used on save)
    const matHidden = roomCard.querySelector(".room-subtotal-materials-input");
    const frtHidden = roomCard.querySelector(".room-subtotal-freight-input");
    const labHidden = roomCard.querySelector(".room-subtotal-labour-input");
    const totalHidden = roomCard.querySelector(".room-total-input");

    if (matHidden) matHidden.value = mat.toFixed(2);
    if (frtHidden) frtHidden.value = frt.toFixed(2);
    if (labHidden) labHidden.value = lab.toFixed(2);
    if (totalHidden) totalHidden.value = roomTotal.toFixed(2);
  }

  function updateEstimateTotals() {
    let mat = 0, frt = 0, lab = 0;

    document.querySelectorAll(".room-card").forEach((roomCard) => {
      mat += toNumber(roomCard.querySelector(".room-subtotal-materials-input")?.value);
      frt += toNumber(roomCard.querySelector(".room-subtotal-freight-input")?.value);
      lab += toNumber(roomCard.querySelector(".room-subtotal-labour-input")?.value);
    });

    const pretax = mat + frt + lab;

    // Visible estimate summary
    setText(document.querySelector(".estimate-materials-value"), money(mat));
    setText(document.querySelector(".estimate-freight-value"), money(frt));
    setText(document.querySelector(".estimate-labour-value"), money(lab));
    setText(document.querySelector(".estimate-pretax-value"), money(pretax));

    // Hidden inputs for save
    const matIn = document.getElementById("subtotal_materials_input");
    const frtIn = document.getElementById("subtotal_freight_input");
    const labIn = document.getElementById("subtotal_labour_input");
    const preIn = document.getElementById("pretax_total_input");

    if (matIn) matIn.value = mat.toFixed(2);
    if (frtIn) frtIn.value = frt.toFixed(2);
    if (labIn) labIn.value = lab.toFixed(2);
    if (preIn) preIn.value = pretax.toFixed(2);

    // If you have tax logic elsewhere, let it run.
    // If not, we keep grand total = pretax for now.
    const taxAmount = toNumber(document.getElementById("tax_amount_input")?.value);
    const grand = pretax + taxAmount;

    setText(document.querySelector(".estimate-grand-total-value"), money(grand));
    const grandIn = document.getElementById("grand_total_input");
    if (grandIn) grandIn.value = grand.toFixed(2);
  }

  function recalcFromRow(row) {
    const roomCard = row.closest(".room-card");
    if (!roomCard) return;

    // Determine which section this row belongs to
    if (row.closest(".materials-tbody")) updateRowTotal(row, "materials");
    if (row.closest(".freight-tbody")) updateRowTotal(row, "freight");
    if (row.closest(".labour-tbody")) updateRowTotal(row, "labour");

    updateRoomTotals(roomCard);
    updateEstimateTotals();
  }

  // Event delegation: any typing in qty/sell_price triggers recalculation
  document.addEventListener("input", function (e) {
    const el = e.target;
    if (!el) return;

    // Only respond to qty/sell edits
    const name = el.getAttribute("name") || "";
    const isQty = name.endsWith("[quantity]");
    const isSell = name.endsWith("[sell_price]");
    if (!isQty && !isSell) return;

    const row = el.closest("tr");
    if (!row) return;

    recalcFromRow(row);
  });

  // On initial page load, compute everything once
  document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".room-card tr").forEach((row) => recalcFromRow(row));
  });

  // Expose a helper so your copy code (or add-row code) can force recalculation
  window.FM_RECALC_ESTIMATE_FROM_ROW = recalcFromRow;
					  
  /* =========================================
     Tax Accordion (Estimate Summary)
  ========================================= */

  document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("js-tax-toggle");
    const panel = document.getElementById("js-tax-breakdown");
    const caret = document.getElementById("js-tax-caret");
    const rowsWrap = document.getElementById("js-tax-breakdown-rows");

    if (!toggleBtn || !panel || !rowsWrap) return;

    function money(n) {
      const num = Number(n) || 0;
      return "$" + num.toFixed(2);
    }

function parseMoney(text) {
  const n = Number(String(text || "").replace(/[^0-9.-]/g, ""));
  return Number.isFinite(n) ? n : 0;
}

function getPretaxTotal() {
  // Prefer hidden input (authoritative)
  const input = document.getElementById("pretax_total_input");
  if (input && input.value !== "") return Number(input.value) || 0;

  // Fallback to visible label
  const el = document.querySelector(".estimate-pretax-value");
  return el ? parseMoney(el.textContent) : 0;
}

    function getTaxRatePercent() {
      const el = document.getElementById("tax_rate_percent_input");
      return Number(el ? el.value : 0) || 0;
    }

    function getTaxAmount() {
      const el = document.getElementById("tax_amount_input");
      return Number(el ? el.value : 0) || 0;
    }

async function renderRows() {
  rowsWrap.innerHTML = "";

  const pretax = getPretaxTotal();

  const groupInput = document.getElementById("tax_group_id_input");
  const groupId = groupInput ? String(groupInput.value || "").trim() : "";

  if (!groupId) {
    rowsWrap.innerHTML = `<div class="text-sm text-gray-500">No tax group selected.</div>`;
    return;
  }

  const tpl = window.FM_TAX_GROUP_RATE_URL_TEMPLATE;
  if (!tpl) {
    rowsWrap.innerHTML = `<div class="text-sm text-red-500">Tax URL template missing.</div>`;
    return;
  }

  const url = tpl.replace("__GROUP__", groupId);

  try {
    const res = await fetch(url, { headers: { Accept: "application/json" } });
    if (!res.ok) throw new Error("Failed to fetch tax breakdown");

    const data = await res.json();

    const taxes =
      (Array.isArray(data?.taxes) && data.taxes) ||
      (Array.isArray(data?.tax_rates) && data.tax_rates) ||
      (Array.isArray(data?.items) && data.items) ||
      [];

    if (!taxes.length) {
      rowsWrap.innerHTML = `<div class="text-sm text-gray-500">No tax rates found for this group.</div>`;
      return;
    }

    let any = false;

    taxes.forEach((t) => {
      const name = String(t?.tax_name ?? t?.name ?? "Tax").trim();

      const pct = Number(
        t?.tax_rate_sales ??
        t?.sales_rate ??
        t?.rate_percent ??
        t?.rate ??
        0
      ) || 0;

      if (pct <= 0) return;

      const amount = pretax * (pct / 100);

      any = true;
      rowsWrap.insertAdjacentHTML(
        "beforeend",
        `<div class="flex items-center justify-between">
          <span>${name} (${pct.toFixed(3)}%)</span>
          <span class="font-semibold">${money(amount)}</span>
        </div>`
      );
    });

    if (!any) {
      rowsWrap.innerHTML = `<div class="text-sm text-gray-500">Tax rates returned, but all were 0%.</div>`;
    }
  } catch (err) {
    rowsWrap.innerHTML = `<div class="text-sm text-red-500">Error loading tax breakdown.</div>`;
  }
}


    function setOpen(isOpen) {
      if (isOpen) {
        panel.classList.remove("hidden");
        toggleBtn.setAttribute("aria-expanded", "true");
        if (caret) caret.style.transform = "rotate(180deg)";
        renderRows();
      } else {
        panel.classList.add("hidden");
        toggleBtn.setAttribute("aria-expanded", "false");
        if (caret) caret.style.transform = "";
      }
    }

    toggleBtn.addEventListener("click", function () {
      const isOpen = !panel.classList.contains("hidden");
      setOpen(!isOpen);
    });
  });

})();
							   
