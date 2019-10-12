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

    /**
     * Send and decode a request
     * @param string $uri URI (Appended to https://api.thetvdb.com)
     * @param string $language Language
     * @return array Response from TVDB as decoded json
     * @throws Exception Unable to parse response
     */
	public function request($uri,$language=null)
	{
		if(empty($language)) //Default to preferred language
			$language=$this->lang;
		$this->headers['language']='Accept-Language: '.$language;
		$result_string=$this->get('https://api.thetvdb.com'.$uri);
		if($result_string!==false)
			return json_decode($result_string,true);
		else
			throw new Exception('Unable to parse response');
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
			{
				$this->error=sprintf('Fetching page %d failed with error: %s',$page,$this->error);
				return false;
			}
				
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

    /**
     * Find information about an episode
     * @param int $series_id Series id
     * @param int $season Season number
     * @param int $episode Episode number
     * @param string $language Language
     * @return array Episode info
     * @throws Exception Unable to parse response
     */
	public function episode_info($series_id,$season,$episode,$language=null)
	{
		if(!is_numeric($series_id) || !is_numeric($season) || !is_numeric($episode))
			throw new InvalidArgumentException('Series id, season and episode must be numeric');
		$episode=$this->request(sprintf('/series/%d/episodes/query?airedSeason=%d&airedEpisode=%d',$series_id,$season,$episode),$language);
		$episode=$episode['data'][0]; //This search will always return one result
		$episode['banner']=$this->banner($series_id);
		$episode['series']=$series_id;
		return $episode;
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
	public function find_episode_by_name($series,$search)
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
		$names=array_combine(array_keys($episodes),array_column($episodes,'episodeName'));
		$names=array_filter($names); //Remove episodes without name
		if(empty($names))
		{
			$this->error=sprintf('No episodes have names in language: %s',$this->lang);
			return false;
		}
		foreach ($names as $episode=>$name)
		{
			if(stripos($name,$search)!==false)
			{
				$episode=$episodes[$episode];
				$episode['banner']=$this->banner($series);
				$episode['series']=$series['id'];
				return $episode;
			}
		}
		//If loop has completed without returning there is no match
		$this->error='Unable to find episode with name: '.$search;
		return false;
	}

	/*Create link to an episode
	Argument should be an array of episode information (returned by episode_info or find_episode_by_name)
	*/
	public function episode_link($episode)
	{
		return sprintf('http://www.thetvdb.com/?tab=episode&seriesid=%d&seasonid=%d&id=%d',$episode['series'],$episode['airedSeasonID'],$episode['id']);
	}

	//Create link to a series
	public function series_link($series_id)
	{
		return 'http://www.thetvdb.com/index.php?id='.$series_id;
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