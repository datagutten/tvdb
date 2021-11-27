<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\tvdb\tests;

use datagutten\tvdb\exceptions\api_error;
use datagutten\tvdb\exceptions\noResultException;
use datagutten\tvdb\tvdb;
use PHPUnit\Framework\TestCase;
use Requests;

class tvdbTest extends TestCase
{
    /**
     * @var tvdb
     */
    public $tvdb;
    public function setUp(): void
    {
        if(file_exists(__DIR__.'/../config_tvdb.php'))
            set_include_path(__DIR__.'/..');
        else
            set_include_path(__DIR__);
    }

    public function testEpisode_info_translated()
    {
        $tvdb = new tvdb();
        $episode = $tvdb->episode_info(81848, 2, 3, 'nb');
        $this->assertEquals('no', $episode['language']['episodeName']);
    }

    /*public function testEpisode_link()
    {

    }*/

    public function test_series()
    {
        $tvdb = new tvdb();
        $series = $tvdb->series(299304);
        $this->assertSame('Ice Road Rescue', $series['seriesName']);
    }

    public function testGetseries()
    {

    }*/

    public function testInvalidLogin()
    {
        $this->expectException(api_error::class);
        $this->expectExceptionMessage('Error from TVDB: API Key Required');
        new tvdb(['api_key'=>'asdf', 'username'=>'user', 'user_key'=>'asdf']);
    }
    public function testLogin()
    {
        new tvdb();
        $this->addToAssertionCount(1);

        //$this->assertNotEmpty($tvdb->headers['Authorization']);
        //$this->assertStringContainsString('Bearer', $this->tvdb->headers['Authorization']);
    }

    public function testGetepisodes()
    {
        $tvdb = new tvdb();
        $episodes = $tvdb->getepisodes(81848);
        $this->assertIsArray($episodes);
        $this->assertArrayHasKey('S01E02', $episodes);
        $this->assertEquals('Lawn Gnome Beach Party of Terror!', $episodes['S01E02']['episodeName']);
    }

    public function testEpisodename()
    {
        $episode = ['airedSeason'=>2, 'airedEpisodeNumber'=>4];
        $this->assertEquals('S02E04', tvdb::episode_name($episode));
        $episode = ['airedSeason'=>2, 'airedEpisodeNumber'=>3, 'episodeName'=>'Tip of the Day'];
        $this->assertEquals('S02E03 - Tip of the Day', tvdb::episode_name($episode));
        $this->assertEmpty(tvdb::episode_name([]));
    }

    public function testFind_episode_by_name()
    {
        $tvdb = new tvdb();
        $episode = $tvdb->find_episode_by_name('Phineas and Ferb', 'Flop Starz');
        $this->assertEquals(1, $episode['airedSeason']);
        $this->assertEquals(3, $episode['airedEpisodeNumber']);
        $this->assertEquals('Flop Starz', $episode['episodeName']);
    }

    public function testFind_episode_by_translated_name()
    {
        $tvdb = new tvdb();
        $episode = $tvdb->find_episode_by_name('Phineas and Ferb', 'Ikke blunk', 'nb');
        $this->assertEquals(2, $episode['airedSeason']);
        $this->assertEquals(8, $episode['airedEpisodeNumber']);
        $this->assertEquals('Ikke blunk', $episode['episodeName']);
    }

    public function testFind_episode_by_name_invalid_lang()
    {
        $tvdb = new tvdb();
        $episode = $tvdb->find_episode_by_name('Phineas and Ferb', 'Flop Starz', 'no');
        $this->assertEquals(1, $episode['airedSeason']);
        $this->assertEquals(3, $episode['airedEpisodeNumber']);
        $this->assertEquals('Flop Starz', $episode['episodeName']);
    }

    public function testFind_episode_by_name_wrong_lang()
    {
        $tvdb = new tvdb();
        $this->expectException(\datagutten\tvdb\exceptions\tvdbException::class);
        $this->expectExceptionMessage('Unable to find episode with name "Ikke blunk" in language "en"');
        $tvdb->find_episode_by_name('Phineas and Ferb', 'Ikke blunk', 'en');
    }

    public function testFindseries()
    {
        $tvdb = new tvdb();
        $series = $tvdb->findseries('Mayday');
        $this->assertEquals(79771, $series['id']);
    }

    public function testSeries_search()
    {
        $tvdb = new tvdb();
        $series = $tvdb->series_search('Ice Road Rescue', 'en');
        $this->assertNotEmpty($series);
        $this->assertSame('Ice Road Rescue', $series['seriesName']);
    }

    public function testBannerName()
    {
        $tvdb = new tvdb();
        $banner_url = $tvdb->banner('Ice Road Rescue');
        $this->assertSame('http://thetvdb.com/banners/graphical/299304-g.jpg', $banner_url);
        $response = Requests::head($banner_url);
        $this->assertSame(200,$response->status_code);
    }

    public function testBannerSearch()
    {
        $tvdb = new tvdb();
        $series=$tvdb->series_search('Ice Road Rescue');
        $banner_url = $tvdb->banner($series);
        $this->assertSame('http://thetvdb.com/banners/graphical/299304-g.jpg', $banner_url);

        $response = Requests::head($banner_url);
        $this->assertSame(200,$response->status_code);
    }

    public function testEpisode_infoNoHits()
    {
        $tvdb = new tvdb();
        $this->expectException(noResultException::class);
        $this->expectExceptionMessage('No results for your query: map[AiredSeason:5 EpisodeNumber:56]');
        $tvdb->episode_info(81848, 5, 56);
    }

    public function testEpisode_info()
	{
		$tvdb = new tvdb();
		$info = $tvdb->episode_info(81848, 2, 56);
		$this->assertEquals('2513911', $info['id']);
		$this->assertEquals('Nerds of a Feather', $info['episodeName']);
	}

    /*public function testSeries_link()
    {

    }

    public function testGet_series_and_episodes()
    {

    }*/
}
