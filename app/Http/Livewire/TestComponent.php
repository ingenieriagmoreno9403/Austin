<?php

namespace App\Http\Livewire;

use Livewire\Component;

class TestComponent extends Component
{
    public $message = 'Hola desde Livewire!';

    public function render()
    {
        return view('livewire.test-component');
    }
}
