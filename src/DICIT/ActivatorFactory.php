<?php

namespace DICIT;

use DICIT\Activators\DefaultActivator;
use DICIT\Activators\StaticInvocationActivator;
use DICIT\Activators\InstanceInvocationActivator;
use DICIT\Activators\LazyActivator;
use DICIT\Activators\RemoteActivator;
use DICIT\Activators\RemoteAdapterFactory;

class ActivatorFactory
{

    private $activators = array();

    public function __construct($deferActivations = false) {
        $this->addActivator('default', new DefaultActivator(), $deferActivations);
        $this->addActivator('builder-static', new StaticInvocationActivator(), $deferActivations);
        $this->addActivator('builder', new InstanceInvocationActivator(), $deferActivations);
        $this->addActivator('remote', new RemoteActivator(new RemoteAdapterFactory()), $deferActivations);
    }

    /**
     * @param string $key
     * @param boolean $deferredActivations
     */
    private function addActivator($key, Activator $activator, $deferredActivations)
    {
        if ($deferredActivations) {
            $activator = new LazyActivator($activator);
        }

        $this->activators[$key] = $activator;
    }

    /**
     *
     * @param string $serviceName
     * @param array $configuration
     * @throws UnbuildableServiceException
     * @return \DICIT\Activator
     */
    public function getActivator($serviceName, array $configuration)
    {
        if (array_key_exists('builder', $configuration)) {
            $builderType = $this->getBuilderType($configuration['builder']);

            if ('static' == $builderType) {
                return $this->activators['builder-static'];
            }
            elseif ('instance' == $builderType) {
                return $this->activators['builder'];
            }
        }
        elseif (array_key_exists('class', $configuration)) {
            if (array_key_exists('remote', $configuration)) {
                return $this->activators['remote'];
            }

            return $this->activators['default'];
        }

        throw new UnbuildableServiceException(sprintf("Unbuildable service : '%s', no suitable activator found.",
            $serviceName));
    }

    private function getBuilderType($builderKey)
    {
        if (false !== strpos($builderKey, '::')) {
            return 'static';
        }
        elseif (false !== strpos($builderKey, '->')) {
            return 'instance';
        }

        return 'null';
    }
}
