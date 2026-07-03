@extends('tyro-dashboard::layouts.admin')

@section('title', 'Send Result SMS')

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div class="w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Smart Result SMS
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Generate and broadcast personalized exam results directly to student contacts and parent mobile numbers</p>
        </div>
    </div>

    <!-- Feedback Banners -->
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Form Panel Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-10 shadow-sm hover:shadow-md transition-all duration-300">
        <form action="{{ route('sms.result.send') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Session <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="session_year_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required>
                        <option value="">Choose...</option>
                        @foreach($sessions as $session) <option value="{{ $session->id }}">{{ $session->session_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Exam <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="exam_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required>
                        <option value="">Choose...</option>
                        @foreach($exams as $exam) <option value="{{ $exam->id }}">{{ $exam->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Class <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="class_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required>
                        <option value="">Choose...</option>
                        @foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Section (Optional)</label>
                    <select name="section_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer">
                        <option value="">All Sections</option>
                        @foreach($sections as $section) <option value="{{ $section->id }}">{{ $section->section_name }}</option> @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Message Template <span class="text-red-500 ml-0.5">*</span></label>
                <div class="flex flex-wrap gap-2 items-center mb-3 bg-gray-50/50 dark:bg-themeDark p-3 rounded-2xl border border-gray-100 dark:border-white/[0.05]">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Click to Insert:</span>
                    <button type="button" class="h-8 px-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-themeBlue hover:text-themeBlue dark:hover:text-themeBlue hover:bg-white dark:hover:bg-themeDark/60 text-gray-600 dark:text-gray-300 font-bold text-xs cursor-pointer transition-all shadow-sm" onclick="insertCode('[name]')">[name]</button>
                    <button type="button" class="h-8 px-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-themeBlue hover:text-themeBlue dark:hover:text-themeBlue hover:bg-white dark:hover:bg-themeDark/60 text-gray-600 dark:text-gray-300 font-bold text-xs cursor-pointer transition-all shadow-sm" onclick="insertCode('[exam]')">[exam]</button>
                    <button type="button" class="h-8 px-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-themeBlue hover:text-themeBlue dark:hover:text-themeBlue hover:bg-white dark:hover:bg-themeDark/60 text-gray-600 dark:text-gray-300 font-bold text-xs cursor-pointer transition-all shadow-sm" onclick="insertCode('[gpa]')">[gpa]</button>
                    <button type="button" class="h-8 px-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-themeBlue hover:text-themeBlue dark:hover:text-themeBlue hover:bg-white dark:hover:bg-themeDark/60 text-gray-600 dark:text-gray-300 font-bold text-xs cursor-pointer transition-all shadow-sm" onclick="insertCode('[marks]')">[marks]</button>
                </div>
                
                <textarea name="message_template" id="message_template" class="w-full min-h-[120px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 p-4 placeholder-gray-400 font-mono" required>Dear Parent, your child [name] has scored GPA: [gpa] and Total Marks: [marks] in [exam]. Please check the marksheet for details. - PIS</textarea>
                <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 mt-2 ml-1">The system will automatically replace the tags with actual student data.</p>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-center mt-8 border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <button type="submit" class="h-11 px-12 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    Process & Send Results
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // ফাংশন: বাটনে ক্লিক করলেই টেক্সট এরিয়াতে শর্টকোড বসে যাবে
    function insertCode(code) {
        const textarea = document.getElementById('message_template');
        const startPos = textarea.selectionStart;
        const endPos = textarea.selectionEnd;
        
        textarea.value = textarea.value.substring(0, startPos) + code + textarea.value.substring(endPos, textarea.value.length);
        textarea.focus();
        textarea.selectionStart = startPos + code.length;
        textarea.selectionEnd = startPos + code.length;
    }
</script>
@endpush
@endsection