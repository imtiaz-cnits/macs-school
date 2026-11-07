# MACS School — Phase 5 Implementation Plan
## Universal Session-Based Fee Invoice Generation

**Target Project:** MACS School Management System  
**Document Type:** Technical Specification & Step-by-Step Implementation Plan  
**Target Milestone:** Phase 5 — Universal Multi-Category Fee Generation  
**Author:** Expert PHP & Laravel Developer / Antigravity Agent  

---

## 1. Proposed Target Architecture

The target architecture unifies all fee generation under a single **Universal Session Fee Generation Engine**. Instead of creating independent silos for each fee category, the existing `FeeGenerationService` will be generalized to resolve billing periods dynamically from each `FeeSetup` configuration.

```
                              ┌─────────────────────────────────────────┐
                              │       Academic Session Available        │
                              └────────────────────┬────────────────────┘
                                                   │
                                                   ▼
                              ┌─────────────────────────────────────────┐
                              │    Resolve Active Session Fee Setups   │
                              │  (Monthly, Exam, Admission, Form Fill)  │
                              └────────────────────┬────────────────────┘
                                                   │
                                                   ▼
                              ┌─────────────────────────────────────────┐
                              │   Resolve Setup Billing Periods (Map)   │
                              │ • 'Monthly'        -> Months 01..12     │
                              │ • Explicit Month   -> Specific Month    │
                              │ • One Time / Empty -> Month 01 / Intake │
                              └────────────────────┬────────────────────┘
                                                   │
                                                   ▼
                              ┌─────────────────────────────────────────┐
                              │     Candidate Student Matching Loop     │
                              │ (Branch, Class, 3-Tier Eligibility)     │
                              └────────────────────┬────────────────────┘
                                                   │
                                                   ▼
                              ┌─────────────────────────────────────────┐
                              │        Idempotent Batch Insertion       │
                              │ • Skip existing valid invoices          │
                              │ • Preserve Paid & Partial records       │
                              │ • In-Transaction Serial Generation      │
                              └────────────────────┬────────────────────┘
                                                   │
                                                   ▼
                              ┌─────────────────────────────────────────┐
                              │      Recalculate Session Readiness      │
                              │       (Ready -> Partial -> Completed)   │
                              └─────────────────────────────────────────┘
```

---

## 2. Exact Files That Need Modification

| File Path | Component | Type of Change | Proposed Scope |
| :--- | :--- | :--- | :--- |
| [`app/Services/FeeGenerationService.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Services/FeeGenerationService.php) | Core Engine | **Modification** | - Add `resolveSetupBillingPeriods(FeeSetup $setup)` helper.<br>- Generalize `generate()` to process all active session setups.<br>- Generalize `getSessionGenerationStatus()` to calculate expected invoices across all categories.<br>- Generalize `evaluateAndGenerateForSession()` to trigger universal session generation. |
| [`app/Console/Commands/FeeAutoGenerate.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Console/Commands/FeeAutoGenerate.php) | Artisan CLI | **Modification** | - Add `--category=` option to allow category-filtered or universal execution.<br>- Update command description and output summaries. |
| [`app/Http/Controllers/SessionYearController.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/SessionYearController.php) | Admin Controller | **Modification** | - Update session readiness payload to include multi-category breakdown in `sessionData` responses. |
| [`resources/views/pages/sessions/index.blade.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/resources/views/pages/sessions/index.blade.php) | UI Modal & Badge | **Modification** | - Update diagnostic modal to show category breakdown (Monthly Tuition, Exam Fees, Annual Fees).<br>- Update labels from "12 Mo Invoices" to "Total Session Invoices". |

> [!NOTE]
> No changes are required in `FeeCollectionController.php`, `FeePayment.php`, `FeeInvoice.php`, or database migration files.

---

## 3. New Methods & Classes Required

No new standalone classes are necessary. The functionality will be cleanly integrated into `FeeGenerationService` via the following modular helper methods:

