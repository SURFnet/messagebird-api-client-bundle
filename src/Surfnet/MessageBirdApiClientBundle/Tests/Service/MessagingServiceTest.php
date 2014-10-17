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

namespace Surfnet\MessageBirdApiClientBundle\Tests\Service;

use Mockery as m;
use Mockery\Matcher\MatcherAbstract;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;
use Surfnet\MessageBirdApiClientBundle\Service\MessagingService;

class MessagingServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testItReturnsSuccessfully()
    {
        $message = new Message('SURFnet', '31612345678', 'body');
        $result = new SendMessageResult(SendMessageResult::STATUS_DELIVERED, []);

        $libraryService = m::mock('Surfnet\MessageBirdApiClient\Messaging\MessagingService')
            ->shouldReceive('send')->once()->with($message)->andReturn($result)
            ->getMock();
        $logger = m::mock('Psr\Log\LoggerInterface');

        $service = new MessagingService($libraryService, $logger);

        $this->assertSame($result, $service->send($message));
    }

    public function testItLogsInvalidMessages()
    {
        $message = new Message('SURFnet', '31612345678', 'body');
        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, [['code' => SendMessageResult::ERROR_INVALID_PARAMS, 'description' => '']]);

        $libraryService = m::mock('Surfnet\MessageBirdApiClient\Messaging\MessagingService')
            ->shouldReceive('send')->once()->with($message)->andReturn($result)
            ->getMock();
        $logger = m::mock('Psr\Log\LoggerInterface')
            ->shouldReceive('notice')->once($this->expectStringContains('Invalid message sent to MessageBird'))
            ->getMock();

        $service = new MessagingService($libraryService, $logger);
        $service->send($message);
    }

    public function testItLogsInvalidAccessKeys()
    {
        $message = new Message('SURFnet', '31612345678', 'body');
        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, [['code' => SendMessageResult::ERROR_REQUEST_NOT_ALLOWED, 'description' => '']]);

        $libraryService = m::mock('Surfnet\MessageBirdApiClient\Messaging\MessagingService')
            ->shouldReceive('send')->once()->with($message)->andReturn($result)
            ->getMock();
        $logger = m::mock('Psr\Log\LoggerInterface')
            ->shouldReceive('critical')->once($this->expectStringContains('Invalid access key used for MessageBird'))
            ->getMock();

        $service = new MessagingService($libraryService, $logger);
        $service->send($message);
    }

    /**
     * @param string
     * @return MatcherAbstract
     */
    private function expectStringContains($contains)
    {
        return m::on(function ($string) use ($contains) {
            return strpos($string, $contains) !== false;
        });
    }
}
