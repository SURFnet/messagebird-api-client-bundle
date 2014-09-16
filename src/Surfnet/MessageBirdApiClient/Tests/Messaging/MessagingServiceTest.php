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
use GuzzleHttp\Subscriber\Mock;
use Surfnet\MessageBirdApiClient\Exception\ApiDomainException;
use Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException;
use Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\MessagingService;

class MessagingServiceTest extends \PHPUnit_Framework_TestCase
{
    public function invalidOriginators()
    {
        return [
            'Too long' => ['ThisIsTooLon'],
            'InvalidCharacters' => ['its.invalid'],
            'Too short' => [''],
            'Not a string #1' => [0],
            'Not a string #2' => [null],
            'Not a string #3' => [new \stdClass],
        ];
    }

    /**
     * @dataProvider invalidOriginators
     * @param mixed $originator
     */
    public function testItDetectsInvalidOriginator($originator)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\DomainException');

        new MessagingService(new Client, $originator);
    }

    public function validOriginators()
    {
        return [
            'Length is max 11' => ['LengthIsOkk'],
            'Numbers can have any length' => ['3429038382929284'],
            'Minimum length is 1' => ['a'],
        ];
    }

    /**
     * @dataProvider validOriginators
     * @param mixed $originator
     */
    public function testItAcceptsValidOriginator($originator)
    {
        $this->assertNotEmpty(new MessagingService(new Client, $originator));
    }

    public function testItSendsAMessage()
    {
        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/it-sends-a-message.txt']));

        $messaging = new MessagingService($http, 'SURFnet');
        $this->assertTrue($messaging->send(new Message('31612345678', 'This is a text message.')));
    }

    public function testHandlesUnprocessableEntities()
    {
        $this->setExpectedException(
            'Surfnet\MessageBirdApiClient\Exception\UnprocessableMessageException',
            'message could not be processed'
        );

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/it-handles-unprocessable-entities.txt']));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    public function testHandlesInvalidAccessKey()
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException', 'invalid access key');

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/it-handles-invalid-access-key.txt']));

        $messaging = new MessagingService($http, 'SURFnet');

        try {
            $messaging->send(new Message('31612345678', 'This is a text message.'));
        } catch (InvalidAccessKeyException $e) {
            $this->assertEquals(
                '(#2) Request not allowed (incorrect access_key); (#9) no (correct) recipients found; '
                . '(#10) originator is invalid',
                $e->getErrorString()
            );

            throw $e;
        }
    }

    public function other4xxStatusCodes()
    {
        return [
            [__DIR__ . '/fixtures/other-4xx-404.txt', '(#20) message not found'],
            [__DIR__ . '/fixtures/other-4xx-405.txt', ''],
        ];
    }

    /**
     * @dataProvider other4xxStatusCodes
     * @param string $fixture Filename to HTTP response fixture.
     * @param string $errorString
     */
    public function testThrowsApiDomainExceptionsOnAllOther4xxStatusCodes($fixture, $errorString)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiDomainException', 'client error');

        $http = new Client;

        $http->getEmitter()->attach(new Mock([$fixture]));

        $messaging = new MessagingService($http, 'SURFnet');

        try {
            $messaging->send(new Message('31612345678', 'This is a text message.'));
        } catch (ApiDomainException $e) {
            $this->assertEquals($errorString, $e->getErrorString());

            throw $e;
        }
    }

    public function testThrowsApiRuntimeExceptionsOn500StatusCode()
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException', 'server error');

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/500-server-error.txt']));

        $messaging = new MessagingService($http, 'SURFnet');

        try {
            $messaging->send(new Message('31612345678', 'This is a text message.'));
        } catch (ApiRuntimeException $e) {
            $this->assertEquals('', $e->getErrorString());

            throw $e;
        }
    }

    public function testThrowsApiRuntimeExceptionsOnBrokenJson()
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException', 'valid JSON');

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/201-json-error.txt']));

        $messaging = new MessagingService($http, 'SURFnet');

        try {
            $messaging->send(new Message('31612345678', 'This is a text message.'));
        } catch (ApiRuntimeException $e) {
            $this->assertEquals('', $e->getErrorString());

            throw $e;
        }
    }
}
