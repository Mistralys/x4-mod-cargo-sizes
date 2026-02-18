<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints;

use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ShipDataService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Ship and engine data API endpoint.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ShipsEndpoint
{
    use ErrorResponseTrait;

    private ShipDataService $shipDataService;

    public function __construct(ShipDataService $shipDataService)
    {
        $this->shipDataService = $shipDataService;
    }

    /**
     * GET /api/ships/types
     * List all ship types.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getTypes(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $types = $this->shipDataService->getShipTypes();

            $response->getBody()->write(json_encode(['types' => $types], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/ships/{type}
     * List ships by type.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function getShipsByType(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $type = $args['type'] ?? '';
            $ships = $this->shipDataService->getShipsByType($type);

            $response->getBody()->write(json_encode(['ships' => $ships], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/ships/details/{shipId}
     * Get detailed ship information.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function getDetails(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $shipId = $args['shipId'] ?? '';
            $details = $this->shipDataService->getShipDetails($shipId);

            $response->getBody()->write(json_encode($details->toArray(), JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/ships/{shipId}/engines
     * Get compatible engines for a ship.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array<string, string> $args
     * @return ResponseInterface
     */
    public function getEnginesForShip(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $shipId = $args['shipId'] ?? '';
            $engines = $this->shipDataService->getEnginesForShip($shipId);

            $response->getBody()->write(json_encode(['engines' => $engines], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/engines
     * List all available engines.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAllEngines(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $engines = $this->shipDataService->getAllEngines();

            $response->getBody()->write(json_encode(['engines' => $engines], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }
}

