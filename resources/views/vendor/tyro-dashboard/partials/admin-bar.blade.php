@php
    $hasNotice = \HasinHayder\TyroDashboard\Services\AdminNotice::hasNotice();
@endphp

@if($hasNotice)
    @php
        $message = \HasinHayder\TyroDashboard\Services\AdminNotice::getMessage();
        $bgColor = \HasinHayder\TyroDashboard\Services\AdminNotice::getBgColor();
        $textColor = \HasinHayder\TyroDashboard\Services\AdminNotice::getTextColor();
        $align = \HasinHayder\TyroDashboard\Services\AdminNotice::getAlign();
        $height = \HasinHayder\TyroDashboard\Services\AdminNotice::getHeight();
        
        $justifyContent = 'flex-start';
        if ($align === 'center') {
            $justifyContent = 'center';
        } elseif ($align === 'right') {
            $justifyContent = 'flex-end';
        }
    @endphp
    <div id="tyro-admin-bar" class="fixed top-0 left-0 w-screen z-[9999] flex items-center px-4 box-border text-{{ $align }} justify-{{ $align === 'center' ? 'center' : ($align === 'right' ? 'end' : 'start') }}" style="height: {{ $height }}; background-color: {{ $bgColor }}; color: {{ $textColor }};">
        {!! $message !!}
    </div>
    <style>
        :root {
            --admin-bar-height: {{ $height }};
        }
        .dashboard-layout {
            padding-top: var(--admin-bar-height);
        }
        .sidebar {
            top: var(--admin-bar-height) !important;
            height: calc(100vh - var(--admin-bar-height)) !important;
        }
    </style>
@endif
