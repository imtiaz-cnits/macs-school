# MACS School — Phase 5 Final Production-Safety Review
## Rigorous Technical Audit & Financial Safety Verification

**Auditor:** Antigravity (TALL Stack Specialist & Systems Copilot)  
**Date:** November 7, 2026  
**Target Environment:** Laravel 11.x / PHP 8.3 / MySQL / aaPanel Production  
**Overall Status:** **READY AFTER SPECIFIED CORRECTIONS** (Production Deployment Temporarily Paused)  

---

## Executive Summary of Findings

| Audit Dimension | Status | Key Finding |
| :--- | :---: | :--- |
| **1. Admission Fee Eligibility** | **BLOCKER** | In the current production database, `student_academic_histories` has **0 records** because promotion has not occurred yet. `admission_date` is `NULL` for 542 of 543 students. Relying on "absence of academic history" cannot safely distinguish newly admitted students from continuing students imported in July 2026. |
| **2. Full-Year Dry-Run Scope** | **PASS** | `php artisan fees:auto-generate --session=2026 --year=2026 --all-year --dry-run` successfully evaluated all 12 calendar months (Jan–Dec) across all 87 active setups. Total existing invoices preserved: 9,471; simulated new: 164 (due to setups 90, 91, 92); DB inserts: 0. |
| **3. Fine & Others Policy** | **PASS** | Active Fee Setups for `Fine` (ID: 9) = **0**; `Others` (ID: 10) = **0**. The automated generator only executes setups that exist. Event-based fine safeguards can be codified so future manual setups are excluded from bulk runs. |
| **4. aaPanel Cron Scheduler** | **PASS** | Both `MACS School Laravel Schedule` and `MACS SMS Scheduler` invoke the exact same command: `schedule:run`. Inside `routes/console.php`, `sms:dispatch-scheduled` is already registered under Laravel's schedule every minute. `MACS SMS Scheduler` is a redundant duplicate that stopped executing on 2026-10-22. |

---

## 1. Admission Fee Eligibility Verification

