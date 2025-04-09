<?php

namespace App\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RemoveExtraProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra = [];
        return $record;
    }
} 