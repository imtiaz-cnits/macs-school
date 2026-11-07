# MACS School — Phase 5 Final Entry-Point Safety Audit

**Date:** 2026-11-08  
**Project:** MACS School Management System (Laravel / TALL Stack)  
**Author:** AZD Lead Technical Co-Pilot & Laravel Systems Architect  
**Status:** COMPLETE (Recommended for Commit Review)

---

## Executive Summary

Following the user's directive, this audit performed an exhaustive, read-only code review of **every invoice generation entry point** across the MACS School codebase.

The primary objective was to ensure that the **Play Setup Conflict (Setups 6, 90, 92)** cannot be bypassed through alternative execution paths (such as the CLI, the aaPanel scheduler, manual fee generation UI, or student admission lifecycle events) and that the safety guard is enforced **at the shared `FeeGenerationService` execution boundary**.

**Audit Verdict:**
- **Execution Boundary Hardened:** The conflict guard is now enforced directly inside `FeeGenerationService::generate()`, ensuring that **all callers**—whether HTTP controllers, Artisan console commands, or scheduled tasks—are uniformly intercepted.
- **Selective Scoping Verified:**
  - Broad annual generation (CLI `--all-year`, Scheduler, Admin Retry) is completely blocked for Session 2026.
  - Manual single-month generation for conflict-free classes (e.g. Class Five, Class Six) proceeds normally.
  - Manual generation specifically targeting Class Play Monthly Fee is rejected.
  - Point-of-Admission Admission Fee generation for new students (Setup 91) remains completely functional and is not blocked.
  - Unrelated sessions with no overlapping setups evaluate normally without false positives.
- **Zero Database Writes:** Zero records written, 0 deleted. All 9,715 invoices and 87 Fee Setups remain untouched.

---

## 1. Entry-Point Safety Matrix & Call Chains

| # | Entry Point | Trigger Mechanism | Shared Service Method | Conflict Guard Executed? | Actual System Behavior |
| :- | :--- | :--- | :--- | :---: | :--- |
| **1** | **Admin Annual Generation & Retry** | Web UI Button / `window.GenerateAnnualFees` -> `POST /ajax/sessions/{id}/generate-annual-fees` | `FeeGenerationService::evaluateAndGenerateForSession()` & `generate()` | **YES (Double Guarded)** | Intercepted in controller and service. Returns **HTTP 422 Unprocessable Entity** with `status: 'conflict'`. 0 invoices created. |
| **2** | **CLI `fees:auto-generate --all-year`** | Terminal command: `php artisan fees:auto-generate --session=2026 --year=2026 --all-year` | `FeeGenerationService::generate()` | **YES (Execution Boundary Guard)** | Intercepted at line 117 of `FeeGenerationService`. Exits with **status code 1** and logs: `ABORT: Inspection Required: Play Setup Conflict Detected`. 0 invoices created. |
| **3** | **Scheduler-Triggered Generation** | aaPanel Cron: `routes/console.php` (`fees:auto-generate` daily at 00:05) | `FeeGenerationService::generate()` | **YES (Execution Boundary Guard)** | Intercepted at line 117 of `FeeGenerationService`. Exits with **status code 1** and logs conflict notice. 0 invoices created. |
| **4A** | **Manual UI (Conflicting Class: Play)** | Form `POST /invoice/generate` targeting Class Play (`class_id: 5`) and Monthly Fee (`cat: 3`) | `FeeGenerationService::generate()` | **YES (Targeted Scope Guard)** | Intercepted inside `generate()`. Returns `success: false` with error: `Inspection Required: Play Setup Conflict Detected`. Redirects with flash error. 0 invoices created. |
| **4B** | **Manual UI (Conflict-Free Class: Five)** | Form `POST /invoice/generate` targeting Class Five (`class_id: 7`) and Monthly Fee (`cat: 3`) | `FeeGenerationService::generate()` | **NO (Intentionally Allowed)** | Conflict guard confirms Class Five has no overlapping setups. Generation proceeds normally (`success: true`). |
| **5** | **Point-of-Admission (New Student)** | Registration form `POST /students` -> `StudentController::store()` | `FeeGenerationService::generateAdmissionFeeForNewStudent()` | **ISOLATED (Independent Method)** | Generates single Admission Fee invoice (Setup 91, 1,000 Tk) for new student. Subsequent session evaluation in `store()` safely aborts without running conflicting bulk generation. |
| **6** | **Student Promotion Lifecycle** | Promotion form `POST /students/promote` -> `StudentController::promoteStudents()` | `FeeGenerationService::evaluateAndGenerateForSession()` | **YES (Pre-Evaluation Guard)** | Intercepted by `evaluateAndGenerateForSession()`. Returns early with `setup_conflict`. No annual batch generated. |
| **7** | **Fee Setup Store / Update** | Setup form `POST /fees/setup` -> `FeeSetupController::store()` / `update()` | `FeeGenerationService::evaluateAndGenerateForSession()` | **YES (Pre-Evaluation Guard)** | Intercepted by `evaluateAndGenerateForSession()`. Returns early with `setup_conflict`. No annual batch generated. |

