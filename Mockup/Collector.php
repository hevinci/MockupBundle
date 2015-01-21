<?php

namespace HeVinci\MockupBundle\Mockup;

use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @DI\Service("hevinci.mockup.collector")
 */
class Collector
{
    private $kernel;

    /**
     * @DI\InjectParams({
     *     "kernel" = @DI\Inject("kernel")
     * })
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Given a target (bundle name or single template), returns
     * an array of template references (mockups) related to this
     * target. In case of a bundle target, both bundle-level and
     * app-level mockup directories are searched. If a map.yml
     * file is present, templates referenced in that file are
     * returned. Otherwise, all the templates found are returned.
     *
     * @param string $target Either a bundle name or a template reference
     * @return array
     * @throws \Exception If no mockup directory is found
     */
    public function collect($target)
    {
        $bundles = $this->kernel->getBundles();
        $mockups = [];

        // look for bundle target
        if (array_key_exists($target, $bundles)) {
            $appDir = $this->kernel->getRootDir();
            $appMockupDir = $appDir . '/Resources/' . $bundles[$target]->getName() . '/views/mockup';
            $bundleMockupDir = $bundles[$target]->getPath() . '/Resources/views/mockup';
            $lookupDirs = [];

            if (file_exists($appMockupDir)) {
                $lookupDirs[] = $appMockupDir;
            }

            if (file_exists($bundleMockupDir)) {
                $lookupDirs[] = $bundleMockupDir;
            }

            if (count($lookupDirs) === 0) {
                throw new \Exception(
                    "Bundle '{$bundles[$target]->getName()}' has no mockup directory"
                );
            }

            $mockups = $this->getTemplates($lookupDirs, $target);
        } else {
            // try with simple template
            // TODO: directory option should be added
            $mockups[] = Reference::fromTemplate($target);
        }

        return $mockups;
    }

    private function getTemplates(array $lookupDirs, $bundleName)
    {
        $mockups = [];

        if (false !== $map = $this->getMap($lookupDirs, $bundleName)) {
            $mockups = $map;
        } else {
            $finder = (new Finder())
                ->files()
                ->name('*.twig')
                ->in($lookupDirs);
            $relativePaths = [];

            foreach ($finder as $file) {
                $relativePath = $file->getRelativePathname();

                if (!in_array($relativePath, $relativePaths)) {
                    $mockups[] = Reference::fromPath($bundleName, $relativePath);
                    $relativePaths[] = $relativePath;
                }
            }
        }

        return $mockups;
    }

    private function getMap(array $lookupDirs, $bundleName)
    {
        $hasMap = false;
        $map = [];

        foreach ($lookupDirs as $dir) {
            if (file_exists($file = "{$dir}/map.yml")) {
                $mockups = Yaml::parse(file_get_contents($file));
                $map = array_merge($map, $mockups);
                $hasMap = true;
            }
        }

        if (!$hasMap) {
            return false;
        }

        // remove duplicates in case we have 2 maps (app level and bundle level)
        $map = array_unique($map);

        array_walk($map, function (&$item) use ($bundleName) {
            $item = Reference::fromPath($bundleName, $item);
        });

        return $map;
    }
} 