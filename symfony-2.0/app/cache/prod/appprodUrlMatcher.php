<?php

/**
 * appprodUrlMatcher
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appprodUrlMatcher extends Symfony\Component\Routing\Matcher\UrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(array $context = array(), array $defaults = array())
    {
        $this->context = $context;
        $this->defaults = $defaults;
    }

    public function match($url)
    {
        $url = $this->normalizeUrl($url);

        if ($url === '/') {
            return array_merge($this->mergeDefaults(array(), array (  '_controller' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\DefaultController::indexAction',)), array('_route' => 'homepage'));
        }

        if ($url === '/hello') {
            return array_merge($this->mergeDefaults(array(), array (  '_controller' => 'Sensio\\HelloBundle\\Controller\\HelloController::indexAction',)), array('_route' => 'hello'));
        }

        return false;
    }
}
