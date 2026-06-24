@extends('tyro-dashboard::layouts.admin')

@section('title', 'Grade Setup')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = { darkMode: 'class', theme: { extend: { colors: { themeGreen: '#1e4630' } } } }
</script>
<style>
    /* স্মার্ট ইনপুট স্টাইল */
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
        <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">Grade Setup</h1>
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
        <form action="{{ route('grades.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
                <div>
                    <label class="smart-label">Grade Letter *</label>
                    <input type="text" name="grade_name" placeholder="Ex: A+" class="smart-input" required>
                </div>
                <div>
                    <label class="smart-label">Grade Point (GPA) *</label>
                    <input type="number" step="0.01" name="grade_point" placeholder="Ex: 5.00" class="smart-input" required>
                </div>
                <div>
                    <label class="smart-label">Minimum Mark *</label>
                    <input type="number" name="min_mark" placeholder="Ex: 80" class="smart-input" required>
                </div>
                <div>
                    <label class="smart-label">Maximum Mark *</label>
                    <input type="number" name="max_mark" placeholder="Ex: 100" class="smart-input" required>
                </div>
                <div>
                    <label class="smart-label">Remarks</label>
                    <input type="text" name="remarks" placeholder="Ex: Outstanding" class="smart-input">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-themeGreen hover:bg-green-900 text-white font-black py-4 px-12 rounded-2xl shadow-xl transition-all uppercase tracking-widest text-sm hover:scale-105 active:scale-95">
                    Add Grade
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-2xl p-8 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-6 ml-2">Grading System List</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest">Mark Range</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">Grade Letter</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-center">Grade Point</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest">Remarks</th>
                        <th class="p-4 text-xs font-black text-gray-500 uppercase tracking-widest text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/20 transition-colors">
                        <td class="p-4 text-sm font-bold text-gray-700 dark:text-gray-300">
                            <span class="bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-lg">{{ $grade->min_mark }} - {{ $grade->max_mark }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="px-4 py-1 text-sm font-black rounded-full 
                                {{ $grade->grade_point >= 4.0 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 
                                  ($grade->grade_point == 0 ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400') }}">
                                {{ $grade->grade_name }}
                            </span>
                        </td>
                        <td class="p-4 text-sm font-bold text-gray-900 dark:text-white text-center">{{ number_format($grade->grade_point, 2) }}</td>
                        <td class="p-4 text-sm font-bold text-gray-600 dark:text-gray-400">{{ $grade->remarks ?? '--' }}</td>
                        <td class="p-4 text-right">
                            <form action="{{ route('grades.destroy', $grade->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this grade?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 dark:bg-red-900/20 p-2 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-sm font-bold text-gray-400">No grading rules found. Create the standard marks above!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection