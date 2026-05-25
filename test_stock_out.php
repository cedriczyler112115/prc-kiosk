<?php

use App\Http\Controllers\StockOutController;
use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Http\Request;

echo "--- Testing StockOutController@findUnit ---\n";

$item = Item::first();
if (! $item) {
    echo "No items found.\n";
    exit;
}
echo 'Item ID: '.$item->item_id."\n";

$unit = ItemUnit::where('item_id', $item->item_id)->where('status', 1)->first();
if (! $unit) {
    echo "No available unit found for this item.\n";
    $unit = ItemUnit::first();
    if ($unit) {
        echo "Found a unit (ID: {$unit->id}) for Item {$unit->item_id} with Status {$unit->status}.\n";
    } else {
        echo "No units found at all.\n";
        exit;
    }
} else {
    echo 'Available Unit ID: '.$unit->id.' Serial: '.$unit->serial."\n";
}

$controller = new StockOutController;

// Test 1: Unit not found
echo "\nTest 1: Unit not found\n";
$req1 = new Request(['item_id' => $item->item_id, 'query' => 'NONEXISTENT']);
$res1 = $controller->findUnit($req1);
echo 'Response: '.json_encode($res1->getData())."\n";

// Test 2: Unit found, correct item, correct status
if ($unit && $unit->item_id == $item->item_id && $unit->status == 1) {
    echo "\nTest 2: Success\n";
    $req2 = new Request(['item_id' => $item->item_id, 'query' => $unit->serial]);
    $res2 = $controller->findUnit($req2);
    echo 'Response: '.json_encode($res2->getData())."\n";
}

// Test 3: Wrong Item
$otherItem = Item::where('item_id', '!=', $unit->item_id)->first();
if ($otherItem) {
    echo "\nTest 3: Wrong Item\n";
    $req3 = new Request(['item_id' => $otherItem->item_id, 'query' => $unit->serial]);
    $res3 = $controller->findUnit($req3);
    echo 'Response: '.json_encode($res3->getData())."\n";
}

// Test 4: Wrong Status
// Create a temp unit with wrong status
$badUnit = ItemUnit::create([
    'item_id' => $item->item_id,
    'status' => 0, // Out
    'serial' => 'TEST-BAD-STATUS',
    'full_code' => 'TEST-BAD-STATUS',
    'created_by' => 1,
]);

echo "\nTest 4: Wrong Status\n";
$req4 = new Request(['item_id' => $item->item_id, 'query' => 'TEST-BAD-STATUS']);
$res4 = $controller->findUnit($req4);
echo 'Response: '.json_encode($res4->getData())."\n";

// Cleanup
$badUnit->delete();
