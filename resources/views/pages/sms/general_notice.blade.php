@extends('tyro-dashboard::layouts.admin')

@section('title', 'Send General Notice SMS')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }</script>
<style>
    .smart-input { background-color: #ffffff !important; color: #000000 !important; border: 2px solid #e5e5e5 !important; border-radius: 1rem !important; padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important; width: 100%; outline: none !important; transition: all 0.3s ease; height: 52px; }
    textarea.smart-input { height: auto !important; min-height: 120px; resize: none; }
    .dark .smart-input { background-color: #111827 !important; color: #ffffff !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; }
    .dark .smart-label { @apply text-gray-400; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-10 max-w-[900px] mx-auto min-h-screen">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">General Notice Broadcast</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Send Instant SMS to Parents</p>
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
        <form action="{{ route('sms.general-notice.send') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="smart-label">Select Session *</label>
                    <select name="session_year_id" class="smart-input" required>
                        <option value="">Choose Session</option>
                        @foreach($sessions as $session) 
                            <option value="{{ $session->id }}">{{ $session->session_name }}</option> 
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Target Audience *</label>
                    <select name="target_audience" id="target_audience" class="smart-input" required onchange="toggleFilters()">
                        <option value="all">All Active Students</option>
                        <option value="branch_wise">Specific Branch</option>
                        <option value="class_wise">Specific Class</option>
                        <option value="section_wise">Specific Section</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div id="branch_filter" style="display: none;">
                    <label class="smart-label">Select Branch *</label>
                    <select name="branch_id" id="branch_id" class="smart-input">
                        <option value="">Choose Branch</option>
                        @foreach($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option> @endforeach
                    </select>
                </div>

                <div id="class_filter" style="display: none;">
                    <label class="smart-label">Select Class *</label>
                    <select name="class_id" id="class_id" class="smart-input">
                        <option value="">Choose Class</option>
                        @foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach
                    </select>
                </div>

                <div id="section_filter" style="display: none;">
                    <label class="smart-label">Select Section *</label>
                    <select name="section_id" id="section_id" class="smart-input">
                        <option value="">Choose Section</option>
                        @foreach($sections as $section) <option value="{{ $section->id }}">{{ $section->section_name }}</option> @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-6 relative">
                <label class="smart-label">Message Content *</label>
                <textarea name="message" id="message_body" class="smart-input" placeholder="Type your notice here..." required onkeyup="countChars()"></textarea>
                
                <div class="mt-2 flex justify-between items-center px-1">
                    <span class="text-xs text-gray-500 font-bold" id="encoding_type">Encoding: Auto</span>
                    <span class="text-xs font-bold text-[#1e4630]" id="char_count">Characters: 0 | SMS Cost: 0</span>
                </div>
            </div>

            <div class="flex justify-center mt-8">
                <button type="submit" class="bg-[#1e4630] hover:bg-green-900 text-white font-black py-4 px-16 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95 flex items-center gap-2">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    Send SMS Blast
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function toggleFilters() {
        var audience = document.getElementById('target_audience').value;
        var branchFilter = document.getElementById('branch_filter');
        var classFilter = document.getElementById('class_filter');
        var sectionFilter = document.getElementById('section_filter');

        var branchInput = document.getElementById('branch_id');
        var classInput = document.getElementById('class_id');
        var sectionInput = document.getElementById('section_id');

        // Reset inputs and hide
        branchFilter.style.display = 'none';
        classFilter.style.display = 'none';
        sectionFilter.style.display = 'none';
        branchInput.required = false;
        classInput.required = false;
        sectionInput.required = false;

        // Conditionals
        if(audience === 'branch_wise') {
            branchFilter.style.display = 'block';
            branchInput.required = true;
        } else if(audience === 'class_wise') {
            classFilter.style.display = 'block';
            classInput.required = true;
        } else if(audience === 'section_wise') {
            classFilter.style.display = 'block';
            sectionFilter.style.display = 'block';
            classInput.required = true;
            sectionInput.required = true;
        }
    }

    // Live Character Counter
    function countChars() {
        var msg = document.getElementById('message_body').value;
        var charCount = msg.length;
        
        // Check for Unicode (Bengali, Emojis, etc.)
        var isUnicode = /[^\u0000-\u00ff]/.test(msg);
        
        var limit = isUnicode ? 70 : 160;
        var smsCount = Math.ceil(charCount / limit);
        if(charCount === 0) smsCount = 0;

        document.getElementById('encoding_type').innerText = isUnicode ? 'Encoding: Unicode (Bengali)' : 'Encoding: GSM (English)';
        document.getElementById('encoding_type').style.color = isUnicode ? '#d90429' : '#6b7280';
        
        document.getElementById('char_count').innerText = 'Characters: ' + charCount + ' | SMS Cost: ' + smsCount;
    }
</script>
@endpush
@endsection