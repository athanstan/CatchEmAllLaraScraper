<?php

namespace App\Observers\Pokemon;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Symfony\Component\DomCrawler\Crawler;

class PokemonGenerationScraperObserver extends CrawlObserver
{

    private $content;

    public function __construct()
    {
        $this->content = null;
    }

    /*
     * Called when the crawler will crawl the url.
     */
    public function willCrawl(UriInterface $url, ?string $linkText): void
    {
        Log::info('willCrawl', ['url' => $url]);
    }

    /*
     * Called when the crawler has crawled the given url successfully.
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null,
    ): void {
        Log::info("Crawled: {$url}");

        $crawler = new Crawler((string) $response->getBody());

        $genTableCrawler = $crawler->filter('h3')->reduce(function (Crawler $node) {
            return str_contains($node->text(), 'Generation I');
        })->nextAll()->filter('table')->first();

        $pokemonData = collect($genTableCrawler->filter('tr')->each(function (Crawler $tr, $i) {
            if (!$tr->filter('th')->count()) {
                return (object) [
                    'name' => $tr->filter('td')->eq(2)->text(),
                    'image' => $tr->filter('td img')->attr('src')
                ];
            }
            return null;
        }))->filter()->values();

        dd($pokemonData);
    }

    /*
     * Called when the crawler had a problem crawling the given url.
     */
    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null,
        ?string $linkText = null,
    ): void {
        Log::error("Failed: {$url}");
    }

    /*
     * Called when the crawl has ended.
     */
    public function finishedCrawling(): void
    {
        Log::info("Finished crawling");
    }
}
