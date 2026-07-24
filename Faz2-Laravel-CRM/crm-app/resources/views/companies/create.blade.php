<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Şirket</h2>
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

                <form method="POST" action="{{ route('companies.store') }}" enctype="multipart/form-data">
                    @csrf

                    <label>Ad
                        <input type="text" name="name" value="{{ old('name') }}">
                    </label>

                    <label>Şehir
                        <input type="text" name="city" value="{{ old('city') }}">
                    </label>

                    <label>Logo
                        <input type="file" name="logo">
                    </label>

                    <button type="submit">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
