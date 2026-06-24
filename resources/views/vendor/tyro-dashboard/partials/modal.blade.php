<!-- Global Modal Component -->
<div id="globalModal" class="group/overlay fixed inset-0 bg-black/50 backdrop-blur-[2px] flex items-center justify-center z-[9999] opacity-0 invisible transition-all duration-200 ease-in-out [&.active]:opacity-100 [&.active]:visible">
    <div class="bg-[var(--card)] rounded-lg border border-[var(--border)] shadow-md max-w-[420px] sm:w-[90%] w-[95%] sm:m-0 m-2 transform scale-95 translate-y-[10px] transition-transform duration-200 ease-in-out overflow-hidden relative group-[.active]/overlay:scale-100 group-[.active]/overlay:translate-y-0">
        <div class="flex flex-col">
            <div class="p-5 sm:p-6">
                <div class="flex gap-3 sm:gap-4 items-start">
                    <div id="globalModalIcon" class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-[var(--muted)] text-[var(--foreground)] [&_svg]:w-5 [&_svg]:h-5 [&_svg]:stroke-2"></div>
                    <div class="flex-1">
                        <h2 id="globalModalTitle" class="text-base font-semibold text-[var(--foreground)] m-0 mb-1 leading-normal"></h2>
                        <p id="globalModalMessage" class="text-sm text-[var(--muted-foreground)] leading-relaxed m-0"></p>
                        <div id="globalModalPromptInput" class="mt-4" style="display: none;">
                            <input type="text" id="promptInput" class="form-input w-full text-sm" placeholder="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 p-4 sm:p-6 sm:pt-0 pt-0 bg-transparent flex-row sm:flex-row flex-col-reverse">
                <button type="button" id="globalModalCancel" class="btn bg-transparent text-[var(--foreground)] border border-[var(--border)] py-2 px-4 text-sm font-medium rounded-md cursor-pointer transition-all duration-150 ease-in-out hover:bg-[var(--muted)] hover:border-[var(--border)] sm:w-auto w-full" onclick="closeGlobalModal()">Cancel</button>
                <button type="button" id="globalModalConfirm" class="btn bg-[var(--foreground)] text-[var(--background)] border border-[var(--foreground)] py-2 px-4 text-sm font-medium rounded-md cursor-pointer transition-all duration-150 ease-in-out hover:opacity-90 active:opacity-95 sm:w-auto w-full"></button>
            </div>
        </div>
        <button type="button" class="absolute top-4 right-4 flex items-center justify-center w-7 h-7 rounded-md border-none bg-transparent text-[var(--muted-foreground)] cursor-pointer transition-all duration-150 ease-in-out hover:bg-[var(--muted)] hover:text-[var(--foreground)] p-0" onclick="closeGlobalModal()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-[18px] h-[18px]">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
