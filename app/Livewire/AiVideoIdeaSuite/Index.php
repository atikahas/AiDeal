<?php

namespace App\Livewire\AiVideoIdeaSuite;

use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'video-generation';

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.ai-video-idea-suite.index');
    }
}
