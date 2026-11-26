<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each column is checked first, so a re-run after a failure is safe
        if (! Schema::hasColumn('exam_results', 'auto_score')) {
            Schema::table('exam_results', fn (Blueprint $t) => $t->unsignedSmallInteger('auto_score')->nullable()->after('total'));
        }
        if (! Schema::hasColumn('exam_results', 'adjusted_at')) {
            Schema::table('exam_results', fn (Blueprint $t) => $t->timestamp('adjusted_at')->nullable()->after('auto_score'));
        }
        if (! Schema::hasColumn('exam_results', 'adjusted_by')) {
            // No foreign key on purpose
            Schema::table('exam_results', fn (Blueprint $t) => $t->unsignedBigInteger('adjusted_by')->nullable()->after('adjusted_at'));
        }

        // Tag the answers already saved with the id of their question, so they can be re-marked later
        $key = fn ($text) => md5(mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $text))));

        foreach (DB::table('exam_results')->whereNotNull('answers')->distinct()->pluck('exam_id') as $examId) {
            $qs = DB::table('questions')->where('exam_id', $examId)->orderBy('id')->get(['id', 'question'])->values();

            if ($qs->isEmpty()) {
                continue;
            }

            $byText = [];
            foreach ($qs as $q) {
                $byText[$key($q->question)][] = $q->id;
            }

            foreach (DB::table('exam_results')->where('exam_id', $examId)->whereNotNull('answers')->orderBy('id')->get() as $r) {
                $items = json_decode($r->answers, true);

                if (! is_array($items)) {
                    continue;
                }

                $used = [];
                $changed = false;

                foreach ($items as $i => $it) {
                    if (isset($it['qid'])) {
                        continue;
                    }

                    $id = null;

                    foreach ($byText[$key($it['question'] ?? '')] ?? [] as $cand) {
                        if (! in_array($cand, $used, true)) { $id = $cand; break; }
                    }

                    if (! $id && count($items) === $qs->count() && isset($qs[$i]) && ! in_array($qs[$i]->id, $used, true)) {
                        $id = $qs[$i]->id;
                    }

                    if ($id) {
                        $items[$i]['qid'] = $id;
                        $used[] = $id;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('exam_results')->where('id', $r->id)
                        ->update(['answers' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('exam_results', fn (Blueprint $t) => $t->dropColumn(['auto_score', 'adjusted_at', 'adjusted_by']));
    }
};