<?php

namespace datagutten\tvdb\exceptions;

use WpOrg\Requests\Response;

class HTTPError extends tvdbException
{
    public Response $response;
}