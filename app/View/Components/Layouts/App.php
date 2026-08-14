<?php

namespace App\View\Components\Layouts;

use Illuminate\View\Component;

class App extends Component
{
    public ?string $title = null;

    public function __construct(?string $title = null)
    {
        $this->title = $title;
    }

    public function render()
    {
        return view('layouts.app');
    }
}