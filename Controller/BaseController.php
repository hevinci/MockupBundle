<?php

namespace HeVinci\MockupBundle\Controller;

use JMS\DiExtraBundle\Annotation as DI;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Templating\EngineInterface;

class BaseController
{
    private $templating;
    private $kernel;

    /**
     * @DI\InjectParams({
     *     "templating" = @DI\Inject("templating"),
     *     "kernel"     = @DI\Inject("kernel")
     * })
     *
     * @param EngineInterface $templating
     */
    public function __construct(EngineInterface $templating, KernelInterface $kernel)
    {
        $this->templating = $templating;
        $this->kernel = $kernel;
    }

    /**
     * @EXT\Route("/")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderMockupAction(Request $request)
    {
        $template = 'HeVinciMockupBundle::no-mockup.html.twig';

        if ($mockupName = $request->query->get('path')) {
            $template = $this->findMockup($mockupName);
        }

        return new Response($this->templating->render($template));
    }

    private function findMockup($path)
    {
        // try to find the bundle
        $parts = explode('/', $path);
        $candidate = strtolower($parts[0] . 'bundle');
        $bundle = null;

        foreach ($this->kernel->getBundles() as $registeredBundle) {
            if ($candidate === strtolower($registeredBundle->getName())) {
                $bundle = $registeredBundle;
                break;
            }
        }

        if (!$bundle) {
            throw new \Exception(
                "Unable to find the corresponding bundle for '{$parts[0]}'"
                . ' (mock path must start with the bundle reference in short, lower case notation)'
            );
        }

        // build mockup path and template reference
        array_shift($parts);
        $mockupRelativePath = 'mockup/' . implode('/', $parts) . '.twig';
        $mockupPath = $bundle->getPath() . '/Resources/views/' . $mockupRelativePath;

        if (file_exists($mockupPath)) {
            return $bundle->getName() . '::' . $mockupRelativePath;
        }

        throw new \Exception(
            "Unable to find mockup '{$path}' (looked for file '{$mockupPath}'"
        );
    }
} 