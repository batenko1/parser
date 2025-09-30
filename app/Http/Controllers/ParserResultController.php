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
        $siteFilter = $request->get('site');
        $sortFilter = $request->get('sort');
        $dateRange = $request->get('date_range');

        $query = Site::query();

        $sites = $query->get();

        $articles = Article::query()
            ->with(['stats', 'site'])
            ->when($dateRange, function ($query, $dateRange) {
                [$dateFrom, $dateTo] = explode(' - ', $dateRange);
                $query->whereDate('created_at', '>=', $dateFrom)
                    ->whereDate('created_at', '<=', $dateTo);
            })
            ->when($siteFilter, function ($query, $siteFilter) {
                $query->where('site_id', $siteFilter);
            })
            ->when($sortFilter, function ($query, $sortFilter) {
                $query->addSelect([
                    'last_views' => DB::table('article_stats')
                        ->select('views')
                        ->whereColumn('article_stats.article_id', 'articles.id')
                        ->limit(1)
                ])
                    ->orderBy('last_views', $sortFilter);
            })

            ->paginate(30);

        return view('index', [
            'articles' => $articles,
            'sites'    => $sites,
            'current'  => $siteFilter,
        ]);
    }
}
