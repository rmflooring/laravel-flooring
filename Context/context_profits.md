# context_profits.md

## Project Overview

This project is a Laravel-based estimating and sales system with a
JS-driven estimate builder UI.\
Users build **Estimates** composed of **Rooms**, and each room can
contain:

-   Materials
-   Freight
-   Labour

Each row has: - quantity - sell_price - line_total

Totals roll up to: - Room totals - Estimate subtotal - Tax - Grand total

JavaScript logic for the estimate builder lives in:

`public/assets/js/estimates/estimate.js`

------------------------------------------------------------------------

# Catalog Sources

### Products

Table: `product_styles`

Important fields: - `id` - `product_line_id` - `name` - `cost_price` -
`sell_price`

Sell price currently autofills via:
`/api/product-pricing?product_style_id=&product_line_id=`

------------------------------------------------------------------------

### Labour

Table: `labour_items`

Fields: - `description` - `cost` - `sell` - `unit_measure_id` -
`labour_type_id`

Sell price autofills when a labour description is selected.

------------------------------------------------------------------------

### Freight

Table: `freight_items`

Fields: - `description` - `cost_price` - `sell_price`

Sell price autofills when a freight item is selected.

------------------------------------------------------------------------

# Current Estimate System Behavior

The estimate UI currently:

✔ Autofills **sell_price**\
✔ Calculates **line_total**\
✔ Updates **room totals**\
✔ Updates **estimate totals**

However:

❌ **cost_price is not currently written into the estimate rows** ❌
**cost_total is not calculated** ❌ **estimate_items table may not yet
receive cost data**

This means profit cannot currently be calculated reliably.

------------------------------------------------------------------------

# Goal of Current Work

Implement **cost tracking and profit analysis** across:

1️⃣ Estimates\
2️⃣ Sales (converted from estimates)

Cost fields must flow:

Catalog → Estimate → Sale

------------------------------------------------------------------------

# Required Data Flow

### Materials

product_styles.cost_price → estimate material row cost_price

### Labour

labour_items.cost → estimate labour row cost_price

### Freight

freight_items.cost_price → estimate freight row cost_price

Then calculate:

cost_total = quantity \* cost_price

------------------------------------------------------------------------

# Future Profit Modal

A modal component has been created:

`resources/views/components/modals/profits-modal.blade.php`

Features:

-   Shows all line items
-   Allows editing cost fields
-   Calculates:
    -   Material costs
    -   Freight costs
    -   Labour costs
    -   Total profit
-   Supports **Lock Profits** snapshot

Modal can open from:

Estimate page action bar\
Sale page action bar

------------------------------------------------------------------------

# Key Frontend File

`public/assets/js/estimates/estimate.js`

Important areas:

-   `autofillSellPriceForRow()`
-   Freight dropdown autofill
-   Labour description dropdown autofill
-   Line total calculation
-   Room totals
-   Estimate totals

Cost logic will need to be added in the same places where sell prices
are set.

------------------------------------------------------------------------

# Next Development Steps

1.  Add hidden inputs to row templates:

```{=html}
<!-- -->
```
    [cost_price]
    [cost_total]

for:

-   materials
-   freight
-   labour

2.  Modify JS autofill functions to also populate cost_price.

3.  Update row calculation:

```{=html}
<!-- -->
```
    cost_total = quantity * cost_price

4.  Ensure backend saves these fields in:

-   estimate_items
-   sale_items

5.  Ensure Estimate → Sale conversion copies cost values.

6.  Connect profit modal calculations to these stored values.

------------------------------------------------------------------------

# Notes for Future Chat

If continuing development later:

Start with:

**"We were implementing cost tracking and profit calculations in the
estimate system."**

Important file:

`estimate.js`

Next step was:

**Adding cost fields to estimate row templates and wiring them into JS
autofill.**
