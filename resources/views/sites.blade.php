@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold mb-8 text-center text-gray-800">Проекти</h1>

        <form method="POST" action="{{ route('sites.save') }}" class="bg-white rounded-xl shadow-lg p-8 max-w-4xl mx-auto border border-gray-100">
            @csrf

            <div class="space-y-6">
                @foreach($sites as $site)
                    <div class="border border-gray-100 rounded-lg p-5 hover:shadow-md transition-shadow duration-200">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                            <div class="w-full sm:w-1/3">
                                <h2 class="text-lg font-semibold text-gray-900">{{ $site->name }}</h2>
                            </div>

                            <div class="w-full sm:w-1/3">
                                <label for="flame_{{ $site->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    🔥 Вогник
                                </label>
                                <input
                                    type="number"
                                    name="sites[{{ $site->id }}][flame]"
                                    id="flame_{{ $site->id }}"
                                    value="{{ old('sites.' . $site->id . '.flame', $site->speed_x ?? '') }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Введіть число"
                                    min="0"
                                />
                            </div>

                            <div class="w-full sm:w-1/3">
                                <label for="rocket_{{ $site->id }}" class="block text-sm font-medium text-gray-700 mb-1">
                                    🚀 Ракета
                                </label>
                                <input
                                    type="number"
                                    name="sites[{{ $site->id }}][rocket]"
                                    id="rocket_{{ $site->id }}"
                                    value="{{ old('sites.' . $site->id . '.rocket', $site->very_fast_value ?? '') }}"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    placeholder="Введіть число"
                                    min="0"
                                />
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10 text-center">
                <button
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-10 py-3 rounded-md text-lg shadow-md hover:shadow-lg transition-all"
                >
                    💾 Зберегти
                </button>
            </div>
        </form>
    </div>
@endsection
