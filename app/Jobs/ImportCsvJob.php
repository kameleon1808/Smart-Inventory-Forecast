<?php

namespace App\Jobs;

use App\Domain\ImportJob;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $importJobId, public bool $dryRun = false)
    {
    }

    public function handle(ImportService $service): void
    {
        $job = ImportJob::findOrFail($this->importJobId);

        $job->update(['status' => 'running']);

        try {
            $path = Storage::path($job->file_path);
            $result = $service->import($job->type, $path, $this->dryRun, $job->creator);
            $job->update([
                'status' => $this->dryRun ? 'dry_run' : 'completed',
                'result' => $result,
            ]);
        } catch (Throwable $e) {
            $job->update([
                'status' => 'failed',
                'result' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }
}