### Method 1: `resolveSetupBillingPeriods(FeeSetup $setup): array`
- **Location:** `app/Services/FeeGenerationService.php`
- **Purpose:** Maps any `FeeSetup` record to its designated billing month(s):
  ```php
  /**
   * Resolve canonical billing period(s) for a given FeeSetup.
   *
   * @return array Array of normalized month definitions:
   *               [['month_name' => 'January', 'month_num' => '01', 'is_recurring' => true], ...]
   */
  public function resolveSetupBillingPeriods(FeeSetup $setup): array
  {
      $feeMonth = trim((string)($setup->fee_month ?? ''));

      // 1. Recurring Monthly Fee: All 12 calendar months
      if (strtolower($feeMonth) === 'monthly') {
          return array_map(function ($index, $monthName) {
              return [
                  'month_name'   => $monthName,
                  'month_num'    => str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT),
                  'is_recurring' => true,
              ];
          }, array_keys(self::ALL_MONTHS), self::ALL_MONTHS);
      }

      // 2. Explicit Month (e.g. 'March', 'April', 'July', etc.)
      $monthIndex = array_search(ucfirst(strtolower($feeMonth)), self::ALL_MONTHS, true);
      if ($monthIndex !== false) {
          return [[
              'month_name'   => self::ALL_MONTHS[$monthIndex],
              'month_num'    => str_pad((string)($monthIndex + 1), 2, '0', STR_PAD_LEFT),
              'is_recurring' => false,
          ]];
      }

      // 3. One Time / Yearly Fee: Default to Month 01 (January)
      return [[
          'month_name'   => 'January',
          'month_num'    => '01',
          'is_recurring' => false,
      ]];
  }
  ```

---

## 4. Category/Setup-Specific Period Resolution Strategy

### 4.1 Resolution Matrix
| Category | Setup `fee_month` | Resolved Billing Periods | Target Due Date Default |
| :--- | :--- | :--- | :--- |
| **Monthly Fee** | `'Monthly'` | 12 periods: `01` through `12` | 10th of each calendar month (`YYYY-MM-10`) |
| **Monthly Exam Fee** | `'March'`, `'July'`, `'October'` | 1 period matching setup month: `03`, `07`, or `10` | 10th of specified month |
| **Term Exam Fee** | `'April'`, `'August'`, `'November'` | 1 period matching setup month: `04`, `08`, or `11` | 10th of specified month |
| **Admission Fee** | `''` or `NULL` | 1 period: `01` (or student enrollment month for mid-year entrants) | 10th of admission month |
| **Session Fee** | `''` or `NULL` | 1 period: `01` (`January`) | 10th of January (`YYYY-01-10`) |
| **Registration Fee** | `''` or `NULL` | 1 period: `01` (`January`) | 10th of January |
| **Form Fill up Fee** | Specific month or `''` | Specified month (e.g., `'October'`) or `01` | 10th of specified month |

### 4.2 Handling Overlapping Setups
If multiple active setups exist for the same class and category (e.g., both a `'Monthly'` setup and an explicit `'December'` setup), the engine will group setups by `(class_id, fee_category_id, period)` to prevent redundant invoice generation.

---

## 5. Integration With Existing Session Readiness Lifecycle

### 5.1 Existing Readiness vs Target Readiness
- **Verified Existing Behavior (`FeeGenerationService.php#L519-L558`):**  
  Checks strictly:
  $$\text{is\_ready} = (\text{eligible\_students} > 0 \land \text{monthly\_setups} > 0 \land \text{expected\_invoices} > 0)$$
  where `monthly_setups` only counts `fee_category = 'Monthly Fee'` with `fee_month = 'Monthly'`.
- **Proposed Target Behavior:**  
  Generalize to all active fee setups:
  $$\text{is\_ready} = (\text{eligible\_students} > 0 \land \text{active\_setups} > 0 \land \text{expected\_invoices} > 0)$$
  where `expected_invoices` is the sum of all expected student-setup-period combinations across all active categories.

### 5.2 Automatic Lifecycle Hooks Preservation
The existing hooks in controllers will trigger universal generation automatically:
1. **`FeeSetupController::setupStore()` & `setupUpdate()`:**  
   When a user creates or updates any fee setup (whether Tuition, Exam Fee, or Session Fee), `evaluateAndGenerateForSession()` is triggered.
2. **`StudentController::store()` (New Admission):**  
   Automatically triggers evaluation and generates all applicable annual invoices for the newly admitted student.
3. **`StudentController::promoteStudents()` (Student Promotion):**  
   Automatically generates session invoices for promoted cohorts.
4. **`SessionYearController::generateAnnualFees()` (Admin UI / Retry):**  
   Evaluates all categories and generates missing invoices.

---

## 6. Admin UI Diagnostic Changes

