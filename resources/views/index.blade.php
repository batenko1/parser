@extends('layouts.app')

@section('content')
    @php
        $selectedSites = collect(request()->input('sites', []))->map(fn($v)=>(string)$v)->toArray();
        if (empty($selectedSites) && request()->filled('site')) {
            $selectedSites = [(string)request('site')];
        }
    @endphp

    <div id="protected-content" style="display:none">
        <div class="container mx-auto py-6">
            <h1 class="text-2xl font-bold mb-6">Результати парсингу новин</h1>

            <form method="GET" action="{{ route('index') }}" class="mb-6" id="filter-form">
                <input type="hidden" name="sort" id="sort" value="{{ request('sort') }}">
                <div id="sites-hidden-container">
                    @foreach($selectedSites as $sid)
                        <input type="hidden" name="sites[]" value="{{ $sid }}">
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Діапазон дат</label>
                        <input type="text" name="date_range" id="date-range"
                               value="{{ request('date_range') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-indigo-200"
                               autocomplete="off">
                    </div>

                    <div>
                        <button type="submit"
                                class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                            Застосувати
                        </button>
                    </div>
                </div>

                @if(request('filter_rocket'))
                    <input type="hidden" name="filter_rocket" value="1">
                @endif
                @if(request('filter_fire'))
                    <input type="hidden" name="filter_fire" value="1">
                @endif
            </form>



            <div class="overflow-x-auto bg-white shadow-md rounded-lg relative">
                <table class="min-w-full text-sm text-left text-gray-600">
                    <thead class="bg-gray-100 text-gray-800 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Дата публікації</th>

                        <th class="px-4 py-3 relative">
                            <div class="flex items-center gap-1 relative">
                                <span>Сайт</span>
                                <button type="button" id="filterButton" class="relative p-1 hover:bg-gray-200 rounded">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M3 5a1 1 0 0 1 1-1h16a1 1 0 0 1 .8 1.6l-6.2 8.27a1 1 0 0 0-.2.6V19a1 1 0 0 1-1.45.9l-3-1.5A1 1 0 0 1 9 17.5v-3.03a1 1 0 0 0-.2-.6L2.2 5.6A1 1 0 0 1 3 5z"/>
                                    </svg>
                                    @if(count($selectedSites))
                                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center text-[10px] leading-none w-4 h-4 rounded-full bg-indigo-600 text-white">
                                        {{ count($selectedSites) }}
                                    </span>
                                    @endif
                                </button>
                            </div>

                            <div id="filterPopup" class="hidden absolute z-50 mt-2 w-[320px] bg-white border border-gray-200 rounded-lg shadow-lg p-2">
                                <div class="px-2 py-1 text-[11px] text-gray-500 uppercase">Обрати сайти</div>
                                <div class="max-h-64 overflow-auto px-1 py-1 space-y-1">
                                    @foreach($sites as $site)
                                        <label class="flex items-center gap-2 px-2 py-1 rounded hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" class="site-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                   value="{{ $site->id }}"
                                                {{ in_array((string)$site->id, $selectedSites) ? 'checked' : '' }}>
                                            <span class="text-sm text-gray-700">{{ $site->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex items-center justify-between gap-2 mt-2 px-2 pb-1">
                                    <button type="button" id="resetSites" class="text-sm text-gray-600 hover:text-gray-900 underline">
                                        Скинути
                                    </button>
                                    <div class="flex gap-2">
                                        <button type="button" id="closePopup" class="px-3 py-1.5 rounded border text-sm hover:bg-gray-50">
                                            Закрити
                                        </button>
                                        <button type="button" id="applySites" class="px-3 py-1.5 rounded bg-indigo-600 text-white text-sm hover:bg-indigo-700">
                                            Застосувати
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </th>

                        <th class="px-4 py-3">Заголовок</th>

                        <th class="px-4 py-3 cursor-pointer select-none" onclick="toggleSort('views')">
                            <div class="flex items-center gap-1">
                                <span>Перегляди</span>
                                @if(request('sort') === 'views_asc')
                                    <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3l5 7H5l5-7z"/></svg>
                                @elseif(request('sort') === 'views_desc')
                                    <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 17l-5-7h10l-5 7z"/></svg>
                                @else
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3l5 7H5l5-7zm0 14l-5-7h10l-5 7z"/></svg>
                                @endif
                            </div>
                        </th>

                        <th class="px-4 py-3 cursor-pointer select-none" onclick="toggleSort('speed')">
                            <div class="flex items-center gap-1">
                                <span>Швидкість за годину</span>
                                @if(request('sort') === 'speed_asc')
                                    <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3l5 7H5l5-7z"/></svg>
                                @elseif(request('sort') === 'speed_desc')
                                    <svg class="w-3 h-3 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M10 17l-5-7h10l-5 7z"/></svg>
                                @else
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M10 3l5 7H5l5-7zm0 14l-5-7h10l-5 7z"/></svg>
                                @endif
                            </div>
                        </th>

                        <th class="px-4 py-3 select-none text-center">
                            🚀
                            <input
                                type="checkbox"
                                id="filter-rocket"
                                {{ request('filter_rocket') ? 'checked' : '' }}
                                class="ml-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                        </th>
                        <th class="px-4 py-3 select-none text-center">
                            🔥
                            <input
                                type="checkbox"
                                id="filter-fire"
                                {{ request('filter_fire') ? 'checked' : '' }}
                                class="ml-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                        </th>

                    </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                    @foreach($articles as $idx => $article)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleRow({{ $idx }})">
                            <td class="px-4 py-3 font-medium">{{ $article->created_at->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 font-medium">{{ $article->site->name }}</td>
                            <td class="px-4 py-3">
                                <a
                                    rel="nofollow noreferrer"
                                    href="{{ $article->link }}"
                                   onclick="event.stopPropagation()"
                                   target="_blank" class="hover:underline">{{ html_entity_decode((string) $article->title, ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
                                </a>
                                </td>
                            <td class="px-4 py-3">{{ $article->stats->sortByDesc('id')->first()->views ?? 0 }}</td>
                            <td class="px-4 py-3">{{ round($article->stats->sortByDesc('id')->first()->views_speed ?? 0) }}</td>
                            <td class="px-4 py-3">
                                @if($article->is_very_fast)
                                    🚀
                                @endif
                            </td>
                            <td class="px-4 py-3">

                                @if($article->speed_x > 0)
                                    @for($i = 0; $i < min($article->speed_x, 3); $i++)
                                        🔥
                                    @endfor
                                @endif

                            </td>
                        </tr>

                        <tr id="details-{{ $idx }}" class="hidden bg-gray-50">
                            <td colspan="7" class="px-6 py-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Історія переглядів</h3>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach($article->stats()->orderBy('id')->get() as $stat)
                                        <li class="flex justify-between border-b pb-1">
                                            <span>{{ $stat->created_at->format('d.m.Y H:i') }}</span>
                                            <span class="font-semibold">{{ $stat->views }} / {{ $stat->views_speed ? round($stat->views_speed) : 0 }} в годину</span>
                                            @if($stat->error)
                                                <span>{{ $stat->error }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                    @if($article->stats->count() === 0)
                                        <li class="text-gray-500">Немає даних</li>
                                    @endif
                                </ul>

                                <br>

                                <div style="margin-bottom: 10px;">
                                    <b>Title</b> ({{ \Illuminate\Support\Str::length($article->meta_title) }} символов) -
                                    {{ $article->meta_title }}
                                </div>

                                <hr>

                                <div style="margin-bottom: 10px;">
                                    <b>Description</b> ({{ \Illuminate\Support\Str::length($article->meta_description) }} символов) -
                                    {{ $article->meta_description }}
                                </div>

                                <hr>

                                <div>
                                    <b>Text</b> ({{ preg_match_all('/[\p{L}\p{N}_]+/u', strip_tags($article->text)) }} слів) -
                                    {!! $article->text !!}
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $articles->appends(request()->query())->links('pagination::tailwind') }}
            </div>
        </div>
    </div>

    <div id="password-screen" class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="bg-white shadow-lg rounded-lg p-6 w-80 text-center">
            <h2 class="text-lg font-bold mb-4">🔒 Введіть пароль</h2>
            <input type="password" id="page-password"
                   class="w-full border border-gray-300 rounded px-3 py-2 mb-4 focus:ring focus:ring-indigo-200"
                   placeholder="Пароль">
            <button onclick="checkPassword()"
                    class="w-full bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                Увійти
            </button>
            <p id="error-msg" class="text-red-500 text-sm mt-2 hidden">Невірний пароль</p>
        </div>
    </div>

    <script>
        function toggleRow(idx) {
            document.getElementById('details-' + idx).classList.toggle('hidden');
        }

        const popup = document.getElementById('filterPopup');
        const button = document.getElementById('filterButton');

        button.addEventListener('click', (e) => {
            e.stopPropagation();
            popup.classList.toggle('hidden');
            const rect = button.getBoundingClientRect();
            popup.style.left = '30px';
            popup.style.top = '40px';
        });

        document.getElementById('closePopup').addEventListener('click', () => popup.classList.add('hidden'));

        document.getElementById('resetSites').addEventListener('click', () => {
            document.querySelectorAll('.site-checkbox').forEach(cb => cb.checked = false);
        });

        document.getElementById('applySites').addEventListener('click', () => {
            const checked = Array.from(document.querySelectorAll('.site-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            const container = document.getElementById('sites-hidden-container');
            container.innerHTML = '';
            checked.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'sites[]';
                input.value = val;
                container.appendChild(input);
            });

            document.getElementById('filter-form').submit();
        });

        document.addEventListener('click', (e) => {
            if (!popup.contains(e.target) && !button.contains(e.target)) {
                popup.classList.add('hidden');
            }
        });

        function toggleSort(column) {
            const params = new URLSearchParams(window.location.search);
            const current = params.get('sort');
            let newSort = '';

            if (column === 'views') {
                if (current === 'views_asc') newSort = 'views_desc';
                else if (current === 'views_desc') newSort = '';
                else newSort = 'views_asc';
            } else if (column === 'speed') {
                if (current === 'speed_asc') newSort = 'speed_desc';
                else if (current === 'speed_desc') newSort = '';
                else newSort = 'speed_asc';
            }

            const sortInput = document.getElementById('sort');
            sortInput.value = newSort;
            document.getElementById('filter-form').submit();
        }

        const CORRECT_PASSWORD = "12345";
        const STORAGE_KEY = "page_access_granted";
        function showContent() {
            document.getElementById("password-screen").style.display = "none";
            document.getElementById("protected-content").style.display = "block";
        }
        function checkPassword() {
            const input = document.getElementById("page-password").value;
            if (input === CORRECT_PASSWORD) {
                localStorage.setItem(STORAGE_KEY, "true");
                showContent();
            } else {
                document.getElementById("error-msg").classList.remove("hidden");
            }
        }
        document.addEventListener("DOMContentLoaded", () => {
            if (localStorage.getItem(STORAGE_KEY) === "true") showContent();
        });

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


        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('filter-form');

            function toggleHiddenInput(name, checked) {
                const existing = form.querySelector(`input[name="${name}"]`);
                if (existing) existing.remove();
                if (checked) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = '1';
                    form.appendChild(input);
                }
                form.submit();
            }

            const rocket = document.getElementById('filter-rocket');
            const fire = document.getElementById('filter-fire');

            rocket.addEventListener('change', () => toggleHiddenInput('filter_rocket', rocket.checked));
            fire.addEventListener('change', () => toggleHiddenInput('filter_fire', fire.checked));
        });
    </script>
@endsection
