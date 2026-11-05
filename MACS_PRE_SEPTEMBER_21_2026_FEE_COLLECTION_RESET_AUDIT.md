# MACS School Management System — Pre-September 21, 2026 Fee Collection Reset Audit

**Document Version:** 2.0.0 (Revised Cutoff Audit)  
**Audit Date:** November 04, 2026  
**System Environment:** Laravel 11.x | PHP 8.4.3 | MySQL 8.4.3 | Timezone: `Asia/Dhaka`  
**Database Name:** `macs_school` (Refreshed from `macsinjaazhdb.sql` on 2026-11-04 06:01 AM UTC)  
**Safety Backup:** `scratch/macs_school_backup_before_20261104.sql` (11.6 MB)  
**Revised Cutoff Policy:** **`payment_date < '2026-09-21 00:00:00'` (Asia/Dhaka)**  
**Execution Mode:** **STRICT READ-ONLY RE-AUDIT & RECONCILIATION (ZERO DATA MODIFIED)**  

---

## 1. Executive Summary & Revised Cutoff Confirmation

### 1.1 Policy Revision Confirmation
The school authority of MACS School has explicitly confirmed that **all fee collection transactions recorded throughout the entire day of September 20, 2026 are also experimental and must be included in the reset scope.**

- **Superseded Policy:** Collections recorded strictly before September 20, 2026 (i.e. through September 19, 2026 inclusive).
- **Authoritative Revised Policy:** Collections recorded **prior to September 21, 2026 00:00:00 Asia/Dhaka** (i.e., through September 20, 2026 23:59:59 inclusive) are classified as experimental and marked for reset.
- **Authoritative Preserved Scope:** All fee collection transactions with `payment_date >= '2026-09-21 00:00:00'` represent legitimate, active collections and must remain **100% untouched and preserved**.

### 1.2 Core Audit Key Metrics (Revised)

| Metric Category | Complete Database Total | Revised Reset Scope (`< 2026-09-21`) | Preserved Active Scope (`>= 2026-09-21`) | Audit Classification |
| :--- | :--- | :--- | :--- | :--- |
| **Total Payment Records (`fee_payments`)** | 7,166 rows | **44 rows** (IDs: 2 to 47) | 7,122 rows (IDs: 48 to 7169) | `VERIFIED IN DATABASE` |
| **Total Collection Amount (`paid_amount`)** | ৳3,878,890.00 | **৳27,600.00** | **৳3,851,290.00** | `VERIFIED IN DATABASE` |
| **Total Invoices Affected (`fee_invoices`)** | 9,188 rows | **44 invoices** | 7,122 paid invoices | `VERIFIED IN DATABASE` |
| **Total Students Affected** | 543 students | **13 students** | 517 students | `VERIFIED IN DATABASE` |
| **Gross Invoiced Amount (`amount`)** | ৳5,749,500.00 | ৳28,050.00 (on affected 44) | ৳5,721,450.00 | `VERIFIED IN DATABASE` |
| **Discounts / Waivers (`discount`)** | ৳931,420.00 | ৳450.00 (on Invoices 658 & 660) | ৳930,970.00 | `VERIFIED IN DATABASE` |
| **Net Invoiced Amount (`net_amount`)** | ৳4,818,080.00 | ৳27,600.00 (on affected 44) | ৳4,790,480.00 | `VERIFIED IN DATABASE` |
| **Current Outstanding Due (`due_amount`)** | ৳868,000.00 | ৳0.00 (currently marked Paid) | ৳868,000.00 | `VERIFIED IN DATABASE` |
| **Recalculated Due After Reset** | ৳895,600.00 | **৳27,600.00** | ৳868,000.00 | `VERIFIED IN DATABASE` |
| **Split Invoices (Pre- & Post-Cutoff)** | 0 invoices | **0 invoices** | 0 invoices | `VERIFIED IN DATABASE` |
| **Payment vs. Invoice Balance Mismatch** | ৳0.00 (0 mismatch) | **৳0.00 (0 mismatch)** | ৳0.00 (0 mismatch) | `VERIFIED IN DATABASE` |
| **Affected Fee Categories** | 6 active categories | **3 Categories** (Monthly, Term Exam, Monthly Exam) | All categories | `VERIFIED IN DATABASE` |
| **Affected Academic Classes** | 11 classes | **5 Classes** (Play, One, Five, Nine, Ten) | All 11 classes | `VERIFIED IN DATABASE` |
| **Affected Academic Branches** | 1 Branch (Jalalpur) | **1 Branch (Jalalpur Branch)** | Jalalpur Branch | `VERIFIED IN DATABASE` |

