<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Fırsat</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('deals.store') }}">
                    @csrf

                    <label>Başlık
                        <input type="text" name="title" value="{{ old('title') }}">
                    </label>

                    <label>Tutar
                        <input type="number" step="0.01" name="amount" value="{{ old('amount') }}">
                    </label>

                    <label>Şirket
                        <select name="company_id">
                            <option value="">— seç —</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <button type="submit">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
