<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\tvdb_tests;

use datagutten\tvdb\objects;
use datagutten\tvdb\objects\Series;
use datagutten\tvdb\TVDBScrape;
use PHPUnit\Framework\TestCase;

require __DIR__.'/../vendor/autoload.php';
class TVDBScrapeTest extends TestCase
{
    private TVDBScrape $tvdb;

    public function setUp(): void
    {
        $this->tvdb = new TVDBScrape();
    }

    public function testSeries2()
    {
        $series = $this->tvdb->series('miraculous-ladybug', lang:'eng');
        $this->assertInstanceOf(Series::class, $series);
        //$this->assertEquals('S03E17', $series[377012]);
        $this->assertContains('Aired Order', $series->orders);
    }

    public function testSeason()
    {
        $season = $this->tvdb->season_simple('border-security', 3, 'dvd');
        $this->assertArrayHasKey(377012, $season);
        $this->assertEquals('S03E17', $season[377012]);
    }

    public function testSeries()
    {
        $season = $this->tvdb->season_simple('border-security',3, ordering: 'dvd');
        $this->assertArrayHasKey(377012, $season);
        $this->assertEquals('S03E17', $season[377012]);
    }

    public function testSeries3()
    {
        $season = $this->tvdb->season_simple('border-security',3, ordering: 'dvd');
        $this->assertArrayHasKey(377012, $season);
        $this->assertEquals('S03E17', $season[377012]);
    }

    public function testProductionCode()
    {
        $series = $this->tvdb->series('looney-tunes-cartoons');
        $episode = $series->episode(7730437);
        $this->assertInstanceOf(objects\Episode::class, $episode);
        $this->assertEquals('065 / N/A / 029', $episode->production_code);
    }

    public function testProductionCode2()
    {
        $series = $this->tvdb->series('looney-tunes-cartoons');
        $episode = $series->episode(8151491);
        $this->assertInstanceOf(objects\Episode::class, $episode);
        $this->assertEquals('075', $episode->production_code);
    }

    public function testLanguages()
    {
        $series = $this->tvdb->series('miraculous-ladybug');
        $languages = $series->languages();
        $this->assertArrayHasKey('dan', $languages);
    }

    public function testTranslation()
    {
        $series = $this->tvdb->series('miraculous-ladybug', 'dan');
        $episode = $series->episode(5379014);
        $this->assertEquals('Stormvejr på vej', $episode->title);
    }

    public function testNoTranslation()
    {
        $series = $this->tvdb->series('phineas-and-ferb', 'swe');
        $episode = $series->episode(363248);
        $this->assertEmpty($episode->title);
    }
}
