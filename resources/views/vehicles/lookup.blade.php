<x-app-layout title="Hasil pencarian pelat" :active-workshop="$activeWorkshop">
    <h1 class="text-2xl font-bold">Kendaraan belum ditemukan</h1>
    <p class="mt-4 break-words text-slate-600">Pelat <strong>{{ $plate }}</strong> belum tercatat di {{ $activeWorkshop->name }}.</p>
    @can('create', [\App\Models\Vehicle::class, $activeWorkshop])
        <a href="{{ route('vehicles.create', ['plate_number' => $plate]) }}" class="auth-button mt-6 w-auto">Tambah kendaraan dengan pelat ini</a>
    @else
        <p class="mt-4 text-sm text-slate-500">Minta pemilik atau kasir untuk menambahkan kendaraan.</p>
    @endcan
    <div class="mt-8 max-w-2xl">@include('vehicles._lookup')</div>
    <a href="{{ route('vehicles.index') }}" class="auth-link mt-6 inline-flex min-h-11 items-center">Kembali ke daftar kendaraan</a>
</x-app-layout>
