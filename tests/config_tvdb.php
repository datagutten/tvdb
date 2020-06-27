<?php
return [
    'api_key'=> getenv('API_KEY'),
    'username'=>getenv('USERNAME'),
    'user_key'=>getenv('USER_KEY'),
    'cache_path'=>__DIR__.'/test_data/tvdb_cache_dir'];