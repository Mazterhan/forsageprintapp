<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mat='Матеріал замовника рулонний';
$q=trim(mb_strtolower($mat,'UTF-8'));
$mat2='Матеріал замовника рулонний ';
$q2=trim(mb_strtolower($mat2,'UTF-8'));
echo "norm1=[$q]\n";
echo "norm2=[$q2]\n";
