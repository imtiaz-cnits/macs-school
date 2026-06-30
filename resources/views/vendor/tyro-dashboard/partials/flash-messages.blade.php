@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        showSuccess({!! json_encode(session('success')) !!}, "Success");
    });
</script>
@endif

@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        showModal("Error", {!! json_encode(session('error')) !!}, "alert", { variant: 'danger', confirmText: 'OK' });
    });
</script>
@endif

@if (session('warning'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        showModal("Warning", {!! json_encode(session('warning')) !!}, "alert", { variant: 'info', confirmText: 'OK' });
    });
</script>
@endif

@if (session('info'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        showInfo({!! json_encode(session('info')) !!});
    });
</script>
@endif

@if ($errors->any() && config('tyro-dashboard.resource_ui.show_global_errors', true))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const errorList = [];
        @foreach ($errors->all() as $error)
            errorList.push({!! json_encode($error) !!});
        @endforeach
        showModal("Validation Error", errorList.join('\n'), "alert", { variant: 'danger', confirmText: 'OK' });
    });
</script>
@endif
