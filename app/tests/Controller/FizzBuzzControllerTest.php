<?php

namespace App\Tests\Controller;

use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzServiceInterface;
use App\Service\FizzBuzzStatisticsService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzControllerTest extends WebTestCase
{
    private $fizzBuzzService;
    private $validator;
    private $statisticsService;
    private $controller;

    protected function setUp(): void
    {
        $this->fizzBuzzService = $this->createMock(FizzBuzzServiceInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->statisticsService = $this->createMock(FizzBuzzStatisticsService::class);
        $this->controller = new \App\Controller\FizzBuzzController();
    }

    public function testGetFizzBuzzWithValidParameters(): void
    {
        $request = new Request([
            'int1' => '3',
            'int2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->withAnyParameters()
            ->willReturn(new ConstraintViolationList());

        $this->statisticsService->expects($this->once())
            ->method('trackRequest')
            ->withAnyParameters();

        $expectedResult = ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz'];
        $this->fizzBuzzService->expects($this->once())
            ->method('generate')
            ->withAnyParameters()
            ->willReturn($expectedResult);

        $response = $this->controller->getFizzBuzz(
            $request,
            $this->fizzBuzzService,
            $this->validator,
            $this->statisticsService
        );

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(json_encode($expectedResult), $response->getContent());
    }

    public function testGetFizzBuzzWithInvalidParameters(): void
    {
        $request = new Request([
            'int1' => '0',
            'int2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);

        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'This value should be positive.',
                null,
                [],
                null,
                'int1',
                '0'
            )
        ]);

        $this->validator->expects($this->once())
            ->method('validate')
            ->withAnyParameters()
            ->willReturn($violations);

        $this->statisticsService->expects($this->never())
            ->method('trackRequest');

        $response = $this->controller->getFizzBuzz(
            $request,
            $this->fizzBuzzService,
            $this->validator,
            $this->statisticsService
        );

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals(
            json_encode(['errors' => ['int1: This value should be positive.']]),
            $response->getContent()
        );
    }
} 