<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p=App\Models\OrderProposal::find(20)->payload['products'][0]??[];
echo 'servicesEnabledRaw='.var_export($p['servicesEnabledRaw']??null,true).PHP_EOL;
