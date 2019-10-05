<?Php
require 'tvdb.php';
$tvdb=new tvdb;
$tvdb->lang='en';
$series='24 Hours in A&E';
$season=3;
$episode=15;
// S03E15 - A Few Good Men
//$series=$tvdb->findseries($series);
$series=248699;
$info = $tvdb->episode_info($series,$season,$episode);
if($info===false)
    echo $tvdb->error."\n";
else
    print_r($info);
//print_r($tvdb->find_episode_by_name($series,'A Few Good Men'));
