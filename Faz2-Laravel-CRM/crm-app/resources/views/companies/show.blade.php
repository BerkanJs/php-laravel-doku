<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $company->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if (session('success'))
                    <p>{{ session('success') }}</p>
                @endif

                @if ($company->logo_path)
                    <img src="{{ Storage::url($company->logo_path) }}" alt="{{ $company->name }} logosu" style="max-width: 150px;">
                @endif

                <p>Şehir: {{ $company->city }}</p>

                <h3 class="font-semibold mt-4">Kişiler</h3>
                <ul>
                    @forelse ($company->contacts as $contact)
                        <li>{{ $contact->name }} — {{ $contact->email }}</li>
                    @empty
                        <li>Henüz kişi eklenmemiş.</li>
                    @endforelse
                </ul>

                <p><a href="{{ route('contacts.create') }}">+ Yeni kişi ekle</a></p>

                <h3 class="font-semibold mt-4">Fırsatlar</h3>
                <ul>
                    @forelse ($company->deals as $deal)
                        <li>{{ $deal->title }} — {{ $deal->amount }} TL</li>
                    @empty
                        <li>Henüz fırsat eklenmemiş.</li>
                    @endforelse
                </ul>
                <p><a href="{{ route('deals.create') }}">+ Yeni fırsat ekle</a></p>

                <p><a href="{{ route('companies.index') }}">← Şirket listesine dön</a></p>

                @can('delete', $company)
                    <form method="POST" action="{{ route('companies.destroy', $company) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Şirketi Sil (admin)</button>
                    </form>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
