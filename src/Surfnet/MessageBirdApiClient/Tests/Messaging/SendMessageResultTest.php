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

use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;

class SendMessageResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider nonStringDeliveryStatuses
     * @param mixed $deliveryStatus
     */
    public function testItOnlyAcceptsStringDeliveryStatus($deliveryStatus)
    {
        $this->setExpectedException(
            'Surfnet\MessageBirdApiClient\Exception\InvalidArgumentException',
            'Delivery status must be string'
        );

        new SendMessageResult($deliveryStatus, []);
    }

    public function nonStringDeliveryStatuses()
    {
        return [
            'May not be integer' => [0],
            'May not be float' => [3.3],
            'May not be array' => [array()],
            'May not be object' => [new \stdClass],
        ];
    }

    /**
     * @dataProvider itDeterminesSuccessSuccessfullyDataPoints
     * @param boolean $successExpected
     * @param string $deliveryStatus
     * @param array $errors
     */
    public function testItDeterminesSuccessSuccessfully($successExpected, $deliveryStatus, array $errors)
    {
        $result = new SendMessageResult($deliveryStatus, $errors);

        $this->assertSame($successExpected, $result->isSuccess());
    }

    public function itDeterminesSuccessSuccessfullyDataPoints()
    {
        $someErrors = [
            ['code' => SendMessageResult::ERROR_INTERNAL_ERROR, 'message' => 'what happened', 'parameter' => 'yeah'],
        ];

        return [
            'Buffered, no errors' => [true, SendMessageResult::STATUS_BUFFERED, []],
            'Sent, no errors' => [true, SendMessageResult::STATUS_SENT, []],
            'Delivered, no errors' => [true, SendMessageResult::STATUS_DELIVERED, []],
            'Delivery failed, no errors' => [false, SendMessageResult::STATUS_DELIVERY_FAILED, []],
            'Not sent, no errors' => [false, SendMessageResult::STATUS_NOT_SENT, []],
            'Buffered, errors' => [false, SendMessageResult::STATUS_BUFFERED, $someErrors],
            'Sent, errors' => [false, SendMessageResult::STATUS_SENT, $someErrors],
            'Delivered, errors' => [false, SendMessageResult::STATUS_DELIVERED, $someErrors],
            'Delivery failed, errors' => [false, SendMessageResult::STATUS_DELIVERY_FAILED, $someErrors],
            'Not sent, errors' => [false, SendMessageResult::STATUS_NOT_SENT, $someErrors],
        ];
    }

    public function testItDeterminesMessageValidity()
    {
        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, [
            ['code' => SendMessageResult::ERROR_INVALID_PARAMS, 'message' => '', 'parameter' => '']
        ]);
        $this->assertTrue($result->isMessageInvalid());

        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, []);
        $this->assertFalse($result->isMessageInvalid());
    }

    public function testItDeterminesAccessKeyValidity()
    {
        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, [
            ['code' => SendMessageResult::ERROR_REQUEST_NOT_ALLOWED, 'message' => '', 'parameter' => '']
        ]);
        $this->assertTrue($result->isAccessKeyInvalid());

        $result = new SendMessageResult(SendMessageResult::STATUS_NOT_SENT, []);
        $this->assertFalse($result->isAccessKeyInvalid());
    }
}
