<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Kişi</h2>
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

                <form method="POST" action="{{ route('contacts.store') }}">
                    @csrf

                    <label>Ad
                        <input type="text" name="name" value="{{ old('name') }}">
                    </label>

                    <label>E-posta
                        <input type="email" name="email" value="{{ old('email') }}">
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
