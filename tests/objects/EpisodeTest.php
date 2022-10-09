<?php
namespace datagutten\tvdb_tests\objects;

require __DIR__.'/../../vendor/autoload.php';

use datagutten\tvdb\objects;
use datagutten\tvdb\TVDBScrape;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EpisodeTest extends TestCase
{
    public function testScrapeEpisode()
    {
        $tvdb = new TVDBScrape();
        $series = $tvdb->series('ice-road-rescue');
        $episode = $series->episode(8783932);
        $this->assertEquals(new DateInterval('PT45M'), $episode->duration);
        $this->assertEquals(new DateTimeImmutable('2021-11-25 0:00:00'), $episode->date);
    }

    public function testScrapeEpisode2()
    {
        $tvdb = new TVDBScrape();
        $series = $tvdb->series('phineas-and-ferb');
        $episode = $series->episode(4209871);
        $this->assertEquals(new DateInterval('PT12M'), $episode->duration);
        $this->assertEquals(new DateTimeImmutable('2011-12-02 0:00:00'), $episode->date);
        $this->assertEquals('A Phineas and Ferb Family Christmas', $episode->title);
        $this->assertEquals('317a', $episode->production_code);
        
        $this->assertInstanceOf(objects\Series::class, $episode->series_obj);
        //$this->assertInstanceOf(objects\Season::class, $episode->season_obj);
        $this->assertEquals(3, $episode->season);
        $this->assertEquals(27, $episode->episode);
    }
}