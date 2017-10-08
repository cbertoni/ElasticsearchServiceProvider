<?php

namespace xmarcos\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ElasticsearchServiceProviderSilexV1 extends ElasticsearchServiceProviderFactory implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $config = $this->config;
        $app[$this->prefix] = $app->share(
            function (Application $app) use ($config) {
                if (empty($config['aws'])) {
                    return ElasticsearchClientFactory::createClient($config);
                } else {
                    return ElasticsearchClientAWSSignatureFactory::createClient($config);
                }
            }
        );
    }
}
