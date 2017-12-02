<?Php
class tvdb
{
	private $ch;
	private $http_status;
	public $lang;
	public $error='';
	public $debug=false; //Set to true to show debug info
	private $headers=array();
	private $search_languages;
	public $last_search_language=false; //Language for the last search
	function __construct()
	{
		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);	
		curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,1);
		$this->headers=array('Content-Type: application/json');
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,$this->headers);

		require 'config_tvdb.php';
		$this->search_languages=$search_languages;
		$this->lang=$default_language;
		$this->login($api_key,$username,$user_key);
	}
	public function get($url)
	{
		curl_setopt($this->ch, CURLOPT_URL,$url);
		curl_setopt($this->ch,CURLOPT_HTTPGET,true);
		curl_setopt($this->ch,CURLOPT_HTTPHEADER,$this->headers);
		$data=curl_exec($this->ch);		
		$this->http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($data===false)
		{
			$this->error=curl_error($this->ch);
			return false;
		}
		elseif($this->http_status!=200)
		{
			$this->error=sprintf('HTTP request returned code %s',$this->http_status);
			return false;
		}
		elseif(empty($data))
		{
			$this->error='HTTP request returned empty response';
			return false;
		}
		else
			return $data;
	}

	//Send login request and store token
	public function login($apikey,$username,$userkey)
	{
		$request=json_encode(array('apikey'=>$apikey,'username'=>$username,'userkey'=>$userkey));
		curl_setopt($this->ch,CURLOPT_URL,'https://api.thetvdb.com/login');
		curl_setopt($this->ch,CURLOPT_POSTFIELDS,$request);

		$result_string=curl_exec($this->ch);
		if($result_string===false)
			return false;
		$token=json_decode($result_string,true)['token'];
		$this->headers['token']='Authorization: Bearer '.$token;
	}
	
	//Send and decode a request
	public function request($uri,$language=false)
	{
		if($language===false) //Default to preferred language
			$language=$this->lang;
		$this->headers['language']='Accept-Language: '.$language;
		$result_string=$this->get('https://api.thetvdb.com'.$uri);
		if($result_string!==false)
			return json_decode($result_string,true);
		else
			//throw new Exception('Result is false');
			return false;
	}

	//Get a series by id
	public function getseries($series_id,$language=false)
	{
		if(!is_numeric($series_id))
			throw new Exception('Series ID must be numeric');
		$response=$this->request('/series/'.$series_id,$language);
		if($response===false) //Request has failed
			return false;
		else
			return $response['data'];
	}

	//Fetch and sort episodes for a series
	public function getepisodes($series_id,$language=false)
	{
		if(empty($series_id))
			throw new Exception('Series ID is empty');

		if(!is_numeric($series_id))
			throw new Exception('Series ID must be numeric');
		$last_page=1;
		$episodes=array(); //Initialize episodes array
		for($page=1; $page<=$last_page; $page++)
		{
			$episodes_page=$this->request(sprintf('/series/%s/episodes?page=%s',$series_id,$page),$language);
			if($episodes_page===false) //Request failed
				return false;
			$episodes=array_merge($episodes,$episodes_page['data']); //Merge new page to episodes array
			$last_page=$episodes_page['links']['last'];
		}

		foreach($episodes as $episode)
		{
			//$episodes_sorted[$episode['airedSeason']][$episode['airedEpisodeNumber']]=$episode;
			$key=sprintf('S%02dE%02d',$episode['airedSeason'],$episode['airedEpisodeNumber']);
			$episodes_sorted[$key]=$episode;
		}
		ksort($episodes_sorted); //Sort episodes by key
		return $episodes_sorted;
	}
	
	//Search for a series
	public function series_search($search,$language=false)
	{
		if(empty($search))
		{
			$this->error='findseries was called with empty search string';
			return false;
		}

		$search=str_replace('its',"it's",$search);

		if($language===false)
		{
			foreach($this->search_languages as $language) //Loop through languages until we get results
			{
				$seriesinfo=$this->request('/search/series?name='.urlencode($search),$language);
				if($seriesinfo!==false)
					break;
			}
		}
		else
			$seriesinfo=$this->request('/search/series?name='.urlencode($search),$language);

		if($seriesinfo===false) //No match
		{
			$this->error='No series found for search '.$search;
			return false;
		}
		$this->last_search_language=$language; //Save the search language
		if(count($seriesinfo['data'])>1) //Multiple hits
		{
			foreach($seriesinfo['data'] as $series)
			{
				if($series['seriesName']==$search) //Try to find exact name match
					return $series;
			}
			//If we are here there was no exact match
			if($this->debug)
				$this->error='Multiple matches found, but no exact name match. First result is returned';
			return $seriesinfo['data'][0];
		}
		else //Single hit
			return $seriesinfo['data'][0];
	}

	//Search for a series and get series information
	//Return is getseries()
	public function findseries($search,$language=false)
	{
		if(is_numeric($search))
			return $this->getseries($search,$language);
		else
		{
			$search_result=$this->series_search($search,$language);
			if($search_result===false)
				return false;
			else
			{
				if($language===false) //Return results in the same language as the search
					$language=$this->last_search_language;
				return $this->getseries($search_result['id'],$language);
			}
		}
	}

	//Get series and episodes for a series ID
	public function get_series_and_episodes($seriesid,$language=false)
	{
		$series['Series']=$this->getseries($seriesid,$language);
		$series['Episode']=$this->getepisodes($seriesid,$language);
		return $series;
	}

	//Find information about an episode
	//$series can be series id, series name or an array returned by getepisodes()
	public function episode_info($series,$season,$episode)
	{
		if(!is_array($series))
		{
			$series=$this->findseries($series);
			if($series===false)
				return false;
		}
		$episodes=$this->getepisodes($series['id']);
		if($episodes===false)
			return false;

		$epname=sprintf('S%02dE%02d',$season,$episode);
		if(isset($episodes[$epname]))
			return $episodes[$epname];
		else
		{
			$this->error=sprintf('%s not found',$epname);
			return false;
		}
	}

	//Get series banner
	public function banner($series)
	{
		if(!is_array($series))
		{
			$series=$this->findseries($series);
			if($series===false)
				return false;
		}

		if(!empty($series['banner']))
			return "http://thetvdb.com/banners/{$series['banner']}";
		else
			return false;
	}

	//Find episode by name
	public function find_episode_by_name($episodes,$search)
	{
		if(!is_array($episodes))
		{
			$series=$this->findseries($episodes);
			if($series===false)
				return false;
			$episodes=$this->getepisodes($series['id']);
			if($episodes===false)
				return false;
		}

		$names=array_combine(array_keys($episodes),array_column($episodes,'episodeName'));
		$names=array_filter($names); //Remove episodes without name

		foreach ($names as $episode=>$name)
		{
			if(stripos($name,$search)!==false)
			{
				return $episodes[$episode];
			}
		}
		return false; //If loop has completed without returning there is no match
	}

	//Create link to an episode or series
	public function link($info,$series_id=false)
	{
		if(!isset($info['Episode'])) //Single episode
		{
			$info['Episode']=$info;
			$info['Episode']['seriesid']=$series_id;
			return "http://www.thetvdb.com/?tab=episode&seriesid={$info['Episode']['seriesid']}&seasonid={$info['Episode']['airedSeasonID']}&id={$info['Episode']['id']}";
		}
		else //Entire series
			return "http://www.thetvdb.com/index.php?id={$info['Series']['id']}";	 
	}

	//Format episode number and name from episode array
	public function episodename($episode)
	{

		if(!isset($episode['airedEpisodeNumber']))
			return false;
		$episodename=sprintf('S%02dE%02d',$episode['airedSeason'],$episode['airedEpisodeNumber']);
		if(!empty($episode['episodeName']))
			$episodename.=' - '.$episode['episodeName'];

		return $episodename;
	}
}