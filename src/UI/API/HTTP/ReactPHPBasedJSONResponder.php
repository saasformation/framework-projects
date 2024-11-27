<?php declare(strict_types=1);

namespace SaaSFormation\Framework\Projects\UI\API\HTTP;

use Psr\Http\Message\ResponseInterface;
use React\Http\Message\Response;
use SaaSFormation\Framework\Contracts\UI\HTTP\ResponderInterface;
use SaaSFormation\Framework\Contracts\UI\HTTP\StatusEnum;

class ReactPHPBasedJSONResponder implements ResponderInterface
{
    private const string VALID_FOR_CONTENT_TYPE = 'application/json';

    private const array DEFAULT_HEADERS = [
        'Content-Type' => self::VALID_FOR_CONTENT_TYPE,
        'Vary' => 'Accept',
    ];

    public function validForContentType(): string
    {
        return self::VALID_FOR_CONTENT_TYPE;
    }

    /**
     * @param StatusEnum $statusCode
     * @param array<mixed, mixed>|null $data
     * @param array<string, string>|null $headers
     * @return ResponseInterface
     */
    public function respond(StatusEnum $statusCode, ?array $data = null, ?array $headers = null): ResponseInterface
    {
        $body = "";
        if(isset($data)) {
            $serializedBody = json_encode($data);
            if($serializedBody) {
                $body = $serializedBody;
            }
        }

        return new Response($statusCode->value, $headers ? array_merge($headers, self::DEFAULT_HEADERS) : self::DEFAULT_HEADERS, $body);
    }
}