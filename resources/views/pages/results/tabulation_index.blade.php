@extends('tyro-dashboard::layouts.admin')

@section('title', 'Generate Tabulation Sheet')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }</script>
<style>
    .smart-input { background-color: #ffffff !important; color: #000000 !important; border: 2px solid #e5e5e5 !important; border-radius: 1rem !important; padding: 0.75rem 1rem !important; font-weight: 600 !important; font-size: 0.875rem !important; width: 100%; outline: none !important; transition: all 0.3s ease; height: 52px; }
    .dark .smart-input { background-color: #111827 !important; color: #ffffff !important; border-color: #374151 !important; }
    .smart-input:focus { border-color: #1e4630 !important; box-shadow: 0 0 0 4px rgba(30, 70, 48, 0.1) !important; }
    .smart-label { @apply block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5 ml-1; }
    .dark .smart-label { @apply text-gray-400; }
</style>
@endpush

@section('content')
<div class="p-4 md:p-10 max-w-[1200px] mx-auto min-h-screen">
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Tabulation Sheet</h1>
        <p class="text-[10px] text-gray-500 font-black uppercase tracking-[0.4em] mt-1">Class Wise Result Summary</p>
    </div>

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 font-bold rounded-r-lg shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-10 border border-gray-100 dark:border-gray-700">
        <form action="{{ route('results.tabulation.generate') }}" method="POST" target="_blank">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div>
                    <label class="smart-label">Session *</label>
                    <select name="session_year_id" class="smart-input" required>
                        <option value="">Choose Session</option>
                        @foreach($sessions as $session) <option value="{{ $session->id }}">{{ $session->session_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Branch *</label>
                    <select name="branch_id" class="smart-input" required>
                        <option value="">Choose Branch</option>
                        @foreach($branches as $branch) <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Exam *</label>
                    <select name="exam_id" class="smart-input" required>
                        <option value="">Choose Exam</option>
                        @foreach($exams as $exam) <option value="{{ $exam->id }}">{{ $exam->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="smart-label">Class *</label>
                    <select name="class_id" class="smart-input" required>
                        <option value="">Choose Class</option>
                        @foreach($classes as $class) <option value="{{ $class->id }}">{{ $class->class_name }}</option> @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-center">
                <button type="submit" class="bg-[#1e4630] hover:bg-green-900 text-white font-black py-4 px-16 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95">
                    Generate Tabulation Sheet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection