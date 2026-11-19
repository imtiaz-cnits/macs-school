@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
        <!-- Results Summary -->
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
            Showing
            <span class="font-mono font-black text-gray-900 dark:text-white">{{ number_format($paginator->firstItem() ?? 0) }}</span>
            to
            <span class="font-mono font-black text-gray-900 dark:text-white">{{ number_format($paginator->lastItem() ?? 0) }}</span>
            of
            <span class="font-mono font-black text-gray-900 dark:text-white">{{ number_format($paginator->total()) }}</span>
            results
        </div>

        <!-- Mobile Prev / Next Controls -->
        <div class="flex sm:hidden items-center gap-2 w-full justify-between">
            @if ($paginator->onFirstPage())
                <span class="h-9 px-4 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white/60 dark:bg-themeNavy/60 text-xs font-black uppercase tracking-wider text-gray-400 inline-flex items-center gap-1.5 opacity-50 cursor-not-allowed">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="h-9 px-4 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 hover:border-themeBlue hover:text-themeBlue inline-flex items-center gap-1.5 transition-all">
                    Previous
                </a>
            @endif

            <span class="text-xs font-mono font-black text-gray-600 dark:text-gray-300">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="h-9 px-4 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-xs font-black uppercase tracking-wider text-gray-700 dark:text-gray-200 hover:border-themeBlue hover:text-themeBlue inline-flex items-center gap-1.5 transition-all">
                    Next
                </a>
            @else
                <span class="h-9 px-4 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white/60 dark:bg-themeNavy/60 text-xs font-black uppercase tracking-wider text-gray-400 inline-flex items-center gap-1.5 opacity-50 cursor-not-allowed">
                    Next
                </span>
            @endif
        </div>

        <!-- Desktop Numbered Pagination -->
        <div class="hidden sm:flex items-center gap-1.5">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Previous"
                      class="h-9 w-9 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white/60 dark:bg-themeNavy/60 text-gray-300 dark:text-gray-600 inline-flex items-center justify-center cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous"
                   class="h-9 w-9 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue hover:bg-sky-50/50 dark:hover:bg-themeBlue/10 inline-flex items-center justify-center transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="h-9 px-1.5 inline-flex items-center justify-center text-xs font-black text-gray-400 dark:text-gray-500 select-none">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="h-9 min-w-[2.25rem] px-3 rounded-xl bg-gradient-to-r from-themeBlue to-themeGreen text-white font-mono font-black text-xs shadow-sm shadow-themeBlue/25 inline-flex items-center justify-center">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}"
                               class="h-9 min-w-[2.25rem] px-3 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-gray-600 dark:text-gray-300 font-mono font-bold text-xs hover:border-themeBlue hover:text-themeBlue hover:bg-sky-50/50 dark:hover:bg-themeBlue/10 inline-flex items-center justify-center transition-all"
                               aria-label="Go to page {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next"
                   class="h-9 w-9 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white dark:bg-themeNavy text-gray-600 dark:text-gray-300 hover:border-themeBlue hover:text-themeBlue hover:bg-sky-50/50 dark:hover:bg-themeBlue/10 inline-flex items-center justify-center transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Next"
                      class="h-9 w-9 rounded-xl border border-gray-200 dark:border-white/[0.08] bg-white/60 dark:bg-themeNavy/60 text-gray-300 dark:text-gray-600 inline-flex items-center justify-center cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
