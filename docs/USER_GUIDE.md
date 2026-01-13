# User Guide

For managers and waiters using Smart Inventory + Forecast.

## Table of Contents
1. Roles & Permissions
2. Getting Started (login, active location)
3. Inventory (Items, Units/Conversions)
4. Warehouses & Ledger (Receipts/Waste/Internal Use/Adjustments)
5. Stock Count / Inventory
6. Recipes / Normatives
7. Menu Usage (sales proxy)
8. Expected Consumption & Variance Report
9. Procurement Suggestions
10. Purchase Orders & Receiving
11. Forecasts
12. Anomalies (Alerts)
13. Import / Export CSV
14. Reports
15. Audit Log / Period Lock

---

## 1) Roles & Permissions
- **Admin**: manage locations/users/roles; approve POs; resolve anomalies; set period lock.
- **Manager**: access location data, post stock counts, approve POs, resolve anomalies, run imports/exports.
- **Waiter/Viewer**: limited to operational screens (ledger, usage, viewing data).
Tip: Active location controls what data you see and can change.

## 2) Getting Started
1. **Login** with provided credentials.
2. **Choose Active Location** from the nav dropdown (top bar). This scopes all lists/forms.
3. Navigate using top menu links: Dashboard, Forecasts, Alerts, Import/Export, Audit, etc.

## 3) Inventory (Items, Units/Conversions)
- **Purpose**: Maintain item catalog.
- **Who**: Manager/Admin.
- **Navigation**: Top nav “Inventory” → Items.
- **Steps**:
  1. Click “Add item”.
  2. Fill SKU, name, category, base unit, pack/min/safety stock, lead time, status.
  3. Save.
- Units/conversions are seeded; conversions editable via imports (CSV) if needed.
Common mistakes: Wrong base unit—review unit before saving.

## 4) Warehouses & Ledger
- **Purpose**: Track stock movements per warehouse/location.
- **Navigation**: Top nav “Stock” opens the Ledger. Header buttons: “New receipt”, “Waste”, “Internal use”, “Adjustment”, and “Stock count” (opens the stock count list at `/stock-counts`). You can also open the forms directly via Stock → Receipt/Waste/Internal Use/Adjustment.
- **How to post**:
  1. From Ledger, click the needed action button (e.g., New receipt or Adjustment).
  2. Fill warehouse, date/time, item, unit, quantity. Receipts also need supplier/invoice; Waste/Internal use need a reason. Adjustment is for manual corrections (enter a positive qty to increase stock, negative to reduce).
  3. Submit to post to the ledger and return to the ledger list.
- Tip: Period lock blocks back-dated entries for non-admins; adjust the lock date (Admin/Manager) if you need to post older movements.

## 5) Stock Count / Inventory
- **Purpose**: Align system stock to physical count and auto-generate adjustments.
- **Navigation**: From Stock → Ledger header click “Stock count” (links to `/stock-counts`). The list shows drafts and posted counts. Click “New stock count” to create or “Edit” on a draft to continue.
- **Detailed steps**:
  1. Click “New stock count”.
  2. Select Warehouse and Count date/time.
  3. Add lines: pick Item, enter counted quantity in the base unit shown (e.g., g, l, pcs). Add more lines as needed.
  4. Click **Save draft**. You will be redirected to the list with a banner. The draft appears at the top; use “Continue editing” or “Edit” to reopen it.
  5. Review/edit draft if needed.
  6. Post (Manager/Admin): inside the draft screen, click **Post**. The system compares counted vs current balance and creates a “Stock count” entry in the Stock ledger for the difference.
- **Notes**: Active location must match the warehouse. Use base units only. Ledger filters must include the count date/time to see the generated adjustment.

## 6) Recipes / Normatives
- **Purpose**: Define ingredient norms for menu items with versioning.
- **Navigation**: Top nav “Menu” → Menu items → click “Recipe” on a menu item.
- **Detailed steps**:
  1. Open Menu items → click **Recipe** on the chosen menu item.
  2. Set **Valid from** for the new version (each version is date-scoped).
  3. Add ingredients: select Item, edit the quantity, and see the **Current unit** (read-only, from the item’s base unit). The dropdown is only for changing the unit if needed; the system stores quantity in base units automatically.
  4. Publish (Save). The page lists “Existing versions” with dates and the ingredient breakdown so you can confirm what already exists.
- **Tips / constraints**:
  - Avoid overlapping validity ranges; if a version already covers that date, pick a different start/end.
  - Ensure needed units/conversions exist for your ingredients.
  - Use new versions to evolve recipes over time without losing history.

