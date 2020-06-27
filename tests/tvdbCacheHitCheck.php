<?php

namespace datagutten\tvdb\tests;

use Exception;

class tvdbCacheHitCheck extends \datagutten\tvdb\tvdb_cache
{
    function request($uri, $language = null)
    {
        throw new Exception('Cache not hit');
    }
}