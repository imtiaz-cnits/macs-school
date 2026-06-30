@extends('tyro-dashboard::layouts.admin')

@section('title', 'Certificate Hub')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { 
        darkMode: 'class', 
        theme: { 
            extend: { 
                colors: { themeGreen: '#1e4630' } 
            } 
        } 
    }
</script>
<style>
    /* স্মার্ট ড্রপডাউন ও ইনপুট স্টাইল - লাইট ও ডার্ক মোড কালার ফিক্সড */
    .smart-select {
        background-color: #ffffff !important; /* লাইট মোডে সাদা ব্যাকগ্রাউন্ড */
        color: #000000 !important; /* লাইট মোডে একদম কালো টেক্সট (সমস্যার সমাধান) */
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
    
    /* প্লেসহোল্ডার (হালকা লেখা) এর কালার */
    .smart-select::placeholder {
        color: #9ca3af !important; 
        font-weight: 500 !important;
    }

    /* ডার্ক মোডের স্টাইল */
    .dark .smart-select { 
        background-color: #111827 !important; /* ডার্ক মোডে কালো ব্যাকগ্রাউন্ড */
        color: #ffffff !important; /* ডার্ক মোডে সাদা টেক্সট */
        border-color: #374151 !important; 
    }
    
    .smart-select:focus { 
        border-color: #1e4630 !important; 
        box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important;
    }
    
    .smart-label { 
        @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; 
    }
    
    .dark .smart-label {
        @apply text-gray-400;
    }
</style>
@endpush

@section('content')
<div class="p-4 md:p-8 max-w-[1400px] mx-auto min-h-screen">
    
    <div class="mb-10">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Certificate Hub</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-10 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('certificates.generate') }}" method="POST" target="_blank">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div>
                    <label class="smart-label">Student ID / Identity</label>
                    <input type="text" name="student_id" placeholder="Ex: PIS-2026-01-0002" class="smart-select" required>
                </div>
                
                <div>
                    <label class="smart-label">Document Type</label>
                    <select name="type" id="docType" class="smart-select" required>
                        <option value="testimonial">Testimonial</option>
                        <option value="tc">Transfer Certificate (TC)</option>
                        <option value="general">General Certificate</option>
                    </select>
                </div>
                
                <div>
                    <label class="smart-label">Issue Date</label>
                    <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" class="smart-select">
                </div>
            </div>

            <div id="tc_extra_fields" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10 hidden bg-themeGreen/5 dark:bg-gray-900/50 p-6 rounded-3xl border border-themeGreen/20 dark:border-gray-700">
                <div>
                    <label class="smart-label text-themeGreen dark:text-gray-300">Reason for Leaving</label>
                    <input type="text" name="leaving_reason" placeholder="Ex: Change of Residence / To admit elsewhere" class="smart-select">
                </div>
                <div>
                    <label class="smart-label text-themeGreen dark:text-gray-300">Last Exam Result</label>
                    <input type="text" name="last_exam_result" placeholder="Ex: Passed with GPA-5.00" class="smart-select">
                </div>
            </div>

            <div class="flex justify-center">
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-black py-5 px-20 rounded-2xl shadow-2xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95 flex items-center gap-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Generate Document PDF
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Document Type চেঞ্জ হলে TC এর ফিল্ডগুলো দেখানোর লজিক
    document.getElementById('docType').addEventListener('change', function() {
        const tcFields = document.getElementById('tc_extra_fields');
        if (this.value === 'tc') {
            tcFields.classList.remove('hidden');
        } else {
            tcFields.classList.add('hidden');
        }
    });
</script>
@endsection