## 7) Menu Usage (sales proxy)
- **Purpose**: Record menu items used/sold per day.
- **Navigation**: Direct link `/menu-usage` (or Top nav “Menu” → Menu Usage if present). Takođe, na stranicama Reports → Expected Consumption i Reports → Variance postoji link “Enter usage” koji vodi na istu formu.
- **Steps**:
  1. Pick the date (used_on).
  2. Choose the Menu item and enter Quantity (sold/used count).
  3. Submit. The record feeds expected consumption calculations and downstream variance/procurement/forecast logic for the active location.
- Notes: Ensure the active location is set correctly. Recipes must exist for the menu items to translate usage into expected ingredient consumption.

## 8) Reports (Variance)
- **Purpose**: Compare expected vs actual usage at the item level.
- **Navigation**: Top nav “Reports” (`/reports`).
- **Steps**:
  1. Pick date range (warehouse optional).
  2. Review expected vs actual, variance %, net change. Actual comes from ledger movements that subtract stock (waste/internal use/adjustments); expected from menu usage × recipe ingredients.
- **Troubleshooting**: verify recipes exist for the dates, menu usage entries are present, and ledger movements exist for actuals; ensure active location is correct; if filtering by warehouse, confirm items have movements there.

## 9) Procurement Suggestions
- **Purpose**: Suggest reorder quantities.
- **Navigation**: Procurement → Suggestions.
- **Steps**:
  1. Select warehouse.
  2. Review suggested quantities per item (computed from current stock, expected/actual usage, lead time, safety stock, min stock). You can adjust quantities before creating a PO.
  3. Enter Supplier and create PO draft.
- **Notes**: Suggestions factor in open purchase orders and stock balances. Use PO draft for further approval/receiving in Purchase Orders.

## 10) Purchase Orders & Receiving
- **Purpose**: Manage POs and receipts into stock.
- **Navigation**: Procurement → Purchase Orders.
- **PO lifecycle**:
  - Draft: created from Suggestions or manually; editable.
  - Approve/Send: Manager/Admin via “Approve”.
  - Status moves through DRAFT/SUBMITTED/APPROVED/SENT/PARTIALLY_RECEIVED/CLOSED.
- **Receiving**:
  1. Open PO detail → “Receive goods”.
  2. Per line, received qty is prefilled with remaining; adjust if partial.
  3. Post receipt to update stock and PO status (partial or closed). Receipts create stock transactions (type RECEIPT).
- **Notes**: Only Manager/Admin can approve/send/receive. Partial receipts leave PO in PARTIALLY_RECEIVED; fully received closes it.

## 11) Forecasts
- **Purpose**: Generate baseline demand forecasts (org/location, items).
- **Navigation**: Forecasts.
- **Steps**:
  1. Select location and horizon (14d default). Optional: filter items.
  2. Click “Generate forecast” (queued call to forecast-service).
  3. Review latest predictions table per day/item (with lower/upper if available).
- **Notes**: `FORECAST_SERVICE_URL` points to FastAPI service; scheduled jobs train weekly and predict daily. Ensure forecast-service is running before generating. Active location scopes results.

## 12) Anomalies (Alerts)
- **Purpose**: Highlight risky behavior (waste spikes, large variance, too many adjustments) so managers can react.
- **Navigation**: Alerts.
- **Steps**:
  1. Use filters (status/severity/type) to narrow the list.
  2. Click an alert to see details and context; add a comment if you’re investigating.
  3. Managers/Admins can change status (Open → Investigating → Resolved or mark as False Positive).
- **Tip**: Tune detection thresholds via Alerts → Thresholds (e.g., % variance limit, waste spike threshold, adjustment count per week).

## 13) Import / Export CSV
- **Navigation**: Data → Import/Export.
- **Imports**: items, unit conversions, recipes, opening stock. Supports dry-run (validate only) and queues large jobs.
- **Exports**: stock ledger, current balances, procurement suggestions, variance report.
- **Tips**:
  - Use Dry-run first to catch validation errors without saving.
  - Imports run on the queue; watch the import job list for status/results.
  - Uploaded CSVs are stored under `storage/`.

## 14) Reports
- **Navigation**: Reports (single page showing expected vs actual variance).
- **Usage**: Pick a date range, click Filter, and review per-item expected/actual/variance/net change. Use “Enter usage” link to add menu usage if data is missing.

## 15) Audit Log / Period Lock
- **Audit Log**: Navigation → Audit; browse who changed what (items, recipes, PO approvals, stock counts, adjustments).
- **Period Lock**: Navigation → Period lock (Manager/Admin); set `lock_before_date` to block back-dated transactions for non-admins. Admins can adjust the lock when needed.

---

### Troubleshooting
- Cannot post due to lock: check Period lock date; admins can override by adjusting lock or posting with current date.
- Missing data in reports: ensure active location is correct, recipes/usage exist, and ledger entries are posted.
- Imports stuck: verify queue worker running and check recent import jobs list for errors.
