<?php
class HelloController extends AppController {

    var $helpers = null; //Because the 'Html' and 'Form' helpers are enabled by default
    var $uses = array();
    var $components = array();
    var $layout = null;
    var $autoLayout = false;
    var $autoRender = false;


    function index() {
        echo "Hello World";
    }
}
