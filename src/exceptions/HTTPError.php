<?php

namespace datagutten\tvdb\exceptions;

use WpOrg\Requests\Response;

class HTTPError extends TVDBException
{
    public Response $response;
}