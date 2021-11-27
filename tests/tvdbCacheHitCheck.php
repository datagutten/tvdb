<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace datagutten\tvdb\tests;

use datagutten\tvdb\tvdb_cache;
use Exception;

class tvdbCacheHitCheck extends tvdb_cache
{
    public function request(string $uri, string $language = ''): array
    {
        throw new Exception('Cache not hit');
    }
}