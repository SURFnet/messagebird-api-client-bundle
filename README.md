# MessageBird API Client Bundle

A Symfony2 bundle to integrate MessageBird's messaging service.

## Installation

 * Add the package to your Composer file (adjust version if needed)
    ```sh
    composer require surfnet/messagebird-api-client-bundle:dev-develop
    ```

 * Add the bundle to your kernel in `app/AppKernel.php`
    ```php
    public function registerBundles()
    {
        // ...
        $bundles[] = new Surfnet\MessageBirdApiClientBundle\SurfnetMessageBirdApiClientBundle;
    }
    ```

 * Configure your MessageBird access key and the originator
    ```yml
    surfnet_messagebird_api_client:
      authorization: 'AccessKey test_xxxxxxxxx'
      messaging:
        # Max 11 alphanumeric chars or a telephone number (31612345678)
        originator: 'YourCompany'
    ```
