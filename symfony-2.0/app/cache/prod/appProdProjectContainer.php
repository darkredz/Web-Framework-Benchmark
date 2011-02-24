<?php
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;
class appProdProjectContainer extends Container
{
    public function __construct()
    {
        $this->parameters = $this->getDefaultParameters();
        $this->services =
        $this->scopedServices =
        $this->scopeStacks = array();
        $this->set('service_container', $this);
        $this->scopes = array('request' => 'container');
        $this->scopeChildren = array('request' => array());
    }
    protected function getCacheWarmerService()
    {
        return $this->services['cache_warmer'] = new \Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerAggregate(array(0 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\ClassMapCacheWarmer($this), 1 => new \Symfony\Bundle\FrameworkBundle\CacheWarmer\RouterCacheWarmer($this->get('router.real'))));
    }
    protected function getEventDispatcherService()
    {
        $this->services['event_dispatcher'] = $instance = new \Symfony\Bundle\FrameworkBundle\EventDispatcher($this);
        $instance->registerKernelListeners(array('core.request' => array(0 => array(0 => array(0 => 'request_listener', 1 => 'handle'))), 'core.response' => array(0 => array(0 => array(0 => 'response_listener', 1 => 'filter'))), 'core.exception' => array(-128 => array(0 => array(0 => 'exception_listener', 1 => 'handle')))));
        return $instance;
    }
    protected function getExceptionListenerService()
    {
        return $this->services['exception_listener'] = new \Symfony\Component\HttpKernel\Debug\ExceptionListener('Symfony\\Bundle\\FrameworkBundle\\Controller\\ExceptionController::showAction', NULL);
    }
    protected function getFilesystemService()
    {
        return $this->services['filesystem'] = new \Symfony\Bundle\FrameworkBundle\Util\Filesystem();
    }
    protected function getForm_ContextService()
    {
        return $this->services['form.context'] = new \Symfony\Component\Form\FormContext(array('validator' => $this->get('validator'), 'validation_groups' => 'Default', 'field_factory' => $this->get('form.field_factory'), 'csrf_protection' => true, 'csrf_field_name' => '_token', 'csrf_provider' => $this->get('form.csrf_provider')));
    }
    protected function getForm_CsrfProviderService()
    {
        return $this->services['form.csrf_provider'] = new \Symfony\Component\Form\CsrfProvider\SessionCsrfProvider($this->get('session'), 'secret');
    }
    protected function getForm_FieldFactoryService()
    {
        return $this->services['form.field_factory'] = new \Symfony\Component\Form\FieldFactory\FieldFactory(array(0 => new \Symfony\Component\Form\FieldFactory\ValidatorFieldFactoryGuesser($this->get('validator.mapping.class_metadata_factory'))));
    }
    protected function getHttpKernelService()
    {
        $this->services['http_kernel'] = $instance = new \Symfony\Bundle\FrameworkBundle\HttpKernel($this, new \Symfony\Bundle\FrameworkBundle\Controller\ControllerResolver($this, $this->get('controller_name_converter'), NULL));
        $instance->setEventDispatcher($this->get('event_dispatcher'));
        return $instance;
    }
    protected function getRequestService()
    {
        if (!isset($this->scopedServices['request'])) {
            throw new \RuntimeException('You cannot create a service ("request") of an inactive scope ("request").');
        }
        throw new \RuntimeException('You have requested a synthetic service ("request"). The DIC does not know how to construct this service.');
    }
    protected function getRequestListenerService()
    {
        return $this->services['request_listener'] = new \Symfony\Bundle\FrameworkBundle\RequestListener($this, $this->get('router.real'), NULL);
    }
    protected function getResponseService()
    {
        $instance = new \Symfony\Component\HttpFoundation\Response();
        $instance->setCharset('UTF-8');
        return $instance;
    }
    protected function getResponseListenerService()
    {
        return $this->services['response_listener'] = new \Symfony\Component\HttpKernel\ResponseListener();
    }
    protected function getRouter_CachedService()
    {
        return $this->services['router.cached'] = new \Symfony\Bundle\FrameworkBundle\Routing\CachedRouter('/var/www/symfony-2.0/app/cache/prod', 'appprodUrlMatcher', 'appprodUrlGenerator');
    }
    protected function getRouter_RealService()
    {
        return $this->services['router.real'] = new \Symfony\Component\Routing\Router(new \Symfony\Bundle\FrameworkBundle\Routing\LazyLoader($this, 'routing.loader.real'), '/var/www/symfony-2.0/app/config/routing.php', array('cache_dir' => '/var/www/symfony-2.0/app/cache/prod', 'debug' => false, 'generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator', 'generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper', 'generator_cache_class' => 'appprodUrlGenerator', 'matcher_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher', 'matcher_base_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher', 'matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper', 'matcher_cache_class' => 'appprodUrlMatcher'));
    }
    protected function getRouting_Loader_RealService()
    {
        $a = $this->get('kernel');
        $b = new \Symfony\Bundle\FrameworkBundle\Routing\FileLocator($a);
        $c = new \Symfony\Component\Routing\Loader\LoaderResolver();
        $c->addLoader(new \Symfony\Component\Routing\Loader\XmlFileLoader($b));
        $c->addLoader(new \Symfony\Component\Routing\Loader\YamlFileLoader($b));
        $c->addLoader(new \Symfony\Component\Routing\Loader\PhpFileLoader($b));
        return $this->services['routing.loader.real'] = new \Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader($this->get('controller_name_converter'), NULL, $c);
    }
    protected function getTranslation_Loader_PhpService()
    {
        return $this->services['translation.loader.php'] = new \Symfony\Component\Translation\Loader\PhpFileLoader();
    }
    protected function getTranslation_Loader_XliffService()
    {
        return $this->services['translation.loader.xliff'] = new \Symfony\Component\Translation\Loader\XliffFileLoader();
    }
    protected function getTranslation_Loader_YmlService()
    {
        return $this->services['translation.loader.yml'] = new \Symfony\Component\Translation\Loader\YamlFileLoader();
    }
    protected function getTranslatorService()
    {
        $this->services['translator'] = $instance = new \Symfony\Bundle\FrameworkBundle\Translation\Translator($this, $this->get('translator.selector'), array('cache_dir' => '/var/www/symfony-2.0/app/cache/prod/translations', 'debug' => false), NULL);
        $instance->setFallbackLocale('en');
        return $instance;
    }
    protected function getTranslator_RealService()
    {
        $this->services['translator.real'] = $instance = new \Symfony\Bundle\FrameworkBundle\Translation\Translator($this, $this->get('translator.selector'), array('cache_dir' => '/var/www/symfony-2.0/app/cache/prod/translations', 'debug' => false), NULL);
        $instance->setFallbackLocale('en');
        return $instance;
    }
    protected function getRouterService()
    {
        return $this->get('router.real');
    }
    protected function getControllerNameConverterService()
    {
        return $this->services['controller_name_converter'] = new \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser($this->get('kernel'), NULL);
    }
    protected function getTranslator_SelectorService()
    {
        return $this->services['translator.selector'] = new \Symfony\Component\Translation\MessageSelector();
    }
    public function getParameter($name)
    {
        $name = strtolower($name);
        if (!array_key_exists($name, $this->parameters)) {
            throw new \InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }
        return $this->parameters[$name];
    }
    public function hasParameter($name)
    {
        return array_key_exists(strtolower($name), $this->parameters);
    }
    public function setParameter($name, $value)
    {
        throw new \LogicException('Impossible to call set() on a frozen ParameterBag.');
    }
    protected function getDefaultParameters()
    {
        return array(
            'kernel.root_dir' => '/var/www/symfony-2.0/app',
            'kernel.environment' => 'prod',
            'kernel.debug' => false,
            'kernel.name' => 'app',
            'kernel.cache_dir' => '/var/www/symfony-2.0/app/cache/prod',
            'kernel.logs_dir' => '/var/www/symfony-2.0/app/logs',
            'kernel.bundles' => array(
                'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'TwigBundle' => 'Symfony\\Bundle\\TwigBundle\\TwigBundle',
                'ZendBundle' => 'Symfony\\Bundle\\ZendBundle\\ZendBundle',
                'SwiftmailerBundle' => 'Symfony\\Bundle\\SwiftmailerBundle\\SwiftmailerBundle',
                'DoctrineBundle' => 'Symfony\\Bundle\\DoctrineBundle\\DoctrineBundle',
                'HelloBundle' => 'Sensio\\HelloBundle\\HelloBundle',
            ),
            'kernel.charset' => 'UTF-8',
            'request_listener.class' => 'Symfony\\Bundle\\FrameworkBundle\\RequestListener',
            'controller_resolver.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerResolver',
            'controller_name_converter.class' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerNameParser',
            'response_listener.class' => 'Symfony\\Component\\HttpKernel\\ResponseListener',
            'exception_listener.class' => 'Symfony\\Component\\HttpKernel\\Debug\\ExceptionListener',
            'exception_listener.controller' => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ExceptionController::showAction',
            'form.field_factory.class' => 'Symfony\\Component\\Form\\FieldFactory\\FieldFactory',
            'form.field_factory.validator_guesser.class' => 'Symfony\\Component\\Form\\FieldFactory\\ValidatorFieldFactoryGuesser',
            'form.csrf_provider.class' => 'Symfony\\Component\\Form\\CsrfProvider\\SessionCsrfProvider',
            'form.context.class' => 'Symfony\\Component\\Form\\FormContext',
            'form.csrf_protection.enabled' => true,
            'form.csrf_protection.field_name' => '_token',
            'form.csrf_protection.secret' => 'secret',
            'form.validation_groups' => 'Default',
            'event_dispatcher.class' => 'Symfony\\Bundle\\FrameworkBundle\\EventDispatcher',
            'http_kernel.class' => 'Symfony\\Bundle\\FrameworkBundle\\HttpKernel',
            'response.class' => 'Symfony\\Component\\HttpFoundation\\Response',
            'error_handler.class' => 'Symfony\\Component\\HttpKernel\\Debug\\ErrorHandler',
            'error_handler.level' => NULL,
            'filesystem.class' => 'Symfony\\Bundle\\FrameworkBundle\\Util\\Filesystem',
            'cache_warmer.class' => 'Symfony\\Component\\HttpKernel\\CacheWarmer\\CacheWarmerAggregate',
            'cache_warmer.autoloader_map.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\ClassMapCacheWarmer',
            'router.class' => 'Symfony\\Component\\Routing\\Router',
            'router.cached.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\CachedRouter',
            'routing.loader.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\DelegatingLoader',
            'routing.file_locator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Routing\\FileLocator',
            'routing.resolver.class' => 'Symfony\\Component\\Routing\\Loader\\LoaderResolver',
            'routing.loader.xml.class' => 'Symfony\\Component\\Routing\\Loader\\XmlFileLoader',
            'routing.loader.yml.class' => 'Symfony\\Component\\Routing\\Loader\\YamlFileLoader',
            'routing.loader.php.class' => 'Symfony\\Component\\Routing\\Loader\\PhpFileLoader',
            'router.options.generator_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_base_class' => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
            'router.options.generator_dumper_class' => 'Symfony\\Component\\Routing\\Generator\\Dumper\\PhpGeneratorDumper',
            'router.options.matcher_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
            'router.options.matcher_base_class' => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
            'router.options.matcher_dumper_class' => 'Symfony\\Component\\Routing\\Matcher\\Dumper\\PhpMatcherDumper',
            'router.cache_warmer.class' => 'Symfony\\Bundle\\FrameworkBundle\\CacheWarmer\\RouterCacheWarmer',
            'router.options.matcher.cache_class' => 'appprodUrlMatcher',
            'router.options.generator.cache_class' => 'appprodUrlGenerator',
            'routing.resource' => '/var/www/symfony-2.0/app/config/routing.php',
            'translator.class' => 'Symfony\\Bundle\\FrameworkBundle\\Translation\\Translator',
            'translator.identity.class' => 'Symfony\\Component\\Translation\\IdentityTranslator',
            'translator.selector.class' => 'Symfony\\Component\\Translation\\MessageSelector',
            'translation.loader.php.class' => 'Symfony\\Component\\Translation\\Loader\\PhpFileLoader',
            'translation.loader.yml.class' => 'Symfony\\Component\\Translation\\Loader\\YamlFileLoader',
            'translation.loader.xliff.class' => 'Symfony\\Component\\Translation\\Loader\\XliffFileLoader',
            'translator.fallback_locale' => 'en',
            'translation.resources' => array(
                0 => array(
                    0 => 'xliff',
                    1 => '/var/www/symfony-2.0/vendor/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/translations/validators.fr.xliff',
                    2 => 'fr',
                    3 => 'validators',
                ),
            ),
            'kernel.cache_warmup' => true,
            'kernel.compiled_classes' => array(
                0 => 'Symfony\\Component\\Routing\\RouterInterface',
                1 => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcherInterface',
                2 => 'Symfony\\Component\\Routing\\Matcher\\UrlMatcher',
                3 => 'Symfony\\Component\\Routing\\Generator\\UrlGeneratorInterface',
                4 => 'Symfony\\Component\\Routing\\Generator\\UrlGenerator',
                5 => 'Symfony\\Component\\Routing\\Router',
                6 => 'Symfony\\Component\\HttpFoundation\\ParameterBag',
                7 => 'Symfony\\Component\\HttpFoundation\\HeaderBag',
                8 => 'Symfony\\Component\\HttpFoundation\\Request',
                9 => 'Symfony\\Component\\HttpFoundation\\Response',
                10 => 'Symfony\\Component\\HttpFoundation\\ResponseHeaderBag',
                11 => 'Symfony\\Component\\HttpKernel\\HttpKernel',
                12 => 'Symfony\\Component\\HttpKernel\\ResponseListener',
                13 => 'Symfony\\Component\\HttpKernel\\Controller\\ControllerResolver',
                14 => 'Symfony\\Component\\HttpKernel\\Controller\\ControllerResolverInterface',
                15 => 'Symfony\\Bundle\\FrameworkBundle\\RequestListener',
                16 => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerNameParser',
                17 => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\ControllerResolver',
                18 => 'Symfony\\Bundle\\FrameworkBundle\\Controller\\Controller',
                19 => 'Symfony\\Component\\EventDispatcher\\EventInterface',
                20 => 'Symfony\\Component\\EventDispatcher\\Event',
                21 => 'Symfony\\Component\\EventDispatcher\\EventDispatcherInterface',
                22 => 'Symfony\\Component\\EventDispatcher\\EventDispatcher',
                23 => 'Symfony\\Bundle\\FrameworkBundle\\EventDispatcher',
                24 => 'Symfony\\Component\\Form\\FormContext',
                25 => 'Symfony\\Component\\Form\\FormContextInterface',
            ),
            'kernel.autoload_classes' => array(
            ),
            'translation.loaders' => array(
                'translation.loader.php' => 'php',
                'translation.loader.yml' => 'yml',
                'translation.loader.xliff' => 'xliff',
            ),
        );
    }
}
