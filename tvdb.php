<?Php
class tvdb
{
	public $apikey;
	private $ch;
	private $http_status;
	private $linebreak="\n";
	public $lang='no';
	public $error='';
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
			return false;
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
	private function getseries($id,$language='en') //Get a series by id
	{
		$file="series/$id/all/$language.xml";
		$cachefile='cache/'.$file;
		if(!file_exists($cachefile))
		{
			$url="http://www.thetvdb.com/api/{$this->apikey}/".$file;
	
			if(!file_exists($dir=dirname($cachefile)))
				mkdir($dir,0777,true);

			if(($xmlstring=$this->get($url))!==false)
				file_put_contents($cachefile,$xmlstring);
			else
			{
				$this->error="Error fetching data from TVDB".$this->linebreak;
				return false;
			}
		}
		else
			$xmlstring=file_get_contents($cachefile);
		return simplexml_load_string($xmlstring);
	}
	
	public function findseries($search,$language='all')
	{
		$key=$this->apikey;
		if($search=='')
		{
			$this->error.='findseries was called without specifying any series'.$this->linebreak;
			return false;
		}
		if(!is_numeric($search))
		{	
			$search=str_replace('its',"it's",$search);
			$seriesinfo=$this->get_and_parse($url="http://www.thetvdb.com/api/GetSeries.php?language=$language&seriesname=".urlencode($search));
			//var_dump(isset($seriesinfo['Series'][0]));
			if($seriesinfo===false)
			{
				$this->error.="Error connecting to TheTVDB".$this->linebreak;
				return false;
			}
			if(isset($seriesinfo['Series'][0]))
			{
				foreach($seriesinfo['Series'] as $key=>$series)
				{
					$id[$key]=$series['seriesid'];
					$lang[$key]=$series['language'];
				}
				if($language=='all')
					$returnlang=$this->lang; //If all languages are searched, use the preferred language for return
				else
					$returnlang=$language;
				if(count(array_unique($id))==1 && ($key=array_search($returnlang,$lang))!==false) //If all matches are from the same series with different languages, return the requested language
					$seriesinfo['Series']=$seriesinfo['Series'][$key];
				else
				{
					$this->error.="Multiple matches for \"$search\" for language \"$language\"".$this->linebreak;
					return false;
				}
			}
			if(!isset($seriesinfo['Series']['seriesid']))
			{
				$this->error.="Series not found on TheTVDB: $search".$this->linebreak;
				return false;
			}
			$id=$seriesinfo['Series']['seriesid'];
		}
		else
			$id=$search;

		if(is_numeric($id)) //Hvis id er funnet, hent episoder
		{
			$episodes=$this->getseries($id,$this->lang);

			if(($episodes===false || $episodes->Series->SeriesName=='') && ($episodes=$this->getseries($id))===false) //If information was not found in the preferred language, try English
			{
				$this->error.="Could not find episodes for the series".$this->linebreak;
				return false;
			}
			$episoder=json_decode(json_encode($episodes),true);
			//echo "Episoder: ";
			//var_dump($episoder);
			return $episoder;
		}
	}
	public function finnepisode($serie,$sesong,$episode) //Finn informasjon om en episode
	{
		if (!is_array($serie))
			$serie=$this->findseries($serie);
		
		if(is_array($serie))
		{
			foreach ($serie['Episode'] as $episodedata) //Gå gjennom alle episoder i alle sesonger til riktig episode blir funnet
				if ($episodedata['SeasonNumber']==$sesong && $episodedata['EpisodeNumber']==$episode)
					break;

			$return=array('Episode'=>$episodedata,'Series'=>$serie['Series']);
		}
		else
			$return=false;
	return $return;
	}
	public function banner($serie)
	{
		if(!is_array($serie))
		{
			$serie=urlencode(str_replace('.',' ',$serie));
			$xml=$this->get_and_parse("http://www.thetvdb.com/api/GetSeries.php?seriesname=$serie&language=all");
		}
	
		if(isset($xml['Series']['banner']))
			$banner="http://thetvdb.com/banners/{$xml['Series']['banner']}";
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

}