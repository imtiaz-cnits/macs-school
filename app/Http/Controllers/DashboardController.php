<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
   public function index()
{
    $data = [
        'totalStudents' => \App\Models\Student::count(),
        'totalBranches'  => \App\Models\Branch::count(),
        'totalTeachers'  => \App\Models\Teacher::count(),
        'totalSections'  => \App\Models\Section::count(),
        'totalClasses'   => \App\Models\Classes::count(), // এখানে ক্লাস কাউন্ট যোগ করা হলো
        'recentStudents' => \App\Models\Student::with('schoolClass')->latest()->take(5)->get(),
    ];

    return view('vendor.tyro-dashboard.dashboard.index', $data);
}
}
