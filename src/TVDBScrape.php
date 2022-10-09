<?php

namespace datagutten\tvdb;

use datagutten\tvdb\exceptions\HTTPError;
use datagutten\tvdb\exceptions\tvdbException;
use DOMDocument;
use DOMNode;
use DOMXPath;
use WpOrg\Requests;

/**
 * Main TVDB class for live fetching (no cache)
 */
class TVDBScrape
{
    public Requests\Session $session;
    public bool $cache_breaker = false;

    public function __construct()
    {
        $this->session = new Requests\Session('https://www.thetvdb.com/');
    }

    /**
     * @param $slug
     * @param null $lang
     * @return objects\Series
     * @throws tvdbException
     */
    public function series($slug, $lang = null): objects\Series
    {
        /*$xpath = $this->get_xpath('/series/' . $slug);
        $scraper = new scraper\Series($xpath, $lang);*/
        return new objects\Series(slug: $slug, language: $lang, tvdb: $this, /*scraper: $scraper*/);
    }

    /*    public function episode_obj(string $series_slug, int $episode_id)
        {
            $uri = sprintf('/series/%s/episodes/%d', $series_slug, $episode_id);
            $scraper = new scraper\EpisodeScraper::
        }*/

    /**
     * Get a page and return a DOMXpath object for the page
     * @param string $url Page URL
     * @return DOMXPath
     * @throws TVDBException
     */
    public function get_xpath(string $url): DOMXPath
    {
        $data = $this->get($url);
        $dom = new DOMDocument();
        @$dom->loadHTML($data);
        return new DOMXPath($dom);
    }

    /**
     * HTTP GET request
     * @param string $url URL to GET
     * @return string Response body
     * @throws HTTPError HTTP error
     */
    public function get(string $url): string
    {
        if ($this->cache_breaker)
            $url .= '?cb=' . rand();

        $response = $this->session->get($url);
        if (!$response->success)
        {
            $exception = Requests\Exception\Http::get_class($response->status_code);
            $exception2 = new HTTPError('HTTP error', 0, new $exception(null, $response));
            $exception2->response = $response;
            throw $exception2;
        }
        return $response->body;
    }

    /**
     * Get episode orders
     * @param string $slug Series slug
     * @return array Orders
     * @throws TVDBException
     * @deprecated Use series object method
     */
    public function orders(string $slug): array
    {
        $data = $this->get('series/' . $slug);
        $dom = new DOMDocument();
        @$dom->loadHTML($data);
        $xpath = new DOMXPath($dom);
        $orders_dom = $xpath->query('//ul[@class="nav nav-pills seasontype"]/li/a');
        $orders = [];
        foreach ($orders_dom as $order)
        {
            //$key = $order->getAttribute('data-type');
            $key = preg_replace('/.tab-(.+)/', '$1', $order->getAttribute('href'));
            $orders[$key] = $order->textContent;
        }
        return $orders;
    }

    /**
     * Get season
     * @param string $slug Series slug
     * @param int $season Season number
     * @param string $ordering Episode ordering
     * @return array Simple array with episode id as key and episode name as value
     * @throws HTTPError
     */
    public function season_simple(string $slug, int $season, string $ordering = 'official'): array
    {
        $data = $this->get(sprintf('/series/%s/seasons/%s/%d', $slug, $ordering, $season));
        preg_match_all('#(S[0-9]+E[0-9]+).+?episodes/([0-9]+)#s', $data, $matches);
        return array_combine($matches[2], $matches[1]);
    }

    /**
     * Get episodes
     * @param string $slug Series slug
     * @param string $ordering Episode ordering
     * @param bool $id_key Use episode id as key in returned array
     * @return objects\Episode[]
     * @throws TVDBException
     */
    public function episodes(string $slug, string $ordering = 'official', bool $id_key = false, int $season = null): array
    {
        $series = $this->series($slug);
        if(empty($season))
            return $series->all_episodes($ordering);
        else
            return $series->season($season, $ordering)->episodes();
    }

    /**
     * @param string $episode_href
     * @param array $languages
     * @return DOMNode|null
     * @throws tvdbException
     * @deprecated
     */
    public function episode(string $episode_href, $languages = []): ?DOMNode
    {
        $xpath = $this->get_xpath($episode_href);
        //$languages = self::languages($xpath);
        if ($languages)
        {
            foreach ($languages as $language)
            {
                $translation = $xpath->query(sprintf("//div[@id='translations']/div[@data-language=\"%s\"]", $language));
                if ($translation->length > 0)
                    break;
            }
        }
        else //Use first language
            $translation = $xpath->query("//div[@id='translations']/div");

        if (empty($translation))
            return null;
        return $translation->item(0);
    }
}