<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$proposal = App\Models\OrderProposal::query()->find(20);
if (!$proposal) {
    echo "not found\n";
    exit;
}
$payload = is_array($proposal->payload) ? $proposal->payload : [];
$products = is_array($payload['products'] ?? null) ? $payload['products'] : [];
foreach ($products as $i => $p) {
    $idx = $p['index'] ?? ($i + 1);
    $type = $p['productTypeName'] ?? '';
    $material = $p['material'] ?? '';
    $cut = $p['services']['cutting'] ?? '(missing)';
    echo '#'.$idx.' type='.$type.' material='.$material.' cutting='.$cut.PHP_EOL;
}
