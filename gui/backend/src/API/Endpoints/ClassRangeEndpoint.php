<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints;

use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ClassRangeService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ShipDataService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\DTOs\ClassRangeRequest;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class-range calculation API endpoint.
 * Computes min/max/median physics ranges for all ships of a given type.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ClassRangeEndpoint
{
    private ClassRangeService $classRangeService;

    public function __construct()
    {
        $shipDataService = new ShipDataService();
        $this->classRangeService = new ClassRangeService($shipDataService);
    }

    /**
     * POST /api/calculate/class-range
     * Class-wide physics range calculation.
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

            $classRangeRequest = ClassRangeRequest::fromArray($body);
            $result = $this->classRangeService->calculateClassRange($classRangeRequest);

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
