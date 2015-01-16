<?php

namespace HeVinci\MockupBundle\Manager;

use HeVinci\MockupBundle\Twig\AssetsExtension;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @DI\Service("hevinci.mockup.export_manager")
 */
class ExportManager
{
    private $twig;
    private $assetExtension;
    private $filesystem;
    private $container;

    /**
     * @DI\InjectParams({
     *     "templating" = @DI\Inject("templating"),
     *     "extension"  = @DI\Inject("hevinci.mockup.asset_extension"),
     *     "filesystem" = @DI\Inject("filesystem"),
     *     "container"  = @DI\Inject("service_container")
     * })
     *
     * @param \Twig_Environment $twig
     * @param AssetsExtension $extension
     * @param Filesystem $filesystem
     * @param ContainerInterface $container
     */
    public function __construct(
        \Twig_Environment $twig,
        AssetsExtension $extension,
        Filesystem  $filesystem,
        ContainerInterface $container
    )
    {
        $this->twig = $twig;
        $this->assetExtension = $extension;
        $this->filesystem = $filesystem;
        $this->container = $container;
    }

    /**
     * Exports a list of templates as static file(s) with asset dependencies bundled.
     *
     * @param array $templates  Array of templates references (symfony notation)
     * @param string $targetDir Pathname of the directory where templates are to be exported
     * @throws \Exception
     */
    public function exportTemplates(array $templates, $targetDir)
    {
        $this->prepareEnvironment(php_sapi_name() === 'cli' || defined('STDIN'));
        $this->filesystem->mkdir($targetDir);

        foreach ($templates as $template) {
            $this->assetExtension->setDepthLevel($this->findDepthLevel($template));
            $content = $this->twig->loadTemplate($template)->render([]);
            $this->assetExtension->setDepthLevel(0);
            $this->writeFile($template, $content, $targetDir);
        }

        $this->dumpAssets($targetDir);
    }

    private function prepareEnvironment($fakeRequest = true)
    {
        if ($fakeRequest) {
            // twig asset helpers require a request scope,
            // which is not provided in a CLI context
            $request = new Request();
            $request->setSession(new Session());
            $this->container->enterScope('request');
            $this->container->set('request', $request, 'request');
            $this->container->get('request_stack')->push($request);
        }

        // replace default asset extension to collect assets
        // (*Note*: removeExtension is deprecated)
        $this->twig->removeExtension('assets');
        $this->twig->addExtension($this->assetExtension);
    }

    private function findDepthLevel($templateName)
    {
        $parts = explode('::', $templateName);
        $path = explode('/', $parts[1]);
        array_shift($path); // remove 'mockup' segment;
        array_pop($path); // remove file name

        return count($path);
    }

    private function writeFile($templateName, $content, $targetDir)
    {
        $parts = explode('::', $templateName);
        $path = explode('/', $parts[1]);
        array_shift($path); // remove 'mockup' segment;
        $fileName = array_pop($path);
        $filename = substr($fileName, 0, -5); // remove '.twig' extension
        $directory = $targetDir . '/' . implode('/', $path);
        $this->filesystem->mkdir($directory);
        file_put_contents($directory . '/' . $filename, $content);
    }

    private function dumpAssets($targetDir)
    {
        $assets = $this->assetExtension->getAssets();

        if (count($assets) === 0) {
            return;
        }

        $assetDir = $targetDir . '/assets';
        $webDir = $this->container->get('kernel')->getRootdir() . '/../web';
        $this->filesystem->mkdir($assetDir);

        foreach ($assets as $asset) {
            if ($asset === '/') {
                continue;
            }

            $this->filesystem->copy($webDir . $asset, $assetDir . $asset);
        }

        // TODO: this should obviously be removed in favour of a config setting
        $this->filesystem->mirror(
            $webDir . '/vendor/fortawesome/font-awesome/fonts',
            $assetDir . '/vendor/fortawesome/font-awesome/fonts'
        );
    }
}
