<?php

/**
 * Copyright 2014 SURFnet bv
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Surfnet\MessageBirdApiClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('surfnet_message_bird_api_client');

        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('base_url')
                    ->defaultValue('https://rest.messagebird.com')
                    ->validate()
                        ->ifTrue(function ($url) {
                            if (!is_string($url)) {
                                return true;
                            }

                            $parts = parse_url($url);

                            return $parts === false
                                || empty($parts['scheme'])
                                || empty($parts['host']);
                        })
                        ->thenInvalid("Invalid base URL '%s': scheme and host are required.")
                    ->end()
                ->end()
                ->scalarNode('authorization')
                    ->validate()
                        ->ifTrue(function ($headerValue) {
                            return strpos($headerValue, 'AccessKey ') !== 0;
                        })
                            ->thenInvalid(
                                "Authorization value '%s' should be in the format 'AccessKey your_access_key_here'."
                            )
                        ->end()
                    ->isRequired()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
