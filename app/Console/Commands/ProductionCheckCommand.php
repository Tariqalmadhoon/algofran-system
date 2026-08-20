<?php

namespace App\Console\Commands;

use App\Services\ProductionReadinessService;
use Illuminate\Console\Command;

class ProductionCheckCommand extends Command
{
    protected $signature = 'system:production-check {--json : Return machine-readable JSON output}';

    protected $description = 'Verify production configuration and operational prerequisites without exposing secrets';

    public function handle(ProductionReadinessService $readiness): int
    {
        $checks = $readiness->checks();
        $passed = $readiness->passed($checks);

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'status' => $passed ? 'ready' : 'not_ready',
                'checks' => $checks,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            foreach ($checks as $check) {
                $label = $check['passed'] ? 'PASS' : ($check['required'] ? 'FAIL' : 'WARN');
                $this->line("[{$label}] {$check['name']}: {$check['message']}");
            }

            $passed
                ? $this->info('Production preflight passed.')
                : $this->error('Production preflight failed. Resolve every required check before deployment.');
        }

        return $passed ? self::SUCCESS : self::FAILURE;
    }
}
