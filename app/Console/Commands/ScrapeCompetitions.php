<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Competition;
use Spatie\Browsershot\Browsershot;


class ScrapeCompetitions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'competitions:scrape';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape Taiwanese music competition websites';

    /**
     * Execute the console command.
     */
    public function handle() {
        $sites = [
            'https://www.taiwanmusic.org/',                                         // 文化盃音樂大賽
            'https://www.taiwanmusic.org/2026_culturalcup_001.html',
            'https://contest.fso.org.tw/index.php/index/application',              // 藝享盃音樂大賽
            'https://web.arte.gov.tw/music/',                                       // 全國學生音樂比賽決賽
            'https://www.asianmusic.com.tw/%E5%8D%80%E5%9F%9F%E8%B3%BD%E7%B0%A1%E7%AB%A0/', // 亞洲盃國際音樂大賽
            'https://www.taiwanpianofestival.com/schedule--venues.html',            // 國際大師鋼琴藝術節
            'https://www.taiwanpianofestival.com/zh/competition?tab=schedule',
            'https://www.taipeimaf.com/',                                           // Taipei Music Academy & Festival
            'https://www.kfem.info/%E5%8F%B0%E7%81%A3%E5%9C%8B%E9%9A%9B%E6%96%B0%E4%B8%96%E4%BB%A3-%E5%A0%B1%E5%90%8D%E7%B0%A1%E7%AB%A0',
            'https://www.kawai.com.tw/index.php/page/contest/',                     // Kawai
            'https://www.cheer100music.com/',                                       // 大台南國際音樂大賽
            'https://art.tc.edu.tw/music/',                                         // 臺中市學生音樂比賽
            'https://www.asiangifted.org/0506',                                     // 亞洲音樂節青少年演奏公開賽
            'https://www.music-taiwan.com/en/piano',                                // Taipei Excellence Piano & Violin
            'https://event.culture.tw/mocweb/reg/NTSO/Detail.init.ctr?actId=50121', // NTSO 國際青少年管弦樂營
            'https://piano.ntdtv.com/',                                             // 新唐人國際鋼琴大賽
            'https://www.seidof.com.tw/news.php',                                   // JVC勝利盃全國音樂大賽
            'https://tw.yamaha.com/zh/news/',                      // Yamaha Taiwan events & competitions
            'https://www.taiwanpaa.org/competition',            // 茱藝雅盃音樂大賽
        ];

        foreach($sites as $url) {
            $this->scrapeAndStore($url);
            sleep(60);
        }

        $indexSites = [
            'https://www.tpcityart.tp.edu.tw/' => ['ul.clearfix li a', 'https://www.tpcityart.tp.edu.tw'],
            'https://www.music-taiwan.com/zh-tw/global/activity' => ['div.standard-service h3 a', 'https://www.music-taiwan.com/zh-tw/global/activity'],
            'https://sites.google.com/view/bnwmusic' => ['a.XqQF9c', 'https://sites.google.com'],
            'https://www.oneandonlymusic.com.tw/' => ['a[data-testid="linkElement"]', 'https://www.oneandonlymusic.com.tw']
        ];

        foreach($indexSites as $indexUrl => [$selector, $baseUrl]) {
            $this->scrapeIndexAndStore($indexUrl, $selector, $baseUrl);
            sleep(60);
        }
    }

    private function scrapeIndexAndStore(string $indexUrl, string $linkSelector, string $baseUrl = '') {
        $this->info("Loading index: $indexUrl");

        // $html = Browsershot::url($indexUrl)->waitUntilNetworkIdle()->bodyHtml();
         $html = Browsershot::url($indexUrl)
            ->setChromePath('/usr/bin/chromium')
            ->setNodeBinary('/usr/bin/node')
            ->setNpmBinary('/usr/bin/npm')
            ->setNodeModulePath(base_path('node_modules'))
            ->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
                'disable-dev-shm-usage',
            ])
        ->waitUntilNetworkIdle()
        ->bodyHtml();
        $crawler = new Crawler($html);

        $links = $crawler->filter($linkSelector)->each(function ($node) use ($baseUrl) {
            $href = $node->attr('href');
            $text = $node->text();
            $combined = $href . $text;
            if(!str_contains($combined, '2026') && !str_contains($combined, '115')) {
                return null;
            }
            return str_starts_with($href, 'http') ? $href : $baseUrl . $href;
        });

        $links = array_values(array_filter($links));
        $this->info("Found " . count($links) . " matching links");

        foreach($links as $url) {
            $this->scrapeAndStore($url);
            sleep(60);
        }
    }

    private function scrapeAndStore($url) {
        $encodedUrl = preg_replace_callback('/[^\x00-\x7F]+/u', fn($m) => rawurlencode($m[0]), $url);
        // $html = Browsershot::url($encodedUrl)
        //     ->timeout(60)
        //     ->waitUntilNetworkIdle()
        //     ->bodyHtml();
        $html = Browsershot::url($encodedUrl)
            ->setChromePath('/usr/bin/chromium')
            ->setNodeBinary('/usr/bin/node')
            ->setNpmBinary('/usr/bin/npm')
            ->setNodeModulePath(base_path('node_modules'))
            ->addChromiumArguments([
                'no-sandbox',
                'disable-setuid-sandbox',
                'disable-dev-shm-usage',
            ])
            ->timeout(60)
            ->waitUntilNetworkIdle()
            ->bodyHtml();


        $crawler = new Crawler($html);
        $text = $crawler->filter('body')->text();
        $text = mb_substr($text, 0, 18000);

        $this->info("Text length: " . strlen($text));

        if (empty(trim($text))) {
            $this->info("Empty page, skipping.");
            return;
        }

        $data = $this->extract($text);
        $this->info("Competitions found: " . count($data));

        foreach($data as $competition) {
            $name = $competition['name'] ?? '';
            $location = $competition['location'] ?? '';
            $date = $competition['date'] ?? '';

            $this->info("Name: " . $name);
            $this->info("Date: " . $date);
            $this->info("Location: " . $location);

            $genericCities = ['台北', '台中', '台南', '高雄', '新北', '桃園', '基隆', '新竹', '嘉義', '台灣', '線上', '網路', 'online'];
            $stripped = preg_replace('/^台灣/', '', trim($location));

            if(in_array(trim($location), $genericCities) || in_array($stripped, $genericCities) || $stripped === '') {
                $location = '';
            }       
            if(in_array($location, ['蘆洲功學社音樂廳', '台灣-功學社蘆洲音樂廳', '功學社蘆洲音樂廳'])) $location = '蘆洲功學社';

            $location = preg_replace('/(大學|學院|學校|國民中學|國民小學|中學|高中|高職|國小|國中).+$/u', '$1', $location);

            [$lat, $lng] = $this->geocode($location);
            $this->info("Coords: $lat , $lng");

            Competition::updateOrCreate(
                ['source_url' => $url, 'name' => $name, 'date' => $date],
                [
                    'location' => $location,
                    'lat'      => $lat,
                    'lng'      => $lng,
                ]
            );
        }
    }

    private function extract(string $text): array
    {
        $models = [
            'llama-3.3-70b-versatile',
            'openai/gpt-oss-120b',
            'llama-3.1-8b-instant',
            'openai/gpt-oss-20b',
        ];

        foreach($models as $model) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0,
                'max_tokens' => 1700,
                'messages' => [[
                    'role'    => 'user',
                    'content' => "Extract all music competitions from this text. You MUST always include name, date, and location for every item. For location: use the specific school, venue, or institution name where the competition is held — this is often found under 承辦單位, 比賽地點, 辦理地點, or 地點. Add '台灣' in front of the location if a specific venue is found, use empty string if no specific venue is found. Do not return just a city name like 台北市 — always use the specific school or venue name. If the location is a hall or auditorium inside a school or institution, return only the school or institution name, not the hall name. If the competition is online or virtual, use empty string. Return JSON array only, no markdown: [{ \"name\":\"\",\"date\":\"\",\"location\":\"\"}]\n\n" . $text,
                ]],
            ]);

            $this->info("Model: $model | Status: " . $response->status());

            if($response->status() === 429) continue;

            $text = $response->json('choices.0.message.content');
            $this->info("Response: " . $text);

            return json_decode($text, true) ?? [];
        }

        $this->info("All models exhausted.");
        return [];
    }

    private function geocode(string $address): array
    {
        if(preg_match('/[\S]*[市縣][\S]*?[區鄉鎮市][\S]*?(?:路|街|大道)[一二三四五六七八九十\d]*段?/u', $address, $matches)) {
            $address = $matches[0];
        }

        $address = preg_replace('/\d+號.*$|\d+$|[（()）<>]/u', '', $address);
        $address = trim($address);
        $this->info("Geocoding: " . $address);

        if(empty($address)) return [null, null];

        $response = Http::get('https://nominatim.openstreetmap.org/search', [
            'q'               => $address,
            'format'          => 'json',
            'limit'           => 1,
            'accept-language' => 'zh-TW',
        ]);

        $data = $response->json();
        return [$data[0]['lat'] ?? null, $data[0]['lon'] ?? null];
    }
}
