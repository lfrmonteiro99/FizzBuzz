<?php

namespace App\Controller;

use App\Service\FizzBuzzStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class FizzBuzzStatisticsController extends AbstractController
{
    public function __construct(
        private readonly FizzBuzzStatisticsService $statisticsService
    ) {
    }

    public function getMostFrequentRequest(): JsonResponse
    {
        $statistics = $this->statisticsService->getMostFrequentRequest();
        return new JsonResponse($statistics->toArray());
    }
} 