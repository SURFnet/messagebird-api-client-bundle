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
use GuzzleHttp\Exception\ClientException as GuzzleClientException;
use GuzzleHttp\Exception\ServerException as GuzzleServerException;
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
    private $http;

    /**
     * The sender's telephone number (see Message#recipient for documentation) or an alphanumeric
     * string of a maximum of 11 characters.
     *
     * @var string
     */
    private $originator;

    /**
     * @param ClientInterface $http
     * @param string $originator See MessageService#originator.
     * @throws DomainException Thrown when the originator is incorrectly formatted.
     * @throws InvalidArgumentException
     */
    public function __construct(ClientInterface $http, $originator)
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

        $this->http = $http;
        $this->originator = $originator;
    }

    /**
     * @param Message $message
     * @return boolean Whether the message was successfully delivered, sent or buffered. When FALSE,
     *                 delivery failed.
     *
     * @throws UnprocessableMessageException
     * @throws InvalidAccessKeyException
     * @throws ApiDomainException
     */
    public function send(Message $message)
    {
        try {
            $response = $this->http->post('/messages', [
                'json' => [
                    'originator' => $this->originator,
                    'recipients' => $message->getRecipient(),
                    'body'       => $message->getBody(),
                ]
            ]);
        } catch (GuzzleClientException $e) {
            $response = $e->getResponse();
        } catch (GuzzleServerException $e) {
            $response = $e->getResponse();
        }

        try {
            $document = $response->json();
        } catch (\RuntimeException $e) {
            throw new ApiRuntimeException('The server did not return valid JSON.', [], $e);
        }

        $statusCode = (int) $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            $this->handleHttpErrors($document, $statusCode);
        }

        if (!isset($document['recipients']['totalDeliveryFailedCount'])) {
            throw new ApiRuntimeException(
                'The server returned an invalid response; delivery information is missing.',
                []
            );
        }

        return $document['recipients']['totalDeliveryFailedCount'] === 0;
    }

    /**
     * Throws meaningful exceptions when possible (4xx-5xx status codes), ApiRuntimeExceptions when it cannot.
     *
     * @param mixed $document
     * @param int $statusCode
     * @throws InvalidAccessKeyException Thrown when the server doesn't accept the configured API access key.
     * @throws UnprocessableMessageException Thrown when the server doesn't accept the format of the sent message.
     * @throws ApiDomainException
     */
    private function handleHttpErrors($document, $statusCode)
    {
        if (isset($document['errors']) && is_array($document['errors'])) {
            $errors = $document['errors'];
        } else {
            $errors = [];
        }

        if ($statusCode === 401) {
            throw new InvalidAccessKeyException(
                'An invalid access key was used to access the MessageBird API.',
                $errors
            );
        } elseif ($statusCode === 422) {
            throw new UnprocessableMessageException(
                'The message could not be processed by MessageBird.',
                $errors
            );
        } elseif ($statusCode >= 400 && $statusCode < 500) {
            throw new ApiDomainException(sprintf('A client error occurred (%d).', $statusCode), $errors);
        } elseif ($statusCode >= 500 && $statusCode < 600) {
            throw new ApiRuntimeException('A server error occurred.', $errors);
        } else {
            throw new ApiRuntimeException(
                sprintf('The server responded with an unexpected HTTP status code (%s).', $statusCode),
                $errors
            );
        }
    }
}
