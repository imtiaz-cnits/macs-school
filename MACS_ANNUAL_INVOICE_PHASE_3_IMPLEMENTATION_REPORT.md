# MACS School — Phase 3: Due Report Compatibility Implementation Report

**Author / Role:** Senior Laravel Architect, Database Architect, Financial Systems Specialist  
**Project:** MACS School Management System — Annual Monthly Fee Invoice Generation  
**Phase:** Phase 3 — Due Report Compatibility  
**Date:** 2026-11-06  
**Status:** COMPLETE — READY FOR REVIEW  

---

## 1. Executive Summary

Phase 3 addresses the financial reporting compatibility challenges that arise when all twelve monthly fee invoices are generated in advance (e.g., on January 1st for the entire academic session). 

Prior to Phase 3, `FeeReportController.php` contained a fallback clause:
```php
$q->whereBetween('due_date', [$startDate, $endDate])
  ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
```
Because of this `orWhereBetween('created_at', ...)` clause, pre-generating 12 monthly invoices on January 1 caused all 12 invoices (February through December) to match the January date range due to their shared `created_at` timestamp. This artificially inflated the January Due Report with future unbilled months and produced misleading financial balances. Furthermore, on default page visits where date parameters were absent, the date filter was bypassed completely, loading all historical uncollected invoices under a single month header.

### Key Outcomes of Phase 3
1. **Complete Elimination of `created_at` Fallback:** The date filter now relies strictly on invoice `due_date`, preventing any future-month invoice created in advance from polluting current-period reports.
2. **Explicit Separation of Business Logic:**
   - **Monthly / Period Due Report (Period-based):** Uses `due_date BETWEEN $startDate AND $endDate`. Only invoices maturing within the selected window appear.
   - **Cumulative Arrears Report (Outstanding-based):** Uses `due_date <= $endDate` with `status != 'Cancelled'` and `due_amount > 0`. Incorporates all past unpaid balances up to the cut-off date while strictly excluding future invoices (`due_date > $endDate`).
3. **Complementary Metrics:** Both `periodTotalDue` and `cumulativeTotalDue` are computed simultaneously, providing administrators complete transparency between monthly billing obligations and school-wide historical debt.
4. **UI Integration:** Added a custom Alpine.js dropdown for "Due Filter Mode" (Rule 7) and instant toggle pills inside the Defaulters Tab, fully adhering to the MACS design system and strict English UI terminology.
5. **Zero Baseline Mutation & 100% Test Pass:** All 14 mandated test scenarios passed with 100% success. Baseline database invoices remain unchanged at **9,188**.

---

## 2. Initial Report Query Audit

An audit of `app/Http/Controllers/FeeReportController.php` was conducted across all SQL queries, date dimensions, status filters, and aggregation points:

| Query / Endpoint | Target Model | Date Dimension Used | Status & Amount Filters | Business Interpretation | Audit Finding & Classification |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `index()` — Payments | `FeePayment` | `whereBetween('payment_date', [$startDate, $endDate])` | None (all recorded payments) | Actual cash collection during selected period | **VERIFIED:** Semantically correct. Payments must always be indexed by `payment_date`. |
| `index()` — Dues (Legacy) | `FeeInvoice` | `due_date BETWEEN ... OR created_at BETWEEN ...` | `status != 'Cancelled'` & `due_amount > 0` | Monthly dues / defaulter invoices | **VERIFIED (DEFECTIVE):** Contained `created_at` fallback that pulled 12 months into January. Skipped date filter on default visit. |
| `index()` — Defaulters List | `FeeInvoice` | Grouped from `$dues` by `student_id` | Derived from `$duesQuery` | Student-level consolidated debt | **VERIFIED:** Inherited defective date logic from `$duesQuery`. |
| `index()` — Due Months Summary | `FeeInvoice` | Grouped from `$dues` using `$resolveMonth` | Derived from `$duesQuery` | Breakdown of unpaid fees by month | **VERIFIED:** Inherited defective date logic from `$duesQuery`. |
| `summaryReport()` | `FeeInvoice` | No date filter (grouped by session/class/branch) | `status != 'Cancelled'` | Academic session category-level collection % | **VERIFIED:** Operates on entire session. No `created_at` or `due_date` collision. |

