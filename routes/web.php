<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SessionYearController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\AdmitCardController;
use App\Http\Controllers\SeatPlanController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\ClassRoutineController;
use App\Http\Controllers\ExamRoutineController;
use App\Http\Controllers\FeeSetupController;
use App\Http\Controllers\FeeCollectionController;
use App\Http\Controllers\FeeInvoiceController;
use App\Http\Controllers\FeeReportController;

// Custom Login Page Override
Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

// ২. Tyro Dashboard এর Error ফিক্স করার জন্য এই রাউটটি যোগ করুন
Route::get('/tyro-login', function () {
    return redirect()->route('login');
})->name('tyro-login.login');

// ১. মেইন ইউআরএল এ গেলে ড্যাশবোর্ডে পাঠাবে
Route::get('/', function () {
    return redirect()->route('dashboard.dashboard');
});

// ২. ড্যাশবোর্ড রাউট (যা কন্ট্রোলার থেকে ডাটা নিয়ে আসবে)
Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'roles:editor,admin,super-admin'])
    ->name('dashboard.dashboard');

// ৩. প্যাকেজের /admin ইউআরএল ওভাররাইড করে ডাইরেক্ট স্কুল ড্যাশবোর্ড দেখাবে
Route::get('admin', [DashboardController::class, 'index'])
    ->middleware(['auth', 'roles:editor,admin,super-admin'])
    ->name('tyro-dashboard.index');

// Tyro Dashboard এর মিডলওয়্যার
Route::middleware(['auth', 'roles:editor, admin, super-admin'])->group(function () {
    
    // ১. এই রাউটটি আপনার পেজ (ভিউ) লোড করছে (এটি ঠিক আছে)
    Route::get('/classes', function () {
        return view('pages.classes.index');
    })->name('classes.index');

    // ২. এই রাউটটি ডাটা আদান-প্রদান করবে (এটি মিসিং থাকতে পারে)
    Route::apiResource('ajax/classes', ClassesController::class); 
});

Route::middleware(['auth', 'roles:editor, admin, super-admin'])->group(function () {
    
    // Section Management Routes
    Route::get('/sections', function () {
        return view('pages.sections.index');
    })->name('sections.index');

    Route::resource('ajax/sections', SectionController::class);
    
});

Route::middleware(['auth', 'roles:editor, admin, super-admin'])->group(function () {
    
    // Shift Management Routes
    Route::get('/shifts', function () {
        return view('pages.shifts.index');
    })->name('shifts.index');

    Route::resource('ajax/shifts', ShiftController::class);
    
});

Route::middleware(['auth', 'roles:editor, admin, super-admin'])->group(function () {
    
    // Session Year Management Routes
    Route::get('/sessions', function () {
        return view('pages.sessions.index');
    })->name('sessions.index');

    Route::resource('ajax/sessions', SessionYearController::class);
    
});

Route::middleware(['auth', 'roles:editor, admin, super-admin'])->group(function () {
    
    // Branch Management Routes
    Route::get('/branches', function () {
        return view('pages.branches.index');
    })->name('branches.index');

    Route::resource('ajax/branches', BranchController::class);
    
});

