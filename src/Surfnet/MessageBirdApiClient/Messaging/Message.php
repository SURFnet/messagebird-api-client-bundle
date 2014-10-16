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

class Message
{
    /**
     * The sender's telephone number (see Message#recipient for documentation) or an alphanumeric
     * string of a maximum of 11 characters.
     *
     * @var string
     */
    private $originator;

    /**
     * The telephone number of the recipient, consisting of the country code (e.g. '31' for The Netherlands),
     * the area/city code (e.g. '6' for Dutch mobile phones) and the subscriber number (e.g. '12345678').
     *
     * An example value would thus be 31612345678.
     *
     * @var string
     */
    private $recipient;

    /**
     * @var string
     */
    private $body;

    /**
     * @param string $originator
     * @param string $recipient
     * @param string $body
     * @throws DomainException Thrown when the originator or recipient is not formatted properly. See #originator,
     *                         #recipient.
     * @throws InvalidArgumentException
     */
    public function __construct($originator, $recipient, $body)
    {
        if (!is_string($originator)) {
            throw new InvalidArgumentException('Message originator is not a string.');
        }

        if (!preg_match('~^(\d+|[a-z0-9]{1,11})$~i', $originator)) {
            throw new DomainException(
                'Message originator is not a valid:'
                . ' must be a string of digits or a string consisting of 1-11 alphanumerical characters.'
            );
        }

        if (!is_string($recipient)) {
            throw new InvalidArgumentException('Message recipient must be string.');
        }

        if (!preg_match('~^\d+$~', $recipient)) {
            throw new DomainException('Message recipient must consist of digits only.');
        }

        if (!is_string($body)) {
            throw new InvalidArgumentException('Message body must be string.');
        }

        $this->originator = $originator;
        $this->recipient = $recipient;
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getOriginator()
    {
        return $this->originator;
    }

    /**
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
