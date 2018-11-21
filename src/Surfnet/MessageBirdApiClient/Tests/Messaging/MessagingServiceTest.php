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

namespace Surfnet\MessageBirdApiClient\Tests\Messaging;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\MessagingService;

class MessagingServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testItSendsAMessage()
    {
        $handler = new MockHandler([
            new Response(
                200,
                [],
                file_get_contents(__DIR__ . '/fixtures/it-sends-a-message.json')
            )
        ]);

        $http = new Client(['handler' => $handler]);

        $messaging = new MessagingService($http);
        $result = $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));

        $this->assertTrue($result->isSuccess());
    }

    public function testHandlesUnprocessableEntities()
    {
        $handler = new MockHandler([
            new Response(422, [], file_get_contents(__DIR__ . '/fixtures/it-handles-unprocessable-entities.json'))
        ]);

        $http = new Client(['handler' => $handler]);

        $messaging = new MessagingService($http);
        $result = $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));

        $this->assertTrue($result->isMessageInvalid());
    }

    public function testHandlesInvalidAccessKey()
    {
        $handler = new MockHandler([
            new Response(401, [], file_get_contents(__DIR__ . '/fixtures/it-handles-invalid-access-key.json'))
        ]);

        $http = new Client(['handler' => $handler]);

        $messaging = new MessagingService($http);
        $result = $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));

        $this->assertTrue($result->isAccessKeyInvalid());
        $this->assertEquals(
            '(#2) Request not allowed (incorrect access_key); (#9) no (correct) recipients found; '
                . '(#10) originator is invalid',
            $result->getErrorsAsString()
        );
    }

    /**
     * @dataProvider other4xxStatusCodes
     * @param string $fixture Filename to HTTP response fixture.
     * @param string $errorString
     */
    public function testThrowsApiDomainExceptionsOnOther4xxStatusCodes($statusCode, $fixture, $errorString)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException', $errorString);

        $handler = new MockHandler([
            new Response($statusCode, [], file_get_contents($fixture))
        ]);

        $http = new Client(['handler' => $handler]);

        $messaging = new MessagingService($http);
        $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));
    }

    /**
     * @dataProvider other5xxStatusCodes
     * @param string $fixture Filename to HTTP response fixture.
     * @param string $errorString
     */
    public function testHandlesOther5xxStatusCodes($statusCode, $fixture, $errorString)
    {
        $handler = new MockHandler([
            new Response($statusCode, [], file_get_contents($fixture))
        ]);

        $http = new Client(['handler' => $handler]);

        $messaging = new MessagingService($http);
        $result = $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));

        $this->assertFalse($result->isSuccess());
        $this->assertEquals($errorString, $result->getErrorsAsString());
    }

    public function testThrowsApiRuntimeExceptionsOnBrokenJson()
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException', 'valid JSON');

        $handler = new MockHandler([
            new Response(201, [], 'This is not valid JSON.')
        ]);

        $http = new Client(['handler' => $handler]);
        $messaging = new MessagingService($http);
        $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));
    }

    public function testThrowsApiRuntimeExceptionWhenUnknownStatusCode()
    {
        $this->setExpectedException(
            'Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException',
            'Unexpected MessageBird server behaviour'
        );

        $handler = new MockHandler([
            new Response(101, [], '[]')
        ]);

        $http = new Client(['handler' => $handler]);
        $messaging = new MessagingService($http);
        $messaging->send(new Message('SURFnet', '31612345678', 'This is a text message.'));
    }

    public function other4xxStatusCodes()
    {
        return [
            [406, __DIR__ . '/fixtures/other-4xx-406.json', '(#20) message not found'],
            [415, __DIR__ . '/fixtures/other-4xx-415.json', 'Unexpected MessageBird server behaviour (HTTP 415)'],
        ];
    }

    public function other5xxStatusCodes()
    {
        return [
            [502, __DIR__ . '/fixtures/502-bad-gateway.json', '(#9) no (correct) recipients found'],
            [503, __DIR__ . '/fixtures/503-service-unavailable.json', ''],
        ];
    }
}
