<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ImageJob;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;

    public string $activeTab = 'image-generation';
    public $imageHistory;

    public function mount()
    {
        $this->loadHistory();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function loadHistory()
    {
        $this->imageHistory = ImageJob::where('user_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.ai-image-idea-suite.index');
    }
}
