<?php

namespace Surfnet\MessageBirdApiClientBundle\Service;

use Psr\Log\LoggerInterface;
use Surfnet\MessageBirdApiClient\Exception\ApiDomainException;
use Surfnet\MessageBirdApiClient\Exception\ApiException;
use Surfnet\MessageBirdApiClient\Exception\ApiRuntimeException;
use Surfnet\MessageBirdApiClient\Exception\InvalidAccessKeyException;
use Surfnet\MessageBirdApiClient\Exception\UnprocessableMessageException;
use Surfnet\MessageBirdApiClient\Messaging\Message;
use Surfnet\MessageBirdApiClient\Messaging\MessagingService as LibraryMessagingService;

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

    public function send(Message $message)
    {
        try {
            return $this->messagingService->send($message);
        } catch (UnprocessableMessageException $e) {
            $this->logger->notice(
                'MessageBird: a text message could not be sent, probably due to wrong user input.',
                $this->createMessageLogContext($message, $e)
            );
        } catch (InvalidAccessKeyException $e) {
            $this->logger->critical(
                'MessageBird: a text message could not be sent, probably due to wrong user input.',
                $this->createMessageLogContext($message, $e)
            );
        } catch (ApiDomainException $e) {
            $this->logger->warning(
                'MessageBird: a text message could not be sent due to some client error.',
                $this->createMessageLogContext($message, $e)
            );
        } catch (ApiRuntimeException $e) {
            $this->logger->error(
                'MessageBird: an unexpected error occurred while sending a text message.',
                $this->createMessageLogContext($message, $e)
            );
        }

        return false;
    }

    /**
     * @param Message $message
     * @param ApiException $e
     * @return array
     */
    private function createMessageLogContext(Message $message, ApiException $e)
    {
        return [
            'message' => ['recipient' => $message->getRecipient(), 'body' => $message->getBody()],
            'error'   => $e->getErrorString(),
        ];
    }
}
