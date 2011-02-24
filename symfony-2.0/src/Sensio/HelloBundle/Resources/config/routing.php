<?php

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();
$collection->add('hello', new Route('/hello', array(
    '_controller' => 'HelloBundle:Hello:index',
)));

return $collection;
