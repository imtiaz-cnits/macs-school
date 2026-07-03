@extends('tyro-dashboard::layouts.admin')

@section('title', 'Grade Setup')

@section('content')
<div class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div class="w-full">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
                Grade Setup
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Configure mark distributions, GPA scales, and grade boundary settings</p>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-950/20 border-l-4 border-themeGreen text-themeGreen dark:text-green-400 font-bold rounded-r-2xl shadow-sm text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 text-red-700 dark:text-red-400 font-bold rounded-r-2xl shadow-sm text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Form Panel Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 md:p-10 shadow-sm hover:shadow-md transition-all duration-300 mb-8">
        <form action="{{ route('grades.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Grade Letter <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="text" name="grade_name" placeholder="Ex: A+" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Grade Point (GPA) <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="number" step="0.01" name="grade_point" placeholder="Ex: 5.00" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Minimum Mark <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="number" name="min_mark" placeholder="Ex: 80" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Maximum Mark <span class="text-red-500 ml-0.5">*</span></label>
                    <input type="number" name="max_mark" placeholder="Ex: 100" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400" required>
                </div>
                <div>
                    <label class="text-[10px] font-black tracking-widest text-gray-555 dark:text-gray-400 uppercase mb-2 block">Remarks</label>
                    <input type="text" name="remarks" placeholder="Ex: Outstanding" class="w-full h-11 border-2 border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-themeDark focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-sm font-semibold text-gray-700 dark:text-gray-250 px-3 placeholder-gray-400">
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 dark:border-white/[0.05] pt-6">
                <button type="submit" class="h-11 px-8 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all text-xs uppercase tracking-widest flex items-center justify-center">
                    Add Grade
                </button>
            </div>
        </form>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-themeNavy border border-gray-100 dark:border-white/[0.06] rounded-3xl p-6 shadow-sm hover:shadow-md transition-all duration-300">
        <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-wider mb-6 block ml-2">Grading System List</h3>
        
        <div class="table-container bg-transparent !border-none !shadow-none !mt-2 !mb-0 overflow-x-auto">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Mark Range</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-36">Grade Letter</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-center w-36">Grade Point</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em]">Remarks</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-3 !px-4 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-36">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors border-b border-gray-100 dark:border-white/[0.04]">
                        <td class="py-4 px-4">
                            <span class="bg-gray-50 dark:bg-themeDark border border-gray-100 dark:border-white/[0.06] text-gray-700 dark:text-gray-300 text-xs font-bold px-3 py-1 rounded-lg inline-block">
                                {{ $grade->min_mark }} - {{ $grade->max_mark }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg inline-block 
                                {{ $grade->grade_point >= 4.0 ? 'bg-green-50 text-themeGreen dark:bg-green-950/20 dark:text-green-400' : 
                                  ($grade->grade_point == 0 ? 'bg-red-50 text-red-600 dark:bg-red-950/20 dark:text-red-400' : 'bg-blue-50 text-themeBlue dark:bg-blue-950/20 dark:text-blue-400') }}">
                                {{ $grade->grade_name }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-sm font-mono font-black text-gray-800 dark:text-gray-200 text-center">{{ number_format($grade->grade_point, 2) }}</td>
                        <td class="py-4 px-4 text-sm font-semibold text-gray-600 dark:text-gray-450">{{ $grade->remarks ?? '--' }}</td>
                        <td class="py-4 px-4 text-right">
                            <form action="{{ route('grades.destroy', $grade->id) }}" method="POST" class="inline" onsubmit="return confirmDelete(event);">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn text-red-650 hover:text-red-800 hover:border-red-600" title="Delete Grade">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-500 font-bold uppercase tracking-wider">No grading rules found. Create the standard marks above!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    async function confirmDelete(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (await showDanger('Delete Grade', 'Are you sure you want to delete this grading rule? This action cannot be undone.')) {
            form.submit();
        }
    }
</script>
@endpush
@endsection