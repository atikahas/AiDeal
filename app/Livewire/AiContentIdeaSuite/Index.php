<?php

namespace App\Livewire\AiContentIdeaSuite;

use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'staff-magika';

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.ai-content-idea-suite.index');
    }
}
