<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$v = App\Models\OrderProposal::find(20)->payload['products'][0]['services']['cutting'] ?? '';
echo 'len='.mb_strlen($v,'UTF-8').' hex=';
for($i=0;$i<strlen($v);$i++){echo strtoupper(str_pad(dechex(ord($v[$i])),2,'0',STR_PAD_LEFT));}
echo " value=[$v]\n";
