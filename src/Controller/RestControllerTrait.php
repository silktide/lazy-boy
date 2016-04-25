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
        } else {
            $data = $this->normaliseData($data);
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
            $payload["context"] = $this->normaliseData($data);
        }
        return new JsonResponse($payload, $code);
    }

    protected function normaliseData($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (isset($this->prohibitedKeys[$key])) {
                    unset ($data[$key]);
                } else {
                    $data[$key] = $this->normaliseData($value);
                }
            }
        }
        return $data;
    }

    protected function addProhibitedKeys(array $keys)
    {
        foreach ($keys as $key) {
            if (is_string($key)) {
                $this->prohibitedKeys[$key] = true;
            }
        }
    }

    protected function removeProhibitedKeys(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->prohibitedKeys[$key]);
        }
    }

}