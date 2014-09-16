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

namespace Surfnet\MessageBirdApiClientBundle\Tests\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;
use Surfnet\MessageBirdApiClientBundle\DependencyInjection\Configuration;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    protected function getConfiguration()
    {
        return new Configuration;
    }

    public function testBaseUrlSchemeAndHostAreRequired()
    {
        $this->assertConfigurationIsInvalid(
            ['surfnet_message_bird_api_client' => ['base_url' => 'file:///', 'authorization' => 'AccessKey dummy', 'messaging' => ['originator' => 'SURFnet']]],
            "scheme and host are required"
        );

        $this->assertConfigurationIsInvalid(
            ['surfnet_message_bird_api_client' => ['base_url' => 'messagebird.com', 'authorization' => 'AccessKey dummy', 'messaging' => ['originator' => 'SURFnet']]],
            "scheme and host are required"
        );
    }

    public function testAuthorizationIsRequired()
    {
        $this->assertConfigurationIsInvalid(
            ['surfnet_message_bird_api_client' => ['messaging' => ['originator' => 'SURFnet']]],
            'child node "authorization"'
        );
    }

    public function testAuthorizationIsMustBeInASpecificFormat()
    {
        $this->assertConfigurationIsInvalid(
            ['surfnet_message_bird_api_client' => ['authorization' => 'AxesQuay', 'messaging' => ['originator' => 'SURFnet']]],
            "should be in the format 'AccessKey"
        );
    }

    public function testMessagingOriginatorIsRequired()
    {
        $this->assertConfigurationIsInvalid(
            ['surfnet_message_bird_api_client' => ['authorization' => 'AccessKey dummy']],
            'child node "messaging"'
        );

        $this->assertConfigurationIsInvalid(
            ['surfnet_message_bird_api_client' => ['authorization' => 'AccessKey dummy', 'messaging' => []]],
            'child node "originator"'
        );
    }
}
