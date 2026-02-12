<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints;

use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\PhysicsService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\PhysicsRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Physics calculation API endpoint.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class PhysicsEndpoint
{
    private PhysicsService $physicsService;

    public function __construct()
    {
        $this->physicsService = new PhysicsService();
    }

    /**
     * POST /api/calculate/physics
     * Single ship physics calculation.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function calculate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            
            if (!is_array($body)) {
                return $this->errorResponse($response, 'Invalid request body', 400);
            }

            $physicsRequest = PhysicsRequest::fromArray($body);
            $result = $this->physicsService->calculatePhysics($physicsRequest);

            $response->getBody()->write(json_encode($result->toArray(), JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/calculate/batch
     * Batch calculation for multiple configurations.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function calculateBatch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            
            if (!is_array($body) || !isset($body['requests']) || !is_array($body['requests'])) {
                return $this->errorResponse($response, 'Invalid request body, expected {requests: [...]}', 400);
            }

            $results = [];
            foreach ($body['requests'] as $requestData) {
                $physicsRequest = PhysicsRequest::fromArray($requestData);
                $result = $this->physicsService->calculatePhysics($physicsRequest);
                $results[] = $result->toArray();
            }

            $response->getBody()->write(json_encode(['results' => $results], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Internal server error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create an error response.
     *
     * @param ResponseInterface $response
     * @param string $message
     * @param int $statusCode
     * @return ResponseInterface
     */
    private function errorResponse(ResponseInterface $response, string $message, int $statusCode): ResponseInterface
    {
        $response->getBody()->write(json_encode(['error' => $message], JSON_THROW_ON_ERROR));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