### 1.1. Source Inspection of Student Workflow
- **Controller:** [`app/Http/Controllers/StudentController.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/StudentController.php#L324-L480) (`store` method).
- **Admission Form View:** [`resources/views/pages/students/admission.blade.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/resources/views/pages/students/admission.blade.php#L757).
- **Promotion Method:** [`StudentController::promoteStudents`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/StudentController.php#L1163-L1218).

### 1.2. Database Reality & Investigation Results
A direct query of the production database revealed:
1. **Total Active Students:** 543 students.
2. **`admission_date` column:** `NULL` or empty for **542 students** (only 1 student has a date). The admission form `admission.blade.php` does not present an `admission_date` field or `admission_type` selector.
3. **`student_academic_histories` table:** Contains exactly **0 records**.
   - In MACS School, `StudentAcademicHistory` records are written strictly when an admin runs the **Student Promotion** feature in `StudentController::promoteStudents`.
   - Because the system went live with Session 2026, promotion has never been executed yet.
4. **Student Creation Distribution:**
   - 2026-07-22: **535 students** (initial historical batch import covering Class Play through Class Ten).
   - 2026-08-06 to 2026-10-14: **7 students** added individually.
5. **Admission Fee Setup in Database:**
   - Only **1 active setup** exists for Admission Fee (`Setup ID: 91 | Class ID: 5 (Play) | Session ID: 1 | Amount: 1000.00 | fee_month = null`).
   - Total existing Admission Fee invoices in database: **0**.

### 1.3. Safety Conclusion & Recommended Solution
> [!CAUTION]
> **Financial Risk Identified:** If the universal annual generator runs Admission Fee in bulk relying solely on `$student->academicHistories()->doesntExist()`, any continuing student who was imported in July 2026 and has never been promoted through the software would appear identical to a newly admitted student!

#### The Smallest Safe Solution (No Speculative Fallbacks):
1. **Exclude Admission Fee from Bulk Session Generation**:
   The session-wide batch command (`fees:auto-generate --all-year`) should **NOT** automatically broadcast Admission Fee invoices across an entire class in bulk.
2. **Point-of-Admission Invoice Generation**:
   Generate the Admission Fee invoice **strictly at the moment a new student is admitted** via `StudentController::store()`:
   - When a student is submitted through `admission.blade.php`, `StudentController::store()` already has the exact newly created `$student` object.
   - It can check if an active `Admission Fee` setup exists for that student's class and generate the 1-time Admission Fee invoice directly upon admission.
3. **Zero Risk for Continuing Students**:
   Existing continuing students are never touched, and no bulk cron job will ever erroneously bill an old student for Admission Fee.

---

## 2. Full-Year Dry-Run Scope Verification

### 2.1. Executed Test
The command was executed with `--all-year` and `--dry-run`:
```bash
php artisan fees:auto-generate --session=2026 --year=2026 --all-year --dry-run
```

### 2.2. Execution Log & Verification Evidence
- **Scope Confirmed:** `Execution Scope: Full Annual (All 12 Months: January to December)`.
- **Setups Processed by Month:**
  - **January:** 14 setups (12 Monthly Fee + 1 Play Monthly Fee (Setup 90) + 1 Play Admission Fee (Setup 91)).
  - **February:** 12 setups (12 Monthly Fee).
  - **March:** 24 setups (12 Monthly Fee + 12 Monthly Exam Fee).
  - **April:** 24 setups (12 Monthly Fee + 12 Term Exam Fee).
  - **May:** 24 setups (12 Monthly Fee + 12 Monthly Exam Fee).
  - **June:** 24 setups (12 Monthly Fee + 12 Monthly Exam Fee).
  - **July:** 24 setups (12 Monthly Fee + 12 Term Exam Fee).
  - **August:** 24 setups (12 Monthly Fee + 12 Monthly Exam Fee).
  - **September:** 12 setups (12 Monthly Fee).
  - **October:** 24 setups (12 Monthly Fee + 12 Monthly Exam Fee).
  - **November:** 24 setups (12 Monthly Fee + 12 Term Exam Fee).
  - **December:** 13 setups (12 Monthly Fee + 1 Play Monthly Fee (Setup 92)).
- **Result Output:**
  ```
  Simulated Invoices Created: 164 | Invoices Skipped (Existing): 9471
  Zero database rows were created.
  Global advisory lock acquired and released cleanly.
  ```

### 2.3. Mathematical Breakdown of the Numbers
The user asked to clarify the difference between `9,715`, `9,566`, and the dry-run counts:

```mermaid
flowchart TD
    TotalInvoices["Total Invoices in fee_invoices Table: 9,715"]
    TotalInvoices --> ActiveInvoices["Active Invoices: 9,566"]
    TotalInvoices --> CancelledInvoices["Cancelled Invoices: 149"]
    
    ActiveInvoices --> Paid["Paid: 7,339"]
    ActiveInvoices --> Unpaid["Unpaid: 2,226"]
    ActiveInvoices --> Partial["Partial: 1"]
```

| Scope | Metric | Count | Details |
| :--- | :--- | :---: | :--- |
| **Database Global** | Total Rows in `fee_invoices` | **9,715** | All 9,715 rows belong to Session 2026 (`session_year_id = 1`). |
| **Database Global** | Paid Invoices | **7,339** | Preserved with 100% integrity. |
| **Database Global** | Unpaid Invoices | **2,226** | Existing open invoices. |
| **Database Global** | Partial Invoices | **1** | Existing partial invoice. |
| **Database Global** | Active Invoices Subtotal | **9,566** | `7,339 (Paid) + 2,226 (Unpaid) + 1 (Partial) = 9,566`. |
| **Database Global** | Cancelled Invoices | **149** | `9,566 + 149 = 9,715`. |
| **Dry-Run Scope** | Invoices Skipped | **9,471** | Valid active slots matching the 87 active setups. |
| **Dry-Run Scope** | Simulated New | **164** | Arising from Setups 90, 91, and 92 (explained below). |
| **Dry-Run Scope** | Database Mutations | **0** | Verified dry-run safety. |

### 2.4. Discovery: Setup 90, 91, and 92 Anomalies in Play Class
During the full-year audit, we identified why 164 invoices were simulated as missing:
1. **Setup ID 6:** Class Play, `Monthly Fee`, `fee_month = 'Monthly'`, Amount 650. (This covers all 12 months for Play).
2. **Setup ID 90:** Class Play, `Monthly Fee`, `fee_month = NULL`, Amount 650. (Created 2026-09-20). Because `fee_month` is NULL, the system treated it as a One-Time fee in January, simulating 56 extra invoices.
3. **Setup ID 91:** Class Play, `Admission Fee`, `fee_month = NULL`, Amount 1000. (Created 2026-10-20). Simulating 54 invoices in January.
4. **Setup ID 92:** Class Play, `Monthly Fee`, `fee_month = 'December'`, Amount 650. (Created 2026-11-01). Because Setup 6 already covers December, this second December setup simulated 54 extra invoices.

> [!NOTE]
> Setups 90 and 92 are redundant manual duplicate setups for Play. Deactivating or deleting Setups 90 and 92 will immediately align the expected count with the actual database count!

---

## 3. Fine and Others Policy Verification

### 3.1. Database Investigation
- **Category 9 (`Fine`):** Total setups = **0**, Active setups = **0**, Existing invoices = **0**.
- **Category 10 (`Others`):** Total setups = **0**, Active setups = **0**, Existing invoices = **0**.
- **UI Inspection:** The application has no automated disciplinary fine logic or event-triggered fine tables.

### 3.2. Generation Engine Protection
In `FeeGenerationService`, generation iterates strictly over `FeeSetup::where('session_year_id', $session->id)->where('status', 'Active')`.
Because no active setups exist for `Fine` or `Others`, **zero invoices can be generated**.

### 3.3. Safeguard Recommendation
To guarantee that an administrator who sets up a manual fine for an individual student never triggers a class-wide automated billing run:
- Add an explicit exclusion in `FeeGenerationService`:
  ```php
  // Exclude manual / event-based categories from automated universal background generation
  $setupQuery->whereHas('category', function ($cq) {
      $cq->whereNotIn(DB::raw('LOWER(TRIM(name))'), ['fine', 'others']);
  });
  ```
- This ensures `Fine` and `Others` remain 100% manual.

---

## 4. aaPanel Cron Task Verification

### 4.1. Comparison of Cron Configurations from Provided Artifacts

| Attribute | `MACS School Laravel Schedule` (Image 2) | `MACS SMS Scheduler` (Image 3) |
| :--- | :--- | :--- |
| **Command** | `cd /www/wwwroot/macs.injaazh.com && /www/server/php/83/bin/php artisan schedule:run >> .../cron.log 2>&1` | `/www/server/php/83/bin/php /www/wwwroot/macs.injaazh.com/artisan schedule:run >> /dev/null 2>&1` |
| **Cycle** | Every 1 minute | Every 1 minute |
| **Invoked Command** | `schedule:run` | `schedule:run` |
| **Last Execute Time** | **2026-11-06 20:57:57** (Active) | **2026-10-22 18:24:02** (Stale / Stopped) |
| **Logging** | Logged to `/storage/logs/cron.log` | Discarded (`/dev/null`) |

### 4.2. Codebase Schedule Definition
In [`routes/console.php`](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/routes/console.php#L10-L14):
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:resolve-missing-out-punches')->dailyAt('23:59')->timezone('Asia/Dhaka');
Schedule::command('fees:auto-generate')->dailyAt('00:05')->timezone('Asia/Dhaka');
Schedule::command('sms:dispatch-scheduled')->everyMinute()->withoutOverlapping();
```

### 4.3. Technical Assessment
1. **Identical Executions**: Both aaPanel tasks run `artisan schedule:run`. Neither of them runs an isolated SMS command.
2. **SMS Dispatch Already Scheduled**: `sms:dispatch-scheduled` is already registered inside Laravel's native schedule and runs every minute with `->withoutOverlapping()`.
3. **Single Scheduler Best Practice**: Running `MACS School Laravel Schedule` every minute automatically handles SMS dispatch, daily fee auto-generation, and missing punch resolution in one unified, logged process.
4. **Conclusion**: Keeping `MACS SMS Scheduler` disabled/stopped is **100% safe, verified, and correct**. It does not disable SMS dispatching.

---

## 5. Summary of Status & Recommended Next Steps

### Overall Project Status:
**`READY AFTER SPECIFIED CORRECTIONS`**  
*(Production Deployment should remain paused until the two adjustments below are approved).*

### Required Concrete Adjustments:
1. **Admission Fee Policy**:
   - Exclude `Admission Fee` from bulk automated session generation (`fees:auto-generate`).
   - Restrict Admission Fee invoicing strictly to the student admission event (`StudentController::store`) for newly registered students.
2. **Fine & Others Safeguard**:
   - Add explicit exclusion of `fine` and `others` categories from automatic background batch loops.
3. **Play Class Setup Cleanup**:
   - Review and deactivate redundant Setup IDs 90 and 92 in the Admin UI so Play class expects exactly 12 monthly fee invoices.

---

*Report prepared strictly according to codebase inspection and non-destructive dry-run testing.*