// =======================================================
// Student Management (Editor এবং Super Admin উভয়েই পাবে)
// =======================================================
Route::middleware(['auth', 'roles:editor,admin,super-admin'])->group(function () {

    // ---------------------------------------------------
    // Student Views (Prefix: /student, Name: student.)
    // ---------------------------------------------------
    Route::prefix('student')->name('student.')->group(function () {
        
        // ১. অ্যাডমিশন ফর্ম (GET: /student/admission)
        Route::get('/admission', function () {
            return view('pages.students.admission');
        })->name('admission');

        // ২. স্টুডেন্ট প্রোফাইল দেখা (GET: /student/view/{id})
        Route::get('/view/{id}', function ($id) {
            return view('pages.students.view', compact('id'));
        })->name('view');

        // ৩. স্টুডেন্ট প্রোফাইল এডিট (GET: /student/edit/{id})
        Route::get('/edit/{id}', function ($id) {
            return view('pages.students.edit', compact('id'));
        })->name('edit');

        
        Route::get('/promotion', function () {
                return view('pages.students.promotion');
            })->name('promotion');
        
    });

    // ---------------------------------------------------
    // Student List & AJAX API
    // ---------------------------------------------------
    
    // ৪. স্টুডেন্ট লিস্ট দেখার রাউট (GET: /students)
    Route::get('/students', function () {
        return view('pages.students.index');
    })->name('students.index');

    // 🚨 ৬. Custom AJAX রাউটগুলো অবশ্যই Resource এর ঠিক উপরে বসবে 🚨
    Route::get('/ajax/students/promotion-list', [StudentController::class, 'getStudentsForPromotion'])->name('students.promotion.list');
    Route::get('/ajax/students/detect', [StudentController::class, 'detectStudentInfo'])->name('students.detect');
    Route::post('/ajax/students/promote', [StudentController::class, 'promoteStudents'])->name('students.promote'); // (এটি মিসিং ছিল)
    Route::get('/ajax/students/export-excel', [StudentController::class, 'exportExcel'])->name('students.export.excel');
    Route::get('/ajax/students/export-pdf', [StudentController::class, 'exportPDF'])->name('students.export.pdf');

    // ৫. ডাটা সেভ, আপডেট, ডিলিট এবং রিড করার জন্য AJAX রাউট (API Resource)
    Route::resource('ajax/students', StudentController::class);


       /*
    |--------------------------------------------------------------------------
    | Teacher Management Routes
    |--------------------------------------------------------------------------
    */
    
    // ১. ফর্ম দেখানোর রাউট (GET)
    Route::get('/teacher/add', function () {
        return view('pages.teachers.create');
    })->name('teacher.add');

    // ২. শিক্ষক লিস্ট দেখার রাউট (GET) - ভবিষ্যতের জন্য
    Route::get('/teachers', function () {
        return view('pages.teachers.index');
    })->name('teachers.index');

    // ৩. ডাটা সেভ করার AJAX রাউট (API Resource)
    Route::resource('ajax/teachers', TeacherController::class);

    // Teacher View & Edit Pages
    Route::get('/teacher/view/{id}', function ($id) {
        return view('pages.teachers.view', compact('id'));
    })->name('teacher.view');

    Route::get('/teacher/edit/{id}', function ($id) {
        return view('pages.teachers.edit', compact('id'));
    })->name('teacher.edit');



});



