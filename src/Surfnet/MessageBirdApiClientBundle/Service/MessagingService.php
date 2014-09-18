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

namespace Surfnet\MessageBirdApiClientBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\MessageBirdApiClient\Exception\ApiDomainException;
use Surfnet\MessageBirdApiClient\Exception\ApiException;
use Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException;
use Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException;
use Surfnet\MessageBirdApiClient\Exception\UnprocessableMessageException;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\MessagingService as LibraryMessagingService;
use Surfnet\MessageBirdApiClient\Messaging\SendMessageResult;

class MessagingService
{
    /**
     * @var LibraryMessagingService
     */
    private $messagingService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LibraryMessagingService $messagingService, LoggerInterface $logger)
    {
        $this->messagingService = $messagingService;
        $this->logger = $logger;
    }

    /**
     * @param Message $message
     * @return SendMessageResult
     */
    public function send(Message $message)
    {
        try {
            $result = $this->messagingService->send($message);
        } catch (ApiRuntimeException $e) {
            $this->logger->error(
                sprintf('Unexpected communication failure with MessageBird; %s', $e->getMessage()),
                $this->createMessageLogContext($message)
            );

            throw $e;
        }

        if ($result->isMessageInvalid()) {
            $this->logger->notice(
                sprintf('Invalid message sent to MessageBird (%s)', $result->getErrorsAsString()),
                $this->createMessageLogContext($message)
            );
        }

        if ($result->isAccessKeyInvalid()) {
            $this->logger->critical(
                sprintf('Invalid access key used for MessageBird (%s)', $result->getErrorsAsString()),
                $this->createMessageLogContext($message)
            );
        }

        return $result;
    }

    /**
     * @param Message $message
     * @return array
     */
    private function createMessageLogContext(Message $message)
    {
        return [
            'message' => ['recipient' => $message->getRecipient(), 'body' => $message->getBody()],
        ];
    }
}
