# MessageBird API Client Bundle
[![Build Status](https://travis-ci.org/SURFnet/messagebird-api-client-bundle.svg)](https://travis-ci.org/SURFnet/messagebird-api-client-bundle) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/SURFnet/messagebird-api-client-bundle/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/SURFnet/messagebird-api-client-bundle/?branch=develop) [![SensioLabs Insight](https://insight.sensiolabs.com/projects/12ce0c8c-26c1-4d08-bca3-563f04519936/mini.png)](https://insight.sensiolabs.com/projects/12ce0c8c-26c1-4d08-bca3-563f04519936)

A Symfony2 bundle to integrate MessageBird's messaging service.

## Installation

 * Add the package to your Composer file
    ```sh
    composer require surfnet/messagebird-api-client-bundle:~1.0
    ```

 * Add the bundle to your kernel in `app/AppKernel.php`
    ```php
    public function registerBundles()
    {
        // ...
        $bundles[] = new Surfnet\MessageBirdApiClientBundle\SurfnetMessageBirdApiClientBundle;
    }
    ```

 * Configure your MessageBird access key
    ```yml
    surfnet_message_bird_api_client:
      authorization: 'AccessKey test_xxxxxxxxx'
    ```

## Usage

### Sending a message

```php
public function fooAction()
{
    $message = new \Surfnet\MessageBirdApiClient\Messaging\Message(
        'SURFnet',
        '31612345678',
        'Your one-time SMS security token: 9832'
    );
    
    /** @var \Surfnet\MessageBirdApiClientBundle\Service\MessagingService $messaging */
    $messaging = $this->get('surfnet_message_bird_api_client.messaging');
    $result = $messaging->send($message);
    
    if ($result->isSuccess()) {
        // Message has been buffered, sent or delivered.
    }
}
```

## Release strategy
Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management fro more information on the release strategy used in Stepup projects.
