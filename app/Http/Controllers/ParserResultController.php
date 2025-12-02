<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ParserResultController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $query = Site::query();

        $sites = $query->get();

        $articles = $this->getArticles($request, $user, true);


        return view('index', [
            'articles' => $articles,
            'sites' => $sites,
        ]);
    }

    private function getArticles($request, $user, $isPaginate = true)
    {

        $sitesFilter = $request->get('sites');
        $sortFilter = $request->get('sort');
        $dateRange = $request->get('date_range');
        $search = $request->get('search');

        $filterRocket = $request->get('filter_rocket');
        $filterFire = $request->get('filter_fire') ?? ($user && $user->role_id == 2) ? 1 : false;

        $fieldSort = 'views';
        $typeSort = 'asc';

        switch ($sortFilter) {
            case 'views_asc':
                $fieldSort = 'views';
                $typeSort = 'asc';
                break;
            case 'views_desc':
                $fieldSort = 'views';
                $typeSort = 'desc';
                break;
            case 'speed_asc':
                $fieldSort = 'speed';
                $typeSort = 'asc';
                break;
            case 'speed_desc':
                $fieldSort = 'speed';
                $typeSort = 'desc';
                break;

        }

        $articles = Article::query()
            ->with(['stats', 'site'])
            ->when($dateRange, function ($query, $dateRange) {
                [$dateFrom, $dateTo] = explode(' - ', $dateRange);
                $query->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            })
            ->when($sitesFilter, function ($query, $sitesFilter) {
                $query->whereIn('site_id', $sitesFilter);
            })
            ->when($filterFire, function ($query) {
                $query->where('speed_x', '>', 0);
            })
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'ilike', "%{$search}%");
            })
            ->when($filterRocket, function ($query) {
                $query->where('is_very_fast', true);
            })
            ->when($sortFilter, function ($query) use ($fieldSort, $typeSort) {

                if ($fieldSort === 'views') {
                    $query->addSelect([
                        'last_views' => DB::table('article_stats')
                            ->select('views')
                            ->whereColumn('article_stats.article_id', 'articles.id')
                            ->orderByDesc('created_at')
                            ->limit(1)
                    ])
                        ->orderBy('last_views', $typeSort);
                }

                if ($fieldSort === 'speed') {
                    $subquery = DB::table('article_stats')
                        ->select('views_speed')
                        ->whereColumn('article_stats.article_id', 'articles.id')
                        ->orderByDesc('article_stats.created_at')
                        ->limit(1);

                    $query->addSelect([
                        'last_speed' => $subquery
                    ])->orderByRaw('(COALESCE((' . $subquery->toSql() . '), 0)) ' . $typeSort, $subquery->getBindings());
                }

            })
            ->when(!$sortFilter, function ($query) {
                $query->orderBy('created_at', 'desc');
            });

        if ($isPaginate) {
            return $articles->paginate(100);
        }

        return $articles->limit(50000)->get();
    }

    public function export(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $user = auth()->user();

        $response = new StreamedResponse(function () use ($request, $user) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Дата публікації', 'Сайт', 'Заголовок', 'Перегляди',
                'Швидкість за годину', 'Ракета', 'Вогонь', 'Title', 'Meta description'
            ]);

            $articles = $this->getArticles($request, $user, false);

            foreach ($articles as $article) {
                $lastStat = $article->stats->sortByDesc('id')->first();

                fputcsv($handle, [
                    $article->created_at->format('d.m.Y H:i'),
                    $article->site->name,
                    html_entity_decode((string)$article->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    $lastStat->views ?? 0,
                    round($lastStat->views_speed ?? 0),
                    $article->is_very_fast ? 'Ракета' : '',
                    $article->speed_x > 0 ? 'Огонь' : '',
                    $article->meta_title,
                    $article->meta_description
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="articles.csv"');

        return $response;
    }

}