In [`resources/views/pages/sessions/index.blade.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/resources/views/pages/sessions/index.blade.php#L130-L177):

1. **Diagnostic Grid Cards:**
   - **Target:** Show total expected invoices across all categories.
   - **Existing:** Show total existing active invoices across all categories.
   - **Missing:** Show missing count across all categories.
   - **Students:** Show eligible student count.
2. **Breakdown Panel in Modal:**
   - Display a categorized list:
     - *Monthly Fee:* $X$ invoices expected / $Y$ generated
     - *Exam Fees (Monthly & Term):* $X$ invoices expected / $Y$ generated
     - *Session & Admission Fees:* $X$ invoices expected / $Y$ generated
3. **Table Column Fee Status Badge:**
   - `Completed` (Emerald): When 100% of all category invoices are present.
   - `Partial (X missing)` (Amber): Clickable to open the breakdown modal and trigger generation.
   - `Pending Setup` (Blue): When no fee setups exist.

---

## 7. Generation Status & Retry Behavior

### 7.1 Status Transitions
$$\text{Pending Setup} \longrightarrow \text{Ready} \longrightarrow \text{Generating} \longrightarrow \text{Partial} \longrightarrow \text{Completed}$$

1. **`pending_fee_setup`:** Session has students but zero active fee setups.
2. **`pending_students`:** Session has fee setups but zero eligible students in scope.
3. **`ready`:** Setups and students exist; 0 invoices generated yet.
4. **`partial`:** Some invoices generated, but missing invoices exist (or cancelled invoices exist).
5. **`completed`:** All expected invoices for all active setups and eligible students exist in the database.

### 7.2 Safe Retry Guarantee
Clicking **"Generate Missing Invoices Now"** or running `fees:auto-generate --session=X`:
- Acquires the MySQL advisory lock.
- Scans all setups.
- Slices candidate students.
- Skips every student-setup-period where an invoice already exists.
- Inserts only the missing invoices.
- Recalculates coverage status to `completed`.

---

## 8. Backward Compatibility Strategy

1. **Preservation of Existing Invoices:**  
   The 9,680 existing invoices in the database will remain 100% intact. Zero modifications will be made to `paid_amount`, `due_amount`, or `discount`.
2. **Existing Single-Month Billing:**  
   The manual generation interface ([`FeeInvoiceController::generate()`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeInvoiceController.php#L51)) will continue to work for single-month / single-class generation without regression.
3. **Financial Reporting:**  
   `FeeReportController` already groups collections and dues across categories; its outputs will seamlessly reflect newly generated multi-category invoices.

---

## 9. Minimal Implementation Verification Checklist

After implementation, verify through targeted checks (no large testing framework):

- [ ] **Check 1: Syntax Validation:** Run `php -l` on all modified files.
- [ ] **Check 2: Dry-Run CLI Execution:**  
  `php artisan fees:auto-generate --session=2026 --dry-run`  
  Verify that it discovers Monthly Fee, Monthly Exam Fee, and Term Exam Fee setups.
- [ ] **Check 3: Status Calculation Accuracy:**  
  Verify `FeeGenerationService::getSessionGenerationStatus(1)` correctly counts expected invoices across all categories.
- [ ] **Check 4: Idempotency Verification:**  
  Ensure that running generation on Session 2026 skips all 9,680 existing invoices with zero duplicates.
- [ ] **Check 5: Cancelled Invoice Safety:**  
  Ensure existing Cancelled invoices (94 Monthly Fee, 31 Monthly Exam Fee, 24 Term Exam Fee) are preserved.
- [ ] **Check 6: UI Responsiveness:**  
  Ensure `https://macs.injaazh.com/sessions` renders with the updated 15-item pagination and status badges.

---

## 10. Recommended Implementation Sequence

1. **Step 1: Core Engine Generalization (`FeeGenerationService.php`)**
   - Add `resolveSetupBillingPeriods()`.
   - Update `generate()` setup query to include all active setups when annual/session generation is requested.
   - Update `getSessionGenerationStatus()` to evaluate all active setups.
2. **Step 2: CLI Command Update (`FeeAutoGenerate.php`)**
   - Support optional `--category=` filtering while defaulting to universal session generation.
3. **Step 3: UI Enhancement (`sessions/index.blade.php`)**
   - Update fee status modal to display multi-category breakdown.
4. **Step 4: Targeted Verification**
   - Execute dry-run checks and syntax tests.
5. **Step 5: Code Review & User Sign-Off**
   - Present final diff to user before deployment.

