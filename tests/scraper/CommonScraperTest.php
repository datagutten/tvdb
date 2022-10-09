<?php

namespace datagutten\tvdb_tests\scraper;

use datagutten\tvdb\scraper;
use datagutten\tvdb\TVDBScrape;
use PHPUnit\Framework\TestCase;

class CommonScraperTest extends TestCase
{

    public function testEpisodeLanguages()
    {
        $tvdb = new TVDBScrape();
        $xpath = $tvdb->get_xpath('/series/miraculous-ladybug/episodes/5379014');
        $languages = scraper\Common::languages($xpath);
        $this->assertArrayHasKey('eng', $languages);
    }

    public function testDefaultEpisodeLanguage()
    {
        $tvdb = new TVDBScrape();
        $xpath = $tvdb->get_xpath('/series/miraculous-ladybug/episodes/5379014');
        $language = scraper\Common::default_language($xpath);
        $this->assertEquals('fra', $language);
    }
}
