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
                'MessageBird: ' . $e->getMessage(),
                $this->createMessageLogContext($message)
            );
        } catch (InvalidAccessKeyException $e) {
            $this->logger->critical(
                'MessageBird: ' . $e->getMessage(),
                $this->createMessageLogContext($message)
            );
        } catch (ApiDomainException $e) {
            $this->logger->warning(
                'MessageBird: ' . $e->getMessage(),
                $this->createMessageLogContext($message)
            );
        } catch (ApiRuntimeException $e) {
            $this->logger->error(
                'MessageBird: ' . $e->getMessage(),
                $this->createMessageLogContext($message)
            );
        }

        return false;
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
