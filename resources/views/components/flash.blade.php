@if (session('saved'))
    <div role="status" class="mb-4 rounded-2xl bg-success-soft px-4 py-3 text-sm font-semibold text-success">{{ session('saved') }}</div>
@endif
