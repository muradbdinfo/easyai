<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class MarketingController extends Controller
{
    public function home()
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        return view('marketing.home', compact('plans'));
    }

    public function pricing()
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        return view('marketing.pricing', compact('plans'));
    }

    public function contact()
    {
        return view('marketing.contact');
    }
}