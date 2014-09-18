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

namespace Surfnet\MessageBirdApiClient\Exception;

use Exception;

class ApiRuntimeException extends RuntimeException
{
    /**
     * @param string $message
     * @param array $errors The original array of error messages as produced by MessageBird.
     * @param Exception|null $previous
     * @param int $code
     */
    public function __construct($message, array $errors, Exception $previous = null, $code = 0)
    {
        $message = sprintf('%s %s', $message, $this->createErrorString($errors));

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param array $errors
     * @return string E.g. (#9) no (correct) recipients found; (#10) originator is invalid
     */
    private function createErrorString(array $errors)
    {
        return join('; ', array_map(function ($error) {
            return sprintf('(#%d) %s', $error['code'], $error['description']);
        }, $errors));
    }
}
