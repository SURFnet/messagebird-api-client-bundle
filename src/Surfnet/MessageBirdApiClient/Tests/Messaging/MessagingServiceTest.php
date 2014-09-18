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
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Subscriber\Mock;
use Surfnet\MessageBirdApiClient\Exception\ApiDomainException;
use Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException;
use Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\MessagingService;

class MessagingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider invalidOriginatorTypes
     * @param mixed $originator
     */
    public function testItThrowsAnExceptionWhenGivenAnOriginatorOfAnInvalidType($originator)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\InvalidArgumentException');

        new MessagingService(new Client, $originator);
    }

    /**
     * @dataProvider invalidOriginatorFormats
     * @param mixed $originator
     */
    public function testItThrowsAnExceptionWhenGivenAnIncorrectlyFormattedOriginator($originator)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\DomainException');

        new MessagingService(new Client, $originator);
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
        $this->setExpectedException(
            'Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException',
            '(#2) Request not allowed (incorrect access_key); (#9) no (correct) recipients found; '
            . '(#10) originator is invalid'
        );

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/it-handles-invalid-access-key.txt']));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    /**
     * @dataProvider other4xxStatusCodes
     * @param string $fixture Filename to HTTP response fixture.
     * @param string $errorString
     */
    public function testThrowsApiDomainExceptionsOnAllOther4xxStatusCodes($fixture, $errorString)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiDomainException', $errorString);

        $http = new Client;

        $http->getEmitter()->attach(new Mock([$fixture]));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    /**
     * @dataProvider statusCodes5xx
     * @param string $fixture Filename to HTTP response fixture.
     * @param string $errorString
     */
    public function testThrowsApiRuntimeExceptionsOn5xxStatusCode($fixture, $errorString)
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException', $errorString);

        $http = new Client;
        $http->getEmitter()->attach(new Mock([$fixture]));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    public function testThrowsApiRuntimeExceptionsOnBrokenJson()
    {
        $this->setExpectedException('Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException', 'valid JSON');

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/201-json-error.txt']));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    public function testThrowsApiRuntimeExceptionWhenDeliveryInfoMissing()
    {
        $this->setExpectedException(
            'Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException',
            'delivery information is missing'
        );

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/it-throws-when-delivery-info-missing.txt']));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    public function testThrowsApiRuntimeExceptionWhenUnknownStatusCode()
    {
        $this->setExpectedException(
            'Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException',
            'unexpected HTTP status code'
        );

        $http = new Client;
        $http->getEmitter()->attach(new Mock([__DIR__ . '/fixtures/101-switching-protocols.txt']));

        $messaging = new MessagingService($http, 'SURFnet');
        $messaging->send(new Message('31612345678', 'This is a text message.'));
    }

    public function invalidOriginatorTypes()
    {
        return [
            'Integer instead of string' => [0],
            'NULL instead of string'    => [null],
            'object instead of string'  => [new \stdClass],
        ];
    }

    public function invalidOriginatorFormats()
    {
        return [
            'Too long'          => ['ThisIsTooLon'],
            'InvalidCharacters' => ['its.invalid'],
            'Too short'         => [''],
        ];
    }

    public function validOriginators()
    {
        return [
            'Length is max 11' => ['LengthIsOkk'],
            'Numbers can have any length' => ['3429038382929284'],
            'Minimum length is 1' => ['a'],
        ];
    }

    public function other4xxStatusCodes()
    {
        return [
            [__DIR__ . '/fixtures/other-4xx-404.txt', '(#20) message not found'],
            [__DIR__ . '/fixtures/other-4xx-405.txt', ''],
        ];
    }

    public function statusCodes5xx()
    {
        return [
            [__DIR__ . '/fixtures/500-server-error.txt', '(#9) no (correct) recipients found'],
            [__DIR__ . '/fixtures/503-service-unavailable.txt', ''],
        ];
    }
}
