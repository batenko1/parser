<?php

namespace App\Console\Commands;

use App\Jobs\UpdateArticleStatJob;
use App\Models\ScheduledArticleUpdate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessScheduledArticleUpdates extends Command
{
    protected $signature   = 'articles:process-scheduled {--batch=500}';
    protected $description = 'Dispatch UpdateArticleStatJob for due scheduled updates';

    public function handle(): int
    {
        $batch = (int) $this->option('batch');
        $processedTotal = 0;

        while (true) {
            $ids = DB::transaction(function () use ($batch) {
                $jobs = ScheduledArticleUpdate::query()
                    ->where('processed', false)
                    ->where('run_at', '<=', now())
                    ->orderBy('run_at')
                    ->limit($batch)
                    ->lock('FOR UPDATE SKIP LOCKED')
                    ->get(['id', 'article_id']);

                if ($jobs->isEmpty()) {
                    return [];
                }

                ScheduledArticleUpdate::query()
                    ->whereIn('id', $jobs->pluck('id'))
                    ->update(['processed' => true, 'updated_at' => now()]);

                return $jobs->map(fn($j) => ['id' => $j->id, 'article_id' => $j->article_id])->all();
            });

            if (empty($ids)) {
                break;
            }

            foreach ($ids as $row) {
                UpdateArticleStatJob::dispatch($row['article_id']);
            }

            $processedTotal += count($ids);

            if (count($ids) < $batch) {
                break;
            }
        }

        $this->info("Scheduled updates dispatched: {$processedTotal}");
        return self::SUCCESS;
    }
}
