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
- **Navigation**: Inventory → Items.
- **Steps**:
  1. Click “Add item”.
  2. Fill SKU, name, category, base unit, pack/min/safety stock, lead time, status.
  3. Save.
- Units/conversions are seeded; conversions editable via imports (CSV) if needed.
Common mistakes: Wrong base unit—review unit before saving.

## 4) Warehouses & Ledger
- **Purpose**: Track stock movements per warehouse/location.
- **Navigation**: Stock → Ledger; Stock → Receipt/Waste/Internal Use.
- **Receipts/Waste/Internal Use/Adjustments**:
  1. Choose warehouse, item, unit, quantity.
  2. Receipts require supplier name; Waste/Internal use require reason.
  3. Submit to post to ledger.
- Adjustments: use type “Adjustment”.
Tip: Period lock blocks dates before lock for non-admins.

## 5) Stock Count / Inventory
- **Purpose**: Align system stock to physical count.
- **Navigation**: Stock Count (create/edit/post).
- **Steps**:
  1. Create a count (warehouse, date, lines with counted qty).
  2. Edit if needed while in draft.
  3. Post (Manager/Admin) to create stock count adjustment in ledger.
Common mistakes: Posting with wrong location; ensure active location matches warehouse.

## 6) Recipes / Normatives
- **Purpose**: Define ingredient norms for menu items with versioning.
- **Navigation**: Recipes → select Menu Item.
- **Steps**:
  1. Pick menu item, set valid_from.
  2. Add ingredients (item, unit, qty).
  3. Save to create a new version.
Tip: Avoid overlapping versions by date.

## 7) Menu Usage (sales proxy)
- **Purpose**: Record menu items used/sold per day.
- **Navigation**: Menu Usage.
- **Steps**:
  1. Select date, menu item, quantity.
  2. Submit to feed expected consumption.

## 8) Expected Consumption & Variance Report
- **Purpose**: Compare expected vs actual usage.
- **Navigation**: Reports → Expected Consumption; Reports → Variance.
- **Variance steps**:
  1. Pick date range (and warehouse optional).
  2. View expected, actual, variance %, net change.
Troubleshooting: Ensure recipes and usage exist to populate expected.

## 9) Procurement Suggestions
- **Purpose**: Suggest reorder quantities.
- **Navigation**: Procurement → Suggestions.
- **Steps**:
  1. Select warehouse.
  2. Review suggested quantities; edit if needed.
  3. Create PO draft (supplier required).
Tip: Suggestions use stock, expected/actual, and lead time/safety stock.

## 10) Purchase Orders & Receiving
- **Purpose**: Manage POs and receipts into stock.
- **Navigation**: Procurement → Purchase Orders.
- **PO Approval**: Manager/Admin via “Approve”.
- **Receiving**:
  1. Open PO detail → Receive goods.
  2. Enter received qty per line (prefilled remaining).
  3. Post receipt to update stock and PO status (partial/closed).

## 11) Forecasts
- **Purpose**: Generate baseline demand forecasts (org/location, items).
- **Navigation**: Forecasts.
- **Steps**:
  1. Select location/horizon (optional item filter).
  2. Click Generate forecast (queued).
  3. Latest predictions list per day/item.
Notes: Forecast-service URL set via `FORECAST_SERVICE_URL`. Scheduled train/predict jobs run weekly/daily.

## 12) Anomalies (Alerts)
- **Purpose**: Flag waste spikes, variance spikes, adjustment counts.
- **Navigation**: Alerts.
- **Steps**:
  1. Filter by status/severity/type.
  2. Open alert, add comments.
  3. Managers/Admins can update status (Open/Investigating/Resolved/False Positive).
Tip: Thresholds configurable via Alerts → Thresholds.

## 13) Import / Export CSV
- **Navigation**: Data → Import/Export.
- **Import types**: items, unit conversions, recipes, opening stock. Supports dry-run (validate only) and queues jobs.
- **Export types**: stock ledger, balances, procurement suggestions, variance.
Note: Uploaded files stored under storage; monitor import job list for status/results.

## 14) Reports
- **Pages**: Expected Consumption, Variance (under Reports menu).
- **Usage**: choose dates/filters and review tables.

## 15) Audit Log / Period Lock
- **Audit Log**: Navigation → Audit; view actions (items, recipes, PO approvals, stock counts, adjustments).
- **Period Lock**: Navigation → Period lock (Manager/Admin); set `lock_before_date` to block back-dated transactions for non-admins.

---

### Troubleshooting
- Cannot post due to lock: check Period lock date; admins can override by adjusting lock or posting with current date.
- Missing data in reports: ensure active location is correct, recipes/usage exist, and ledger entries are posted.
- Imports stuck: verify queue worker running and check recent import jobs list for errors.
