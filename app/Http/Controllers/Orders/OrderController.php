<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return view('orders.index');
    }

    public function calculation(Request $request)
    {
        return view('orders.calculation');
    }

    public function saved(Request $request)
    {
        return view('orders.saved');
    }
}
