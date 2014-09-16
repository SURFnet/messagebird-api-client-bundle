<?php

namespace Surfnet\MessageBirdApiClient\Exception;

interface ApiException
{
    /**
     * A string listing the errors returned by the MessageBird API.
     *
     * @return string
     */
    public function getErrorString();
}
