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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;
use Surfnet\MessageBirdApiClientBundle\Service\MessagingService;

class MessagingServiceTest extends TestCase
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

        // Create a mock for Surfnet\MessageBirdApiClient\Messaging\MessagingService
        $libraryService = $this->getMockBuilder('Surfnet\MessageBirdApiClient\Messaging\MessagingService')
            ->disableOriginalConstructor()
            ->getMock();

        // Set the expectation on the send method to be called once with $message and return $result
        $libraryService->expects($this->once())
            ->method('send')
            ->with($message)
            ->willReturn($result);

        // Create a mock for Psr\Log\LoggerInterface
        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->getMock();

        // Set the expectation on the critical method to be called once with a message containing 'Invalid access key used for MessageBird'
        $logger->expects($this->once())
            ->method('notice')
            ->with($this->stringContains('Invalid message sent to MessageBird'));

        // Create the MessagingService instance with the mocks
        $service = new MessagingService($libraryService, $logger);

        // Invoke the send method that should trigger logging
        $service->send($message);
    }

    public function testItLogsInvalidAccessKeys()
    {
        $message = new Message('SURFnet', '31612345678', 'body');
        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, [['code' => SendMessageResult::ERROR_REQUEST_NOT_ALLOWED, 'description' => '']]);

        // Create a mock for Surfnet\MessageBirdApiClient\Messaging\MessagingService
        $libraryService = $this->getMockBuilder('Surfnet\MessageBirdApiClient\Messaging\MessagingService')
            ->disableOriginalConstructor()
            ->getMock();

        // Set the expectation on the send method to be called once with $message and return $result
        $libraryService->expects($this->once())
            ->method('send')
            ->with($message)
            ->willReturn($result);

        // Create a mock for Psr\Log\LoggerInterface
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        // Set the expectation on the critical method to be called once with a message containing 'Invalid access key used for MessageBird'
        $logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Invalid access key used for MessageBird'));

        // Create the MessagingService instance with the mocks
        $service = new MessagingService($libraryService, $logger);

        // Invoke the send method that should trigger logging
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

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }


}