### Empirical Evidence of the `created_at` Mismatch Bug
In the local development database:
- Total invoices: **9,188**
- Invoices where `YEAR(created_at) != YEAR(due_date) OR MONTH(created_at) != MONTH(due_date)`: **5,866** (63.8% of database)
- In November 2026: **1,035 invoices** have `due_date = '2026-11-10'` and `created_at` in November. A report run for November 1–6 using `created_at` pulled all 1,035 invoices even though their due date had not yet arrived!

---

## 3. Files Modified

| File Path | Nature of Change | Lines Changed | Justification & Compatibility |
| :--- | :--- | :--- | :--- |
| `app/Http/Controllers/FeeReportController.php` | Backend Logic | +42 / -10 | Eliminated `created_at` fallback; added `due_type` support (`period` vs `cumulative`); computed complementary metrics (`cumulativeTotalDue`, `periodTotalDue`); strictly preserved all existing view variable names and types. |
| `resources/views/pages/fees/reports.blade.php` | Frontend UI / TALL Stack | +63 / -7 | Added Rule 7 Custom Select Dropdown for `due_type`; added quick-switch toggle pills in Defaulters Tab; enhanced card metrics and printable header labels; preserved responsive layouts and print pagination. |

*No modifications were made to `FeeGenerationService.php`, `FeeAutoGenerate.php`, `FeeInvoiceController.php`, payment collection logic, or database schema.*

---

## 4. Monthly Due Logic (Period-Based)

### Business Semantics
- **Objective:** Measure fee billing obligations originating within a specific calendar month or billing cycle.
- **Date Dimension:** Strictly `FeeInvoice.due_date`.
- **Query Filter:**
  ```php
  $duesQuery->whereBetween('due_date', [$startDate, $endDate]);
  ```
- **Future Months Exclusion:** If twelve invoices for January–December 2027 are generated on January 1, 2027:
  - January report (`2027-01-01` to `2027-01-31`): Matches only invoices with `due_date` in January.
  - February report (`2027-02-01` to `2027-02-28`): Matches only invoices with `due_date` in February.
  - Future unbilled months (e.g. February–December) are strictly excluded from the January report.
- **Status & Amount Rules:**
  - `status != 'Cancelled'` strictly enforced.
  - `due_amount > 0` ensures paid invoices (`due_amount = 0`) are excluded.
  - Partial invoices contribute only their remaining `due_amount` balance.

---

## 5. Cumulative Arrears Logic (Outstanding-Based)

### Business Semantics
- **Objective:** Measure total unpaid school fees owed by students up to a specific cut-off date (historical debt position).
- **Date Dimension:** `FeeInvoice.due_date <= $endDate`.
- **Query Filter:**
  ```php
  $duesQuery->where('due_date', '<=', $endDate);
  ```
- **Historical Arrears Preservation:** Invoices from previous months and prior academic years that remain unpaid (`due_amount > 0`) are retained and aggregated.
- **Future Months Exclusion:** Invoices whose `due_date > $endDate` are strictly excluded, regardless of when they were created in the database.
- **Status & Balance Handling:**
  - Fully paid invoices (`due_amount <= 0`) are excluded.
  - Unpaid invoices (`due_amount = net_amount`) are included.
  - Partial invoices contribute only their remaining `due_amount` (e.g. net 1,000, paid 400 -> contributes 600).
  - Cancelled invoices (`status = 'Cancelled'`) are defensively excluded regardless of balance.

---

## 6. Payment Report Compatibility

### Payment Date Integrity
- Payment collection reporting continues to be strictly governed by `FeePayment.payment_date`:
  ```php
  $paymentsQuery = FeePayment::with(['student.schoolClass', 'student.branch', 'invoice.feeSetup.category', 'collector'])
      ->whereBetween('payment_date', [$startDate, $endDate]);
  ```
- If an invoice due in January 2027 is paid in March 2027 (`payment_date = '2027-03-20'`):
  - January Collection Report: Shows ৳ 0 (no payment recorded in January).
  - March Collection Report: Shows ৳ 1,000 collection (payment recorded in March).
- Consolidated multi-fee receipt grouping (`resolveMasterReceipt`), collection timestamp resolution (`resolveCollectionDate`), and method breakdowns (`methodBreakdown`) remain completely unaltered.

---

## 7. Historical Data Compatibility

