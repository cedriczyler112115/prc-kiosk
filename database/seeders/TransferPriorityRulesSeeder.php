<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransferPriorityRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = config('transfer_priority_rules.rules', []);
        if (! is_array($rules) || $rules === []) {
            return;
        }

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            DB::table('transfer_priority_rules')->updateOrInsert(
                ['rule_key' => (string) ($rule['rule_key'] ?? '')],
                [
                    'from_transaction_id' => $rule['from_transaction_id'] ?? null,
                    'to_transaction_id' => $rule['to_transaction_id'] ?? null,
                    'priority_score' => (int) ($rule['priority_score'] ?? 0),
                    'sequence' => (int) ($rule['sequence'] ?? 0),
                    'is_active' => (bool) ($rule['is_active'] ?? true),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
