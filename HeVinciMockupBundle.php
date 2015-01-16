<?php

namespace HeVinci\MockupBundle;

use Claroline\KernelBundle\Bundle\AutoConfigurableInterface;
use Claroline\KernelBundle\Bundle\ConfigurationBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class HeVinciMockupBundle extends Bundle implements AutoConfigurableInterface
{
    public function supports($environment)
    {
        return true;
    }

    public function getConfiguration($environment)
    {
        $config = new ConfigurationBuilder();

        return $config->addRoutingResource(__DIR__ . '/Resources/config/routing.yml', null, 'mockup');
    }
}