### Mismatched Records & Historical Imports
- **Audit Reality:** 5,866 existing historical invoices possess `created_at` timestamps that differ from their `due_date` months due to past bulk imports and batch operations.
- **Resolution:** By anchoring the report to `due_date`, historical invoices now appear consistently in their true academic due month, eliminating duplicate appearances across different report periods.
- **Month Name Resolution:** Preserved the centralized `FeeInvoice::month_name` accessor:
  1. Priority 1: `feeSetup->fee_month` (if valid month name)
  2. Priority 2: `invoice_no` regex extraction (`INV-YYYYMM-XXXX`)
  3. Priority 3: Non-monthly category exception (`One Time` / `Yearly`)
  4. Priority 4: `due_date` format
  5. Priority 5: `created_at` format
  6. Fallback: `General`
- The helper closure in `FeeReportController.php` remains unchanged:
  ```php
  $resolveMonth = function ($inv) {
      if (!$inv) return 'General';
      return $inv->month_name ?: 'General';
  };
  ```

---

## 8. Regression Test Results

An automated, non-destructive test suite (`scratch/test_phase3_suite.php`) was executed with complete transactional rollback protection. All 14 scenarios required by the operator, plus an additional safety test for cancelled status handling, were verified:

| # | Scenario Description | Tested Condition | Expected Result | Actual Result | Classification |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **1** | 12 invoices generated on same date with 12 distinct due dates | 12 invoices created with `created_at = '2027-01-01'` and due dates Jan 10 through Dec 10 | 12 invoices stored with identical creation dates and differing due dates | **PASS** (12 invoices verified) | **VERIFIED** |
| **2** | January monthly report includes January only | `start_date=2027-01-01`, `end_date=2027-01-31`, `due_type=period` | Exactly 1 invoice (Jan 10); Feb–Dec excluded | **PASS** (1 invoice found, due 2027-01-10) | **VERIFIED** |
| **3** | February monthly report excludes January and March | `start_date=2027-02-01`, `end_date=2027-02-28`, `due_type=period` | Exactly 1 invoice (Feb 10); Jan & Mar–Dec excluded | **PASS** (1 invoice found, due 2027-02-10) | **VERIFIED** |
| **4** | December monthly report includes December only | `start_date=2027-12-01`, `end_date=2027-12-31`, `due_type=period` | Exactly 1 invoice (Dec 10); Jan–Nov excluded | **PASS** (1 invoice found, due 2027-12-10) | **VERIFIED** |
| **5** | January cumulative arrears includes all dues up to Jan 31 | `end_date=2027-01-31`, `due_type=cumulative` | 1 invoice (Jan 10); Feb–Dec excluded | **PASS** (1 invoice found, Feb-Dec excluded) | **VERIFIED** |
| **6** | February cumulative arrears includes Jan + Feb | `end_date=2027-02-28`, `due_type=cumulative` | 2 invoices (Jan 10 and Feb 10); Mar–Dec excluded | **PASS** (2 invoices found: Jan 10, Feb 10) | **VERIFIED** |
| **7** | Paid invoices excluded from outstanding arrears | Invoice with `status='Paid'` and `due_amount=0.00` | Excluded from both period dues and cumulative arrears | **PASS** (0 paid invoices in dues) | **VERIFIED** |
| **7B**| Extra Safety: Cancelled invoices with positive due | Invoice with `status='Cancelled'` and `due_amount=1000.00` | Strictly excluded from both reports | **PASS** (0 cancelled invoices in dues) | **VERIFIED** |
| **8** | Partial invoices contribute only remaining balance | Net 900, paid 400, `due_amount=500.00` | Contributes exactly 500.00 to `totalDue` | **PASS** (due_amount = 500.00 verified) | **VERIFIED** |
| **9** | Previous-month arrears retained in cumulative report | Querying March cumulative arrears with unpaid Jan, Feb, Mar | All 3 prior months included in list and sum | **PASS** (Jan, Feb, Mar all included) | **VERIFIED** |
| **10**| Future-dated invoices excluded from cumulative arrears | March cumulative query (`end_date=2027-03-31`) vs April+ | Invoices due after March 31 strictly excluded | **PASS** (0 future invoices present) | **VERIFIED** |
| **11**| Imported invoice with mismatched `created_at` | Created `2027-01-01`, `due_date=2027-05-15` | Excluded from Jan report (0); present in May report (1) | **PASS** (Jan: 0, May: 1) | **VERIFIED** |
| **12**| Payment collection reports use `payment_date` | January invoice paid on `2027-03-20` | Excluded from Jan collection (0); present in Mar collection (1) | **PASS** (Jan: 0, Mar: 1) | **VERIFIED** |
| **13**| Multi-month date range filters functional | Q1 query (`2027-01-01` to `2027-03-31`) | Invoices for months 01, 02, and 03 included | **PASS** (Months 01, 02, 03 matched) | **VERIFIED** |
| **14**| Existing `month_name` accessor intact | Invoices evaluated across all months | `January`, `February`, `December` resolved correctly | **PASS** (Jan, Feb, Dec verified) | **VERIFIED** |

