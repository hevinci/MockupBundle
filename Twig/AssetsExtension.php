<?php

namespace HeVinci\MockupBundle\Twig;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Bundle\TwigBundle\Extension\AssetsExtension as BaseExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @DI\Service("hevinci.mockup.asset_extension")
 */
class AssetsExtension extends BaseExtension
{
    private $container;
    private $assets = [];
    private $depthLevel = 0;

    /**
     * @DI\InjectParams({
     *     "container"  = @DI\Inject("service_container")
     * })
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct($container);
    }

    public function getAssetUrl($path, $packageName = null, $absolute = false, $version = null)
    {
        $url = $this->container->get('templating.helper.assets')->getUrl($path, $packageName, $version);

        // collect asset
        if (!in_array($url, $this->assets)) {
            $this->assets[] = $url;
        }

        // ensure the url (which starts with a slash) is relative
        // to the document
        $link = '.';

        // if the document is in a sub-directory, the url
        // must be adjusted
        for ($i = 0; $i < $this->depthLevel; ++$i) {
            $link .= '/..';
        }

        // presume assets will be in a dedicated "assets" directory

        return $link . '/assets' . $url;
    }

    public function setDepthLevel($level)
    {
        $this->depthLevel = $level;
    }

    public function getAssets()
    {
        return $this->assets;
    }
} 