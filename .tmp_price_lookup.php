<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$item=App\\Models\\PriceItem::where('name','Плівка ORAGUARD 210 G')->first(['name','category','material_type','internal_code']);
var_export($item?->toArray());
