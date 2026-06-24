@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Marksheet')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }
</script>
<style>
    .smart-input {
        background-color: #ffffff !important; color: #000000 !important; 
        border: 2px solid #e5e5e5 !important; border-radius: 1rem !important;
        padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important;
        width: 100%; outline: none !important; transition: all 0.3s ease; height: 52px;
    }
    .smart-input::placeholder { color: #9ca3af !important; font-weight: 500 !important; }
    
    .dark .smart-input { background-color: #111827 !important; color: #ffffff !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important; }
    
    .smart-label { @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; }
    .dark .smart-label { @apply text-gray-400; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-10 max-w-[1200px] mx-auto min-h-screen">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Marksheet Hub</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Pabna International School</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-10 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('results.generate') }}" method="POST" target="_blank">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                
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
                    <label class="smart-label">Select Exam *</label>
                    <select name="exam_id" class="smart-input" required>
                        <option value="">Choose Exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Student ID / Roll *</label>
                    <input type="text" name="student_identity" placeholder="Ex: PIS-2026-01-0002" class="smart-input" required>
                </div>
            </div>
            <div class="flex justify-center">
                <button type="submit" class="bg-[#1e4630] hover:bg-green-900 text-white font-black py-4 px-16 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95">
                    Generate Marksheet PDF
                </button>
            </div>
        </form>
    </div>
</div>
@endsection