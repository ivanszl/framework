<?php

namespace OmniApp\Middleware;

use OmniApp\App;

class PrettyException extends \OmniApp\Middleware
{
    public function call()
    {
        try {
            ob_start();
            $this->next();
            echo ob_get_clean();
        } catch (\Exception $e) {
            ob_clean();
            App::render(__DIR__ . '/views/error.php', array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
                'message' => $e->getMessage(),
                'type' => get_class($e)
            ));
        }
    }
}