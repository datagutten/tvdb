<?php

namespace datagutten\tvdb\cache;

use datagutten\tools\files\files;
use DOMDocument;
use DOMXPath;
use Symfony\Component\Filesystem\Filesystem;

class TVDBScrape extends \datagutten\tvdb\TVDBScrape
{
    public string $cache_path;
    protected Filesystem $filesystem;

    public function __construct(string $cache_path)
    {
        parent::__construct();
        if (!file_exists($cache_path))
            throw new \FileNotFoundException($cache_path);
        $this->cache_path = realpath($cache_path);
        $this->filesystem = new Filesystem();
    }

    public function get_xpath(string $url): DOMXPath
    {
        preg_match('#thetvdb\.com/(\w+)/([\w\-]+)/(\w+)/(\d+)#', $url, $matches);
        if (!empty($matches) && $matches[3] == 'episodes')
        {
            $file = $this->episode_file($matches[2], $matches[4]);
            $dom = new DOMDocument();
            if ($this->filesystem->exists($file))
                @$dom->loadHTMLFile($file);
            else
            {
                $data = $this->get($url);
                $this->filesystem->dumpFile($file, $data);
                @$dom->loadHTML($data);
            }
            return new DOMXPath($dom);
        }
        return parent::get_xpath($url);
    }

    protected function episode_file(string $series, string $id, $extension = 'html'): string
    {
        return files::path_join($this->cache_path, 'episodes', $series, $id . '.' . $extension);
    }

}