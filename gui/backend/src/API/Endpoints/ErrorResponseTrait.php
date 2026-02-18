<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints;

use Psr\Http\Message\ResponseInterface;

/**
 * Trait providing standardized error response handling for API endpoints.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
trait ErrorResponseTrait
{
    /**
     * Helper to return standard JSON error response.
     *
     * @param ResponseInterface $response
     * @param string $message
     * @param int $status
     * @return ResponseInterface
     */
    private function errorResponse(ResponseInterface $response, string $message, int $status): ResponseInterface
    {
        $payload = [
            'success' => false,
            'error' => $message
        ];

        $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
