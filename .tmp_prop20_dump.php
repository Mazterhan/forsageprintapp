<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$p=App\\Models\\OrderProposal::find(20);
$prod=$p->payload['products'][0]??[];
var_export([
 'productTypeId'=>$prod['productTypeId']??null,
 'productTypeName'=>$prod['productTypeName']??null,
 'material'=>$prod['material']??null,
 'services'=>$prod['services']??null,
]);
