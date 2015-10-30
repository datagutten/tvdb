<?Php
class tvdb
{
	public $apikey;
	private $ch;
	private $http_status;
	public $lang='no';
	public $error='';
	public $debug=false; //Set to true to show debug info
	function __construct($apikey)
	{
		$this->ch=curl_init();
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,1);	
		curl_setopt($this->ch,CURLOPT_FOLLOWLOCATION,1);
		$this->apikey=$apikey;
	}
	public function get($url)
	{
		curl_setopt($this->ch, CURLOPT_URL,$url);
		$data=curl_exec($this->ch);		
		$this->http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if($this->http_status!=200)
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
	public function get_and_parse($url)
	{
		$string=$this->get($url);
		if($string===false)
			return false;
		else
			return json_decode(json_encode(simplexml_load_string($string)),true);
	}
	private function getseries($id,$language) //Get a series by id
	{
		if($language===false) //Default to preferred language
			$language=$this->lang;
		$file="series/$id/all/$language.xml";
		$cachefile=dirname(__FILE__).'/cache/'.$file;
		if(!file_exists($cachefile))
		{
			$url="http://www.thetvdb.com/api/{$this->apikey}/".$file;
	
			if(!file_exists($dir=dirname($cachefile)))
				mkdir($dir,0777,true);

			if(($xmlstring=$this->get($url))!==false)
				file_put_contents($cachefile,$xmlstring);
			else
				return false;
		}
		else
			$xmlstring=file_get_contents($cachefile);
		return simplexml_load_string($xmlstring);
	}
	
	public function findseries($search,$language=false)
	{
		$key=$this->apikey;
		if($language===false) //Default to preferred language
			$language=$this->lang;
		if(empty($search))
			trigger_error('findseries was called without specifying any series',E_USER_ERROR);
		if(!is_numeric($search))
		{	
			$search=str_replace('its',"it's",$search);
			$seriesinfo=$this->get_and_parse($url="http://www.thetvdb.com/api/GetSeries.php?language=$language&seriesname=".urlencode($search));
			if($seriesinfo===false)
			{
				$this->error=sprintf('Error connecting to TheTVDB HTTP status code %s',$this->http_status);
				return false;
			}
			if(isset($seriesinfo['Series'][0]))
			{
				foreach($seriesinfo['Series'] as $series)
				{
					if($series['language']==$this->lang) //Find the first match in the preferred language
						break;
					else
						$series=false;
				}
				if($series===false)
				{
					$this->error=sprintf('Multiple matches found, none of them in the preferred language (%s)',$this->lang);
					return false;
				}
				else
				{
					if($this->debug)
						$this->error=sprintf('Multiple matches found, returning the first match in the preferred language (%s)',$this->lang);
					$seriesinfo['Series']=$series;
				}
			}
			elseif(!isset($seriesinfo['Series']['seriesid']))
			{
				if($language!='all')
				{
					if($this->debug)
						$this->error=sprintf('Series not found in preferred language (%s), trying all',$this->lang);
					$episodes=$this->findseries($search,'all'); //Retry search in all languages

					if($episodes===false)
					{
						$this->error='Series not found on TheTVDB';
						return false;
					}
					else
						return $episodes;
				}
				else
					return false;
			}
			$id=$seriesinfo['Series']['seriesid'];
		}
		else
			$id=$search;

		if(is_numeric($id)) //Hvis id er funnet, hent episoder
		{
			$episodes=$this->getseries($id,$language);
			if(($episodes===false || $episodes->Series->SeriesName=='') && ($episodes=$this->getseries($id,'en'))===false) //If information was not found in the specified language, try English
			{
				$this->error='Could not find episodes for the series';
				return false;
			}
			$episoder=json_decode(json_encode($episodes),true);
			return $episoder;
		}
	}
	public function finnepisode($serie,$sesong,$episode) //Finn informasjon om en episode
	{
		if(!is_array($serie))
			$serie=$this->findseries($serie);
		if($serie===false)
			return false;

		foreach ($serie['Episode'] as $episodedata) //Gå gjennom alle episoder i alle sesonger til riktig episode blir funnet
		{
			if($episodedata['SeasonNumber']==$sesong && $episodedata['EpisodeNumber']==$episode)
				return array('Episode'=>$episodedata,'Series'=>$serie['Series']);
		}
		//If the loop has completed without returning, the episode is not found
		$this->error='Episode not found. Try clearing cache if it is a new episode';
		return false;
	}
	public function banner($series)
	{
		if(!is_array($series))
		{
			$series=urlencode(str_replace('.',' ',$series));
			$series=$this->get_and_parse("http://www.thetvdb.com/api/GetSeries.php?seriesname=$serie&language=all");
		}
		if(!empty($series['Series']['banner']))
			$banner="http://thetvdb.com/banners/{$series['Series']['banner']}";
		else
			$banner=false;

		return $banner;
	}
	public function finnepisodenavn($find,$episoder)
	{
		$found=false;
		//print_r($episoder);
		foreach ($episoder['Episode'] as $episode)
		{
			if(is_array($episode['EpisodeName'])) //Skip episodes with no name
				continue;
			if(stripos($episode['EpisodeName'],$find)!==false)
			{
				$found=true;
				break;
			}
		}
		if($found)
			$return=array('Episode'=>$episode,'Series'=>$episoder['Series']);
		else
			$return=false;
		return $return;
	}
	public function link($info)
	{
		if(!isset($info['Episode']) && isset($info['seriesid']))
			$info['Episode']=$info;
		if(isset($info['Episode'][0])) //Entire series
			return "http://www.thetvdb.com/index.php?id={$info['Series']['id']}";
		else
			return "http://www.thetvdb.com/?tab=episode&seriesid={$info['Episode']['seriesid']}&seasonid={$info['Episode']['seasonid']}&id={$info['Episode']['id']}"; //Single episode
	}
	public function episodename($tvdbinfo)
	{
		if(!isset($tvdbinfo['Episode']))
			return false;

		$episodename='S'.str_pad($tvdbinfo['Episode']['Combined_season'],2,'0',STR_PAD_LEFT).
		             'E'.str_pad($tvdbinfo['Episode']['EpisodeNumber']  ,2,'0',STR_PAD_LEFT);
		if(!empty($tvdbinfo['Episode']['EpisodeName']))
			$episodename.=' - '.$tvdbinfo['Episode']['EpisodeName'];

		return $episodename;
	}

}