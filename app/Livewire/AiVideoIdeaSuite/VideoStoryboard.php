<?php

namespace App\Livewire\AiVideoIdeaSuite;

use Livewire\Component;

class VideoStoryboard extends Component
{
    public $videoIdea = '';
    public $sceneCount = 5;
    public $language = 'English';
    public $storyboardOutput = [];

    public array $sceneCounts = [3, 5, 7, 10];
    public array $languages = ['English', 'Malay', 'Chinese', 'Tamil'];

    protected function rules(): array
    {
        return [
            'videoIdea' => 'required|string|min:10|max:1000',
            'sceneCount' => 'required|integer|in:3,5,7,10',
            'language' => 'required|string|in:English,Malay,Chinese,Tamil',
        ];
    }

    public function generateStoryboard()
    {
        $this->validate();

        // TODO: Implement storyboard generation logic

        session()->flash('message', __('Storyboard generation feature coming soon!'));
    }

    public function resetForm()
    {
        $this->reset([
            'videoIdea',
            'sceneCount',
            'language',
            'storyboardOutput',
        ]);

        $this->sceneCount = 5;
        $this->language = 'English';
    }

    public function render()
    {
        return view('livewire.ai-video-idea-suite.video-storyboard');
    }
}
