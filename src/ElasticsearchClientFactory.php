<?php

namespace xmarcos\Silex;

use Elasticsearch\ClientBuilder;
use Exception;

class ElasticsearchClientFactory
{
    public static function createClient(array $config = [])
    {
        $builder = ClientBuilder::create();

        if (!empty($config['hosts'])) {
            if (!is_array($config['hosts'])) {
                $config['hosts'] = [$config['hosts']];
            }

            $builder->setHosts($config['hosts']);
        }

        return $builder
        /*
         * [TD] Allow bad versions of json-ext due the imposibility to satisfy
         * Elasticsearch Client PHP version requirements which is PHP >= 5.6
         * For more info please refer to https://github.com/elastic/elasticsearch-php/issues/534
         */
        ->allowBadJSONSerialization()
        ->build();
    }
}