---

## 2. Comparative Analysis: Previous Audit vs. Revised Audit

The expansion of the cutoff date to include September 20, 2026 incorporates all collections recorded on that day. The delta and revised totals are compared below:

| Metric | Previous Audit (Pre-Sept 20 Cutoff) | September 20, 2026 Delta (Newly Added) | Revised Audit (Pre-Sept 21 Cutoff) |
| :--- | :---: | :---: | :---: |
| **Cutoff Rule** | `payment_date < '2026-09-20'` | `payment_date = '2026-09-20'` | `payment_date < '2026-09-21'` |
| **Payment IDs Affected** | IDs 2 to 21 (18 rows) | IDs 22 to 47 (26 rows) | **IDs 2 to 47 (44 rows)** |
| **Total Reset Amount** | ৳14,200.00 | ৳13,400.00 | **৳27,600.00** |
| **Affected Invoices** | 18 invoices | 26 invoices | **44 invoices** |
| **Affected Students** | 11 students | 2 students (`25110P00006`, `25110P00007`) | **13 students** |
| **Affected Categories** | 1 Category (Monthly Fee) | 2 Additional (Term Exam, Monthly Exam) | **3 Categories** |
| **Master Receipts Involved** | 4 bulk + 6 single (10 receipts) | 2 bulk master receipts (`REC-20260920-3452`, `REC-20260920-1005`) | **12 receipts** (6 bulk + 6 single) |
| **Preserved Valid Payments** | 7,148 payments (৳3,864,690.00) | -26 payments (-৳13,400.00) | **7,122 payments (৳3,851,290.00)** |
| **Preserved Paid Invoices** | 7,321 Paid (post-reset est.) | -26 Paid invoices | **7,295 Paid invoices** |
| **Unpaid Invoices (post-reset)** | 1,717 Unpaid (post-reset est.) | +26 Unpaid invoices | **1,743 Unpaid invoices** |

*Verification:*  
- Payment ID 22 (`REC-20260920-3452-4423`, ৳650.00) through Payment ID 47 (`REC-20260920-1005-664`, ৳650.00), totaling 26 payments and ৳13,400.00, are now fully incorporated into the reset scope.  
- *Classification:* `VERIFIED IN DATABASE`

---

## 3. Revised Cutoff Boundary & Sequential Integrity

An exhaustive SQL query verified the boundary between September 20 and September 21, 2026:

```sql
SELECT id, receipt_no, student_id, fee_invoice_id, paid_amount, payment_date, created_at
FROM fee_payments
WHERE id BETWEEN 45 AND 50
ORDER BY id ASC;
```

### Boundary Verification Results:

```
[Pre-Cutoff / Reset Scope]
ID: 45 | Receipt: REC-20260920-1005-5016  | Paid: ৳150.00 | PayDate: 2026-09-20 | Created: 2026-09-20 08:45:29
ID: 46 | Receipt: REC-20260920-1005-766   | Paid: ৳650.00 | PayDate: 2026-09-20 | Created: 2026-09-20 08:45:29
ID: 47 | Receipt: REC-20260920-1005-664   | Paid: ৳650.00 | PayDate: 2026-09-20 | Created: 2026-09-20 08:45:29 (LAST RESET CANDIDATE)
------------------------------------------------------------------------------------------------------------------
[Post-Cutoff / 100% Preserved Scope]
ID: 48 | Receipt: REC-20260921-1403-10923 | Paid: ৳250.00 | PayDate: 2026-09-21 | Created: 2026-09-21 07:34:47 (FIRST PRESERVED RECORD)
ID: 49 | Receipt: REC-20260921-1403-3888  | Paid: ৳650.00 | PayDate: 2026-09-21 | Created: 2026-09-21 07:34:47
ID: 50 | Receipt: REC-20260921-1403-128   | Paid: ৳700.00 | PayDate: 2026-09-21 | Created: 2026-09-21 07:34:47
```

