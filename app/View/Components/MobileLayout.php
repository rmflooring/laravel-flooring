<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class MobileLayout extends Component
{
    public function __construct(public string $title = 'Floor Manager')
    {
    }

    public function render(): View
    {
        return view('layouts.mobile');
    }
}
