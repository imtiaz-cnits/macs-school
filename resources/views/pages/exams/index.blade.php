@extends('tyro-dashboard::layouts.admin')

@section('title', 'Exam Setup')

@push('meta')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@push('styles')
<!-- Load Alpine.js to fix dropdown component issues -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<style>
    /* Table padding override to align with MACS Design guidelines */
    .table th, .table td {
        padding: 0.875rem 1rem !important;
    }
</style>
@endpush

@section('content')
<div x-data="examSetupPage()" class="w-full min-h-screen">
    
    <!-- Header Section -->
    <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4 no-print">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                Exam Setup
            </h1>
            <p class="text-sm font-medium text-gray-555 dark:text-gray-400 mt-1">Pabna International School - Student Examination Setup</p>
        </div>
        <div class="bg-themeGreen/10 px-5 py-2 rounded-xl border border-themeGreen/20 backdrop-blur-sm">
            <span class="text-xs font-black text-themeGreen uppercase tracking-widest">Active Academic Session</span>
        </div>
    </div>



    <!-- Form Section -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm mb-8">
        <form action="{{ route('exams.store') }}" method="POST" @submit="if(!form.session_year_id) { event.preventDefault(); showAlert('Please select Academic Session!', 'Validation'); }">
            @csrf
            
            <input type="hidden" name="session_year_id" :value="form.session_year_id">
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                <!-- Exam Name -->
                <div class="md:col-span-5">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Exam Name *</label>
                    <input type="text" name="name" placeholder="Ex: 1st Term Exam 2026" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all" required>
                </div>
                
                <!-- Academic Session -->
                <div class="relative md:col-span-5" @click.away="dropdownOpen = false">
                    <label class="block text-[10px] font-black text-gray-555 dark:text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Academic Session *</label>
                    <button type="button" @click="dropdownOpen = !dropdownOpen" class="w-full h-11 px-3 bg-gray-50/50 dark:bg-themeDark border-2 border-gray-100 dark:border-gray-800 rounded-xl flex items-center justify-between text-sm font-semibold text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-4 focus:ring-themeBlue/10 focus:border-themeBlue transition-all text-left">
                        <span class="truncate" x-text="sessionText"></span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="dropdownOpen" x-cloak class="absolute z-50 w-full mt-1.5 bg-white dark:bg-themeNavy border border-gray-150 dark:border-white/[0.08] rounded-2xl shadow-xl py-1 max-h-60 overflow-y-auto" x-transition>
                        <button type="button" @click="selectSession('', 'Select Session')" class="w-full text-left px-4 py-2 text-xs hover:bg-gray-50 dark:hover:bg-themeDark/45 text-gray-400 transition-colors">
                            Select Session
                        </button>
                        @foreach($sessions as $session)
                            <button type="button" @click="selectSession('{{ $session->id }}', '{{ $session->session_name }}')" class="w-full flex items-center justify-between px-4 py-2 text-xs text-left hover:bg-gray-50 dark:hover:bg-themeDark/45 transition-colors" :class="form.session_year_id == '{{ $session->id }}' ? 'bg-indigo-50 dark:bg-themeBlue/10 text-themeBlue font-black' : 'text-gray-700 dark:text-gray-200'">
                                <span>{{ $session->session_name }}</span>
                                <template x-if="form.session_year_id == '{{ $session->id }}'">
                                    <svg class="w-3.5 h-3.5 text-themeBlue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </template>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="w-full h-11 bg-gradient-to-r from-themeBlue to-themeGreen text-white font-black rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all uppercase tracking-widest text-xs active:scale-95 flex items-center justify-center">
                        Save Exam
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Section -->
    <div class="bg-white dark:bg-themeNavy rounded-3xl border border-gray-100 dark:border-white/[0.06] p-6 shadow-sm">
        <h3 class="text-sm font-black text-gray-800 dark:text-white uppercase tracking-widest border-b border-gray-100 dark:border-white/[0.06] pb-4 mb-6">Exam List</h3>
        
        <div class="table-container bg-transparent !border-none !shadow-none !mt-0 !mb-0">
            <table class="w-full text-left border-collapse table">
                <thead>
                    <tr class="!bg-transparent">
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] w-20 text-center">#</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Exam Name</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em]">Session</th>
                        <th class="!bg-transparent border-b border-gray-200 dark:border-white/[0.08] !py-0 !px-0 text-[10px] font-black text-gray-400 dark:text-gray-555 uppercase tracking-[0.2em] text-right w-32">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-150 dark:divide-white/[0.06]">
                    @forelse($exams as $index => $exam)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-themeNavy/25 transition-colors">
                        <td class="py-0 px-0 text-center font-mono font-black text-gray-555 dark:text-gray-400 text-sm">{{ $index + 1 }}</td>
                        <td class="py-0 px-0 text-sm font-bold text-gray-900 dark:text-gray-100">{{ $exam->name }}</td>
                        <td class="py-0 px-0 text-sm font-bold text-gray-600 dark:text-gray-400">{{ $exam->sessionYear->session_name ?? 'N/A' }}</td>
                        <td class="py-0 px-0 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('exams.destroy', $exam->id) }}" method="POST" @submit.prevent="if (await showDanger('Delete Exam', 'Are you sure you want to delete this exam? This action cannot be undone.')) $el.submit()">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn text-red-600 hover:text-red-800 hover:border-red-600" title="Delete Exam">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-12 text-center text-gray-400 font-bold uppercase tracking-wider">No exams found. Create one above!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function examSetupPage() {
        return {
            dropdownOpen: false,
            sessionText: 'Select Session',
            form: {
                session_year_id: ''
            },
            
            selectSession(id, name) {
                this.form.session_year_id = id;
                this.sessionText = name;
                this.dropdownOpen = false;
            }
        };
    }
</script>
@endpush