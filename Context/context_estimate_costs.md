
# Floor Manager Context — Estimate Cost & Profit System

## Overview

The Estimate Builder in Floor Manager now supports **full sell + cost tracking** for
materials, freight, and labour. The system calculates both revenue and cost totals
per line item, allowing accurate profit calculations at the room and estimate level.

This document captures the current working architecture so development can resume
later without losing context.

---

# Frontend Estimate Builder

File:
public/assets/js/estimates/estimate.js

The estimate builder dynamically calculates totals when users change quantity or price.

## Sell Calculations

line_total = quantity × sell_price

## Cost Calculations

cost_total = quantity × cost_price

Both totals update automatically when:
• quantity changes
• sell price changes
• item selection changes

Hidden form fields store the values so they submit with the estimate form.

### Hidden Inputs Submitted

rooms[x][materials][y][line_total]
rooms[x][freight][y][line_total]
rooms[x][labour][y][line_total]

rooms[x][materials][y][cost_price]
rooms[x][materials][y][cost_total]

rooms[x][freight][y][cost_price]
rooms[x][freight][y][cost_total]

rooms[x][labour][y][cost_price]
rooms[x][labour][y][cost_total]

---

# Backend

Controller:
app/Http/Controllers/Admin/EstimateController.php

Validation now includes:

rooms.*.materials.*.line_total
rooms.*.freight.*.line_total
rooms.*.labour.*.line_total

This ensures the calculated totals from the frontend are preserved during validation.

---

# Database

Table:
estimate_items

Columns used by the estimate builder:

id
estimate_id
estimate_room_id
item_type

quantity
unit

sell_price
line_total

cost_price
cost_total

notes
sort_order

product_type
manufacturer
style
color_item_number
po_notes

labour_type
description

freight_description

---

# Example Stored Rows

Material

qty: 5
sell_price: 2.25
line_total: 11.25

cost_price: 1.25
cost_total: 6.25

Labour

qty: 10
sell_price: 3.50
line_total: 35.00

cost_price: 1.50
cost_total: 15.00

Freight

qty: 1
sell_price: 150.00
line_total: 150.00

cost_price: 50.00
cost_total: 50.00

---

# Model Logic

File:
app/Models/EstimateItem.php

The model automatically calculates cost totals before saving:

cost_total = quantity × cost_price

Recommended improvement:

Also calculate line_total server-side for safety.

Example:

$sell = (float) ($item->sell_price ?? 0);
$item->line_total = round($qty * $sell, 2);

This prevents incorrect totals if the browser JS fails.

---

# Profit Calculations Enabled

Because both sell and cost totals are stored, the system can now compute:

Line Profit

profit = line_total − cost_total

Room Profit

room_profit = SUM(line_total) − SUM(cost_total)

Estimate Profit

estimate_profit = SUM(line_total) − SUM(cost_total)

Margin

margin = profit ÷ revenue

---

# Future Enhancements

Possible next features:

Live Profit Display in Estimate Summary

Example:

Subtotal: $8,450
Cost: $5,920
Profit: $2,530
Margin: 29.9%

Other potential systems:

• Estimator performance tracking
• Job profitability reporting
• Purchase order cost comparison
• Forecast margin analysis

---

# Key Files

Estimate Builder JS

public/assets/js/estimates/estimate.js

Estimate Controller

app/Http/Controllers/Admin/EstimateController.php

Estimate Item Model

app/Models/EstimateItem.php

Database Table

estimate_items

---

End of Context File
