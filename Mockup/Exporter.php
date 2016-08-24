<?php

namespace HeVinci\MockupBundle\Mockup;

use HeVinci\MockupBundle\Twig\AssetsExtension;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @DI\Service("hevinci.mockup.exporter")
 */
class Exporter
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
     * @param \Twig_Environment  $twig
     * @param AssetsExtension    $extension
     * @param Filesystem         $filesystem
     * @param ContainerInterface $container
     */
    public function __construct(
        \Twig_Environment $twig,
        AssetsExtension $extension,
        Filesystem  $filesystem,
        ContainerInterface $container
    ) {
        $this->twig = $twig;
        $this->assetExtension = $extension;
        $this->filesystem = $filesystem;
        $this->container = $container;
        $this->langs = ['fr', 'en'];
    }

    /**
     * Exports a list of mockups as static file(s) with asset dependencies bundled.
     *
     * @param Reference[] $mockups   Array of templates references
     *                               (symfony notation)
     * @param string      $targetDir Pathname of the directory where
     *                               templates are to be exported
     *
     * @throws \Exception
     */
    public function exportMockups(array $mockups, $targetDir)
    {
        $this->prepareEnvironment(php_sapi_name() === 'cli' || defined('STDIN'));
        $this->filesystem->mkdir($targetDir);

        foreach ($this->langs as $lang) {
            $langDir = $targetDir.DIRECTORY_SEPARATOR.$lang;
            $this->filesystem->mkdir($langDir);
            $map = $this->makeMap($mockups, $langDir);
        }

        for ($i = 0, $max = count($mockups); $i < $max; ++$i) {
            foreach ($this->langs as $lang) {
                $this->container->get('translator')->setLocale($lang);
                $this->container->get('request')->setLocale($lang);
                $langDir = $targetDir.DIRECTORY_SEPARATOR.$lang;
                $depthLevel = $mockups[$i]->getDepthLevel();
                $this->assetExtension->setDepthLevel($depthLevel);
                $content = $this->twig
                    ->loadTemplate($mockups[$i]->getTemplateReference())
                    ->render([
                        '_previous' => $i === 0 ? null : $mockups[$i - 1],
                        '_next' => $i === $max - 1 ? null : $mockups[$i + 1],
                        '_current' => $mockups[$i],
                        '_index' => $map,
                    ]);
                $this->assetExtension->setDepthLevel(0);
                $this->writeFile($mockups[$i], $content, $langDir);
            }
        }

        foreach ($this->langs as $lang) {
            $this->dumpAssets($targetDir.DIRECTORY_SEPARATOR.$lang);
        }
    }

    private function makeMap(array $mockups, $targetDir)
    {
        $content = $this->twig
            ->loadTemplate('HeVinciMockupBundle::index.html.twig')
            ->render(['mockups' => $mockups]);
        $map = Reference::fromTemplate('fake::mockup/index.html.twig');
        $this->writeFile($map, $content, $targetDir);

        return $map;
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

    private function writeFile(Reference $reference, $content, $targetDir)
    {
        $relativePath = $reference->getShortReference();
        $segments = explode('/', $relativePath);
        $name = array_pop($segments);
        $dir = $targetDir.'/'.implode('/', $segments);
        $this->filesystem->mkdir($dir);
        file_put_contents($dir.'/'.$name, $content);
    }

    private function dumpAssets($targetDir)
    {
        $assets = $this->assetExtension->getAssets();

        if (count($assets) === 0) {
            return;
        }

        $assetDir = $targetDir.'/assets';
        $webDir = $this->container->get('kernel')->getRootdir().'/../web';
        $this->filesystem->mkdir($assetDir);

        foreach ($assets as $asset) {
            if ($asset === '/') {
                continue;
            }

            $this->filesystem->copy($webDir.$asset, $assetDir.$asset);
        }

        // TODO: this should obviously be removed in favour of a config setting

        $this->filesystem->mirror(
            $webDir.'/packages/font-awesome/fonts',
            $assetDir.'/packages/font-awesome/fonts'
        );
    }
}
