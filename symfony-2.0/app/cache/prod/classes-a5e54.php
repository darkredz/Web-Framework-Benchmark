<?php
namespace Symfony\Component\Routing
{
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
interface RouterInterface extends UrlMatcherInterface, UrlGeneratorInterface
{
}
}
namespace Symfony\Component\Routing\Matcher
{
interface UrlMatcherInterface
{
    function match($url);
}
}
namespace Symfony\Component\Routing\Matcher
{
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
class UrlMatcher implements UrlMatcherInterface
{
    protected $routes;
    protected $defaults;
    protected $context;
    public function __construct(RouteCollection $routes, array $context = array(), array $defaults = array())
    {
        $this->routes = $routes;
        $this->context = $context;
        $this->defaults = $defaults;
    }
    public function setContext(array $context = array())
    {
        $this->context = $context;
    }
    public function match($url)
    {
        $url = $this->normalizeUrl($url);
        foreach ($this->routes->all() as $name => $route) {
            $compiledRoute = $route->compile();
            if (isset($this->context['method']) && (($req = $route->getRequirement('_method')) && !preg_match(sprintf('#^(%s)$#xi', $req), $this->context['method']))) {
                continue;
            }
                        if ('' !== $compiledRoute->getStaticPrefix() && 0 !== strpos($url, $compiledRoute->getStaticPrefix())) {
                continue;
            }
            if (!preg_match($compiledRoute->getRegex(), $url, $matches)) {
                continue;
            }
            return array_merge($this->mergeDefaults($matches, $route->getDefaults()), array('_route' => $name));
        }
        return false;
    }
    protected function mergeDefaults($params, $defaults)
    {
        $parameters = array_merge($this->defaults, $defaults);
        foreach ($params as $key => $value) {
            if (!is_int($key)) {
                $parameters[$key] = urldecode($value);
            }
        }
        return $parameters;
    }
    protected function normalizeUrl($url)
    {
                if ('/' !== substr($url, 0, 1)) {
            $url = '/'.$url;
        }
                if (false !== $pos = strpos($url, '?')) {
            $url = substr($url, 0, $pos);
        }
                return preg_replace('#/+#', '/', $url);
    }
}
}
namespace Symfony\Component\Routing\Generator
{
interface UrlGeneratorInterface
{
    function generate($name, array $parameters, $absolute = false);
}
}
namespace Symfony\Component\Routing\Generator
{
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
class UrlGenerator implements UrlGeneratorInterface
{
    protected $routes;
    protected $defaults;
    protected $context;
    protected $cache;
    public function __construct(RouteCollection $routes, array $context = array(), array $defaults = array())
    {
        $this->routes = $routes;
        $this->context = $context;
        $this->defaults = $defaults;
        $this->cache = array();
    }
    public function setContext(array $context = array())
    {
        $this->context = $context;
    }
    public function generate($name, array $parameters, $absolute = false)
    {
        if (null === $route = $this->routes->get($name)) {
            throw new \InvalidArgumentException(sprintf('Route "%s" does not exist.', $name));
        }
        if (!isset($this->cache[$name])) {
            $this->cache[$name] = $route->compile();
        }
        return $this->doGenerate($this->cache[$name]->getVariables(), $route->getDefaults(), $route->getRequirements(), $this->cache[$name]->getTokens(), $parameters, $name, $absolute);
    }
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $absolute)
    {
        $defaults = array_merge($this->defaults, $defaults);
        $tparams = array_merge($defaults, $parameters);
                if ($diff = array_diff_key($variables, $tparams)) {
            throw new \InvalidArgumentException(sprintf('The "%s" route has some missing mandatory parameters (%s).', $name, implode(', ', $diff)));
        }
        $url = '';
        $optional = true;
        foreach ($tokens as $token) {
            if ('variable' === $token[0]) {
                if (false === $optional || !isset($defaults[$token[3]]) || (isset($parameters[$token[3]]) && $parameters[$token[3]] != $defaults[$token[3]])) {
                                        if (isset($requirements[$token[3]]) && !preg_match('#^'.$requirements[$token[3]].'$#', $tparams[$token[3]])) {
                        throw new \InvalidArgumentException(sprintf('Parameter "%s" for route "%s" must match "%s" ("%s" given).', $token[3], $name, $requirements[$token[3]], $tparams[$token[3]]));
                    }
                    $url = $token[1].urlencode($tparams[$token[3]]).$url;
                    $optional = false;
                }
            } elseif ('text' === $token[0]) {
                $url = $token[1].$token[2].$url;
                $optional = false;
            } else {
                                if ($segment = call_user_func_array(array($this, 'generateFor'.ucfirst(array_shift($token))), array_merge(array($optional, $tparams), $token))) {
                    $url = $segment.$url;
                    $optional = false;
                }
            }
        }
        if (!$url) {
            $url = '/';
        }
                if ($extra = array_diff_key($parameters, $variables, $defaults)) {
            $url .= '?'.http_build_query($extra);
        }
        $url = (isset($this->context['base_url']) ? $this->context['base_url'] : '').$url;
        if ($absolute && isset($this->context['host'])) {
            $isSecure = (isset($this->context['is_secure']) && $this->context['is_secure']);
            $port = isset($this->context['port']) ? $this->context['port'] : 80;
            $urlBeginning = 'http'.($isSecure ? 's' : '').'://'.$this->context['host'];
            if (($isSecure && $port != 443) || (!$isSecure && $port != 80)) {
                $urlBeginning .= ':'.$port;
            }
            $url = $urlBeginning.$url;
        }
        return $url;
    }
}
}
namespace Symfony\Component\Routing
{
use Symfony\Component\Routing\Loader\LoaderInterface;
class Router implements RouterInterface
{
    protected $matcher;
    protected $generator;
    protected $options;
    protected $defaults;
    protected $context;
    protected $loader;
    protected $collection;
    protected $resource;
    public function __construct(LoaderInterface $loader, $resource, array $options = array(), array $context = array(), array $defaults = array())
    {
        $this->loader = $loader;
        $this->resource = $resource;
        $this->context = $context;
        $this->defaults = $defaults;
        $this->options = array(
            'cache_dir'              => null,
            'debug'                  => false,
            'generator_class'        => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'generator_base_class'   => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper',
            'generator_cache_class'  => 'ProjectUrlGenerator',
            'matcher_class'          => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
            'matcher_base_class'     => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
            'matcher_dumper_class'   => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper',
            'matcher_cache_class'    => 'ProjectUrlMatcher',
        );
                if ($diff = array_diff(array_keys($options), array_keys($this->options))) {
            throw new \InvalidArgumentException(sprintf('The Router does not support the following options: \'%s\'.', implode('\', \'', $diff)));
        }
        $this->options = array_merge($this->options, $options);
    }
    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = $this->loader->load($this->resource);
        }
        return $this->collection;
    }
    public function setContext(array $context = array())
    {
        $this->getMatcher()->setContext($context);
        $this->getGenerator()->setContext($context);
    }
    public function generate($name, array $parameters = array(), $absolute = false)
    {
        return $this->getGenerator()->generate($name, $parameters, $absolute);
    }
    public function match($url)
    {
        return $this->getMatcher()->match($url);
    }
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }
        if (null === $this->options['cache_dir'] || null === $this->options['matcher_cache_class']) {
            return $this->matcher = new $this->options['matcher_class']($this->getRouteCollection(), $this->context, $this->defaults);
        }
        $class = $this->options['matcher_cache_class'];
        if ($this->needsReload($class)) {
            $dumper = new $this->options['matcher_dumper_class']($this->getRouteCollection());
            $options = array(
                'class'      => $class,
                'base_class' => $this->options['matcher_base_class'],
            );
            $this->updateCache($class, $dumper->dump($options));
        }
        require_once $this->getCacheFile($class);
        return $this->matcher = new $class($this->context, $this->defaults);
    }
    public function getGenerator()
    {
        if (null !== $this->generator) {
            return $this->generator;
        }
        if (null === $this->options['cache_dir'] || null === $this->options['generator_cache_class']) {
            return $this->generator = new $this->options['generator_class']($this->getRouteCollection(), $this->context, $this->defaults);
        }
        $class = $this->options['generator_cache_class'];
        if ($this->needsReload($class)) {
            $dumper = new $this->options['generator_dumper_class']($this->getRouteCollection());
            $options = array(
                'class'      => $class,
                'base_class' => $this->options['generator_base_class'],
            );
            $this->updateCache($class, $dumper->dump($options));
        }
        require_once $this->getCacheFile($class);
        return $this->generator = new $class($this->context, $this->defaults);
    }
    protected function updateCache($class, $dump)
    {
        $this->writeCacheFile($this->getCacheFile($class), $dump);
        if ($this->options['debug']) {
            $this->writeCacheFile($this->getCacheFile($class, 'meta'), serialize($this->getRouteCollection()->getResources()));
        }
    }
    protected function needsReload($class)
    {
        $file = $this->getCacheFile($class);
        if (!file_exists($file)) {
            return true;
        }
        if (!$this->options['debug']) {
            return false;
        }
        $metadata = $this->getCacheFile($class, 'meta');
        if (!file_exists($metadata)) {
            return true;
        }
        $time = filemtime($file);
        $meta = unserialize(file_get_contents($metadata));
        foreach ($meta as $resource) {
            if (!$resource->isUptodate($time)) {
                return true;
            }
        }
        return false;
    }
    protected function getCacheFile($class, $extension = 'php')
    {
        return $this->options['cache_dir'].'/'.$class.'.'.$extension;
    }
    protected function writeCacheFile($file, $content)
    {
        $tmpFile = tempnam(dirname($file), basename($file));
        if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $file)) {
            chmod($file, 0644);
            return;
        }
        throw new \RuntimeException(sprintf('Failed to write cache file "%s".', $file));
    }
}
}
namespace Symfony\Component\HttpFoundation
{
class Response
{
    public $headers;
    protected $content;
    protected $version;
    protected $statusCode;
    protected $statusText;
    protected $charset = 'UTF-8';
    static public $statusTexts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    );
    public function __construct($content = '', $status = 200, $headers = array())
    {
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->setProtocolVersion('1.0');
        $this->headers = new ResponseHeaderBag($headers);
    }
    public function __toString()
    {
        $content = '';
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'text/html; charset='.$this->charset);
        }
                $content .= sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText)."\n";
                foreach ($this->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $content .= "$name: $value\n";
            }
        }
        $content .= "\n".$this->getContent();
        return $content;
    }
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }
    public function sendHeaders()
    {
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'text/html; charset='.$this->charset);
        }
                header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));
                foreach ($this->headers->all() as $name => $values) {
            foreach ($values as $value) {
                header($name.': '.$value);
            }
        }
                foreach ($this->headers->getCookies() as $cookie) {
            setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpire(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }
    }
    public function sendContent()
    {
        echo $this->content;
    }
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
    }
    public function setContent($content)
    {
        $this->content = $content;
    }
    public function getContent()
    {
        return $this->content;
    }
    public function setProtocolVersion($version)
    {
        $this->version = $version;
    }
    public function getProtocolVersion()
    {
        return $this->version;
    }
    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = (int) $code;
        if ($this->statusCode < 100 || $this->statusCode > 599) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $code));
        }
        $this->statusText = false === $text ? '' : (null === $text ? self::$statusTexts[$this->statusCode] : $text);
    }
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }
    public function getCharset()
    {
        return $this->charset;
    }
    public function isCacheable()
    {
        if (!in_array($this->statusCode, array(200, 203, 300, 301, 302, 404, 410))) {
            return false;
        }
        if ($this->headers->hasCacheControlDirective('no-store') || $this->headers->getCacheControlDirective('private')) {
            return false;
        }
        return $this->isValidateable() || $this->isFresh();
    }
    public function isFresh()
    {
        return $this->getTtl() > 0;
    }
    public function isValidateable()
    {
        return $this->headers->has('Last-Modified') || $this->headers->has('ETag');
    }
    public function setPrivate()
    {
        $this->headers->removeCacheControlDirective('public');
        $this->headers->addCacheControlDirective('private');
    }
    public function setPublic()
    {
        $this->headers->addCacheControlDirective('public');
        $this->headers->removeCacheControlDirective('private');
    }
    public function mustRevalidate()
    {
        return $this->headers->hasCacheControlDirective('must-revalidate') || $this->headers->has('must-proxy-revalidate');
    }
    public function getDate()
    {
        if (null === $date = $this->headers->getDate('Date')) {
            $date = new \DateTime(null, new \DateTimeZone('UTC'));
            $this->headers->set('Date', $date->format('D, d M Y H:i:s').' GMT');
        }
        return $date;
    }
    public function getAge()
    {
        if ($age = $this->headers->get('Age')) {
            return $age;
        }
        return max(time() - $this->getDate()->format('U'), 0);
    }
    public function expire()
    {
        if ($this->isFresh()) {
            $this->headers->set('Age', $this->getMaxAge());
        }
    }
    public function getExpires()
    {
        return $this->headers->getDate('Expires');
    }
    public function setExpires(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Expires');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Expires', $date->format('D, d M Y H:i:s').' GMT');
        }
    }
    public function getMaxAge()
    {
        if ($age = $this->headers->getCacheControlDirective('s-maxage')) {
            return $age;
        }
        if ($age = $this->headers->getCacheControlDirective('max-age')) {
            return $age;
        }
        if (null !== $this->getExpires()) {
            return $this->getExpires()->format('U') - $this->getDate()->format('U');
        }
        return null;
    }
    public function setMaxAge($value)
    {
        $this->headers->addCacheControlDirective('max-age', $value);
    }
    public function setSharedMaxAge($value)
    {
        $this->headers->addCacheControlDirective('s-maxage', $value);
    }
    public function getTtl()
    {
        if ($maxAge = $this->getMaxAge()) {
            return $maxAge - $this->getAge();
        }
        return null;
    }
    public function setTtl($seconds)
    {
        $this->setSharedMaxAge($this->getAge() + $seconds);
    }
    public function setClientTtl($seconds)
    {
        $this->setMaxAge($this->getAge() + $seconds);
    }
    public function getLastModified()
    {
        return $this->headers->getDate('LastModified');
    }
    public function setLastModified(\DateTime $date = null)
    {
        if (null === $date) {
            $this->headers->remove('Last-Modified');
        } else {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));
            $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s').' GMT');
        }
    }
    public function getEtag()
    {
        return $this->headers->get('ETag');
    }
    public function setEtag($etag = null, $weak = false)
    {
        if (null === $etag) {
            $this->headers->remove('Etag');
        } else {
            if (0 !== strpos($etag, '"')) {
                $etag = '"'.$etag.'"';
            }
            $this->headers->set('ETag', (true === $weak ? 'W/' : '').$etag);
        }
    }
    public function setCache(array $options)
    {
        if ($diff = array_diff(array_keys($options), array('etag', 'last_modified', 'max_age', 's_maxage', 'private', 'public'))) {
            throw new \InvalidArgumentException(sprintf('Response does not support the following options: "%s".', implode('", "', array_keys($diff))));
        }
        if (isset($options['etag'])) {
            $this->setEtag($options['etag']);
        }
        if (isset($options['last_modified'])) {
            $this->setLastModified($options['last_modified']);
        }
        if (isset($options['max_age'])) {
            $this->setMaxAge($options['max_age']);
        }
        if (isset($options['s_maxage'])) {
            $this->setSharedMaxAge($options['s_maxage']);
        }
        if (isset($options['public'])) {
            if ($options['public']) {
                $this->setPublic();
            } else {
                $this->setPrivate();
            }
        }
        if (isset($options['private'])) {
            if ($options['private']) {
                $this->setPrivate();
            } else {
                $this->setPublic();
            }
        }
    }
    public function setNotModified()
    {
        $this->setStatusCode(304);
        $this->setContent(null);
                foreach (array('Allow', 'Content-Encoding', 'Content-Language', 'Content-Length', 'Content-MD5', 'Content-Type', 'Last-Modified') as $header) {
            $this->headers->remove($header);
        }
    }
    public function setRedirect($url, $status = 302)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }
        $this->setStatusCode($status);
        if (!$this->isRedirect()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $status));
        }
        $this->headers->set('Location', $url);
        $this->setContent(sprintf('<html><head><meta http-equiv="refresh" content="1;url=%s"/></head></html>', htmlspecialchars($url, ENT_QUOTES)));
    }
    public function hasVary()
    {
        return (Boolean) $this->headers->get('Vary');
    }
    public function getVary()
    {
        if (!$vary = $this->headers->get('Vary')) {
            return array();
        }
        return is_array($vary) ? $vary : preg_split('/[\s,]+/', $vary);
    }
    public function setVary($headers, $replace = true)
    {
        $this->headers->set('Vary', $headers, $replace);
    }
    public function isNotModified(Request $request)
    {
        $lastModified = $request->headers->get('If-Modified-Since');
        $notModified = false;
        if ($etags = $request->getEtags()) {
            $notModified = (in_array($this->getEtag(), $etags) || in_array('*', $etags)) && (!$lastModified || $this->headers->get('Last-Modified') == $lastModified);
        } elseif ($lastModified) {
            $notModified = $lastModified == $this->headers->get('Last-Modified');
        }
        if ($notModified) {
            $this->setNotModified();
        }
        return $notModified;
    }
        public function isInvalid()
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }
    public function isInformational()
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }
    public function isSuccessful()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
    public function isRedirection()
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }
    public function isClientError()
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }
    public function isServerError()
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }
    public function isOk()
    {
        return 200 === $this->statusCode;
    }
    public function isForbidden()
    {
        return 403 === $this->statusCode;
    }
    public function isNotFound()
    {
        return 404 === $this->statusCode;
    }
    public function isRedirect()
    {
        return in_array($this->statusCode, array(301, 302, 303, 307));
    }
    public function isEmpty()
    {
        return in_array($this->statusCode, array(201, 204, 304));
    }
    public function isRedirected($location)
    {
        return $this->isRedirect() && $location == $this->headers->get('Location');
    }
}
}
namespace Symfony\Component\HttpFoundation
{
class ResponseHeaderBag extends HeaderBag
{
    protected $computedCacheControl = array();
    public function __construct(array $parameters = array())
    {
                        parent::__construct();
        $this->replace($parameters);
    }
    public function replace(array $headers = array())
    {
        parent::replace($headers);
        if (!isset($this->headers['cache-control'])) {
            $this->set('cache-control', '');
        }
    }
    public function set($key, $values, $replace = true)
    {
        parent::set($key, $values, $replace);
                if ('cache-control' === strtr(strtolower($key), '_', '-')) {
            $computed = $this->computeCacheControlValue();
            $this->headers['cache-control'] = array($computed);
            $this->computedCacheControl = $this->parseCacheControl($computed);
        }
    }
    public function remove($key)
    {
        parent::remove($key);
        if ('cache-control' === strtr(strtolower($key), '_', '-')) {
            $this->computedCacheControl = array();
        }
    }
    public function hasCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl);
    }
    public function getCacheControlDirective($key)
    {
        return array_key_exists($key, $this->computedCacheControl) ? $this->computedCacheControl[$key] : null;
    }
    public function clearCookie($name, $path = null, $domain = null)
    {
        $this->setCookie(new Cookie($name, null, time() - 86400, $path, $domain));
    }
    protected function computeCacheControlValue()
    {
        if (!$this->cacheControl && !$this->has('ETag') && !$this->has('Last-Modified') && !$this->has('Expires')) {
            return 'no-cache';
        }
        if (!$this->cacheControl) {
                        return 'private, max-age=0, must-revalidate';
        }
        $header = $this->getCacheControlHeader();
        if (isset($this->cacheControl['public']) || isset($this->cacheControl['private'])) {
            return $header;
        }
                if (!isset($this->cacheControl['s-maxage'])) {
            return $header.', private';
        }
        return $header;
    }
}
}
namespace Symfony\Component\HttpKernel
{
use Symfony\Component\EventDispatcher\EventInterface;
use Symfony\Component\HttpFoundation\Response;
class ResponseListener
{
    public function filter(EventInterface $event, Response $response)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->get('request_type') || $response->headers->has('Content-Type')) {
            return $response;
        }
        $request = $event->get('request');
        $format = $request->getRequestFormat();
        if ((null !== $format) && $mimeType = $request->getMimeType($format)) {
            $response->headers->set('Content-Type', $mimeType);
        }
        return $response;
    }
}
}
namespace Symfony\Component\HttpKernel\Controller
{
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
class ControllerResolver implements ControllerResolverInterface
{
    protected $logger;
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }
    public function getController(Request $request)
    {
        if (!$controller = $request->attributes->get('_controller')) {
            if (null !== $this->logger) {
                $this->logger->err('Unable to look for the controller as the "_controller" parameter is missing');
            }
            return false;
        }
        if ($controller instanceof \Closure) {
            return $controller;
        }
        list($controller, $method) = $this->createController($controller);
        if (!method_exists($controller, $method)) {
            throw new \InvalidArgumentException(sprintf('Method "%s::%s" does not exist.', get_class($controller), $method));
        }
        if (null !== $this->logger) {
            $this->logger->info(sprintf('Using controller "%s::%s"', get_class($controller), $method));
        }
        return array($controller, $method);
    }
    public function getArguments(Request $request, $controller)
    {
        $attributes = $request->attributes->all();
        if (is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
            $repr = sprintf('%s::%s()', get_class($controller[0]), $controller[1]);
        } else {
            $r = new \ReflectionFunction($controller);
            $repr = 'Closure';
        }
        $arguments = array();
        foreach ($r->getParameters() as $param) {
            if (array_key_exists($param->getName(), $attributes)) {
                $arguments[] = $attributes[$param->getName()];
            } elseif ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument (because there is no default value or because there is a non optional argument after this one).', $repr, $param->getName()));
            }
        }
        return $arguments;
    }
    protected function createController($controller)
    {
        if (false === strpos($controller, '::')) {
            throw new \InvalidArgumentException(sprintf('Unable to find controller "%s".', $controller));
        }
        list($class, $method) = explode('::', $controller);
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }
        return array(new $class(), $method);
    }
}
}
namespace Symfony\Component\HttpKernel\Controller
{
use Symfony\Component\HttpFoundation\Request;
interface ControllerResolverInterface
{
    function getController(Request $request);
    function getArguments(Request $request, $controller);
}
}
namespace Symfony\Bundle\FrameworkBundle
{
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
class RequestListener
{
    protected $router;
    protected $logger;
    protected $container;
    public function __construct(ContainerInterface $container, RouterInterface $router, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->router = $router;
        $this->logger = $logger;
    }
    public function handle(EventInterface $event)
    {
        $request = $event->get('request');
        $master = HttpKernelInterface::MASTER_REQUEST === $event->get('request_type');
        $this->initializeSession($request, $master);
        $this->initializeRequestAttributes($request, $master);
    }
    protected function initializeSession(Request $request, $master)
    {
        if (!$master) {
            return;
        }
                if (null === $request->getSession() && $this->container->has('session')) {
            $request->setSession($this->container->get('session'));
        }
                if ($request->hasSession()) {
            $request->getSession()->start();
        }
    }
    protected function initializeRequestAttributes(Request $request, $master)
    {
        if ($master) {
                                    $this->router->setContext(array(
                'base_url'  => $request->getBaseUrl(),
                'method'    => $request->getMethod(),
                'host'      => $request->getHost(),
                'port'      => $request->getPort(),
                'is_secure' => $request->isSecure(),
            ));
        }
        if ($request->attributes->has('_controller')) {
                        return;
        }
                if (false !== $parameters = $this->router->match($request->getPathInfo())) {
            if (null !== $this->logger) {
                $this->logger->info(sprintf('Matched route "%s" (parameters: %s)', $parameters['_route'], json_encode($parameters)));
            }
            $request->attributes->add($parameters);
            if ($locale = $request->attributes->get('_locale')) {
                $request->getSession()->setLocale($locale);
            }
        } elseif (null !== $this->logger) {
            $this->logger->err(sprintf('No route found for %s', $request->getPathInfo()));
        }
    }
}
}
namespace Symfony\Bundle\FrameworkBundle\Controller
{
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
class ControllerNameParser
{
    protected $kernel;
    protected $logger;
    public function __construct(KernelInterface $kernel, LoggerInterface $logger = null)
    {
        $this->kernel = $kernel;
        $this->logger = $logger;
    }
    public function parse($controller)
    {
        if (3 != count($parts = explode(':', $controller))) {
            throw new \InvalidArgumentException(sprintf('The "%s" controller is not a valid a:b:c controller string.', $controller));
        }
        list($bundle, $controller, $action) = $parts;
        $class = null;
        $logs = array();
        foreach ($this->kernel->getBundle($bundle, false) as $b) {
            $try = $b->getNamespace().'\\Controller\\'.$controller.'Controller';
            if (!class_exists($try)) {
                if (null !== $this->logger) {
                    $logs[] = sprintf('Failed finding controller "%s:%s" from namespace "%s" (%s)', $bundle, $controller, $b->getNamespace(), $try);
                }
            } else {
                $class = $try;
                break;
            }
        }
        if (null === $class) {
            if (null !== $this->logger) {
                foreach ($logs as $log) {
                    $this->logger->info($log);
                }
            }
            throw new \InvalidArgumentException(sprintf('Unable to find controller "%s:%s".', $bundle, $controller));
        }
        return $class.'::'.$action.'Action';
    }
}
}
namespace Symfony\Bundle\FrameworkBundle\Controller
{
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver as BaseControllerResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
class ControllerResolver extends BaseControllerResolver
{
    protected $container;
    protected $parser;
    public function __construct(ContainerInterface $container, ControllerNameParser $parser, LoggerInterface $logger = null)
    {
        $this->container = $container;
        $this->parser = $parser;
        parent::__construct($logger);
    }
    protected function createController($controller)
    {
        if (false === strpos($controller, '::')) {
            $count = substr_count($controller, ':');
            if (2 == $count) {
                                $controller = $this->parser->parse($controller);
            } elseif (1 == $count) {
                                list($service, $method) = explode(':', $controller);
                return array($this->container->get($service), $method);
            } else {
                throw new \LogicException(sprintf('Unable to parse the controller name "%s".', $controller));
            }
        }
        list($class, $method) = explode('::', $controller);
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }
        $controller = new $class();
        if ($controller instanceof ContainerAwareInterface) {
            $controller->setContainer($this->container);
        }
        return array($controller, $method);
    }
}
}
namespace Symfony\Bundle\FrameworkBundle\Controller
{
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerAware;
class Controller extends ContainerAware
{
    public function createResponse($content = '', $status = 200, array $headers = array())
    {
        $response = $this->container->get('response');
        $response->setContent($content);
        $response->setStatusCode($status);
        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }
        return $response;
    }
    public function generateUrl($route, array $parameters = array(), $absolute = false)
    {
        return $this->container->get('router')->generate($route, $parameters, $absolute);
    }
    public function forward($controller, array $path = array(), array $query = array())
    {
        return $this->container->get('http_kernel')->forward($controller, $path, $query);
    }
    public function redirect($url, $status = 302)
    {
        $response = $this->container->get('response');
        $response->setRedirect($url, $status);
        return $response;
    }
    public function renderView($view, array $parameters = array())
    {
        return $this->container->get('templating')->render($view, $parameters);
    }
    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->container->get('templating')->renderResponse($view, $parameters, $response);
    }
    public function has($id)
    {
        return $this->container->has($id);
    }
    public function get($id)
    {
        return $this->container->get($id);
    }
}
}
namespace Symfony\Component\EventDispatcher
{
interface EventInterface
{
    function getSubject();
    function getName();
    function setProcessed();
    function isProcessed();
    function all();
    function has($name);
    function get($name);
    function set($name, $value);
}
}
namespace Symfony\Component\EventDispatcher
{
class Event implements EventInterface
{
    protected $processed = false;
    protected $subject;
    protected $name;
    protected $parameters;
    public function __construct($subject, $name, $parameters = array())
    {
        $this->subject = $subject;
        $this->name = $name;
        $this->parameters = $parameters;
    }
    public function getSubject()
    {
        return $this->subject;
    }
    public function getName()
    {
        return $this->name;
    }
    public function setProcessed()
    {
        $this->processed = true;
    }
    public function isProcessed()
    {
        return $this->processed;
    }
    public function all()
    {
        return $this->parameters;
    }
    public function has($name)
    {
        return array_key_exists($name, $this->parameters);
    }
    public function get($name)
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new \InvalidArgumentException(sprintf('The event "%s" has no "%s" parameter.', $this->name, $name));
        }
        return $this->parameters[$name];
    }
    public function set($name, $value)
    {
        $this->parameters[$name] = $value;
    }
}
}
namespace Symfony\Component\EventDispatcher
{
interface EventDispatcherInterface
{
    function connect($name, $listener, $priority = 0);
    function disconnect($name, $listener = null);
    function notify(EventInterface $event);
    function notifyUntil(EventInterface $event);
    function filter(EventInterface $event, $value);
    function hasListeners($name);
    function getListeners($name);
}
}
namespace Symfony\Component\EventDispatcher
{
class EventDispatcher implements EventDispatcherInterface
{
    protected $listeners = array();
    public function connect($name, $listener, $priority = 0)
    {
        if (!isset($this->listeners[$name][$priority])) {
            if (!isset($this->listeners[$name])) {
                $this->listeners[$name] = array();
            }
            $this->listeners[$name][$priority] = array();
        }
        $this->listeners[$name][$priority][] = $listener;
    }
    public function disconnect($name, $listener = null)
    {
        if (!isset($this->listeners[$name])) {
            return;
        }
        if (null === $listener) {
            unset($this->listeners[$name]);
            return;
        }
        foreach ($this->listeners[$name] as $priority => $callables) {
            foreach ($callables as $i => $callable) {
                if ($listener === $callable) {
                    unset($this->listeners[$name][$priority][$i]);
                }
            }
        }
    }
    public function notify(EventInterface $event)
    {
        foreach ($this->getListeners($event->getName()) as $listener) {
            call_user_func($listener, $event);
        }
    }
    public function notifyUntil(EventInterface $event)
    {
        foreach ($this->getListeners($event->getName()) as $listener) {
            $ret = call_user_func($listener, $event);
            if ($event->isProcessed()) {
                return $ret;
            }
        }
    }
    public function filter(EventInterface $event, $value)
    {
        foreach ($this->getListeners($event->getName()) as $listener) {
            $value = call_user_func($listener, $event, $value);
        }
        return $value;
    }
    public function hasListeners($name)
    {
        return (Boolean) count($this->getListeners($name));
    }
    public function getListeners($name)
    {
        if (!isset($this->listeners[$name])) {
            return array();
        }
        krsort($this->listeners[$name]);
        return call_user_func_array('array_merge', $this->listeners[$name]);
    }
}
}
namespace Symfony\Bundle\FrameworkBundle
{
use Symfony\Component\EventDispatcher\EventDispatcher as BaseEventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventInterface;
class EventDispatcher extends BaseEventDispatcher
{
    protected $container;
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    public function registerKernelListeners(array $listeners)
    {
        $this->listeners = $listeners;
    }
    public function notify(EventInterface $event)
    {
        foreach ($this->getListeners($event->getName()) as $listener) {
            if (is_array($listener) && is_string($listener[0])) {
                $listener[0] = $this->container->get($listener[0]);
            }
            call_user_func($listener, $event);
        }
    }
    public function notifyUntil(EventInterface $event)
    {
        foreach ($this->getListeners($event->getName()) as $listener) {
            if (is_array($listener) && is_string($listener[0])) {
                $listener[0] = $this->container->get($listener[0]);
            }
            $ret = call_user_func($listener, $event);
            if ($event->isProcessed()) {
                return $ret;
            }
        }
    }
    public function filter(EventInterface $event, $value)
    {
        foreach ($this->getListeners($event->getName()) as $listener) {
            if (is_array($listener) && is_string($listener[0])) {
                $listener[0] = $this->container->get($listener[0]);
            }
            $value = call_user_func($listener, $event, $value);
        }
        return $value;
    }
}
}
namespace Symfony\Component\Form
{
use Symfony\Component\Form\CsrfProvider\DefaultCsrfProvider;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Validator\ValidatorInterface;
class FormContext implements FormContextInterface
{
    protected $options = null;
    public static function buildDefault(ValidatorInterface $validator, $csrfSecret = null, $csrfProtection = true)
    {
        $options = array(
            'csrf_protection' => $csrfProtection,
            'validator' => $validator,
        );
        if ($csrfProtection) {
            if (empty($csrfSecret)) {
                throw new FormException('Please provide a CSRF secret when CSRF protection is enabled');
            }
            $options['csrf_provider'] = new DefaultCsrfProvider($csrfSecret);
        }
        return new static($options);
    }
    public function __construct(array $options = array())
    {
        if (isset($options['csrf_protection'])) {
            if (!$options['csrf_protection']) {
                                unset($options['csrf_provider']);
            }
            unset($options['csrf_protection']);
        }
        $options['context'] = $this;
        $this->options = $options;
    }
    public function getOptions()
    {
        return $this->options;
    }
}
}
namespace Symfony\Component\Form
{
use Symfony\Component\Form\FieldFactory\FieldFactoryInterface;
use Symfony\Component\Form\CsrfProvider\CsrfProviderInterface;
use Symfony\Component\Validator\ValidatorInterface;
interface FormContextInterface
{
    public function getOptions();
}}
