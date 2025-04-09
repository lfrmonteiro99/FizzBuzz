<?php

namespace App\Interface;

interface SequenceLoggerInterface extends BaseLoggerInterface
{
    /**
     * Log the start of sequence generation.
     *
     * @param array $context The context data
     */
    public function logSequenceStart(array $context = []): void;

    /**
     * Log the completion of sequence generation.
     *
     * @param array $context The context data
     */
    public function logSequenceComplete(array $context = []): void;
} 