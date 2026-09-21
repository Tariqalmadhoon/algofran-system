<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class MobileDistributionController extends Controller
{
    public function __invoke(): View
    {
        return view('mobile-distribution.index');
    }
}
