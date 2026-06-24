@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Subject Setup')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }
</script>
<style>
    .smart-input {
        background-color: #ffffff !important;
        color: #000000 !important; 
        border: 2px solid #e5e5e5 !important;
        border-radius: 1rem !important;
        padding: 0.75rem 1rem !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
        width: 100%;
        outline: none !important;
        transition: all 0.3s ease;
        height: 52px;
    }
    .smart-input::placeholder { color: #9ca3af !important; font-weight: 500 !important; }
    .dark .smart-input { background-color: #111827 !important; color: #ffffff !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; }
    .dark .smart-label { @apply text-gray-400; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-10">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Exam Subject Setup</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-themeGreen text-themeGreen font-bold rounded-r-lg shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 font-bold rounded-r-lg shadow-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-10 border border-gray-100 dark:border-gray-700 mb-10">
        <form action="{{ route('exam-schedules.store') }}" method="POST">
            @csrf
            
            <h4 class="text-sm font-bold text-themeGreen dark:text-gray-300 mb-6 border-b pb-2">Basic Selection</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div>
                    <label class="smart-label">Select Branch *</label>
                    <select name="branch_id" class="smart-input" required>
                        <option value="">Choose Branch</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name ?? $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Select Exam *</label>
                    <select name="exam_id" class="smart-input" required>
                        <option value="">Choose Exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Select Class *</label>
                    <select name="class_id" class="smart-input" required>
                        <option value="">Choose Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Select Subject *</label>
                    <select name="subject_id" class="smart-input" required>
                        <option value="">Choose Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->subject_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h4 class="text-sm font-bold text-themeGreen dark:text-gray-300 mb-6 border-b pb-2">Marks Distribution</h4>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div>
                    <label class="smart-label">Full Marks *</label>
                    <input type="number" step="0.01" name="full_marks" value="100" class="smart-input" required>
                </div>
                <div>
                    <label class="smart-label">Pass Marks *</label>
                    <input type="number" step="0.01" name="pass_marks" value="33" class="smart-input" required>
                </div>
                <div>
                    <label class="smart-label">CT / Tutorial</label>
                    <input type="number" step="0.01" name="ct_marks" value="20" class="smart-input">
                </div>
                <div>
                    <label class="smart-label">Written Marks</label>
                    <input type="number" step="0.01" name="written_marks" value="80" class="smart-input">
                </div>
                <div>
                    <label class="smart-label">MCQ Marks</label>
                    <input type="number" step="0.01" name="mcq_marks" value="0" class="smart-input">
                </div>
            </div>

            <h4 class="text-sm font-bold text-themeGreen dark:text-gray-300 mb-6 border-b pb-2">Schedule Details (Optional)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div>
                    <label class="smart-label">Exam Date</label>
                    <input type="date" name="exam_date" class="smart-input">
                </div>
                <div>
                    <label class="smart-label">Start Time</label>
                    <input type="time" name="start_time" class="smart-input">
                </div>
                <div>
                    <label class="smart-label">End Time</label>
                    <input type="time" name="end_time" class="smart-input">
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-black py-4 px-12 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95">
                    Save Subject
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-8 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-6 ml-2">Scheduled Subjects List</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest">Exam & Class</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest">Subject</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">Marks (Full / Pass)</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">Dist. (CT/W/M)</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $schedule)
                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                        <td class="p-4">
                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $schedule->exam->name ?? 'N/A' }}</div>
                            <div class="text-[11px] font-bold text-themeGreen mt-1 uppercase tracking-widest">Class: {{ $schedule->classes->class_name ?? 'N/A' }}</div>
                            @if($schedule->branch)
                                <div class="text-[9px] font-bold text-gray-400 mt-0.5 uppercase tracking-widest">{{ $schedule->branch->branch_name ?? $schedule->branch->name }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-sm font-bold text-gray-700 dark:text-gray-300">
                            {{ $schedule->subject->subject_name ?? 'N/A' }}
                            @if($schedule->exam_date)
                                <div class="text-[10px] text-gray-400 mt-1">{{ date('d M Y', strtotime($schedule->exam_date)) }}</div>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2 py-1 rounded font-bold text-xs">{{ $schedule->full_marks }}</span> / 
                            <span class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-1 rounded font-bold text-xs">{{ $schedule->pass_marks }}</span>
                        </td>
                        <td class="p-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400">
                            {{ $schedule->ct_marks }} / {{ $schedule->written_marks }} / {{ $schedule->mcq_marks }}
                        </td>
                        <td class="p-4 text-right">
                            <form action="{{ route('exam-schedules.destroy', $schedule->id) }}" method="POST" onsubmit="return confirm('Delete this schedule?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-sm font-bold text-gray-400">No subjects scheduled yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection