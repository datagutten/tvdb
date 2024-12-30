<?php

namespace datagutten\tvdb\cache;

use datagutten\tools\files\files;
use DOMDocument;
use DOMXPath;
use FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;


class TVDBScrape extends \datagutten\tvdb\TVDBScrape
{
    public string $cache_path;
    protected Filesystem $filesystem;
    public bool $cache_breaker = false;

    public function __construct(string $cache_path)
    {
        parent::__construct();
        if (!file_exists($cache_path))
            throw new FileNotFoundException($cache_path);
        $this->cache_path = realpath($cache_path);
        $this->filesystem = new Filesystem();
    }

    public function get_xpath(string $url): DOMXPath
    {
        preg_match('#thetvdb\.com/(\w+)/([\w\-]+)/(\w+)/(\d+)#', $url, $matches);
        preg_match('#/((\w+)/([\w\-]+)/(\w+)/(\w+))#', $url, $matches2);
        if (!empty($matches) && $matches[3] == 'episodes')
            $file = $this->episode_file($matches[2], $matches[4]);
        elseif (!empty($matches2) && $matches2[2] == 'series')
            $file = $this->cache_file($matches2[1]);
        if (!empty($file))
        {
            $dom = new DOMDocument();
            if ($this->filesystem->exists($file) && !$this->cache_breaker)
                @$dom->loadHTMLFile($file);
            else
            {
                $data = $this->get($url);
                $this->filesystem->dumpFile($file, $data);
                @$dom->loadHTML($data);
            }
            return new DOMXPath($dom);
        }
        else
            return parent::get_xpath($url);
    }

    protected function cache_file($url): string
    {
        if (DIRECTORY_SEPARATOR != '/')
            $url = str_replace('/', DIRECTORY_SEPARATOR, $url);
        return files::path_join($this->cache_path, $url . '.html');
    }

    protected function episode_file(string $series, string $id, $extension = 'html'): string
    {
        return files::path_join($this->cache_path, 'episodes', $series, $id . '.' . $extension);
    }

}