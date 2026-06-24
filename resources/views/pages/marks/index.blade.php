@extends('tyro-dashboard::layouts.admin')

@section('title', 'Smart Marks Entry')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }
</script>
<style>
    /* স্মার্ট ড্রপডাউন ও ইনপুট স্টাইল */
    .smart-input {
        background-color: #ffffff !important; color: #000000 !important; 
        border: 2px solid #e5e5e5 !important; border-radius: 1rem !important;
        padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important;
        width: 100%; outline: none !important; transition: all 0.3s ease; height: 52px;
    }
    .dark .smart-input { background-color: #111827 !important; color: #ffffff !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; }
    
    /* নম্বরের ইনপুট বক্সের জন্য স্পেশাল ডিজাইন */
    .mark-input {
        width: 70px; text-align: center; font-weight: bold; border-radius: 0.5rem; border: 2px solid #e5e7eb;
        padding: 0.25rem; outline: none; transition: all 0.2s; background: white; color: black;
    }
    .dark .mark-input { background: #1f2937; border-color: #374151; color: white; }
    .mark-input:focus { border-color: #1e4630; transform: scale(1.05); }
    
    /* সেভ স্ট্যাটাস ইনডিকেটর */
    .save-status { font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 10px; transition: 0.3s; opacity: 0; }
    .status-saving { color: #d97706; background: #fef3c7; opacity: 1; }
    .status-saved { color: #059669; background: #d1fae5; opacity: 1; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Smart Marks Entry</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-xl p-8 border border-gray-100 dark:border-gray-700 mb-8">
        <form action="{{ route('marks.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="smart-label">Academic Session *</label>
                    <select name="session_year_id" class="smart-input" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" {{ request('session_year_id') == $session->id ? 'selected' : '' }}>{{ $session->session_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Select Branch *</label>
                    <select name="branch_id" class="smart-input" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Select Exam *</label>
                    <select name="exam_id" class="smart-input" required>
                        <option value="">Select Exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}" {{ request('exam_id') == $exam->id ? 'selected' : '' }}>{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <div>
                    <label class="smart-label">Select Class *</label>
                    <select name="class_id" class="smart-input" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>{{ $class->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Select Subject *</label>
                    <select name="subject_id" class="smart-input" required>
                        <option value="">Select Subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>{{ $subject->subject_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-themeGreen hover:bg-green-900 text-white font-black py-3 rounded-[1rem] shadow-lg transition-all uppercase tracking-widest text-sm h-[52px]">
                        Load Students
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if(request()->filled(['session_year_id', 'branch_id', 'exam_id', 'class_id', 'subject_id']))
        <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-8 border border-gray-100 dark:border-gray-700 relative">
            
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider ml-2">Enter Marks</h3>
                @if($exam_schedule)
                    <div class="text-xs font-bold text-gray-500 bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600">
                        Full Marks: <span class="text-themeGreen">{{ $exam_schedule->full_marks }}</span> | Pass: <span class="text-red-500">{{ $exam_schedule->pass_marks }}</span>
                    </div>
                @else
                    <div class="text-xs font-bold text-red-500 bg-red-100 px-4 py-2 rounded-xl">Subject not scheduled for this class!</div>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest">ID / Roll</th>
                            <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest">Student Name</th>
                            <th class="p-4 text-xs font-black text-themeGreen uppercase tracking-widest text-center">CT ({{ $exam_schedule->ct_marks ?? 0 }})</th>
                            <th class="p-4 text-xs font-black text-blue-500 uppercase tracking-widest text-center">Written ({{ $exam_schedule->written_marks ?? 0 }})</th>
                            <th class="p-4 text-xs font-black text-purple-500 uppercase tracking-widest text-center">MCQ ({{ $exam_schedule->mcq_marks ?? 0 }})</th>
                            <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">Total</th>
                            <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">Grade</th>
                            <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20">
                            <td class="p-4 text-sm font-bold text-gray-700 dark:text-gray-300">{{ $student->student_identity ?? $student->id }}</td>
                            <td class="p-4 text-sm font-bold text-gray-900 dark:text-white">{{ $student->student_name ?? 'Unknown' }}</td>
                            
                            <td class="p-4 text-center">
                                <input type="number" step="0.5" class="mark-input ct-input" data-id="{{ $student->id }}" value="{{ $student->mark->ct_mark ?? '' }}">
                            </td>
                            <td class="p-4 text-center">
                                <input type="number" step="0.5" class="mark-input written-input" data-id="{{ $student->id }}" value="{{ $student->mark->written_mark ?? '' }}">
                            </td>
                            <td class="p-4 text-center">
                                <input type="number" step="0.5" class="mark-input mcq-input" data-id="{{ $student->id }}" value="{{ $student->mark->mcq_mark ?? '' }}">
                            </td>
                            
                            <td class="p-4 text-center font-black text-gray-800 dark:text-gray-200 text-lg total-display-{{ $student->id }}">
                                {{ $student->mark->total_mark ?? 0 }}
                            </td>
                            <td class="p-4 text-center">
                                <span class="grade-display-{{ $student->id }} px-3 py-1 text-xs font-black rounded-full {{ isset($student->mark) && $student->mark->grade_point >= 4 ? 'bg-green-100 text-green-700' : (isset($student->mark) && ($student->mark->letter_grade == 'F' || $student->mark->letter_grade == 'Fail') ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-700') }}">
                                    {{ $student->mark->letter_grade ?? '--' }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <span class="save-status status-{{ $student->id }}"></span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-sm font-bold text-gray-400">No students found for this specific Branch & Session.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // রিকোয়েস্ট থেকে ফিল্টার প্যারামিটারগুলো নিয়ে আসা হচ্ছে
        const session_year_id = "{{ request('session_year_id') }}";
        const branch_id = "{{ request('branch_id') }}";
        const exam_id = "{{ request('exam_id') }}";
        const class_id = "{{ request('class_id') }}";
        const subject_id = "{{ request('subject_id') }}";

        document.querySelectorAll('.mark-input').forEach(input => {
            input.addEventListener('blur', function() {
                saveMark(this.dataset.id);
            });
            input.addEventListener('keypress', function(e) {
                if(e.key === 'Enter') input.blur(); 
            });
        });

        function saveMark(studentId) {
            const row = document.querySelector(`.ct-input[data-id="${studentId}"]`).closest('tr');
            const ctVal = parseFloat(row.querySelector('.ct-input').value) || 0;
            const writtenVal = parseFloat(row.querySelector('.written-input').value) || 0;
            const mcqVal = parseFloat(row.querySelector('.mcq-input').value) || 0;

            const statusLabel = row.querySelector(`.status-${studentId}`);
            statusLabel.textContent = 'Saving...';
            statusLabel.className = `save-status status-${studentId} status-saving`;

            // Axios দিয়ে কন্ট্রোলারে ৭টি ডাটা পাঠানো হচ্ছে
            axios.post("{{ route('marks.store.ajax') }}", {
                _token: "{{ csrf_token() }}",
                session_year_id: session_year_id,
                branch_id: branch_id,
                exam_id: exam_id,
                class_id: class_id,
                subject_id: subject_id,
                student_id: studentId,
                ct_mark: ctVal,
                written_mark: writtenVal,
                mcq_mark: mcqVal
            })
            .then(response => {
                if(response.data.success) {
                    row.querySelector(`.total-display-${studentId}`).textContent = response.data.total;
                    
                    const gradeSpan = row.querySelector(`.grade-display-${studentId}`);
                    gradeSpan.textContent = response.data.letter_grade;
                    
                    if(response.data.letter_grade === 'F' || response.data.letter_grade === 'Fail') {
                        gradeSpan.className = `grade-display-${studentId} px-3 py-1 text-xs font-black rounded-full bg-red-100 text-red-700`;
                    } else {
                        gradeSpan.className = `grade-display-${studentId} px-3 py-1 text-xs font-black rounded-full bg-green-100 text-green-700`;
                    }

                    statusLabel.textContent = 'Saved ✓';
                    statusLabel.className = `save-status status-${studentId} status-saved`;
                    setTimeout(() => { statusLabel.classList.remove('status-saved'); statusLabel.style.opacity = '0'; }, 2000);
                }
            })
            .catch(error => {
                console.error("Save Error:", error);
                statusLabel.textContent = 'Failed!';
                statusLabel.className = `save-status status-${studentId} bg-red-100 text-red-700 opacity-100`;
            });
        }
    });
</script>
@endsection