<?Php
class tvdb
{
	public $lang;
	public $error='';
	public $debug=false; //Set to true to show debug info
	private $headers=array();
	private $search_languages;
	public $last_search_language=false; //Language for the last search
	function __construct()
	{
		$this->headers['Content-Type'] = 'application/json';
		require 'config_tvdb.php';
		$this->search_languages=$search_languages;
		$this->lang=$default_language;
		$this->login($api_key,$username,$user_key);
	}

    /**
     * Send login request and store token
     * @param $apikey
     * @param $username
     * @param $userkey
     * @throws Requests_Exception
     * @throws Requests_Exception_HTTP
     */
	public function login($apikey,$username,$userkey)
	{
		$request=json_encode(array('apikey'=>$apikey,'username'=>$username,'userkey'=>$userkey));
		$response = Requests::post('https://api.thetvdb.com/login', $this->headers, $request);
		$response->throw_for_status();

		$token=json_decode($response->body,true)['token'];
		$this->headers['Authorization'] = 'Bearer '.$token;
	}

    /**
     * Send and decode a request
     * @param string $uri URI (Appended to https://api.thetvdb.com)
     * @param string $language Language
     * @return array Response from TVDB as decoded json
     * @throws Requests_Exception HTTP error
     */
	public function request($uri,$language=null)
	{
		if(empty($language)) //Default to preferred language
			$language=$this->lang;
		$this->headers['Accept-Language'] = $language;
		$response = Requests::get('https://api.thetvdb.com'.$uri, $this->headers);
		if($response->status_code === 404)
		    return null;
		$response->throw_for_status();
        return json_decode($response->body,true);
	}

    /**
     * Get a series by id
     * @param int $series_id
     * @param string $language
     * @return array Series info
     * @throws Requests_Exception
     */
	public function getseries($series_id,$language=null)
	{
		if(!is_numeric($series_id))
			throw new InvalidArgumentException('Series ID must be numeric');
		$response=$this->request('/series/'.$series_id,$language);
        return $response['data'];
	}

    /**
     * Fetch and sort episodes for a series
     * @param int $series_id Series ID
     * @param string $language Language
     * @return array Episode info
     * @throws Requests_Exception
     */
	public function getepisodes($series_id,$language=null)
	{
		if(empty($series_id))
			throw new InvalidArgumentException('Series ID is empty');

		if(!is_numeric($series_id))
			throw new InvalidArgumentException('Series ID must be numeric');
		$last_page=1;
		$episodes=array(); //Initialize episodes array
		for($page=1; $page<=$last_page; $page++)
		{
			$episodes_page=$this->request(sprintf('/series/%s/episodes?page=%s',$series_id,$page),$language);
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

    /**
     * Search for a series
     * @param string $search Search string
     * @param string $language
     * @return array Series info
     * @throws Requests_Exception
     */
	public function series_search($search,$language=null)
	{
		if(empty($search))
			throw new InvalidArgumentException('Empty search string');

		$search=str_replace('its',"it's",$search);
		if(!empty($language))
		    $languages = [$language];
		else
		    $languages = $this->search_languages;

		foreach ($languages as $language)
        {
            $series_info = $this->request('/search/series?name=' . urlencode($search), $language);
            if(!empty($series_info))
                break;
        }

		if(empty($series_info)) //No match
		{
		    if($this->debug)
			    echo 'No series found for search '.$search;
		    return null;
		}

		$this->last_search_language=$language; //Save the search language
		if(count($series_info['data'])>1) //Multiple hits
		{
			foreach($series_info['data'] as $series)
			{
				if($series['seriesName']==$search) //Try to find exact name match
					return $series;
			}
			//If we are here there was no exact match
			if($this->debug)
				echo 'Multiple matches found, but no exact name match. First result is returned';
			return $series_info['data'][0];
		}
		else //Single hit
			return $series_info['data'][0];
	}

    /**
     * Search for a series and get series information
     * @param string $search Search string
     * @param string $language Language
     * @return array Series info
     * @throws Requests_Exception
     */
	public function findseries($search,$language=null)
	{
		if(is_numeric($search))
			return $this->getseries($search,$language);
		else
		{
			$search_result=$this->series_search($search,$language);
            if(empty($language)) //Return results in the same language as the search
                $language=$this->last_search_language;
            return $this->getseries($search_result['id'],$language);
		}
	}

    /**
     * Get series and episodes for a series ID
     * @param int $series_id Series ID
     * @param string $language Language
     * @return mixed
     * @throws Requests_Exception
     */
	public function get_series_and_episodes($series_id, $language=null)
	{
		$series['Series']=$this->getseries($series_id,$language);
		$series['Episode']=$this->getepisodes($series_id,$language);
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

    /**
     * Get series banner
     * @param $series
     * @return string|null
     * @throws Requests_Exception
     */
	public function banner($series)
	{
		if(!is_array($series))
			$series=$this->findseries($series);

		if(!empty($series['banner']))
			return "http://thetvdb.com/banners/{$series['banner']}";
		else
			return null;
	}

    /**
     * Find episode by name
     * @param $series
     * @param $search
     * @return array|bool
     * @throws Requests_Exception
     */
	public function find_episode_by_name($series,$search)
	{
		if(!is_array($series))
			$series=$this->findseries($series);

		$episodes=$this->getepisodes($series['id']);
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