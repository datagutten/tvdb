<?php


namespace datagutten\tvdb\objects;


use datagutten\tvdb\TVDBScrape;
use datagutten\tvdb\scraper;
use datagutten\tvdb\objects;

class Season extends TVDBObject
{
    /**
     * @var Series Series object
     */
    public Series $series;
    /**
     * @var int Season number
     */
    public int $number;
    /**
     * @var string Season ordering
     */
    public string $ordering;
    /**
     * @var Episode[] Episodes
     */
    public array $episodes;

    protected scraper\Season $scraper;
    protected TVDBScrape $tvdb;

    public function __construct($data, Series $series, TVDBScrape $tvdb = null)
    {
        parent::__construct($data);
        $this->series = $series;
        if (!empty($tvdb))
        {
            $this->tvdb = $tvdb;
            $this->scraper = new scraper\Season($tvdb->get_xpath($this->url()), $this->series->slug);
        }
    }

    public function url(): string
    {
        if (!empty($this->number))
            return sprintf('/series/%s/seasons/%s/%d', $this->series->slug, $this->ordering, $this->number);
        else
            return sprintf('/series/%s/allseasons/%s', $this->series->slug, $this->ordering);
    }


    /**
     * @return objects\Episode[]
     */
    public function episodes($id_key = false): array
    {
        $episodes = [];
        $episode_ids = $this->scraper->episode_ids();
        foreach ($episode_ids as $episode_id)
        {
            $episode = new Episode(['id' => $episode_id], $this, $episode_id, $this->tvdb);
            $episode->scrape();
            if (!$id_key)
                $episodes[] = $episode;
            else
                $episodes[$episode->id] = $episode;
        }
        return $episodes;
    }
	
	public function episode($num)
	{
		$episodes = $this->scraper->episodes();
		foreach($episodes as $episode)
		{
			if($episode['episode']==$num)
			{
				$episode_obj= $this->series->episode($episode['id']);
				$episode_obj->season = $episode['season'];
				$episode_obj->season_obj = $this;
				$episode_obj->episode = $num;
				return $episode_obj;
			}
		}
	}

}