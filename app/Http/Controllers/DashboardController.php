<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZktecoService;

class DashboardController extends Controller
{
    protected $zktecoService;

    public function __construct(ZktecoService $zktecoService)
    {
        $this->zktecoService = $zktecoService;
    }

    public function index()
    {
        $data = [
            'totalStudents' => \App\Models\Student::count(),
            'totalBranches'  => \App\Models\Branch::count(),
            'totalTeachers'  => \App\Models\Teacher::count(),
            'totalSections'  => \App\Models\Section::count(),
            'totalClasses'   => \App\Models\Classes::count(), // এখানে ক্লাস কাউন্ট যোগ করা হলো
            'recentStudents' => \App\Models\Student::with('schoolClass')->latest()->take(5)->get(),
            'connection'     => $this->zktecoService->getConnectionStatus(),
        ];

        return view('vendor.tyro-dashboard.dashboard.index', $data);
    }
}
