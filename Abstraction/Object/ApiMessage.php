<?php
namespace Abstraction\Object;

use Abstraction\Core\AMessage;

class ApiMessage extends AMessage
{
    const SUCCESS = 200;

    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOW = 405;

    const INTERNAL_SERVER_ERROR = 500;

} // end class

