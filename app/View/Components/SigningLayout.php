<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class SigningLayout extends Component
{
    public function __construct(public string $title = 'Document Signing') {}

    public function render(): View
    {
        return view('layouts.signing');
    }
}
