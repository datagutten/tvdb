<?Php

use datagutten\tvdb\tvdb;
use datagutten\tvdb\exceptions\api_error;

require 'vendor/autoload.php';
$tvdb=new tvdb;
$series='24 Hours in A&E';
$season=3;
$episode=15;
// S03E15 - A Few Good Men
//$series=$tvdb->findseries($series);
$series=248699;
try {
    $info = $tvdb->episode_info($series, $season, $episode);
}
catch (api_error $e) {
    die($e->getMessage()."\n");
}
print_r($info);
//print_r($tvdb->find_episode_by_name($series,'A Few Good Men'));
