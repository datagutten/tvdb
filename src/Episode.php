<?php

namespace datagutten\tvdb;

use datagutten\video_tools\EpisodeFormat;

class Episode extends EpisodeFormat
{
    public int $id;
    public int $season_id;
    public string $series_slug;
    public string $url;
    public string $episode_alternate;

    public function __construct(array $fields = [])
    {
        foreach ($fields as $key => $value)
        {
            $this[$key] = $value;
        }
    }

    public function episode_num(): string
    {
        $episode = $this->season_format();
        if (!empty($this->episode))
            $episode .= sprintf('E%02d', $this->episode);
        return $episode;
    }

    public function episode_title(): string
    {
        $name = $this->season_format();
        if (!empty($this->episode))
            $name .= sprintf('E%02d', $this->episode);

        if (!empty($this->title))
            if (!empty($name)) //Append episode title
                $name = sprintf('%s - %s', $name, $this->title);

        //Prepend series name
        return $name;
    }
}