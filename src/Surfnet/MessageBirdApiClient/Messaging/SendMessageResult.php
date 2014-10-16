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

namespace Surfnet\MessageBirdApiClient\Messaging;

use Surfnet\MessageBirdApiClient\Exception\DomainException;
use Surfnet\MessageBirdApiClient\Exception\InvalidArgumentException;

class SendMessageResult
{
    const ERROR_REQUEST_NOT_ALLOWED = 2;
    const ERROR_MISSING_PARAMS = 9;
    const ERROR_INVALID_PARAMS = 10;
    const ERROR_NOT_FOUND = 20;
    const ERROR_NOT_ENOUGH_BALANCE = 25;
    const ERROR_API_NOT_FOUND = 98;
    const ERROR_INTERNAL_ERROR = 99;

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_BUFFERED = 'buffered';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_DELIVERY_FAILED = 'delivery_failed';
    const STATUS_NOT_SENT = 'not_sent';
    const STATUS_UNKNOWN = 'unknown';

    /**
     * @var string
     */
    private $deliveryStatus;

    /**
     * @var array[]
     */
    private $errors;

    /**
     * @param string $deliveryStatus
     * @param array[] $errors
     * @throws InvalidArgumentException
     */
    public function __construct($deliveryStatus, array $errors)
    {
        if (!is_string($deliveryStatus)) {
            throw new InvalidArgumentException('Delivery status must be string.');
        }

        if (!$this->isKnownDeliveryStatus($deliveryStatus)) {
            $deliveryStatus = self::STATUS_UNKNOWN;
        }

        $this->deliveryStatus = $deliveryStatus;
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return count($this->errors) === 0
            && in_array(
                $this->deliveryStatus,
                [self::STATUS_BUFFERED, self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_SCHEDULED]
            );
    }

    /**
     * @return bool
     */
    public function isMessageInvalid()
    {
        return $this->hasErrorWithCode(self::ERROR_INVALID_PARAMS);
    }

    /**
     * @return bool
     */
    public function isAccessKeyInvalid()
    {
        return $this->hasErrorWithCode(self::ERROR_REQUEST_NOT_ALLOWED);
    }

    /**
     * @return array[] Returns the errors returned by the API as an array of arrays with
     *                 keys int code, string description, string parameter.
     */
    public function getRawErrors()
    {
        return $this->errors;
    }

    /**
     * @return string E.g. '(#9) no (correct) recipients found; (#10) originator is invalid'
     */
    public function getErrorsAsString()
    {
        return join('; ', array_map(function ($error) {
            return sprintf('(#%d) %s', $error['code'], $error['description']);
        }, $this->errors));
    }

    /**
     * @param int $code
     * @return bool
     */
    private function hasErrorWithCode($code)
    {
        foreach ($this->errors as $error) {
            if ((int) $error['code'] === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $deliveryStatus
     * @return bool
     */
    private function isKnownDeliveryStatus($deliveryStatus)
    {
        return in_array(
            $deliveryStatus,
            [
                self::STATUS_SCHEDULED,
                self::STATUS_BUFFERED,
                self::STATUS_SENT,
                self::STATUS_DELIVERED,
                self::STATUS_DELIVERY_FAILED,
                self::STATUS_NOT_SENT
            ]
        );
    }
}
