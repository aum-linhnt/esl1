<?php

namespace TDSoft\AiTutor\Conversations;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiException;

final class PurgeConversationsCommand extends Command
{
    protected $signature = 'ai-tutor:purge-conversations {--execute : Actually delete; default is dry run}';

    protected $description = 'Purge inactive conversations only; preserve billing and unresolved requests';

    public function handle(ConversationRetention $retention): int
    {
        $days = filter_var(config('ai-tutor.retention.conversation_days', 365), FILTER_VALIDATE_INT);
        if ($days === false || $days < 1 || $days > 36500) {
            $this->error('AI_RETENTION_CONFIG_INVALID');

            return self::FAILURE;
        }
        $cutoff = now()->subDays($days);
        $count = $skipped = 0;
        DB::table('tutor_ai_conversations')->where('updated_at', '<', $cutoff)->orderBy('id')
            ->chunkById(100, function ($rows) use ($retention, $cutoff, &$count, &$skipped) {
                foreach ($rows as $row) {
                    try {
                        if ($retention->erase($row->id, $cutoff, ! $this->option('execute'))) {
                            $count++;
                        }
                    } catch (AiException $error) {
                        if ($error->errorCode !== 'AI_CONVERSATION_BUSY') {
                            throw $error;
                        }
                        $skipped++;
                    }
                }
            });
        $this->line(($this->option('execute') ? 'Deleted: ' : 'Dry run eligible: ').$count.'; busy skipped: '.$skipped);

        return self::SUCCESS;
    }
}
