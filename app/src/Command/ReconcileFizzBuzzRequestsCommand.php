<?php

namespace App\Command;

use App\Service\ReconciliationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reconcile-fizzbuzz-requests',
    description: 'Reconcile pending FizzBuzz requests'
)]
class ReconcileFizzBuzzRequestsCommand extends Command
{
    public function __construct(
        private readonly ReconciliationService $reconciliationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            $this->reconciliationService->reconcilePendingRequests();
            $io->success('Reconciliation completed successfully');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Reconciliation failed: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
} 