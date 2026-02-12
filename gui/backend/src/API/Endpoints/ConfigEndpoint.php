<?php
declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\GUI\API\Endpoints;

use Mistralys\X4\Mods\CargoSizesMod\GUI\Services\ConfigService;
use Mistralys\X4\Mods\CargoSizesMod\GUI\Exceptions\GUIException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Configuration API endpoint.
 *
 * @package X4 Cargo Sizes Mod
 * @subpackage GUI Backend
 */
class ConfigEndpoint
{
    private ConfigService $configService;

    public function __construct()
    {
        $this->configService = new ConfigService();
    }

    /**
     * GET /api/config
     * Get current configuration.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $config = $this->configService->getConfig();

            $response->getBody()->write(json_encode($config, JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to read configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/config
     * Update configuration.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            
            if (!is_array($body)) {
                return $this->errorResponse($response, 'Invalid request body', 400);
            }

            $this->configService->updateConfig($body);

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Configuration updated successfully'], JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (GUIException $e) {
            return $this->errorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update configuration: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/config/validate
     * Validate configuration without saving.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function validate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $body = $request->getParsedBody();
            
            if (!is_array($body)) {
                return $this->errorResponse($response, 'Invalid request body', 400);
            }

            $validation = $this->configService->validateConfig($body);

            $response->getBody()->write(json_encode($validation->toArray(), JSON_THROW_ON_ERROR));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Validation failed: ' . $e->getMessage(), 500);
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
