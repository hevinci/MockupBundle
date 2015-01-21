<?php

namespace HeVinci\MockupBundle\Mockup;

class Reference
{
    private $bundleName;
    private $relativePath;
    private $templateReference;
    private $depthLevel;
    private $shortReference;

    /**
     * @param string $bundleName    Name of the bundle
     * @param string $relativePath  Path from the "mockup" directory
     * @return Reference
     */
    static public function fromPath($bundleName, $relativePath)
    {
        return new self($bundleName, $relativePath);
    }

    /**
     * @param string $reference Template reference in Symfony notation
     * @return Reference
     */
    static public function fromTemplate($reference)
    {
        return new self(null, null, $reference);
    }

    private function __construct(
        $bundleName = null,
        $relativePath = null,
        $templateReference = null
    )
    {
        $this->bundleName = $bundleName;
        $this->relativePath = $relativePath;
        $this->templateReference = $templateReference;
        $this->initialize();
    }

    /**
     * Returns the template reference of the mockup in Symfony notation.
     *
     * @return string
     */
    public function getTemplateReference()
    {
        return $this->templateReference;
    }

    /**
     * Returns the directory depth of the mockup from the "mockup"
     * directory (e.g. "0" for a mockup at the root of that directory).
     *
     * @return int
     */
    public function getDepthLevel()
    {
        if (!$this->depthLevel) {
            $segments = explode('/', $this->relativePath);

            if (count($segments) === 1) {
                return 0; // mockup isn't is a sub-directory
            }

            $this->depthLevel = count($segments) - 1;
        }

        return $this->depthLevel;
    }

    /**
     * Returns the relative path of the mockup from the "mockup"
     * directory, minus the ".twig" extension.
     *
     * @return string
     */
    public function getShortReference()
    {
        if (!$this->shortReference) {
            $segments = explode('/', $this->relativePath);

            if ($segments[0] === 'mockup') {
                array_shift($segments);
            }

            $reference = implode('/', $segments);
            $this->shortReference = substr($reference, 0, -5); // remove ".twig" part
        }

        return $this->shortReference;
    }

    private function initialize()
    {
        if (!$this->templateReference) {
            $this->templateReference = $this->bundleName
                . '::mockup/'
                . $this->relativePath;
        }

        if (!$this->bundleName || !$this->relativePath) {
            $parts = explode('::', $this->templateReference);

            if (count($parts) === 1) {
                throw new \Exception(
                    "'{$this->templateReference}' is not a valid template reference"
                );
            }

            $this->bundleName = $parts[0];
            $segments = explode('/', $parts[1]);

            if ($segments[0] === 'mockup') {
                array_shift($segments);
            }

            $this->relativePath = implode('/', $segments);
        }
    }
} 