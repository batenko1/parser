@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-6">

        <h1 class="text-2xl font-bold mb-6">Результати парсингу новин</h1>

        <form method="GET" action="{{ route('index') }}" class="mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сайт</label>
                    <select name="site" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200">
                        <option value="">Усі сайти</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" {{ request('site') == $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Діапазон дат</label>
                    <input type="text" name="date_range" id="date-range"
                           value="{{ request('date_from') && request('date_to') ? request('date_from') . ' - ' . request('date_to') : '' }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200" autocomplete="off">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Сортування</label>
                    <select name="sort" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200">
                        <option value="">Без сортування</option>
                        <option value="asc" {{ request('sort') === 'asc' ? 'selected' : '' }}>Дата ↑</option>
                        <option value="desc" {{ request('sort') === 'desc' ? 'selected' : '' }}>Дата ↓</option>
                    </select>
                </div>

                <div>
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                        Застосувати
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-x-auto bg-white shadow-md rounded-lg">
            <table class="min-w-full text-sm text-left text-gray-600">
                <thead class="bg-gray-100 text-gray-800 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">Дата публікації</th>
                    <th class="px-4 py-3">Сайт</th>
                    <th class="px-4 py-3">Заголовок</th>
                    <th class="px-4 py-3">Перегляди</th>
                    <th class="px-4 py-3">Посилання</th>


                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($articles as $idx => $article)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleRow({{ $idx }})">
                        <td class="px-4 py-3 font-medium">{{ $article->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $article->site->name }}</td>
                        <td class="px-4 py-3">{{ $article['title'] }}</td>
                        <td class="px-4 py-3">
                            {{ $article->stats->last()?->views }}
                        </td>
                        <td class="px-4 py-3 text-blue-600">
                            <a href="{{ $article['link'] }}" target="_blank" class="hover:underline">
                                Перейти
                            </a>
                        </td>
                    </tr>

                    <tr id="details-{{ $idx }}" class="hidden bg-gray-50">
                        <td colspan="4" class="px-6 py-4">
                            <h3 class="font-semibold text-gray-700 mb-2">Історія переглядів</h3>
                            <ul class="space-y-1 text-sm text-gray-600">
                                @foreach($article->stats ?? [] as $stat)
                                    <li class="flex justify-between border-b pb-1">
                                        <span>{{ $stat->created_at->format('d.m.Y H:i') }}</span>
                                        <span class="font-semibold">{{ $stat->views }}</span>
                                    </li>
                                @endforeach
                                @if($article->stats->count() === 0)
                                    <li class="text-gray-500">Немає даних</li>
                                @endif
                            </ul>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                            Немає даних
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $articles->appends(request()->query())->links('pagination::tailwind') }}
        </div>

    </div>

    <script>
        function toggleRow(idx) {
            const row = document.getElementById('details-' + idx);
            row.classList.toggle('hidden');
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const start = "{{ request('date_from') }}";
            const end = "{{ request('date_to') }}";

            $('#date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Очистити',
                    format: 'YYYY-MM-DD',
                    applyLabel: 'Вибрати',
                    customRangeLabel: 'Свої дати'
                },
                ranges: {
                    'Сьогодні': [moment(), moment()],
                    'Вчора': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Останні 7 днів': [moment().subtract(6, 'days'), moment()],
                    'Останні 30 днів': [moment().subtract(29, 'days'), moment()],
                    'Цей місяць': [moment().startOf('month'), moment().endOf('month')],
                    'Минулий місяць': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                startDate: start ? moment(start) : moment().subtract(29, 'days'),
                endDate: end ? moment(end) : moment()
            }, function (startDate, endDate, label) {
                $('#date-range').val(startDate.format('YYYY-MM-DD') + ' - ' + endDate.format('YYYY-MM-DD'));
            });

            if (start && end) {
                $('#date-range').data('daterangepicker').setStartDate(start);
                $('#date-range').data('daterangepicker').setEndDate(end);
                $('#date-range').val(start + ' - ' + end);
            }

            $('#date-range').on('cancel.daterangepicker', function (ev, picker) {
                $(this).val('');
            });
        });
    </script>
@endsection
