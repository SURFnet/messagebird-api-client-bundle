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

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Surfnet\MessageBirdApiClient\Exception\ApiDomainException;
use Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException;
use Surfnet\MessageBirdApiClient\Exception\DomainException;
use Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException;
use Surfnet\MessageBirdApiClient\Exception\InvalidArgumentException;
use Surfnet\MessageBirdApiClient\Exception\UnprocessableMessageException;

class MessagingService
{
    /**
     * A Guzzle client, configured with MessageBird's API base url and a valid Authorization header.
     *
     * @var ClientInterface
     */
    private $guzzleClient;

    /**
     * @param ClientInterface $guzzleClient
     */
    public function __construct(ClientInterface $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param Message $message
     * @return SendMessageResult
     *
     * @throws ApiRuntimeException
     * @throws TransferException Thrown by Guzzle during communication failure or unexpected server behaviour.
     */
    public function send(Message $message)
    {
        $response = $this->guzzleClient->post('/messages', [
            'json' => [
                'originator' => $message->getOriginator(),
                'recipients' => $message->getRecipient(),
                'body'       => $message->getBody(),
            ],
            'exceptions' => false,
        ]);

        try {
            $document = $response->json();
        } catch (\RuntimeException $e) {
            throw new ApiRuntimeException('The MessageBird server did not return valid JSON.', [], $e);
        }

        if (isset($document['errors'])) {
            $errors = $document['errors'];
        } else {
            $errors = [];
        }

        $statusCode = (int) $response->getStatusCode();

        if (!in_array($statusCode, [200, 201, 204, 401, 404, 405, 422]) && !($statusCode >= 500 && $statusCode < 600)) {
            throw new ApiRuntimeException(sprintf('Unexpected MessageBird server behaviour (HTTP %d)', $statusCode), $errors);
        }

        if (!isset($document['recipients']['items'][0]['status'])) {
            $deliveryStatus = SendMessageResult::STATUS_NOT_SENT;
        } else {
            $deliveryStatus = $document['recipients']['items'][0]['status'];
        }

        return new SendMessageResult($deliveryStatus, $errors);
    }
}
