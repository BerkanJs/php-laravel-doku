<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Şirketler</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p><a href="{{ route('companies.create') }}">+ Yeni şirket ekle</a></p>
                <ul>
                    @foreach ($companies as $company)
                        <li><a href="{{ route('companies.show', $company) }}">{{ $company->name }}</a> — {{ $company->city }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