### Timestamp Cross-Check:
- `payment_date < '2026-09-21' AND DATE(created_at) >= '2026-09-21'`: **0 records**
- `payment_date >= '2026-09-21' AND DATE(created_at) < '2026-09-21'`: **0 records**
- **Conclusion:** The sequential integrity across the cutoff boundary is 100% clean. Every payment ID from 2 to 47 belongs strictly to the pre-September 21 scope. Every payment ID from 48 to 7169 belongs strictly to the preserved post-September 21 scope.
- *Classification:* `VERIFIED IN DATABASE`

---

## 4. Complete Roster of the 44 Reset Candidate Payments & Invoices

Every single transaction in the reset candidate scope is cataloged below:

| P.ID | Receipt No | Payment Date | Student ID | Student Name | Class | Fee Category | Billing Period | Inv ID | Gross (৳) | Disc (৳) | Net (৳) | Paid (৳) | Recalc Due (৳) | Recalc Status |
| :---: | :--- | :---: | :---: | :--- | :--- | :--- | :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| **2** | `REC-20260802-3363` | 2026-08-02 | `23120P00092` | Moriom Akhter Sinha | One | Monthly Fee | 2026-03 | 31 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **4** | `REC-20260803-2218` | 2026-08-03 | `26020100136` | Riyad Al Hasan | One | Monthly Fee | 2026-03 | 75 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **5** | `REC-20260803-1206-32` | 2026-08-03 | `23110P00093` | Morsalin Ahmed | One | Monthly Fee | 2026-03 | 32 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **7** | `REC-20260803-5181-135` | 2026-08-03 | `25120P00015` | Md. Aryan Mridha | Play | Monthly Fee | 2026-03 | 135 | 700.00 | 0.00 | 700.00 | 700.00 | **700.00** | **Unpaid** |
| **8** | `REC-20260803-3474` | 2026-08-03 | `25110P00001` | Md. Tahasan Mahmud | Play | Monthly Fee | 2026-03 | 121 | 700.00 | 0.00 | 700.00 | 700.00 | **700.00** | **Unpaid** |
| **9** | `REC-20260827-8354-357` | 2026-08-27 | `23010200282` | Nurjahan Tihan | Five | Monthly Fee | 2026-01 | 357 | 800.00 | 0.00 | 800.00 | 800.00 | **800.00** | **Unpaid** |
| **10** | `REC-20260827-8354-995` | 2026-08-27 | `23010200282` | Nurjahan Tihan | Five | Monthly Fee | 2026-02 | 995 | 800.00 | 0.00 | 800.00 | 800.00 | **800.00** | **Unpaid** |
| **11** | `REC-20260827-8354-1431` | 2026-08-27 | `23010200282` | Nurjahan Tihan | Five | Monthly Fee | 2026-03 | 1431 | 800.00 | 0.00 | 800.00 | 800.00 | **800.00** | **Unpaid** |
| **12** | `REC-20260827-8354-1968` | 2026-08-27 | `23010200282` | Nurjahan Tihan | Five | Monthly Fee | 2026-04 | 1968 | 800.00 | 0.00 | 800.00 | 800.00 | **800.00** | **Unpaid** |
| **13** | `REC-20260827-8354-2505` | 2026-08-27 | `23010200282` | Nurjahan Tihan | Five | Monthly Fee | 2026-05 | 2505 | 800.00 | 0.00 | 800.00 | 800.00 | **800.00** | **Unpaid** |
| **14** | `REC-BULK-20260831-6602-658` | 2026-08-31 | `25110P00001` | Md. Tahasan Mahmud | Play | Monthly Fee | 2026-01 | 658 | 650.00 | 200.00 | 450.00 | 450.00 | **450.00** | **Unpaid** |
| **15** | `REC-BULK-20260831-6602-659` | 2026-08-31 | `25110P00002` | Jannatul Mawa | Play | Monthly Fee | 2026-01 | 659 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **16** | `REC-BULK-20260831-2996-660` | 2026-08-31 | `25110P00003` | Md. Hussain Ahmed | Play | Monthly Fee | 2026-01 | 660 | 650.00 | 250.00 | 400.00 | 400.00 | **400.00** | **Unpaid** |
| **17** | `REC-BULK-20260831-2996-661` | 2026-08-31 | `25110P00004` | Hassan Ahmed | Play | Monthly Fee | 2026-01 | 661 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **18** | `REC-20260907-4270-589` | 2026-09-07 | `16010N00514` | Rohan Hussain | Ten | Monthly Fee | 2026-01 | 589 | 1,600.00 | 0.00 | 1,600.00 | 1,600.00 | **1,600.00** | **Unpaid** |
| **19** | `REC-20260907-4270-1227` | 2026-09-07 | `16010N00514` | Rohan Hussain | Ten | Monthly Fee | 2026-02 | 1227 | 1,600.00 | 0.00 | 1,600.00 | 1,600.00 | **1,600.00** | **Unpaid** |
| **20** | `REC-20260907-4270-1663` | 2026-09-07 | `16010N00514` | Rohan Hussain | Ten | Monthly Fee | 2026-03 | 1663 | 1,600.00 | 0.00 | 1,600.00 | 1,600.00 | **1,600.00** | **Unpaid** |
| **21** | `REC-20260919-4925` | 2026-09-19 | `22010500496` | Mst. Sumaiya Khatun | Nine | Monthly Fee | 2026-01 | 571 | 1,100.00 | 0.00 | 1,100.00 | 1,100.00 | **1,100.00** | **Unpaid** |
| **22** | `REC-20260920-3452-4423` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-08 | 4423 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **23** | `REC-20260920-3452-10921` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Term Exam Fee | August | 10921 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **24** | `REC-20260920-3452-3886` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-07 | 3886 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **25** | `REC-20260920-3452-126` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-03 | 126 | 700.00 | 0.00 | 700.00 | 700.00 | **700.00** | **Unpaid** |
| **26** | `REC-20260920-3452-9837` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Exam Fee | July | 9837 | 150.00 | 0.00 | 150.00 | 150.00 | **150.00** | **Unpaid** |
| **27** | `REC-20260920-3452-3349` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-06 | 3349 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **28** | `REC-20260920-3452-2812` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-05 | 2812 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **29** | `REC-20260920-3452-2275` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-04 | 2275 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **30** | `REC-20260920-3452-10379` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Term Exam Fee | April | 10379 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **31** | `REC-20260920-3452-1738` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-03 | 1738 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **32** | `REC-20260920-3452-5015` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Exam Fee | March | 5015 | 150.00 | 0.00 | 150.00 | 150.00 | **150.00** | **Unpaid** |
| **33** | `REC-20260920-3452-765` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-02 | 765 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **34** | `REC-20260920-3452-663` | 2026-09-20 | `25110P00006` | Salman Sahir | Play | Monthly Fee | 2026-01 | 663 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **35** | `REC-20260920-1005-4424` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-08 | 4424 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **36** | `REC-20260920-1005-10922` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Term Exam Fee | August | 10922 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **37** | `REC-20260920-1005-3887` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-07 | 3887 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **38** | `REC-20260920-1005-127` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-03 | 127 | 700.00 | 0.00 | 700.00 | 700.00 | **700.00** | **Unpaid** |
| **39** | `REC-20260920-1005-9838` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Exam Fee | July | 9838 | 150.00 | 0.00 | 150.00 | 150.00 | **150.00** | **Unpaid** |
| **40** | `REC-20260920-1005-3350` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-06 | 3350 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **41** | `REC-20260920-1005-2813` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-05 | 2813 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **42** | `REC-20260920-1005-2276` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-04 | 2276 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **43** | `REC-20260920-1005-10380` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Term Exam Fee | April | 10380 | 250.00 | 0.00 | 250.00 | 250.00 | **250.00** | **Unpaid** |
| **44** | `REC-20260920-1005-1739` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-03 | 1739 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **45** | `REC-20260920-1005-5016` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Exam Fee | March | 5016 | 150.00 | 0.00 | 150.00 | 150.00 | **150.00** | **Unpaid** |
| **46** | `REC-20260920-1005-766` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-02 | 766 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **47** | `REC-20260920-1005-664` | 2026-09-20 | `25110P00007` | Mst. Saima Akhter Shobha | Play | Monthly Fee | 2026-01 | 664 | 650.00 | 0.00 | 650.00 | 650.00 | **650.00** | **Unpaid** |
| **TOTAL** | — | — | — | — | — | — | — | — | **৳28,050.00** | **৳450.00** | **৳27,600.00** | **৳27,600.00** | **৳27,600.00** | **44 Unpaid** |

