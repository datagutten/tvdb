<?php

namespace datagutten\tvdb\scraper;

use datagutten\tvdb\exceptions;
use datagutten\tvdb\objects;
use DateInterval;
use DateTimeImmutable;
use DOMXPath;
use Exception;


/**
 * Episode scraper
 * All page parsing should be in this class
 */
class Episode extends Common
{
    private DOMXPath $xpath;
    /**
     * @var ?string
     */
    public ?string $language;

    public function __construct(DOMXPath $xpath, ?string $language = null)
    {
        $this->xpath = $xpath;
        $this->language = $language;
    }

    public function info(): array
    {
        $episode_info = [];
        $fields = [
            'production_code' => 'Production Code',
            'runtime_string' => 'Runtime',
        ];
        foreach ($this->xpath->query('//ul[@class="list-group"]/li') as $item)
        {
            $title = $item->getElementsByTagName('strong')->item(0)->textContent;

            if ($title == 'Originally Aired')
            {
                $href = $this->xpath->query('span/a/@href', $item)->item(0);
                if (!empty($href) && preg_match('#/on-today/([0-9\-]+)#', $href->textContent, $matches))
                {
                    $episode_info['date'] = DateTimeImmutable::createFromFormat('Y-m-d', $matches[1]);
                    $episode_info['date'] = $episode_info['date']->setTime(0,0);
                }
            }
            else
            {
                $key = array_search($title, $fields);
                if ($key === false)
                    continue;
                $episode_info[$key] = trim($item->getElementsByTagName('span')->item(0)->textContent);
            }
        }

        if (!empty($episode_info['runtime_string']))
        {
            $runtime = preg_replace('/([0-9]+)\s+minutes/', 'PT$1M', $episode_info['runtime_string']);
            try
            {
                $episode_info['runtime'] = new DateInterval($runtime);
            }
            catch (Exception $e)
            {
            }
        }

        $id = $this->xpath->query('//a[@data-type="episode"]/@data-id');
        if (!empty($id))
            $episode_info['id'] = (int)$id->item(0)->textContent;

        return $episode_info;
    }

    /**
     * Get season and episode number
     * @param string $ordering
     * @return int[]
     * @throws exceptions\EpisodeNotFound Episode has no number in selected ordering
     */
    public function episode(string $ordering): array
    {
        preg_match_all('#/series/([\w-]+)/seasons/(\w+)/[0-9]+">Season (\w+).+?Episode (\w+)#s', $this->xpath->document->saveHTML(), $matches);
        foreach (array_keys($matches[0]) as $key)
        {
            if ($matches[2][$key] == $ordering)
            {
                //$series_slug = $matches[2][$key];
                return [intval($matches[3][$key]), intval($matches[4][$key])];
            }
        }
        throw new exceptions\EpisodeNotFound(sprintf('Episode has no number in ordering %s', $ordering));
    }

    /**
     * Get series information for the episode
     * @return array
     */
    public function series(): array
    {
        $link = $this->xpath->query('//div[@class="crumbs"]/a')->item(2);
        $uri = $link->getAttribute('href');
        $slug = preg_replace('#/series/([\w-]+)#', '$1', $uri);
        return [
            'series' => $link->nodeValue,
            'series_slug' => $slug,
            'series_url' => 'https://thetvdb.com' . $uri
        ];
    }

    /**
     * Scrape episode page
     * @param DOMXPath $xpath
     * @param string|null $language Language code
     * @param string|null $ordering Episode order
     * @param objects\Series|null $series Series object
     * @return objects\Episode Episode object
     * @throws exceptions\HTTPError HTTP error fetching episode page
     */
    public static function scrape(DOMXPath $xpath, ?string $language = null, ?string $ordering = null, ?objects\Series $series = null): objects\Episode
    {
        $scraper = new static($xpath, $language);
        //list($season, $episode) = $scraper->episode($ordering);
        //var_dump($season);
        //$translation = $scraper->translation($language);
        //list($title, $overview) = static::translation($xpath, $language);
        $info = $scraper->info();

        return new objects\Episode([
            'scraper' => $scraper,
            'series' => $series,
            'id' => $info['id'],
            /*'title' => $title,
            'description' => $overview,*/
            /*'season' => $season,
            'episode' => $episode,*/
            'date' => $info['date'] ?? null,
            'duration' => $info['runtime'] ?? null,
            'production_code' => $info['production_code'] ?? null
        ]);
    }
}