<?php

namespace datagutten\tvdb\scraper;

use datagutten\tvdb\exceptions\TVDBException;
use datagutten\tvdb\objects;
use DOMXPath;

class Series
{
    public DOMXPath $xpath;
    public string $slug;
    /**
     * @var ?string
     */
    public ?string $language = null;

    public function __construct(DOMXPath $xpath, ?string $language = null)
    {
        $this->xpath = $xpath;
        $this->language = $language;
    }

    public function scrape_data(): array
    {
        if (empty($this->language))
            $this->language = 'eng';

        list($title, $overview) = $this->translation($this->language);
        return [
            'title' => $title,
            'overview' => $overview,
            'banners' => $this->banners(),
            'orders' => $this->orders(),
            'language' => $this->language,
        ];
    }

    /**
     * @return objects\Series
     * @deprecated
     */
    public function scrape(): objects\Series
    {
        return new objects\Series($this->scrape_data());
    }


    /**
     * Get series banners
     * @return array Banner URLs
     */
    public function banners(): array
    {
        $banners = $this->xpath->query('//a[@rel="artwork_banners"]');
        $banner_urls = [];
        foreach ($banners as $banner) {
            $banner_urls[] = $banner->attributes->getNamedItem('href')->textContent;
        }

        return $banner_urls;
    }

    function translation(string $language): array
    {
        return Common::translation($this->xpath, $language);
    }

    /**
     * Get episode orders
     * @return array Orders
     */
    public function orders(): array
    {
        $orders_dom = $this->xpath->query('//ul[@class="nav nav-pills seasontype"]/li/a');
        $orders = [];
        foreach ($orders_dom as $order) {
            //$key = $order->getAttribute('data-type');
            $key = preg_replace('/.tab-(.+)/', '$1', $order->getAttribute('href'));
            $orders[$key] = $order->textContent;
        }
        return $orders;
    }

    public function languages(): array
    {
        return Common::languages($this->xpath);
    }
}