*Verification:*  
- Zero split payments exist among these 44 invoices.  
- The 2 invoices with legitimate waivers (Inv #658: ৳200 discount; Inv #660: ৳250 discount) preserve their net amounts (৳450.00 and ৳400.00 respectively).  
- All 44 invoices return cleanly to status `'Unpaid'` with `due_amount = net_amount`.  
- *Classification:* `VERIFIED IN DATABASE`

---

## 5. Class-Wise and Category-Wise Breakdown

### 5.1 Class-Wise Impact Distribution

| Class Name | Academic Order | Payments Count | Affected Students | Invoices Affected | Total Collected (৳) |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Play** | 1 | 32 | 7 | 32 | ৳16,950.00 |
| **One** | 3 | 3 | 3 | 3 | ৳750.00 |
| **Five** | 7 | 5 | 1 | 5 | ৳4,000.00 |
| **Nine** | 9 | 1 | 1 | 1 | ৳1,100.00 |
| **Ten** | 10 | 3 | 1 | 3 | ৳4,800.00 |
| **Total** | — | **44** | **13** | **44** | **৳27,600.00** |

### 5.2 Category-Wise Impact Distribution

| Fee Category | Payments Count | Total Collected (৳) | Notes |
| :--- | :---: | :---: | :--- |
| **Monthly Fee** | 36 | ৳26,000.00 | Regular monthly tuition billings across 5 classes |
| **Term Exam Fee** | 4 | ৳1,000.00 | 2 Term Exams (April & August) for Salman Sahir & Saima Akhter |
| **Monthly Exam Fee** | 4 | ৳600.00 | 2 Monthly Exams (March & July) for Salman Sahir & Saima Akhter |
| **Total** | **44** | **৳27,600.00** | — |

*Classification:* `VERIFIED IN DATABASE`

---

## 6. The 13 Affected Students Roster & Protection of Their Post-Cutoff Payments

A comprehensive inspection of the 13 affected students was conducted to confirm the exact balance between their reset candidate payments and their preserved legitimate collections:

| Student ID | Student Identity | Student Name | Class | Reset Candidate Payments Count | Reset Candidate Amount (৳) | Preserved Post-Cutoff Payments Count | Preserved Post-Cutoff Amount (৳) | Protection Status |
| :---: | :---: | :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| 32 | `25110P00001` | Md. Tahasan Mahmud | Play | 2 | ৳1,150.00 | 0 | ৳0.00 | Invoices reset to Unpaid |
| 33 | `25110P00002` | Jannatul Mawa | Play | 1 | ৳650.00 | 14 | ৳5,020.00 | **14 payments 100% Preserved** |
| 34 | `25110P00003` | Md. Hussain Ahmed | Play | 1 | ৳400.00 | 12 | ৳3,760.00 | **12 payments 100% Preserved** |
| 35 | `25110P00004` | Hassan Ahmed | Play | 1 | ৳650.00 | 12 | ৳6,050.00 | **12 payments 100% Preserved** |
| 37 | `25110P00006` | Salman Sahir | Play | 13 | ৳6,700.00 | 2 | ৳800.00 | **2 payments 100% Preserved** |
| 38 | `25110P00007` | Mst. Saima Akhter Shobha | Play | 13 | ৳6,700.00 | 2 | ৳820.00 | **2 payments 100% Preserved** |
| 46 | `25120P00015` | Md. Aryan Mridha | Play | 1 | ৳700.00 | 14 | ৳3,650.00 | **14 payments 100% Preserved** |
| 123 | `23120P00092` | Moriom Akhter Sinha | One | 1 | ৳250.00 | 14 | ৳2,920.00 | **14 payments 100% Preserved** |
| 124 | `23110P00093` | Morsalin Ahmed | One | 1 | ৳250.00 | 14 | ৳7,420.00 | **14 payments 100% Preserved** |
| 167 | `26020100136` | Riyad Al Hasan | One | 1 | ৳250.00 | 14 | ৳4,630.00 | **14 payments 100% Preserved** |
| 314 | `23010200282` | Nurjahan Tihan | Five | 5 | ৳4,000.00 | 10 | ৳4,260.00 | **10 payments 100% Preserved** |
| 527 | `22010500496` | Mst. Sumaiya Khatun | Nine | 1 | ৳1,100.00 | 13 | ৳10,440.00 | **13 payments 100% Preserved** |
| 545 | `16010N00514` | Rohan Hussain | Ten | 3 | ৳4,800.00 | 11 | ৳4,960.00 | **11 payments 100% Preserved** |
| **Total** | — | — | — | **44** | **৳27,600.00** | **132** | **৳54,730.00** | **৳54,730.00 Protected** |

> [!IMPORTANT]
> **Critical Finding on Salman Sahir & Saima Akhter:**  
> Both Salman Sahir and Mst. Saima Akhter Shobha made 2 legitimate fee payments on/after September 21, 2026:
> - Salman Sahir: Payment ID 1363 (৳650.00) and Payment ID 1364 (৳150.00) on 2026-10-04.
> - Saima Akhter: Payment ID 1365 (৳650.00) and Payment ID 1366 (৳170.00) on 2026-10-04.  
> These 4 payments (totaling ৳1,620.00) are for October 2026 invoices and **will remain completely untouched**. Only their 26 September 20 payments will be reset.
> *Classification:* `VERIFIED IN DATABASE`

---

## 7. Re-Evaluation of the 10 Edge Cases under Revised Boundary

| # | Edge Case Scenario | Database Findings & Actual Data | Correct Handling & Safety Treatment | Audit Classification |
| :-: | :--- | :--- | :--- | :--- |
| **1** | **Invoice paid completely before cutoff** | Exactly **44 invoices** (৳27,600.00) fall into this category. None received subsequent payments. | Reset pre-cutoff payment. Set `paid_amount = 0.00`, `due_amount = net_amount`, `status = 'Unpaid'`. | `VERIFIED IN DATABASE` |
| **2** | **Invoice partially paid before cutoff** | **0 instances.** No pre-cutoff invoice was partially paid. All 44 were paid in full. | The recalculation formula natively supports partials: `due_amount = net_amount - valid_post_payments`. | `VERIFIED IN DATABASE` |
| **3** | **Split payments (before & on/after cutoff)** | **0 instances.** Zero invoices have payments spanning both sides of September 21, 2026. | Supported by formula: post-cutoff payment is preserved; invoice balance adjusts to reflect remaining post-cutoff balance. | `VERIFIED IN DATABASE` |
| **4** | **Bulk receipt covering multiple invoices** | **6 master receipts** pre-cutoff cover 38 invoices: 4 from pre-Sept 20 (12 inv) + 2 from Sept 20 (`REC-20260920-3452` [13] and `REC-20260920-1005` [13]). **Zero receipts cross cutoff.** | All items under these 6 receipts are pre-cutoff. Archiving clears the entire experimental bulk receipt cleanly. | `VERIFIED IN DATABASE` |
| **5** | **Advance or unallocated payments** | **0 instances.** Zero payments with `fee_invoice_id IS NULL` or `0`. Zero payments exceeding invoice `net_amount`. | Every payment in MACS is strictly bound 1:1 to a specific invoice. No floating credit exists. | `VERIFIED IN DATABASE` |
| **6** | **Refunds, negative payments, reversals** | **0 instances.** Zero payments with `paid_amount <= 0`. Zero notes containing "refund", "reversal", or "void". | No legacy reversal records exist to complicate the reset. | `VERIFIED IN DATABASE` |
| **7** | **Cancelled receipts with existing invoice allocation** | 149 invoices have `status = 'Cancelled'`. **0 payments** are linked to them (৳0.00). | Cancelled invoices are already zero-balance and completely unlinked from payments. Unaffected. | `VERIFIED IN DATABASE` |
| **8** | **Invoices with discounts / waivers** | Across the 44 target invoices, **2 invoices** have discounts: Inv #658 (৳200 waiver) and Inv #660 (৳250 waiver). | **Preserve original discount.** Net billing obligation remains ৳450 and ৳400 respectively. | `VERIFIED IN DATABASE` |
| **9** | **Invoice created before cutoff but collected after** | **5,326 invoices** were generated before Sept 21 but collected on or after Sept 21. | **DO NOT FILTER BY INVOICE DATE.** The reset filter MUST strictly use `fee_payments.payment_date < '2026-09-21'`. | `VERIFIED IN DATABASE` |
| **10** | **Payment created before cutoff but posted after (or vice versa)** | **0 instances.** `payment_date` and `DATE(created_at)` are 100% aligned with respect to the September 21 cutoff boundary. | `payment_date < '2026-09-21'` cleanly isolates all 44 records without date edge discrepancies. | `VERIFIED IN DATABASE` |

---

## 8. Accounting, Financial Statements & Reporting Impact

### 8.1 Financial Statements (Income vs. Expense)
- **Controller:** [FinancialReportController.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FinancialReportController.php#L58-L75)
- **Query:** `FeePayment::whereBetween('payment_date', [$startDate, $endDate])->sum('paid_amount')`
- **Impact:**
  - Reports executed for periods prior to September 21, 2026 will drop from ৳27,600.00 to **৳0.00** (reflecting zero real cash collections).
  - Reports executed from September 21, 2026 onward will show **exactly ৳3,851,290.00** (100% reconciled and preserved).

### 8.2 Collections & Dues Report
- **Controller:** [FeeReportController.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Http/Controllers/FeeReportController.php#L21-L61)
- **Impact:**
  - Active collections drop by 44 transactions.
  - Active outstanding dues increase by ৳27,600.00. The 44 invoices immediately appear in the Dues Report ready for fresh collections by administrative staff.

### 8.3 Class-Wise Financial Report
- **Service:** [ClassWiseFinancialReportService.php](file:///d:/Running%20Works/04.%20Clients%20Back-end%20Projects%20-%202025/PHP%20-%20Laravel/MACS%20School/macs-school/app/Services/ClassWiseFinancialReportService.php#L129-L138)
- **Impact:**
  - Total Due (`net_amount`) across all classes remains completely unchanged.
  - Class Play collected amount drops by ৳16,950.00; Class One by ৳750.00; Class Five by ৳4,000.00; Class Nine by ৳1,100.00; Class Ten by ৳4,800.00.
  - Outstanding amounts for these classes increase by the exact same amounts.

---

## 9. Dry-Run Simulation Verification Results

An ACID transaction simulation with immediate rollback was executed against the database to test the revised reset:

```
BEFORE REVISED RESET:
  Total Payments: 7,166 (৳3,878,890.00)
  Invoices Paid Amount: ৳3,878,890.00
  Invoices Due Amount:  ৳868,000.00
  Status Counts: Paid=7,339, Unpaid=1,699, Partial=1

SIMULATING RESET OF 44 PAYMENTS ON 44 INVOICES...

AFTER REVISED RESET:
  Total Payments: 7,122 (৳3,851,290.00)
  Invoices Paid Amount: ৳3,851,290.00
  Invoices Due Amount:  ৳895,600.00
  Status Counts: Paid=7,295, Unpaid=1,743, Partial=1

RECONCILIATION VERIFICATION:
  Payment reduction:      ৳27,600.00 (Expected: ৳27,600.00)
  Invoice paid reduction: ৳27,600.00 (Expected: ৳27,600.00)
  Invoice due increase:   ৳27,600.00 (Expected: ৳27,600.00)
  Unpaid invoices diff:   +44 (Expected: +44)
  Paid invoices diff:     -44 (Expected: -44)
  Total Invoices with Payment Mismatch: 0 (Must be 0)

SIMULATION ROLLED BACK CLEANLY. ZERO DATABASE CHANGES.
```

*Verification:*  
- Mathematical reconciliation matches to the exact cent.  
- Zero data was modified on disk.  
- *Classification:* `VERIFIED IN DATABASE`

---

## 10. Recommended Reset Methodology & Step-by-Step Execution Plan

When explicit authorization is granted by the user, the execution will follow this strict 4-step sequence:

### Step 1: Pre-Execution Safety Snapshot
Create isolated backup dumps of the two affected tables before any command is executed:
```bash
mysqldump -u root -p macs_school fee_payments > scratch/pre_reset_fee_payments_20261104.sql
mysqldump -u root -p macs_school fee_invoices > scratch/pre_reset_fee_invoices_20261104.sql
```

### Step 2: Dedicated Archive Table Creation
Create table `fee_payments_pre_20260921_archive` matching `fee_payments` schema and copy the 44 records:
```sql
CREATE TABLE IF NOT EXISTS fee_payments_pre_20260921_archive LIKE fee_payments;
INSERT INTO fee_payments_pre_20260921_archive 
SELECT * FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00';
```

### Step 3: Atomic Reset & Balance Recalculation (Inside Single Transaction)
```sql
START TRANSACTION;

-- 1. Remove the 44 archived payments from active table
DELETE FROM fee_payments WHERE payment_date < '2026-09-21 00:00:00';

-- 2. Authoritatively recalculate the 44 affected invoices
UPDATE fee_invoices i
LEFT JOIN (
    SELECT fee_invoice_id, COALESCE(SUM(paid_amount), 0) as total_valid_paid
    FROM fee_payments
    WHERE payment_date >= '2026-09-21 00:00:00'
    GROUP BY fee_invoice_id
) p ON i.id = p.fee_invoice_id
SET 
    i.paid_amount = COALESCE(p.total_valid_paid, 0.00),
    i.due_amount = i.net_amount - COALESCE(p.total_valid_paid, 0.00),
    i.status = CASE 
        WHEN COALESCE(p.total_valid_paid, 0.00) = 0.00 THEN 'Unpaid'
        WHEN (i.net_amount - COALESCE(p.total_valid_paid, 0.00)) <= 0.00 THEN 'Paid'
        ELSE 'Partial'
    END,
    i.updated_at = NOW()
WHERE i.id IN (
    31, 32, 75, 121, 126, 127, 135, 357, 571, 589, 658, 659, 660, 661, 663, 664, 
    765, 766, 995, 1227, 1431, 1663, 1738, 1739, 1968, 2275, 2276, 2505, 2812, 
    2813, 3349, 3350, 3886, 3887, 4423, 4424, 5015, 5016, 9837, 9838, 10379, 
    10380, 10921, 10922
);

COMMIT;
```

### Step 4: Post-Reset Authoritative Verification
Verify that:
1. `SELECT COUNT(*), SUM(paid_amount) FROM fee_payments` returns exactly **7,122 rows and ৳3,851,290.00**.
2. `SELECT SUM(paid_amount) FROM fee_invoices` returns exactly **৳3,851,290.00**.
3. Invoices with payment mismatch count equals **0**.

---

## 11. Instant Rollback Capability

Because all 44 records will be archived in `fee_payments_pre_20260921_archive`, a complete rollback can be executed in under 1 second if ever requested:

```sql
START TRANSACTION;
INSERT INTO fee_payments SELECT * FROM fee_payments_pre_20260921_archive;
UPDATE fee_invoices i
JOIN (
    SELECT fee_invoice_id, SUM(paid_amount) as total_paid
    FROM fee_payments
    GROUP BY fee_invoice_id
) p ON i.id = p.fee_invoice_id
SET 
    i.paid_amount = p.total_paid,
    i.due_amount = i.net_amount - p.total_paid,
    i.status = CASE WHEN i.net_amount - p.total_paid <= 0 THEN 'Paid' ELSE 'Partial' END
WHERE i.id IN (
    31, 32, 75, 121, 126, 127, 135, 357, 571, 589, 658, 659, 660, 661, 663, 664, 
    765, 766, 995, 1227, 1431, 1663, 1738, 1739, 1968, 2275, 2276, 2505, 2812, 
    2813, 3349, 3350, 3886, 3887, 4423, 4424, 5015, 5016, 9837, 9838, 10379, 
    10380, 10921, 10922
);
COMMIT;
```

---

## 12. Pre-Execution Verification Checklist

- [x] **Criterion 1: Full Database Backup Exists & Verified** (`scratch/macs_school_backup_before_20261104.sql`)
- [x] **Criterion 2: Revised Cutoff Boundary Confirmed (`< 2026-09-21 00:00:00`)**
- [x] **Criterion 3: Exact Payment Count & Amount Established (44 payments, ৳27,600.00)**
- [x] **Criterion 4: Sequential ID Boundary Verified (IDs 2 to 47 pre-cutoff; IDs 48 to 7169 preserved)**
- [x] **Criterion 5: Zero Split Payments Confirmed across Cutoff**
- [x] **Criterion 6: Post-Cutoff Legitimate Payments Safeguarded (7,122 payments, ৳3,851,290.00)**
- [x] **Criterion 7: Discount & Waiver Preservation Confirmed (৳450.00 preserved on Inv #658 & #660)**
- [x] **Criterion 8: Dry-Run Simulation Completed with Zero Mismatch**
- [ ] **Criterion 9: Explicit Written Approval Received from User to Execute Reset**

---

### Audit Sign-off
**Audit Status:** Revised Re-Audit Successfully Completed.  
**Mode Compliance:** 100% Strict Read-Only. No rows modified or deleted.  
**Next Step:** Await explicit user review and written instruction before running any modifying reset script.

