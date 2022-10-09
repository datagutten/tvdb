<?php

namespace datagutten\tvdb_tests;

use datagutten\tvdb\objects;
use datagutten\tvdb\TVDBScrape;
use PHPUnit\Framework\TestCase;

class TVDBTest extends TestCase
{
    /**
     * @var TVDBScrape
     */
    public TVDBScrape $tvdb;

    public function setUp(): void
    {
        $this->tvdb = new TVDBScrape();
    }

    public function testSeason()
    {
        $series = $this->tvdb->series('miraculous-ladybug');
        //$season = $this->tvdb->season_simple('miraculous-ladybug', 2);
        $season = $series->season(2);

        $this->assertInstanceOf(objects\Season::class, $season);
        $this->assertInstanceOf(objects\Episode::class, $season->episodes()[0]);
        $this->assertEquals('official', $season->ordering);
    }
}
