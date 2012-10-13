<?php

namespace OmniApp\Http;

use OmniApp\Data\MimeType;
use OmniApp\Registry;
use OmniApp\Exception\Pass;
use OmniApp\App;
use OmniApp\Config;

class Request extends Registry
{
    /**
     * @var array Route params
     */
    public $params = array();

    /**
     * @var \OmniApp\App
     */
    public $app;

    protected $path;
    protected $script_name;
    protected $path_info;
    protected $url;
    protected $body;
    protected $headers;
    protected $accept;
    protected $accept_language;
    protected $accept_encoding;
    protected $env;

    /**
     * @param \OmniApp\App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;

        $this->env = new Config($_SERVER);
    }

    /**
     * Get protocol
     *
     *
     * @return string
     */
    public function protocol()
    {
        return $this->env('SERVER_PROTOCOL');
    }

    /**
     * Get path
     *
     *
     * @return mixed
     */
    public function path()
    {
        if (null === $this->path) $this->path = parse_url($this->env('REQUEST_URI'), PHP_URL_PATH);

        return $this->path;
    }

    /**
     * Get request uri
     *
     * @return string
     */
    public function uri()
    {
        return $this->env('REQUEST_URI');
    }

    /**
     * Get script name
     *
     * @return string
     */
    public function scriptName()
    {
        if (null === $this->script_name) {
            $_script_name = $this->env('SCRIPT_NAME');
            if (strpos($this->env('REQUEST_URI'), $_script_name) !== 0) {
                $_script_name = str_replace('\\', '/', dirname($_script_name));
            }
            $this->script_name = rtrim($_script_name, '/');
        }
        return $this->script_name;
    }

    /**
     * Get root uri
     *
     * @return string
     */
    public function rootUri()
    {
        return $this->scriptName();
    }

    /**
     * Get path info
     *
     * @return string
     */
    public function pathInfo()
    {
        if (null === $this->path_info) {
            $_path_info = substr_replace($this->uri(), '', 0, strlen($this->scriptName()));
            if (strpos($_path_info, '?') !== false) {
                // Query string is not removed automatically
                $_path_info = substr_replace($_path_info, '', strpos($_path_info, '?'));
            }
            $this->path_info = $_path_info;
        }
        return $this->path_info;
    }

    /**
     * LEGACY: Get Resource URI (alias for pathInfo)
     *
     * @return string
     */
    public function resourceUri()
    {
        return $this->pathInfo();
    }

    /**
     * Get url
     *
     *
     * @return mixed
     */
    public function url()
    {
        if (null === $this->url) {
            $_url = $this->scheme() . '://' . $this->host();
            if (($this->scheme() === 'https' && $this->port() !== 443) || ($this->scheme() === 'http' && $this->port() !== 80)) {
                $_url .= sprintf(':%s', $this->port());
            }
            $this->url = $_url;
        }

        return $this->url;
    }

    /**
     * Get method
     *
     *
     * @return string
     */
    public function method()
    {
        return $this->env('REQUEST_METHOD');
    }

    /**
     * Is given method?
     *
     * @param string $method
     * @return bool
     */
    public function is($method)
    {
        return $this->method() == strtoupper($method);
    }

    /**
     * Is get method?
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method() === 'GET';
    }

    /**
     * Is post method?
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->method() === 'POST';
    }

    /**
     * Is put method?
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->method() === 'PUT';
    }

    /**
     * Is delete method?
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->method() === 'DELETE';
    }

    /**
     * Is head method?
     *
     * @return bool
     */
    public function isHead()
    {
        return $this->method() === 'HEAD';
    }

    /**
     * Is options method?
     *
     * @return bool
     */
    public function isOptions()
    {
        return $this->method() === 'OPTIONS';
    }

    /**
     * Is ajax
     *
     *
     * @return bool
     */
    public function isAjax()
    {
        return !$this->env('X-Requested-With') && 'XMLHttpRequest' == $this->env('X-Requested-With');
    }

