@extends('tyro-dashboard::layouts.admin')

@section('title', 'Send General Notice SMS')

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div class="w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                General Notice Broadcast
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Broadcast general notice SMS announcements directly to active student contacts and parents</p>
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
        <form action="{{ route('sms.general-notice.send') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Select Session <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="session_year_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required>
                        <option value="">Choose Session</option>
                        @foreach($sessions as $session) 
                            <option value="{{ $session->id }}">{{ $session->session_name }}</option> 
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Target Audience <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="target_audience" id="target_audience" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer" required onchange="toggleFilters()">
                        <option value="all">All Active Students</option>
                        <option value="branch_wise">Specific Branch</option>
                        <option value="class_wise">Specific Class</option>
                        <option value="section_wise">Specific Section</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div id="branch_filter" style="display: none;">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Select Branch <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="branch_id" id="branch_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer">
                        <option value="">Choose Branch</option>
                        @foreach($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option> @endforeach
                    </select>
                </div>

                <div id="class_filter" style="display: none;">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Select Class <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="class_id" id="class_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer">
                        <option value="">Choose Class</option>
                        @foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach
                    </select>
                </div>

                <div id="section_filter" style="display: none;">
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Select Section <span class="text-red-500 ml-0.5">*</span></label>
                    <select name="section_id" id="section_id" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-200 px-3 cursor-pointer">
                        <option value="">Choose Section</option>
                        @foreach($sections as $section) <option value="{{ $section->id }}">{{ $section->section_name }}</option> @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-6 relative">
                <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Message Content <span class="text-red-500 ml-0.5">*</span></label>
                <textarea name="message" id="message_body" class="w-full min-h-[140px] border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 p-4 placeholder-gray-400" placeholder="Type your notice here..." required onkeyup="countChars()"></textarea>
                
                <div class="mt-3 flex justify-between items-center px-1">
                    <span class="text-xs text-gray-500 font-bold" id="encoding_type">Encoding: Auto</span>
                    <span class="text-xs font-black text-themeGreen dark:text-green-400" id="char_count">Characters: 0 | SMS Cost: 0</span>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-center mt-8 border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <button type="submit" class="h-11 px-12 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
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
        document.getElementById('encoding_type').style.color = isUnicode ? '#ef4444' : '#6b7280';
        
        document.getElementById('char_count').innerText = 'Characters: ' + charCount + ' | SMS Cost: ' + smsCount;
    }
</script>
@endpush
@endsection