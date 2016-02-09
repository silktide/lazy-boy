<?php

namespace Silktide\LazyBoy\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * RestControllerTrait
 */
trait RestControllerTrait
{

    private $prohibitedKeys = [
        "password" => true,
        "salt" => true
    ];

    protected function success($data = null, $code = 200)
    {
        if ($data === null) {
            $data = ["success" => true];
        } elseif (is_array($data)) {
            $data = $this->filterProhibitedKeys($data);
        }
        return new JsonResponse($data, $code);
    }

    protected function error($message, $data = [], $code = 400)
    {
        $payload = [
            "success" => false,
            "error" => $message
        ];
        if (!empty($data)) {
            $payload["context"] = $this->filterProhibitedKeys($data);
        }
        return new JsonResponse($payload, $code);
    }

    private function filterProhibitedKeys($data)
    {
        foreach ($data as $key => $value) {
            if (isset($this->prohibitedKeys[$key])) {
                unset ($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->filterProhibitedKeys($value);
            }
        }
        return $data;
    }

}