    /**
     * Is Ajax?
     *
     * @return bool
     */
    public function isXhr()
    {
        return $this->isAjax();
    }

    /**
     * Is secure connection?
     *
     * @return bool
     */
    public function isSecure()
    {
        return $this->scheme() === 'https';
    }

    /**
     * Check If accept mime type?
     *
     * @param array|string $type
     * @return array|null|string
     */
    public function accept($type = null)
    {
        if ($this->accept === null) {
            $this->accept = self::buildAcceptMap($this->env('HTTP_ACCEPT'));
        }

        // if no parameter was passed, just return parsed data
        if (!$type) return $this->accept;
        // Support get best match
        if ($type === true) {
            reset($this->accept);
            return key($this->accept);
        }

        // If type is 'txt', 'xml' and so on, use smarty stracy
        if (is_string($type) && !strpos($type, '/')) {
            $type = MimeType::load()->get($type);
            if (!$type) return null;
        }

        // Force to array
        $type = (array)$type;

        // let’s check our supported types:
        foreach ($this->accept as $mime => $q) {
            if ($q && in_array($mime, $type)) return $mime;
        }

        // All match
        if (isset($this->accept['*/*'])) return $type[0];
        return null;
    }

    /**
     * Check accept giving encoding?
     *
     * @param string|array $type
     * @return array|null|string
     */
    public function acceptEncoding($type = null)
    {
        if ($this->accept_encoding === null) {
            $this->accept_encoding = self::buildAcceptMap($this->env('HTTP_ACCEPT_LANGUAGE'));
        }

        // if no parameter was passed, just return parsed data
        if (!$type) return $this->accept_encoding;
        // Support get best match
        if ($type === true) {
            reset($this->accept_encoding);
            return key($this->accept_encoding);
        }

        // Force to array
        $type = (array)$type;

        // let’s check our supported types:
        foreach ($this->accept_encoding as $lang => $q) {
            if ($q && in_array($lang, $type)) return $lang;
        }
        return null;
    }

    /**
     * Check if accept given language?
     *
     * @param string|array $type
     * @return array|null|string
     */
    public function acceptLanguage($type = null)
    {
        if ($this->accept_language === null) {
            $this->accept_language = self::buildAcceptMap($this->env('HTTP_ACCEPT_LANGUAGE'));
        }

        // if no parameter was passed, just return parsed data
        if (!$type) return $this->accept_language;
        // Support get best match
        if ($type === true) {
            reset($this->accept_language);
            return key($this->accept_language);
        }

        // Force to array
        $type = (array)$type;

        // let’s check our supported types:
        foreach ($this->accept_language as $lang => $q) {
            if ($q && in_array($lang, $type)) return $lang;
        }
        return null;
    }

    /**
     * Get IP
     *
     * @return string
     */
    public function ip()
    {
        if ($ip = $this->env('X_FORWARDED_FOR')) {
            return $ip;
        } elseif ($ip = $this->env('CLIENT_IP')) {
            return $ip;
        }

        return $this->env('REMOTE_ADDR');
    }

    /**
     * Get refer url
     *
     *
     * @return string
     */
    public function refer()
    {
        return $this->env('HTTP_REFERER');
    }

    /**
     * Get host
     *
     * @return string
     */
    public function host()
    {
        if ($host = $this->env('HTTP_HOST')) {
            if (strpos($host, ':') !== false) {
                $hostParts = explode(':', $host);

                return $hostParts[0];
            }

            return $host;
        }
        return $this->env('SERVER_NAME');
    }

    /**
     * Get domain. Alias for host
     *
     * @return string
     */
    public function domain()
    {
        return $this->host();
    }

    /**
     * Get scheme
     *
     * @return string
     */
    public function scheme()
    {
        return !$this->env('HTTPS') || $this->env('HTTPS') === 'off' ? 'http' : 'https';
    }

