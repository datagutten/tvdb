<?php

namespace datagutten\tvdb\objects;

use datagutten\tvdb\exceptions;
use datagutten\tvdb\TVDBScrape;
use datagutten\tvdb\scraper;
use datagutten\tvdb\objects;
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
     * @var string[]
     */
    public array $orders;
    public ?string $language = null;


    protected scraper\Series $scraper;
    protected TVDBScrape $tvdb;

    /**
     * @throws exceptions\tvdbException
     */
    public function __construct(array $data = [], string $slug = null, string $language = null, TVDBScrape $tvdb = null, scraper\Series $scraper = null)
    {
        $this->slug = $slug;
        $this->language = $language;
        if (!empty($tvdb))
        {
            $this->tvdb = $tvdb;
            $xpath = $this->tvdb->get_xpath($this->url());
            $this->scraper = new scraper\Series($xpath, $this->language);
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
     * @return Episode[]
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

    public function season(int $season, string $ordering = 'official'): objects\Season
    {
        if (!in_array($ordering, array_keys($this->orders())))
            throw new InvalidArgumentException(sprintf('Invalid ordering: %s', $ordering));
        return new Season(['number' => $season, 'ordering' => $ordering], $this, $this->tvdb);
    }

    public function episode(int $id): objects\Episode
    {
        $episode = new objects\Episode(['id' => $id, 'series_obj' => $this], id: $id, tvdb: $this->tvdb);
        return $episode->scrape();
    }

    public function languages()
    {
        return $this->scraper->languages();
    }

	public function banners()
	{
		return $this->scraper->banners();
	}
}