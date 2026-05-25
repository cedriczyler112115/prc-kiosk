<?php

namespace Tests\Unit;

use App\Models\Transaction;
use App\Services\TransferPriorityMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferPriorityMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_most_specific_rule_first()
    {
        $txA = Transaction::create(['name' => 'A', 'code' => 'A', 'workflow_order' => 1, 'is_active' => true]);
        $txB = Transaction::create(['name' => 'B', 'code' => 'B', 'workflow_order' => 2, 'is_active' => true]);
        $txC = Transaction::create(['name' => 'C', 'code' => 'C', 'workflow_order' => 3, 'is_active' => true]);

        DB::table('transfer_priority_rules')->insert([
            [
                'rule_key' => 'WILDCARD',
                'from_transaction_id' => null,
                'to_transaction_id' => null,
                'priority_score' => 1,
                'sequence' => 100,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'TO_B',
                'from_transaction_id' => null,
                'to_transaction_id' => $txB->id,
                'priority_score' => 2,
                'sequence' => 50,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_key' => 'A_TO_B',
                'from_transaction_id' => $txA->id,
                'to_transaction_id' => $txB->id,
                'priority_score' => 3,
                'sequence' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $mapper = app(TransferPriorityMapper::class);
        $mapper->clearCache();

        $exact = $mapper->match($txA->id, $txB->id);
        $this->assertSame('A_TO_B', $exact['rule_key']);

        $toOnly = $mapper->match($txC->id, $txB->id);
        $this->assertSame('TO_B', $toOnly['rule_key']);

        $wild = $mapper->match($txA->id, $txC->id);
        $this->assertSame('WILDCARD', $wild['rule_key']);
    }
}
