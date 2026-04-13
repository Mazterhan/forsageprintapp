<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$proposal = App\Models\OrderProposal::query()->find(20);
$payload = is_array($proposal?->payload) ? $proposal->payload : [];
$p = $payload['products'][0] ?? [];
print_r(array_keys($p['services'] ?? []));
echo "\ncutting=" . (($p['services']['cutting'] ?? '(missing)')) . "\n";
