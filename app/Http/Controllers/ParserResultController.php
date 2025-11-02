<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParserResultController extends Controller
{
    public function index(Request $request)
    {
        $sitesFilter = $request->get('sites');
        $sortFilter = $request->get('sort');
        $dateRange = $request->get('date_range');

        $filterRocket = $request->get('filter_rocket');
        $filterFire = $request->get('filter_fire');

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

        $query = Site::query();

        $sites = $query->get();

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
            ->when($filterRocket, function ($query) {
                $query->where('is_very_fast', true);
            })
            ->when($sortFilter, function ($query) use ($fieldSort, $typeSort) {

                if($fieldSort === 'views'){
                    $query->addSelect([
                        'last_views' => DB::table('article_stats')
                            ->select('views')
                            ->whereColumn('article_stats.article_id', 'articles.id')
                            ->orderByDesc('created_at')
                            ->limit(1)
                    ])
                        ->orderBy('last_views', $typeSort);
                }

                if($fieldSort === 'speed'){
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
            })
            ->paginate(100);


        return view('index', [
            'articles' => $articles,
            'sites' => $sites,
        ]);
    }
}
