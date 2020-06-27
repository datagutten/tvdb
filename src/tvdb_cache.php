<?php


namespace datagutten\tvdb;


use Symfony\Component\Filesystem\Filesystem;

class tvdb_cache extends tvdb
{
	public $cache_dir;
	public $filesystem;
	function __construct($config = [])
	{
		parent::__construct($config);
		$this->filesystem = new Filesystem();
		if(empty($this->config['tvdb_cache_dir']))
			$this->cache_dir = __DIR__.'/cache';
		else
			$this->cache_dir = $this->config['tvdb_cache_dir'];

		if(!file_exists($this->cache_dir))
			$this->filesystem->mkdir($this->cache_dir);
		if(!file_exists($this->cache_dir.'/series_search'))
			$this->filesystem->mkdir($this->cache_dir.'/series_search');

	}
	function cache($cache_file, $method, $args)
	{
        $method_name = substr($method, strpos($method, ':')+2);
	    $cache_dir = $this->cache_dir.'/'.$method_name;
		$cache_file = $cache_dir.'/'.$cache_file;

		if(file_exists($cache_file))
		{
			return json_decode(file_get_contents($cache_file), true);
		}
		else
		{
			if(!file_exists($cache_dir))
				$this->filesystem->mkdir($cache_dir);
			$data = call_user_func_array($method, $args);
			file_put_contents($cache_file, json_encode($data));
			return $data;
		}
	}

	function series_search($search, $language=null)
	{
		$cache_file = sprintf('%s_%s.json', $search, $language);
		return $this->cache($cache_file, 'parent::series_search', [$search, $language]);
	}

	function episode_info($series_id,$season,$episode,$language='')
	{
		$cache_file = sprintf('episode_%d_%d_%d_%s.json', $series_id, $season, $episode, $language);
		return $this->cache($cache_file, 'parent::episode_info', [$series_id, $season, $episode, $language]);
	}

}