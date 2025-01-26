<?php

namespace datagutten\tvdb\objects;

use datagutten\tvdb\exceptions;
use datagutten\tvdb\objects;
use datagutten\tvdb\scraper;
use datagutten\tvdb\TVDBScrape;
use InvalidArgumentException;

class Series extends TVDBObject
{
    /**
     * @var string Localized title
     */
    public string $title;
    public string $slug;
    public string $id;

    /**
     * @var string[] Series episode orders
     */
    public array $orders;

    /**
     * @var string Selected language or default language if no language is selected
     */

    public string $language;
    /**
     * @var string Series default language
     */
    public string $default_language;

    /**
     * @var string Series overview
     */
    public string $overview;

    /**
     * @var string[] Series banner image URLs
     */
    public array $banners;

    protected scraper\Series $scraper;
    protected TVDBScrape $tvdb;

    /**
     * @param array $data Series data
     * @param ?string $slug Series slug
     * @param ?string $language Series language
     * @param ?TVDBScrape $tvdb TVDBScrape object
     * @param ?scraper\Series $scraper Series scraper object
     * @throws exceptions\HTTPError HTTP error fetching series page
     */
    public function __construct(array $data = [], string|null $slug = null, string|null $language = null, TVDBScrape|null $tvdb = null, scraper\Series|null $scraper = null)
    {
        $this->slug = $slug;
        if(!empty($language))
            $this->language = $language;
        if (!empty($tvdb))
        {
            $this->tvdb = $tvdb;
            $xpath = $this->tvdb->get_xpath($this->url());
            $this->scraper = new scraper\Series($xpath, $language);
        }
        if (!empty($scraper))
            $this->scraper = $scraper;
        $data = array_merge($data, $this->scraper->scrape_data());
        parent::__construct($data);
    }

    public function __toString(): string
    {
        return $this->title;
    }

    public function url(): string
    {
        return sprintf('https://thetvdb.com/series/%s', $this->slug);
    }

    public function orders(): array
    {
        return $this->scraper->orders();
    }

    /**
     * @return Season[]
     */
    public function seasons(): array
    {

    }

    /**
     * Get all episodes of the series
     * @return Episode[]
     * @throws exceptions\HTTPError HTTP error fetching season page
     */
    public function all_episodes(string $ordering = 'official', $id_key = false): array
    {
        if (!in_array($ordering, array_keys($this->orders())))
            throw new InvalidArgumentException(sprintf('Invalid ordering: %s', $ordering));
        $season = new Season(['ordering' => $ordering], $this, $this->tvdb);
        return $season->episodes($id_key);
    }

    public function title()
    {

    }

    /**
     * @param int $season
     * @param string $ordering
     * @return Season
     * @throws exceptions\HTTPError HTTP error fetching season page
     */
    public function season(int $season, string $ordering = 'official'): objects\Season
    {
        if (!in_array($ordering, array_keys($this->orders())))
            throw new InvalidArgumentException(sprintf('Invalid ordering: %s', $ordering));
        return new Season(['number' => $season, 'ordering' => $ordering], $this, $this->tvdb);
    }

    /**
     * @param int $id
     * @return Episode
     * @throws exceptions\EpisodeNotFound Episode has no number in selected ordering
     * @throws exceptions\HTTPError HTTP error fetching episode page
     */
    public function episode(int $id): objects\Episode
    {
        $episode = new objects\Episode(['id' => $id, 'series_obj' => $this], tvdb: $this->tvdb);
        return $episode->scrape();
    }

    public function languages(): array
    {
        return $this->scraper->languages();
    }

    public function banners(): array
    {
		return $this->scraper->banners();
	}
}