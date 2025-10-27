<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SitesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $sites = [
            [
                'name' => 'Unian',
                'link' => 'https://rss.unian.net/site/news_ukr.rss',
            ],
            [
                'name' => 'TSN',
                'link' => 'https://tsn.ua/rss',
            ],
            [
                'name' => 'Radiotrek',
                'link' => 'https://radiotrek.rv.ua/rss/export.xml'
            ],
            [
                'name' => 'Glavred',
                'link' => 'https://glavred.net/rss'
            ],
            [
                'name' => 'RBC',
                'link' => 'https://www.rbc.ua/static/rss/all.rus.rss.xml'
            ],
            [
                'name' => '24tv',
                'link' => 'https://24tv.ua/rss'
            ],
            [
                'name' => 'Censor',
                'link' => 'https://assets.censor.net/rss/censor.net/rss_uk_news.xml'
            ],
            [
                'name' => 'Obozrevatel',
                'link' => 'https://www.obozrevatel.com/ukr/out/rss/lastnews.xml'
            ],
            [
                'name' => 'Vsviti',
                'link' => 'https://vsviti.com.ua/feed'
            ],
            [
                'name' => 'Focus',
                'link' => 'https://focus.ua/uk/modules/rss.php'
            ],
            [
                'name' => 'Korrespondent',
                'link' => 'http://k.img.com.ua/rss/ua/all_news2.0.xml'
            ],
            [
                'name' => 'Pravda',
                'link' => 'https://www.pravda.com.ua/rss'
            ],
            [
                'name' => 'Zaxid',
                'link' => 'https://zaxid.net/rss'
            ],
            [
                'name' => 'Unn',
                'link' => 'https://unn.ua/rss/news_uk.xml'
            ],
            [
                'name' => 'Itc',
                'link' => 'https://itc.ua/ua/feed'
            ],
            [
                'name' => 'Blik',
                'link' => 'https://blik.ua/rss.xml'
            ],
            [
                'name' => 'Defence-ua',
                'link' => 'https://defence-ua.com/rss/feed.xml'
            ]
        ];

        foreach ($sites as $site) {
            Site::query()->updateOrCreate(
                ['name' => $site['name']],
                ['link' => $site['link']]
            );
        }

    }
}
