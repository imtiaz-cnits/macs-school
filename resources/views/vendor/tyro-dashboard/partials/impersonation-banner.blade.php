@if(session('impersonator_id'))
    @php
        $userModel = config('tyro-dashboard.user_model') ?: 'App\\Models\\User';
        $impersonator = $userModel::find(session('impersonator_id'));
        $currentUser = auth()->user();
    @endphp
    <div class="bg-gradient-to-br from-[#fef3c7] to-[#fde68a] border-b-2 border-[#f59e0b] py-3 px-4 md:px-6 sticky top-0 z-40 shadow-[0_2px_8px_rgba(0,0,0,0.1)]">
        <div class="flex flex-wrap md:flex-nowrap items-center gap-3 md:gap-4 max-w-[1400px] mx-auto">
            <div class="flex items-center justify-center w-10 h-10 bg-[#f59e0b]/20 rounded-full shrink-0">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-[#d97706]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="text-[#78350f] text-sm leading-relaxed w-full md:w-auto md:flex-1 order-2 md:order-none">
                <strong class="text-[#92400e] font-semibold">Impersonation Mode:</strong>
                You are currently logged in as <strong class="text-[#92400e] font-semibold">{{ $currentUser->name }}</strong> ({{ $currentUser->email }}).
                Originally logged in as <strong class="text-[#92400e] font-semibold">{{ $impersonator->name ?? 'Admin' }}</strong>.
            </div>
            <form action="{{ route('tyro-dashboard.leave-impersonation') }}" method="POST" class="m-0 shrink-0 order-3 md:order-none w-full md:w-auto">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 py-2 px-4 bg-white text-[#92400e] border border-[#d97706] rounded-md text-sm font-medium cursor-pointer transition-all duration-150 ease-in-out whitespace-nowrap hover:bg-[#fffbeb] hover:border-[#b45309] hover:-translate-y-px hover:shadow-[0_2px_4px_rgba(0,0,0,0.1)] w-full justify-center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Leave Impersonation
                </button>
            </form>
        </div>
    </div>
@endif
