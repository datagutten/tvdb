<?php


namespace datagutten\tvdb\scraper;


use datagutten\tvdb\exceptions;
use DateTimeImmutable;
use DOMXPath;
use DOMElement;
use Exception;
use datagutten\tvdb\objects;

class Season
{
    private DOMXPath $xpath;
    protected objects\Series $series;
    public string $series_slug;

    /**
     * Season constructor.
     * @param DOMXPath $xpath
     * @param string $series_slug Series slug
     */
    public function __construct(DOMXPath $xpath, string $series_slug)
    {
        $this->xpath = $xpath;
        $this->series_slug = $series_slug;
    }

    /**
     * Get episodes
     * @return array
     * @throws Exception
     */
    public function episodes(): array
    {
        $episodes_xpath = $this->xpath->query('//table[@class="table table-bordered"]/tbody/tr');
        $episodes = [];
        /** @var DOMElement $tr */
        foreach ($episodes_xpath as $tr)
        {
            $cols = $tr->getElementsByTagName('td');
            $episode_num = $cols->item(0)->textContent;
            /** @var DOMElement $link */
            $link = $cols->item(1)->getElementsByTagName('a')->item(0);
            /** @var DOMElement $aired */
            $aired = $cols->item(2)->getElementsByTagName('div')->item(0)->textContent;

            preg_match('/S([0-9]+)E([0-9]+)/', $episode_num, $matches);
            $id = preg_replace('#.+episodes/([0-9]+)#', '$1', $link->getAttribute('href'));
            try
            {
                $aired_obj = new DateTimeImmutable(strval($aired));
            }
            catch (Exception)
            {
                $aired_obj = null;
            }
            $episodes[] = [
                'season' => intval($matches[1]),
                'episode' => intval($matches[2]),
                'title' => trim($link->textContent),
                'id' => intval($id),
                'aired' => $aired_obj,
            ];
        }
        return $episodes;
    }


    public function episode_ids(): array
    {
        $page = $this->xpath->document->saveHTML();
        preg_match_all(sprintf('#/series/%s/episodes/([0-9]+)#', $this->series_slug), $page, $matches);
        return array_unique($matches[1]);
    }

}