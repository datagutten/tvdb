<?php


namespace datagutten\tvdb\objects;


abstract class TVDBObject
{
    public function __construct($data)
    {
        foreach ($data as $key => $value)
        {
            if (!empty($value))
                $this->$key = $value;
        }
    }
}