<x-app-layout title="Tambah pelanggan" :active-workshop="$activeWorkshop">
    <h1 class="text-3xl font-bold tracking-tight">Tambah pelanggan</h1>
    <p class="mt-3 break-words text-sm text-slate-500">Pelanggan akan ditambahkan ke {{ $activeWorkshop->name }}.</p>
    <form method="POST" action="{{ route('customers.store') }}" class="mt-8 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
        @include('customers._form')
    </form>
</x-app-layout>
