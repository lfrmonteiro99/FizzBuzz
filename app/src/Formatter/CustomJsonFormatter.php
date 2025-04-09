<?php

namespace App\Formatter;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class CustomJsonFormatter extends JsonFormatter
{
    public function __construct()
    {
        parent::__construct(
            JsonFormatter::BATCH_MODE_JSON,
            true,  // include stacktraces
            10,    // max normalize depth
            true   // pretty print
        );
    }

    protected function normalize(mixed $data, int $depth = 0): mixed
    {
        if ($depth > $this->maxNormalizeDepth) {
            return 'Over ' . $this->maxNormalizeDepth . ' levels deep, aborting normalization';
        }

        if (is_array($data)) {
            $normalized = [];
            foreach ($data as $key => $value) {
                $normalized[$key] = $this->normalize($value, $depth + 1);
            }
            return $normalized;
        }

        if ($data instanceof \Throwable) {
            return [
                'class' => get_class($data),
                'message' => $data->getMessage(),
                'code' => $data->getCode(),
                'file' => $data->getFile() ?? 'unknown',
                'line' => $data->getLine() ?? 0,
                'trace' => $data->getTraceAsString()
            ];
        }

        return parent::normalize($data, $depth);
    }

    protected function toJson($data, bool $ignoreErrors = false): string
    {
        if (is_array($data)) {
            // Remove the extra field safely
            if (isset($data['extra'])) {
                unset($data['extra']);
            }
        }

        return parent::toJson($data, $ignoreErrors);
    }

    public function format(LogRecord $record): string
    {
        // Remove the extra field safely
        $record->extra = [];

        return parent::format($record);
    }
} 