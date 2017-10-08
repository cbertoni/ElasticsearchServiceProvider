<?php

namespace xmarcos\Silex;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ElasticsearchServiceProviderSilexV2 extends ElasticsearchServiceProviderFactory implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $config = $this->config;
        $app[$this->prefix] = function ($app) use ($config) {
            if (empty($config['aws'])) {
                return ElasticsearchClientFactory::createClient($config);
            } else {
                return ElasticsearchClientAWSSignatureFactory::createClient($config);
            }
        };
    }
}
