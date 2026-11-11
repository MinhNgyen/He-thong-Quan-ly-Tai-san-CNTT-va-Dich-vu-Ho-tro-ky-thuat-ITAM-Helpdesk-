@if (session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm mb-5 flex items-center space-x-2">
        <i class="fa-solid fa-circle-check text-emerald-500"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if ($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm mb-5">
        <ul class="list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
