<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TransferPriorityMapper
{
    private const SEQUENCE_WEIGHT = 1_000_000;

    /** @var array<int, array<string, mixed>>|null */
    private static ?array $rulesCache = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rules(): array
    {
        if (self::$rulesCache !== null && ! app()->environment('testing')) {
            return self::$rulesCache;
        }

        $rows = DB::table('transfer_priority_rules')
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN from_transaction_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN to_transaction_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderBy('sequence', 'asc')
            ->orderBy('priority_score', 'desc')
            ->orderBy('id', 'asc')
            ->get([
                'id',
                'rule_key',
                'from_transaction_id',
                'to_transaction_id',
                'priority_score',
                'sequence',
            ]);

        $mapped = $rows
            ->map(function ($r) {
                $score = $this->computeScore((int) $r->priority_score, (int) $r->sequence);

                return [
                    'id' => (int) $r->id,
                    'rule_key' => (string) $r->rule_key,
                    'from_transaction_id' => $r->from_transaction_id !== null ? (int) $r->from_transaction_id : null,
                    'to_transaction_id' => $r->to_transaction_id !== null ? (int) $r->to_transaction_id : null,
                    'priority_score' => (int) $r->priority_score,
                    'sequence' => (int) $r->sequence,
                    'computed_score' => $score,
                ];
            })
            ->all();

        if (! app()->environment('testing')) {
            self::$rulesCache = $mapped;
        }

        return $mapped;
    }

    public function clearCache(): void
    {
        self::$rulesCache = null;
    }

    /**
     * @return array{rule_id: int|null, rule_key: string|null, computed_score: int}
     */
    public function match(int $fromTransactionId, int $toTransactionId): array
    {
        foreach ($this->rules() as $rule) {
            $fromOk = $rule['from_transaction_id'] === null || $rule['from_transaction_id'] === $fromTransactionId;
            $toOk = $rule['to_transaction_id'] === null || $rule['to_transaction_id'] === $toTransactionId;

            if ($fromOk && $toOk) {
                return [
                    'rule_id' => $rule['id'],
                    'rule_key' => $rule['rule_key'],
                    'computed_score' => $rule['computed_score'],
                ];
            }
        }

        return [
            'rule_id' => null,
            'rule_key' => null,
            'computed_score' => 0,
        ];
    }

    private function computeScore(int $priorityScore, int $sequence): int
    {
        $sequence = max(0, min($sequence, self::SEQUENCE_WEIGHT));

        return ($priorityScore * self::SEQUENCE_WEIGHT) + (self::SEQUENCE_WEIGHT - $sequence);
    }
}