// =======================================================
// Administration Routes (শুধুমাত্র Super Admin পাবে)
// =======================================================
Route::middleware(['auth', 'tyro-dashboard.admin'])->group(function () {

 

     /*
    |--------------------------------------------------------------------------
    | Subject Management Routes
    |--------------------------------------------------------------------------
    */

        // ১. নতুন সাবজেক্ট তৈরির ফর্ম দেখানোর রাউট (GET)
    Route::get('/subject/add', function () {
        // resources/views/pages/subjects/add.blade.php ফাইলটি লোড করবে
        return view('pages.subjects.add');
    })->name('subject.add');

    // ২. সাবজেক্ট লিস্ট দেখার রাউট (GET)
    Route::get('/subjects', function () {
        // resources/views/pages/subjects/index.blade.php ফাইলটি লোড করবে
        return view('pages.subjects.index');
    })->name('subjects.index');

    // ৩. ডাটা সেভ করার AJAX রাউট (API Resource)
    Route::resource('ajax/subjects', SubjectController::class);

    Route::get('/subjects/{id}/edit', [SubjectController::class, 'edit']);
    Route::put('/subjects/{id}', [SubjectController::class, 'update']);



    /*
    |--------------------------------------------------------------------------
    | Attendance Management Routes
    |--------------------------------------------------------------------------
    */

        // ১. ডেইলি হাজিরা দেওয়ার পেজ দেখানোর রাউট (GET)
    Route::get('/attendance', function () {
        // resources/views/pages/attendance/index.blade.php ফাইলটি লোড করবে
        return view('pages.attendance.index');
    })->name('attendance.index');

    // ২. নির্দিষ্ট ক্লাসের স্টুডেন্ট লিস্ট পাওয়ার AJAX রাউট (GET)
    // এটি ড্রপডাউন থেকে ক্লাস সিলেক্ট করলে ওই ক্লাসের স্টুডেন্টদের ডাটা নিয়ে আসবে
    Route::get('/ajax/attendance/students', [\App\Http\Controllers\AttendanceController::class, 'getStudents']);

    Route::get('/ajax/teachers', [\App\Http\Controllers\AttendanceController::class, 'getTeachers']);

    // ৩. হাজিরা ডাটাবেসে সেভ করার AJAX রাউট (POST)
    // এই রাউটটি একইসাথে অনেক স্টুডেন্টের হাজিরা (Bulk Attendance) প্রসেস করবে
    Route::post('/ajax/attendance/save', [\App\Http\Controllers\AttendanceController::class, 'store']);

    // হাজিরা রিপোর্ট দেখার পেজ
    Route::get('/attendance/report', [AttendanceController::class, 'reportIndex'])->name('attendance.report');

    // রিপোর্ট ডাটা ফিল্টার করার AJAX রাউট
    Route::get('/ajax/attendance/report-data', [AttendanceController::class, 'getReportData']);


 /*
    |--------------------------------------------------------------------------
    | ID CARD GENERATION Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/id-cards', [IdCardController::class, 'index'])->name('id-cards.index');
    Route::post('/id-cards/generate', [IdCardController::class, 'generatePDF'])->name('id-cards.generate');

    // সার্টিফিকেট ম্যানেজমেন্ট রাউটস
    Route::get('/certificates', [App\Http\Controllers\CertificateController::class, 'index'])->name('certificates.index');
    Route::post('/certificates/generate', [App\Http\Controllers\CertificateController::class, 'generate'])->name('certificates.generate');

    // Exam Routes
    Route::prefix('exams')->name('exams.')->group(function () {
        Route::get('/', [ExamController::class, 'index'])->name('index');
        Route::post('/store', [ExamController::class, 'store'])->name('store');
        Route::delete('/{id}', [ExamController::class, 'destroy'])->name('destroy');
    });

    // Grade Setup Routes
    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/', [GradeController::class, 'index'])->name('index');
        Route::post('/store', [GradeController::class, 'store'])->name('store');
        Route::delete('/{id}', [GradeController::class, 'destroy'])->name('destroy');
    });

    // Exam Schedule & Subject Setup Routes
    Route::prefix('exam-schedules')->name('exam-schedules.')->group(function () {
        Route::get('/', [ExamScheduleController::class, 'index'])->name('index');
        Route::post('/store', [ExamScheduleController::class, 'store'])->name('store');
        Route::delete('/{id}', [ExamScheduleController::class, 'destroy'])->name('destroy');
    });

    // Smart Marks Entry Routes
    Route::prefix('marks')->name('marks.')->group(function () {
        Route::get('/', [MarkController::class, 'index'])->name('index');
        Route::post('/store-ajax', [MarkController::class, 'storeAjax'])->name('store.ajax');
    });

    // Marksheet & Result Routes
    Route::prefix('results')->name('results.')->group(function () {
        Route::get('/', [ResultController::class, 'index'])->name('index'); // সার্চ করার ফর্ম
        Route::post('/generate', [ResultController::class, 'generate'])->name('generate'); // PDF জেনারেট
        
    });

     // Tabulation Sheet Routes
        Route::get('/tabulation', [ResultController::class, 'tabulationIndex'])->name('results.tabulation');
        Route::post('/tabulation/generate', [ResultController::class, 'tabulationGenerate'])->name('results.tabulation.generate');

    // Admit Card Routes
    Route::prefix('admit-cards')->name('admit-cards.')->group(function () {
        Route::get('/', [AdmitCardController::class, 'index'])->name('index');
        Route::post('/generate', [AdmitCardController::class, 'generate'])->name('generate');
    });

    // Seat Plan Routes
    Route::prefix('seat-plans')->name('seat-plans.')->group(function () {
        Route::get('/', [SeatPlanController::class, 'index'])->name('index');
        Route::post('/generate', [SeatPlanController::class, 'generate'])->name('generate');
    });

    // SMS Management Routes
    Route::prefix('sms')->name('sms.')->group(function () {
        Route::get('/general-notice', [SmsController::class, 'generalNotice'])->name('general-notice');
        Route::post('/general-notice/send', [SmsController::class, 'sendGeneralNotice'])->name('general-notice.send');
        
        // SMS Delivery Report
        Route::get('/report', [SmsController::class, 'report'])->name('report');
        
        // Result SMS
        Route::get('/result', [SmsController::class, 'resultSms'])->name('result');
        Route::post('/result/send', [SmsController::class, 'sendResultSms'])->name('result.send');
    });

    // Class Routine Routes
    Route::prefix('routine')->name('routine.')->group(function () {
        Route::get('/', [ClassRoutineController::class, 'index'])->name('index');
        Route::get('/get', [ClassRoutineController::class, 'getRoutine'])->name('get');
        Route::post('/store', [ClassRoutineController::class, 'store'])->name('store');
        Route::delete('/destroy/{id}', [ClassRoutineController::class, 'destroy'])->name('destroy');
        Route::put('/update/{id}', [ClassRoutineController::class, 'update'])->name('update');
    });

    // Exam Routine Routes
    Route::prefix('exam-routine')->name('exam-routine.')->group(function () {
        Route::get('/', [ExamRoutineController::class, 'index'])->name('index');
        Route::get('/get', [ExamRoutineController::class, 'getRoutine'])->name('get');
        Route::post('/store', [ExamRoutineController::class, 'store'])->name('store');
        Route::delete('/destroy/{id}', [ExamRoutineController::class, 'destroy'])->name('destroy');
    });

        // Fee Management Routes
    Route::prefix('fees')->name('fees.')->group(function () {
        
        // Fee Categories
        Route::get('/categories', [FeeSetupController::class, 'categoryIndex'])->name('categories.index');
        Route::post('/categories', [FeeSetupController::class, 'categoryStore'])->name('categories.store');
        // এডিটের জন্য নতুন দুটি রাউট
        Route::get('/categories/{id}/edit', [FeeSetupController::class, 'categoryEdit'])->name('categories.edit');
        Route::put('/categories/{id}', [FeeSetupController::class, 'categoryUpdate'])->name('categories.update');
    
        Route::delete('/categories/{id}', [FeeSetupController::class, 'categoryDestroy'])->name('categories.destroy');
  

        // Fee Setups
        Route::get('/setup', [FeeSetupController::class, 'setupIndex'])->name('setup.index');
        Route::post('/setup', [FeeSetupController::class, 'setupStore'])->name('setup.store');
        Route::delete('/setup/{id}', [FeeSetupController::class, 'setupDestroy'])->name('setup.destroy');

        // Fee Collection (নতুন)
        Route::get('/collection', [FeeCollectionController::class, 'index'])->name('collection.index');
        Route::post('/collection', [FeeCollectionController::class, 'store'])->name('collection.store');
       

        // Generate Invoices Routes
        Route::get('/invoice/generate', [FeeInvoiceController::class, 'index'])->name('invoice.generate');
        Route::post('/invoice/generate', [FeeInvoiceController::class, 'generate'])->name('invoice.store');
        
        // POS Print Route
        Route::get('/invoice/{id}/pos-print', [FeeInvoiceController::class, 'printPos'])->name('invoice.pos_print');

        // Bulk Payment & Master Receipt Routes
        Route::post('/collection/bulk', [FeeCollectionController::class, 'bulkStore'])->name('collection.bulk_store');
        Route::get('/receipt/{receipt_no}/pos-print', [FeeCollectionController::class, 'printBulkPos'])->name('receipt.pos_print');

        // Fee Reports
        Route::get('/reports', [FeeReportController::class, 'index'])->name('reports.index');
        // নতুন: Summary Report
        Route::get('/reports/summary', [FeeReportController::class, 'summaryReport'])->name('reports.summary');
        
    });

    
        
});