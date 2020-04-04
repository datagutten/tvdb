<?php


namespace datagutten\tvdb\exceptions;


use Requests_Response;

class api_error extends tvdbException
{
    /**
     * @var Requests_Response
     */
    public $response;
    /**
     * api_error constructor.
     * @param $response Requests_Response
     * @param int $code
     * @param tvdbException|null $previous
     */
    public function __construct($response, $code = 0, tvdbException $previous = null) {
        $this->response = $response;
        $error = json_decode($response->body, true);
        $message = sprintf('Error from TVDB: %s', $error['Error']);
        parent::__construct($message, $code, $previous);
    }

}