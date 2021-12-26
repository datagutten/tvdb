<?php

namespace datagutten\tvdb;

use datagutten\tvdb\exceptions\HTTPError;
use datagutten\tvdb\exceptions\tvdbException;
use DateTimeImmutable;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use WpOrg\Requests;

class TVDBScrape
{
    public Requests\Session $session;

    public function __construct()
    {
        $this->session = new Requests\Session('https://www.thetvdb.com/');
    }

    /**
     * Get a page and return a DOMXpath object for the page
     * @param string $url Page URL
     * @return DOMXPath
     * @throws TVDBException
     */
    protected function get_xpath(string $url): DOMXPath
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
     * Get series title
     * @param DOMXPath $xpath
     * @return string
     */
    public static function series_title(DOMXPath $xpath): string
    {
        $title = $xpath->query('//div[@class="crumbs"]/a[starts-with(@href, "/series/")]');
        return trim($title->item(0)->textContent);
    }

    /**
     * Get episode orders
     * @param string $slug Series slug
     * @return array Orders
     * @throws TVDBException
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
     * @return array
     * @throws HTTPError
     */
    public function season(string $slug, int $season, string $ordering = 'official'): array
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
     * @return Episode[]
     * @throws TVDBException
     */
    public function episodes(string $slug, string $ordering = 'official', bool $id_key = false): array
    {
        $xpath = $this->get_xpath(sprintf('/series/%s/allseasons/%s', $slug, $ordering));
        $series = self::series_title($xpath);
        $episodes_xpath = $xpath->query('//li[@class="list-group-item"]');
        $episodes = [];
        /** @var DOMElement $episode */
        foreach ($episodes_xpath as $episode)
        {
            $episode_num = $xpath->query('h4/span', $episode)->item(0)->textContent;
            preg_match('/S([0-9]+)E([0-9]+)/', $episode_num, $matches);
            $episode_obj = new Episode(['series_slug' => $slug, 'series' => $series]);

            $episode_obj->season = intval($matches[1]);
            $episode_obj->episode = intval($matches[2]);

            $title = $xpath->query('h4/a', $episode)->item(0);
            $episode_obj->title = trim($title->textContent);
            $episode_obj->url = 'https://www.thetvdb.com/' . $title->getAttribute('href');
            $episode_obj->id = preg_replace('#.+/([0-9]+)$#', '$1', $episode_obj->url);

            $overview = $xpath->query('div[@class="list-group-item-text"]/div/div[@class="col-xs-9"]', $episode)->item(0);
            $episode_obj->description = trim($overview->textContent);

            $aired = $xpath->query('ul[@class="list-inline text-muted"]/li', $episode)->item(0);
            $episode_obj->date = DateTimeImmutable::createFromFormat('M d, Y', $aired->textContent);
            if (!$id_key)
                $episodes[] = $episode_obj;
            else
                $episodes[$episode_obj->id] = $episode_obj;
        }
        return $episodes;
    }

    /**
     * Get series banners
     * @param string $series_slug Series slug
     * @return array
     * @throws TVDBException
     */
    public function banners(string $series_slug): array
    {
        $xpath = $this->get_xpath('/series/' . $series_slug);
        $banners = $xpath->query('//a[@rel="artwork_banners"]');
        $banner_urls = [];
        foreach ($banners as $banner)
        {
            $banner_urls[] = $banner->attributes->getNamedItem('href')->textContent;
        }

        return $banner_urls;
    }

    /**
     * Get overview languages
     * @param DOMXPath $xpath
     * @return array
     */
    public static function languages(DOMXPath $xpath): array
    {
        $languages_dom = $xpath->query("//div[@id='translations']/div/@data-language");
        $languages = [];
        foreach ($languages_dom as $language)
        {
            $languages[] = $language->nodeValue;
        }
        return $languages;
    }

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

    function overview(string $episode_href, $languages = []): ?string
    {
        $translation = $this->episode($episode_href, $languages);
        if (empty($translation))
            return null;
        return $translation->firstChild->textContent;
    }

    function translation(string $episode_href, $languages = []): array
    {
        $translation = $this->episode($episode_href, $languages);
        if (empty($translation))
            return [null, null];
        $title = $translation->attributes->getNamedItem('data-title')->textContent;
        $overview = $translation->childNodes->item(1)->textContent;
        return [$title, $overview];
    }
}