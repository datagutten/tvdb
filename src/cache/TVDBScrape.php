<?php

namespace datagutten\tvdb\cache;

use datagutten\tools\files\files;
use DOMDocument;
use DOMXPath;

class TVDBScrape extends \datagutten\tvdb\TVDBScrape
{
    public string $cache_path;

    public function __construct(string $cache_path)
    {
        parent::__construct();
        if (!file_exists($cache_path))
            throw new \FileNotFoundException($cache_path);
        $this->cache_path = realpath($cache_path);
    }

    public function get_xpath(string $url): DOMXPath
    {
        preg_match('#thetvdb\.com/(\w+)/([\w\-]+)/(\w+)/(\d+)#', $url, $matches);
        if (!empty($matches) && $matches[3] == 'episodes')
        {
            $file = $this->episode_file($matches[2], $matches[4]);
            if (file_exists($file))
            {
                $dom = new DOMDocument();
                @$dom->loadHTMLFile($file);
                return new DOMXPath($dom);
            }
        }
        return parent::get_xpath($url);
    }

    protected function episode_file(string $series, string $id, $extension = 'html'): string
    {
        return files::path_join($this->cache_path, 'episodes', $series, $id . '.' . $extension);
    }

}