---

## 9. Database Safety

- **Isolation Mechanism:** All test executions utilized `DB::beginTransaction()` with guaranteed `DB::rollBack()` in `finally` blocks.
- **Baseline Count Verification:**
  - Invoice count prior to testing: **9,188**
  - Invoice count following testing: **9,188**
  - Difference: **0 records**
- **Production Safety:** No production migrations, seeder runs, or database mutations were performed.

---

## 10. Git Working Tree Status

A full git inspection confirms clean state and strict preservation of work from Phase 1 and Phase 2:

```text
Changes not staged for commit:
  modified:   app/Console/Commands/FeeAutoGenerate.php          (Phase 2)
  modified:   app/Http/Controllers/FeeInvoiceController.php     (Phase 2)
  modified:   app/Http/Controllers/FeeReportController.php      (Phase 3)
  modified:   resources/views/pages/fees/invoice_generate.blade.php (Phase 2)
  modified:   resources/views/pages/fees/reports.blade.php      (Phase 3)

Untracked files:
  MACS_ANNUAL_INVOICE_FINAL_PRE_IMPLEMENTATION_REVIEW.md
  MACS_ANNUAL_INVOICE_PHASE_1_FINAL_CODE_REVIEW.md
  MACS_ANNUAL_INVOICE_PHASE_1_IMPLEMENTATION_REPORT.md
  MACS_ANNUAL_INVOICE_PHASE_2_FINAL_ACCEPTANCE_AUDIT.md
  MACS_ANNUAL_INVOICE_PHASE_2_IMPLEMENTATION_REPORT.md
  MACS_ANNUAL_INVOICE_PHASE_3_IMPLEMENTATION_REPORT.md
  MACS_ANNUAL_INVOICE_SECOND_PASS_VALIDATION.md
  app/Services/FeeGenerationService.php
```

- **Commit Status:** Zero commits performed.
- **Push Status:** Zero pushes executed.
- **Scratch Files:** All temporary diagnostic scripts (`test_phase3_suite.php`, `test_current_report.php`, `test_view_render.php`, `test_summary_report.php`) have been removed from the repository.

---

## 11. Known Limitations & Cross-Module Observations

1. **`FinancialReportController.php` Fallback:**
   During the source code audit, lines 228–229 of `app/Http/Controllers/FinancialReportController.php` were observed to contain:
   ```php
   $q->whereBetween('due_date', [$startDate, $endDate])
     ->orWhereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
   ```
   While `FinancialReportController` was strictly out of scope for Phase 3, it represents a known potential future leakage point if pre-generated annual invoices are viewed under the General Ledger / School Financial Statements report. It is recommended to address this during Phase 4 or subsequent maintenance.
2. **Default Date Range vs Due Date within Current Month:**
   When opening `/fees/reports` without GET parameters, the form defaults `startDate` to `date('Y-m-01')` and `endDate` to `date('Y-m-d')` (today). If monthly fee invoices for the current month have a due date in the future (e.g. November 10th while today is November 6th), `whereBetween('due_date', ['2026-11-01', '2026-11-06'])` correctly shows 0 invoices for November since they are not yet due. Administrators desiring to view unbilled future obligations for the entire month should set `endDate` to the last day of the month or select "Cumulative Arrears" mode.

---

## 12. Final Verdict

### Acceptance Status: PASS (READY FOR ACCEPTANCE AUDIT)

Phase 3 achieves complete financial correctness:
1. **The `created_at` fallback bug is eliminated.** Pre-generating 12 months of invoices in advance will never leak future months into current-period reports.
2. **Distinct business logic is established.** Monthly Due Reports provide pure period billing metrics, while Cumulative Arrears Reports provide complete historical outstanding debt positions.
3. **Payment collection reports remain 100% untouched** and anchored to `payment_date`.
4. **All 14 mandated regression scenarios passed without exception**, with zero mutations to the baseline database.

Ready for operator review and progression to Phase 4 upon approval.

