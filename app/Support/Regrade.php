<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamResult;

class Regrade
{
    public static function key(?string $text): string
    {
        return md5(mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $text))));
    }

    // Re-marks every submitted attempt of an exam against its current questions.
    // Returns how many attempts were checked and how many scores changed.
    public static function exam(Exam $exam): array
    {
        $stats     = ['results' => 0, 'changed' => 0];
        $questions = $exam->questions()->orderBy('id')->get()->values();

        if ($questions->isEmpty()) {
            return $stats;
        }

        $byId   = $questions->keyBy('id');
        $byText = [];

        foreach ($questions as $q) {
            $byText[self::key($q->question)][] = $q->id;
        }

        ExamResult::where('exam_id', $exam->id)->whereNotNull('answers')
            ->chunkById(100, function ($results) use ($questions, $byId, $byText, &$stats) {
                foreach ($results as $r) {
                    $old = is_array($r->answers) ? $r->answers : [];

                    if (! $old) {
                        continue;
                    }

                    $stats['results']++;
                    $used  = [];
                    $items = [];

                    foreach ($old as $i => $it) {
                        $q = null;

                        if (isset($it['qid'])) {
                            $id = (int) $it['qid'];

                            if ($byId->has($id) && ! in_array($id, $used, true)) {
                                $q = $byId[$id];
                            }
                        } else {
                            // Old answers without a question id: match by the question's text, then by position
                            foreach ($byText[self::key($it['question'] ?? '')] ?? [] as $cand) {
                                if (! in_array($cand, $used, true)) { $q = $byId[$cand]; break; }
                            }

                            if (! $q && count($old) === $questions->count() && isset($questions[$i]) && ! in_array($questions[$i]->id, $used, true)) {
                                $q = $questions[$i];
                            }
                        }

                        if (! $q) {
                            continue;   // this question was deleted
                        }

                        $used[]  = $q->id;
                        $options = array_values($q->options ?? []);
                        $chosen  = $it['chosen'] ?? null;

                        if ($chosen !== null && ! isset($options[(int) $chosen])) {
                            $chosen = null;
                        }

                        $items[] = [
                            'qid'      => $q->id,
                            'question' => $q->question,
                            'options'  => $options,
                            'correct'  => (int) $q->answer,
                            'chosen'   => $chosen === null ? null : (int) $chosen,
                        ];
                    }

                    if (! $items) {
                        continue;
                    }

                    $score  = count(array_filter($items, fn ($x) => $x['chosen'] !== null && $x['chosen'] === $x['correct']));
                    $total  = count($items);
                    $before = (int) $r->score;

                    $r->answers = $items;
                    $r->total   = $total;

                    if ($r->adjusted_at) {
                        // A score the teacher set by hand stays. Only the automatic value moves.
                        $r->auto_score = $score;
                        $r->score      = min((int) $r->score, $total);
                    } else {
                        $r->score = $score;
                    }

                    if ((int) $r->score !== $before) {
                        $stats['changed']++;
                    }

                    $r->save();
                }
            });

        return $stats;
    }
}