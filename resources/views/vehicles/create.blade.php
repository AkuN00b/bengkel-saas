<x-app-layout title="Tambah kendaraan" :active-workshop="$activeWorkshop">
    <h1 class="text-3xl font-bold">Tambah kendaraan</h1>
    <p class="mt-3 break-words text-sm text-slate-500">Kendaraan akan dicatat di {{ $activeWorkshop->name }}. Pelanggan boleh dikosongkan.</p>
    <form method="POST" action="{{ route('vehicles.store') }}" class="mt-8 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
        @include('vehicles._form')
    </form>
</x-app-layout>
