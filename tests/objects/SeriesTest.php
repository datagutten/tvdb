<?php

namespace datagutten\tvdb_tests\objects;

use datagutten\tvdb\TVDBScrape;
use PHPUnit\Framework\TestCase;

class SeriesTest extends TestCase
{
    public function testDefaultLanguage()
    {
        $tvdb = new TVDBScrape();
        $series = $tvdb->series('miraculous-ladybug');
        $this->assertEquals('fra', $series->default_language);
    }
}
