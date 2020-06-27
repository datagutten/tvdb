<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\tvdb\tests;

use datagutten\tvdb\tvdb_cache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class tvdbCacheTest extends TestCase
{
    /**
     * @var tvdb_cache
     */
	public $tvdb;

	public function setUp(): void
	{
        if(file_exists(__DIR__.'/../config_tvdb.php'))
            set_include_path(__DIR__.'/..');
        else
            set_include_path(__DIR__);
		$this->tvdb = new tvdb_cache();
	}
	public function testSeries_search()
	{
		$series = $this->tvdb->series_search('Ice Road Rescue', 'en');
		$this->assertNotEmpty($series);
		$this->assertSame('Ice Road Rescue', $series['seriesName']);
		$this->assertFileExists(__DIR__.'/test_data/cache/series_search/Ice Road Rescue_en.json');
	}

	public function testEpisode_info()
	{
		$info = $this->tvdb->episode_info(81848, 2, 56);
		$this->assertEquals('2513911', $info['id']);
		$this->assertEquals('Nerds of a Feather', $info['episodeName']);
		$this->assertFileExists($this->tvdb->cache_dir.'/episode_info/episode_81848_2_56_.json');
		$tvdb_hit = new tvdbCacheHitCheck();
		$tvdb_hit->episode_info(81848, 2, 56);
	}

    public function testCacheNotHit()
    {
        $this->expectExceptionMessage('Cache not hit');
        $tvdb_hit = new tvdbCacheHitCheck();
        $tvdb_hit->episode_info(81848, 2, 56);
    }

	public function tearDown(): void
	{
		$filesystem = new Filesystem();
		$filesystem->remove(__DIR__.'/test_data');
	}

}