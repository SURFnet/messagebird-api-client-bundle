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

class Message
{
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

    public function __construct($recipient, $body)
    {
        if (!is_string($recipient)) {
            throw new DomainException('Message recipient must be string.');
        }

        if (!preg_match('~^\d+$~', $recipient)) {
            throw new DomainException('Message recipient must consist of digits only.');
        }

        if (!is_string($body)) {
            throw new DomainException('Message body must be string.');
        }

        $this->recipient = $recipient;
        $this->body = $body;
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