---

## 2. Detailed Call-Chain Inspections

### 2.1 Admin Annual Generation & Retry
- **Route:** `routes/web.php` line 22
  ```php
  Route::post('ajax/sessions/{id}/generate-annual-fees', [SessionYearController::class, 'generateAnnualFees']);
  ```
- **Controller:** [app/Http/Controllers/SessionYearController.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/SessionYearController.php#L67-L79)
  ```php
  $result = app(FeeGenerationService::class)->evaluateAndGenerateForSession((int)$session->id, $userId);

  if (($result['status'] ?? '') === 'setup_conflict' || ($result['has_conflict'] ?? false)) {
      return response()->json([
          'status'  => 'conflict',
          'message' => $result['reason'] ?? 'Inspection Required: Play Setup Conflict Detected. Generation rejected.',
          'details' => $result,
      ], 422);
  }
  ```
- **Behavior:** Complete rejection with HTTP 422. Bypassing frontend UI button produces immediate, clean API rejection.

### 2.2 CLI `fees:auto-generate --all-year`
- **Command:** [app/Console/Commands/FeeAutoGenerate.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Console/Commands/FeeAutoGenerate.php#L99-L105)
  ```php
  $result = $generationService->generate($params, $callback);
  if (!$result['success']) {
      $this->error("\nGENERATION FAILED: " . ($result['error'] ?? 'Unknown error occurred.'));
      return 1;
  }
  ```
- **Service Execution Boundary Guard:** [app/Services/FeeGenerationService.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Services/FeeGenerationService.php#L113-L149)
  ```php
  $setupConflicts = $this->detectSetupConflicts((int)$session->id, (int)$targetYear);
  if (!empty($setupConflicts)) {
      $requestedClassId = !empty($params['class_id']) ? (int)$params['class_id'] : null;
      if ($requestedClassId === null) {
          $conflictMsg = "Inspection Required: Play Setup Conflict Detected. Overlapping active setups exist... Automated generation is locked until resolved in Fee Setup.";
          $log('error', "ABORT: {$conflictMsg}");
          return ['success' => false, 'lock_failed' => false, 'has_conflict' => true, 'error' => $conflictMsg];
      }
  }
  ```
- **Behavior:** CLI immediately prints the abort error and exits with code 1. No invoices are processed.

### 2.3 Scheduler-Triggered Generation
- **Console Schedule:** `routes/console.php` line 23
  ```php
  Schedule::command('fees:auto-generate')->dailyAt('00:05')->timezone('Asia/Dhaka');
  ```
- **Behavior:** Delegates to `FeeAutoGenerate::handle()`, which encounters the same execution boundary guard in `FeeGenerationService::generate()`. Cron task logs the conflict and exits with code 1. Database is completely protected.

### 2.4 Manual Fee Generation UI (`FeeInvoiceController::generate`)
- **Controller:** [app/Http/Controllers/FeeInvoiceController.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeInvoiceController.php#L51-L80)
  ```php
  $params = [
      'branch_id'       => (int) $request->branch_id,
      'class_id'        => (int) $request->class_id,
      'session_year_id' => (int) $request->session_year_id,
      'fee_category_id' => (int) $request->fee_category_id,
      'fee_month'       => $request->fee_month,
      'due_date'        => $request->due_date,
      'user_id'         => Auth::id(),
  ];
  $result = $generationService->generate($params);
  ```
- **Behavior:**
  - If `$params['class_id'] == 5` (Play) and `$params['fee_category_id'] == 3` (Monthly Fee): Conflict guard detects intersection and returns `success: false`. Controller redirects back with flash error: `Generation Error: Inspection Required: Play Setup Conflict Detected...`.
  - If `$params['class_id'] == 7` (Class Five): Conflict guard detects zero conflicts for Class Five. Generation proceeds normally.

### 2.5 Student Point-of-Admission Admission Fee Generation
- **Controller:** [app/Http/Controllers/StudentController.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/StudentController.php#L485-L495)
  ```php
  $admFeeResult = app(\App\Services\FeeGenerationService::class)->generateAdmissionFeeForNewStudent($student, Auth::id());
  ```
- **Behavior:** Completely decoupled from annual bulk generation. Generates the single Admission Fee invoice for the newly registered student (Setup 91 in Class Play). Is NOT blocked by the setup conflict guard.

---

## 3. Conflict Detector Scope Verification

Inspection of `detectSetupConflicts(int $sessionYearId, ?int $targetYear = null)` in [FeeGenerationService.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Services/FeeGenerationService.php#L525-L585):

1. **Academic Session & Class Scoping:**
   - Filters by `session_year_id = $sessionYearId` and `status = 'Active'`.
   - Groups by `branch_id . '_' . $class_id . '_' . $fee_category_id`.
   - Setup comparisons occur strictly within the same academic cohort.
2. **Category Exclusion:**
   - Explicitly excludes `admission fee` and `fine` using `whereNotIn(LOWER(TRIM(name)), ['admission fee', 'fine'])`.
3. **Billing Month Resolution:**
   - Calls `resolveSetupBillingPeriods($setup, (int)$year)`.
   - Recurring `'Monthly'` resolves to 12 months: `01` through `12`.
   - Explicit month (e.g. `'March'`, `'December'`) resolves strictly to that calendar month (`03`, `12`).
   - One Time / NULL (`fee_month = NULL`) defaults to Month `01` (January).
4. **Active Setup Filtering:**
   - Inactive setups (`status = 'Inactive'`) are excluded by SQL query constraint (`where('status', 'Active')`).
5. **Exact Detection on Setups 6, 90, 92:**
   - Setup 6 (`Monthly Fee`, Play, `fee_month: 'Monthly'`): covers Months 01 to 12.
   - Setup 90 (`Monthly Fee`, Play, `fee_month: NULL`): covers Month 01.
   - Setup 92 (`Monthly Fee`, Play, `fee_month: 'December'`): covers Month 12.
   - **Result:** Accurately flags Month 01 overlap (Setups 6 & 90) and Month 12 overlap (Setups 6 & 92).
6. **False Positive Immunity:**
   - Monthly Exam Fee (Setups 18, 30, 31 for Play; 19, 32, 33 for Nursery; etc.): Scheduled for distinct months (March, July, October). No overlap.
   - Term Exam Fee (Setups 54, 55, 56 for Play; 57, 58, 59 for Nursery; etc.): Scheduled for distinct months (April, August, November). No overlap.
   - Classes Nursery through Ten: Exactly 1 active recurring setup per class. No overlap.
   - **Result:** Exactly 0 false positives across all 10 other classes.

---

## 4. Verification Suite Results

Targeted verification was executed via `scratch_test_entry_points.php`:

```
=================================================================
MACS SCHOOL: FINAL ENTRY-POINT SAFETY AUDIT VERIFICATION
=================================================================
Initial Total Invoices in DB: 9715

--- 1. ADMIN ANNUAL GENERATION & RETRY ---
  [PASS] Test 1: Admin Retry endpoint returns HTTP 422 Unprocessable Entity
  [PASS] Test 2: Admin Retry endpoint response status is 'conflict'
  [PASS] Test 3: Admin Retry message contains 'Play Setup Conflict Detected'

--- 2. CLI fees:auto-generate --all-year ---
  [PASS] Test 4: CLI fees:auto-generate --all-year exits with status code 1 (Blocked)
  [PASS] Test 5: CLI output explicitly reports Play Setup Conflict Detected / Generation Failed

--- 3. SCHEDULER-TRIGGERED fees:auto-generate ---
  [PASS] Test 6: Scheduler execution of fees:auto-generate exits with status code 1 (Blocked)
  [PASS] Test 7: Scheduler output reports Play Setup Conflict Detected

--- 4. MANUAL SINGLE-MONTH FEE GENERATION UI ---
  [PASS] Test 8: Manual generation for Class Play Monthly Fee is blocked (success: false, has_conflict: true)
  [PASS] Test 9: Manual generation error for Play specifies 'Play Setup Conflict Detected'
  [PASS] Test 10: Manual generation for Class Five Monthly Fee (Conflict-Free Class) is ALLOWED to proceed normally

--- 5. STUDENT POINT-OF-ADMISSION ADMISSION FEE ---
  [PASS] Test 11: Point-of-Admission Admission Fee is NOT blocked and generates invoice successfully for new student
  [PASS] Test 12: Admission Fee invoice has valid canonical invoice_no

--- 6. ZERO DATABASE MODIFICATION GUARANTEE ---
  [PASS] Test 13: FeeInvoice count remains exactly 9715 (0 records written, 0 deleted)
  [PASS] Test 14: FeeSetup count remains exactly 87 (Setups 6, 90, 91, 92 completely untouched)

=================================================================
FINAL RESULTS: 14/14 CHECKS PASSED (100%)
=================================================================
```

---

## 5. Remaining Risks & Observations

1. **Resolution of Play Class Setups 90 & 92:**
   - Setups 90 and 92 remain in `Active` status with 0 invoices in the database.
   - Setup 6 already provides the legitimate 668 invoices for Play Class.
   - Once the user explicitly approves data maintenance in a future session, setting `status = 'Inactive'` on Setups 90 and 92 in the Fee Setup module will clear the conflict and allow Session 2026 to transition to `completed`.
2. **Cancelled Invoices 11739 & 12280:**
   - Both belong to Student 312 for October (Monthly Fee and Monthly Exam Fee). They remain safely cancelled and will not be overwritten by any generation process.

---

## 6. Final Recommendation

### **READY FOR COMMIT REVIEW**

All five generation entry points have been audited at the source code level and verified through actual execution tests. The conflict guard is fully enforced at the shared `FeeGenerationService` execution boundary. No bypass exists.

No Git commit or push has been performed. No production deployment has been initiated.

