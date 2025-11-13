@extends('layouts.app')

@section('content')
    <div class="min-h-[calc(100vh-180px)] flex justify-center items-center py-12">
        <div class="w-full max-w-md bg-white shadow-lg rounded-lg p-8">

            <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Авторизація</h2>

            @if(session('error'))
                <div class="mb-4 p-3 text-sm bg-red-100 text-red-600 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        class="w-full px-4 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-indigo-400"
                        placeholder="Введіть email"
                    >
                    @error('email')
                    <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-4 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-indigo-400"
                        placeholder="Введіть пароль"
                    >
                    @error('password')
                    <div class="text-sm text-red-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Login Button -->
                <button
                    type="submit"
                    class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    Увійти
                </button>

            </form>
        </div>
    </div>
@endsection
