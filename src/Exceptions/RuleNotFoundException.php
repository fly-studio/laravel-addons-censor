<?php

namespace Addons\Censor\Exceptions;

use RuntimeException;
use Addons\Censor\CensorLoader;

class RuleNotFoundException extends RuntimeException {

    public function __construct(string $message, CensorLoader $loader = null, string $key = null)
    {
        if (!empty($loader) && !empty($key))
            $message .= ' In directory '.$loader->getPath($key);
        parent::__construct($message);
    }

}
