<?php

namespace Sensio\HelloBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HelloController extends Controller
{
    public function indexAction()
    {
        //return $this->render('HelloBundle:Hello:index.html.twig', array('name' => $name));
	// 'Hello World';
	//return;
        // render a PHP template instead
        return $this->render('HelloBundle:Hello:index.html.php');
    }
}
