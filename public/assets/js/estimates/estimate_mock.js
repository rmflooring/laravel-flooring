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

    if (newRoomCard) {
      ensureDefaultRows(newRoomCard);
      renumberRooms();
      reindexAllRooms();
      updateRoomTotals(newRoomCard);
      console.log("[addRoom] Success - Room #" + (newIndex + 1) + " added");
    } else {
      console.error("[addRoom] Failed to find new room card");
    }
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

    // Visible total (this exists in your Blade)
    const totalEl = roomCard.querySelector(".room-total-value");
    if (totalEl) totalEl.textContent = formatMoney(roomTotal);

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

  console.log("[estimate_mock.js] Initialization complete");
});
