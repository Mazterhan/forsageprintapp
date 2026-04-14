<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$codes=['SERV-008-MZ','SERV-010','SERV-007-MZ','SERV-009'];
$rows=App\Models\PriceItem::query()->whereIn('internal_code',$codes)->get(['internal_code','name','is_active','visible','service_price'])->keyBy('internal_code');
foreach($codes as $c){
  $r=$rows->get($c);
  if(!$r){echo "$c: MISSING\n"; continue;}
  echo $c.': active='.$r->is_active.' visible='.$r->visible.' price='.$r->service_price.' name='.$r->name."\n";
}