    /**
     * Get port
     *
     * @return int
     */
    public function port()
    {
        return (int)$this->env('SERVER_PORT');
    }

    /**
     * Get user agent
     *
     *
     * @return string
     */
    public function userAgent()
    {
        return $this->env('HTTP_USER_AGENT');
    }

    /**
     * Get content type
     *
     * @return string
     */
    public function contentType()
    {
        return $this->env('CONTENT_TYPE');
    }

    /**
     * Get media type
     *
     * @return null|string
     */
    public function mediaType()
    {
        $contentType = $this->contentType();
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);

            return strtolower($contentTypeParts[0]);
        }
        return null;
    }

    /**
     * @return string
     */
    public function contentCharset()
    {
        $mediaTypeParams = $this->mediaType();
        if (isset($mediaTypeParams['charset'])) {
            return $mediaTypeParams['charset'];
        }
        return null;
    }

    /**
     * Get Content-Length
     *
     * @return int
     */
    public function contentLength()
    {
        if ($len = $this->env('CONTENT_LENGTH')) {
            return (int)$len;
        }
        return 0;
    }

    /**
     * Get the param from query string
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }

    /**
     * Get post data by key
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function post($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;

    }

    /**
     * Get any params from get or post
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function param($key, $default = null)
    {
        return isset($this->params[$key]) ? $this->params[$key] : $default;
    }

    /**
     * Get header or headers
     *
     * @param string $name
     * @return mixed
     */
    public function header($name = null)
    {
        if (null === $this->headers) {
            $_headers = array();
            foreach ($this->env as $key => $value) {
                $_name = false;
                if ('HTTP_' === substr($key, 0, 5)) {
                    $_name = substr($key, 5);
                } elseif ('X_' == substr($key, 0, 2)) {
                    $_name = substr($key, 2);
                } elseif (in_array($key, array('CONTENT_LENGTH',
                    'CONTENT_MD5',
                    'CONTENT_TYPE'))
                ) {
                    $_name = $key;
                }
                if (!$_name) continue;

                // Set headers
                $_headers[strtoupper(str_replace('_', '-', $_name))] = trim($value);
            }

            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $_headers['AUTHORIZATION'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $pass);
            }
            $this->headers = $_headers;
            unset($_headers);
        }

        if ($name === null) return $this->headers;
        $name = strtoupper($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    /**
     * Get cookie
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function cookie($key = null, $default = null)
    {
        if ($key === null) {
            return $_COOKIE;
        }
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function body()
    {
        if (null === $this->body) {
            $this->body = @(string)file_get_contents('php://input');
        }
        return $this->body;
    }

    /**
     * Pass
     *
     * @throws Pass
     */
    public function pass()
    {
        $this->app->cleanBuffer();
        throw new Pass();
    }

    /**
     * Env
     *
     * @param $key
     * @return mixed
     */
    public function env($key = null)
    {
        if (is_array($key)) {
            $this->env = new Config($key);
            return;
        }

        if ($key === null) return $this->env;

        return isset($this->env[$key]) ? $this->env[$key] : null;
    }

    /**
     * Build accept map
     *
     * @param $string
     * @return array
     */
    protected static function buildAcceptMap($string)
    {
        $_accept = array();

        // Accept header is case insensitive, and whitespace isn’t important
        $accept = strtolower(str_replace(' ', '', $string));
        // divide it into parts in the place of a ","
        $accept = explode(',', $accept);
        foreach ($accept as $a) {
            // the default quality is 1.
            $q = 1;
            // check if there is a different quality
            if (strpos($a, ';q=')) {
                // divide "mime/type;q=X" into two parts: "mime/type" i "X"
                list($a, $q) = explode(';q=', $a);
            }
            // mime-type $a is accepted with the quality $q
            // WARNING: $q == 0 means, that mime-type isn’t supported!
            $_accept[$a] = $q;
        }
        arsort($_accept);
        return $_accept;
    }
}
