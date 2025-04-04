<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for health check endpoints
 */
class HealthCheckController extends AbstractController
{
    /**
     * Simple health check endpoint for load balancers
     * 
     * @Route("/health", name="app_health_check", methods={"GET"})
     */
    public function healthCheck(): Response
    {
        return new Response('ok');
    }
    
    /**
     * Detailed health check endpoint for monitoring
     * 
     * @Route("/health/detailed", name="app_health_check_detailed", methods={"GET"})
     */
    public function detailedHealthCheck(Connection $connection): Response
    {
        $status = [
            'status' => 'ok',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'components' => [
                'database' => 'ok',
                'app' => 'ok'
            ]
        ];
        
        // Check database connection
        try {
            $connection->executeQuery('SELECT 1');
        } catch (\Exception $e) {
            $status['components']['database'] = 'error';
            $status['status'] = 'error';
        }
        
        // Return appropriate status code
        $statusCode = $status['status'] === 'ok' ? 200 : 503;
        
        return $this->json($status, $statusCode);
    }
} 