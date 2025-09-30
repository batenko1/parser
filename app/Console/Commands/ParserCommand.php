<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\ArticleService;
use App\Services\ParserSites\CensorParseService;
use App\Services\ParserSites\GlavredParseService;
use App\Services\ParserSites\ObozrevatelParseService;
use App\Services\ParserSites\RadiotrekParseService;
use App\Services\ParserSites\RbcParseService;
use App\Services\ParserSites\TsnParseService;
use App\Services\ParserSites\Tv24ParseService;
use App\Services\ParserSites\UnianParseService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ParserCommand extends Command
{
    protected $signature = 'app:parser-command';
    protected $description = 'Парсинг RSS новостей';

    public function handle(): void
    {
        info('Start command');
        $sites = Site::query()->get();

        foreach ($sites as $site) {
            $this->info("Парсим сайт: {$site->name}");

            try {

                try {
                    $xmlData = file_get_contents($site->link);
                }
                catch (\Exception $e) {
                    $response = Http::withHeaders([
                        'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept'          => 'application/rss+xml,application/xml;q=0.9,*/*;q=0.8',
                    ])->get($site->link);

                    if ($response->successful()) {
                        $xmlData = $response->body();
                    }
                }

                if (!$xmlData) {
                    $this->error("Не удалось получить данные с {$site->link}");
                    continue;
                }

                $xml = new \SimpleXMLElement($xmlData);

                $items = $xml->xpath('//channel/item');

                $items = array_slice($items, 0, 20);

                foreach ($items as $item) {
                    $title = (string) $item->title;
                    $link  = (string) $item->link;

                    $views = $this->getArticleStat($site->name, $link);

                    ArticleService::storeData($title, $link, $site->id, $views);
                }
            } catch (Exception $e) {
                $this->error("Ошибка при парсинге {$site->name}: " . $e->getMessage());
            }
        }
    }

    private function getArticleStat($siteName, $link): int|string
    {
        $views = 0;
        $service = null;
        switch ($siteName) {
            case 'Unian':
                $service = app(UnianParseService::class);
                break;
            case 'TSN';
                $service = app(TsnParseService::class);
                break;
            case 'Radiotrek':
                $service = app(RadiotrekParseService::class); //--
                break;
            case 'Glavred':
                $service = app(GlavredParseService::class);
                break;
            case 'RBC':
                $service = app(RbcParseService::class); //--
                break;
            case '24tv':
                $service = app(Tv24ParseService::class);
                break;
            case 'Censor':
                $service = app(CensorParseService::class);
                break;
            case 'Obozrevatel':
                $service = app(ObozrevatelParseService::class);;
                break;
        }

        if($service) {
            $views = $service->parse($link);
        }

        return $views;
    }
}
