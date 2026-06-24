@extends('tyro-dashboard::layouts.admin')

@section('title', 'Send Result SMS')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }</script>
<style>
    .smart-input { background-color: #ffffff !important; color: #000000 !important; border: 2px solid #e5e5e5 !important; border-radius: 1rem !important; padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important; width: 100%; outline: none !important; transition: all 0.3s ease; height: 52px; }
    textarea.smart-input { height: auto !important; min-height: 120px; resize: none; font-family: monospace; }
    .dark .smart-input { background-color: #111827 !important; color: #ffffff !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; }
    .dark .smart-label { @apply text-gray-400; }
    
    .shortcode-btn { @apply bg-green-50 text-[#1e4630] border border-green-200 hover:bg-green-100 font-bold text-xs py-1.5 px-3 rounded-lg cursor-pointer transition-colors shadow-sm; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-10 max-w-[1000px] mx-auto min-h-screen">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Smart Result SMS</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Send Personalized Exam Results</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 font-bold rounded-r-lg shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-10 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('sms.result.send') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div>
                    <label class="smart-label">Session *</label>
                    <select name="session_year_id" class="smart-input" required>
                        <option value="">Choose...</option>
                        @foreach($sessions as $session) <option value="{{ $session->id }}">{{ $session->session_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Exam *</label>
                    <select name="exam_id" class="smart-input" required>
                        <option value="">Choose...</option>
                        @foreach($exams as $exam) <option value="{{ $exam->id }}">{{ $exam->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Class *</label>
                    <select name="class_id" class="smart-input" required>
                        <option value="">Choose...</option>
                        @foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Section (Optional)</label>
                    <select name="section_id" class="smart-input">
                        <option value="">All Sections</option>
                        @foreach($sections as $section) <option value="{{ $section->id }}">{{ $section->section_name }}</option> @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-2">
                <label class="smart-label">Message Template *</label>
                <div class="flex flex-wrap gap-2 mb-3 bg-gray-50 dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-gray-700">
                    <span class="text-xs font-bold text-gray-500 mt-1">Click to Insert:</span>
                    <button type="button" class="shortcode-btn" onclick="insertCode('[name]')">[name]</button>
                    <button type="button" class="shortcode-btn" onclick="insertCode('[exam]')">[exam]</button>
                    <button type="button" class="shortcode-btn" onclick="insertCode('[gpa]')">[gpa]</button>
                    <button type="button" class="shortcode-btn" onclick="insertCode('[marks]')">[marks]</button>
                </div>
                
                <textarea name="message_template" id="message_template" class="smart-input" required>Dear Parent, your child [name] has scored GPA: [gpa] and Total Marks: [marks] in [exam]. Please check the marksheet for details. - PIS</textarea>
                <p class="text-xs font-bold text-gray-400 mt-2 ml-1">The system will automatically replace the tags with actual student data.</p>
            </div>

            <div class="flex justify-center mt-8">
                <button type="submit" class="bg-[#1e4630] hover:bg-green-900 text-white font-black py-4 px-12 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95 flex items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
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