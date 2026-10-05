<?php

namespace TDSoft\AiTutor\Conversations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiException;

/** Internal deletion engine. HTTP callers must authorize ownership first. */
final class ConversationRetention
{
    public function erase(string $id, ?Carbon $olderThan = null, bool $dryRun = false): bool
    {
        return DB::transaction(function () use ($id, $olderThan, $dryRun) {
            $conversation = DB::table('tutor_ai_conversations')->where('id', $id)->lockForUpdate()->first();
            if (! $conversation) {
                return false;
            }
            $messages = DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)->lockForUpdate()->get();
            if ($olderThan && (Carbon::parse($conversation->updated_at)->gte($olderThan)
                || $messages->contains(fn ($m) => Carbon::parse($m->updated_at)->gte($olderThan)))) {
                return false;
            }
            $requests = $messages->flatMap(fn ($m) => [$m->request_id, $m->embedding_request_id])->all();
            $ownedRequests = DB::table('tutor_ai_requests')->where('user_id', $conversation->user_id)->whereIn('request_id', $requests);
            if ($messages->contains(fn ($m) => in_array($m->status, ['pending', 'processing'], true))
                || (clone $ownedRequests)->whereIn('status', ['pending', 'authorized', 'processing'])->exists()) {
                throw new AiException('AI_CONVERSATION_BUSY');
            }
            if ($dryRun) {
                return true;
            }
            // Clear content-bearing replay cache, not immutable usage or credit accounting.
            $ownedRequests->update(['encrypted_result' => null]);
            $ids = $messages->pluck('id')->all();
            DB::table('tutor_ai_message_sources')->whereIn('message_id', $ids)->delete();
            DB::table('tutor_ai_message_feedback')->whereIn('message_id', $ids)->delete();
            DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)->delete();
            DB::table('tutor_ai_conversations')->where('id', $id)->delete();

            return true;
        }, 3);
    }
}
