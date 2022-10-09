<?php


namespace datagutten\tvdb\objects;

use datagutten\tvdb\exceptions;
use datagutten\tvdb\scraper;
use datagutten\tvdb\scraper\EpisodeScraper;
use datagutten\tvdb\TVDBScrape;
use datagutten\video_tools\EpisodeFormat;
use DateInterval;

class Episode extends EpisodeFormat
{
    /**
     * @var Series Series object
     */
    public Series $series_obj;
    /**
     * @var Season Season object
     */
    public Season $season_obj;
    /**
     * @var DateInterval Episode duration
     */
    public DateInterval $duration;

    //public string $language;
    public string $production_code;
    public int $id;

    protected scraper\Episode $scraper;
    protected TVDBScrape $tvdb;

    public function __construct($data, Season $season = null, int $id = null, TVDBScrape $tvdb = null)
    {
        foreach ($data as $key => $value)
        {
            if (!empty($value))
                $this->$key = $value;
        }
        if (!empty($tvdb))
            $this->tvdb = $tvdb;
        if (!empty($season))
        {
            $this->season_obj = $season;
            $this->series_obj = $season->series;
        }
        if(!empty($this->series_obj))
            $this->series = $this->series_obj->title;

        if (!empty($this->tvdb) && !empty($this->id))
            $this->get_scraper();

    }

    public function url()
    {
        return sprintf('https://thetvdb.com/series/%s/episodes/%d', $this->series_obj->slug, $this->id);
    }

    public function get_scraper()
    {
        $xpath = $this->tvdb->get_xpath($this->url());
        $this->scraper = new scraper\Episode($xpath);
    }

    public function scrape(): static
    {
        $xpath = $this->tvdb->get_xpath($this->url());
        if (empty($this->season_obj->ordering))
        {
            try {
                list($this->season, $this->episode) = $this->scraper->episode('official');
            }
            catch (exceptions\EpisodeNotFound $e)
            {
            }
        }
        else
            list($this->season, $this->episode) = $this->scraper->episode($this->season_obj->ordering);

        $info = $this->scraper->info();
        if (!empty($info['date']))
            $this->date = $info['date'];
        if (!empty($info['runtime']))
            $this->duration = $info['runtime'];
        $this->id = $info['id'];
        if (!empty($info['production_code']))
            $this->production_code = $info['production_code'];

        try
        {
            list($this->title, $this->description) = scraper\Common::translation($xpath, $this->series_obj->language);
        }
        catch (exceptions\TranslationNotFound $e)
        {
            //TODO: Use fallback language?
            $this->title = '';
            $this->description = '';
        }

        return $this;
    }

    public function episode_title(): string
    {
        return $this->episode_number();
    }
}