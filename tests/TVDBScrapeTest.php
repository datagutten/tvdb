<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\tvdb_tests;

use datagutten\tvdb\TVDBScrape;
use PHPUnit\Framework\TestCase;

class TVDBScrapeTest extends TestCase
{

    private TVDBScrape $tvdb;

    public function setUp(): void
    {
        $this->tvdb = new TVDBScrape();
    }

    public function testSeason()
    {
        $season = $this->tvdb->season('border-security', 3, 'dvd');
        $this->assertArrayHasKey(377012, $season);
        $this->assertEquals('S03E17', $season[377012]);
    }
}
