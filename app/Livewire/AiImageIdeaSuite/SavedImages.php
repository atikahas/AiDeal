<?php

namespace App\Livewire\AiImageIdeaSuite;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ImageJob;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SavedImages extends Component
{
    use WithPagination;

    public $perPage = 12;
    public $filterTool = '';
    public $filterDateFrom = '';
    public $filterDateTo = '';
    public $selectedImage = null;
    public $showImageModal = false;

    protected $queryString = [
        'filterTool' => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo' => ['except' => ''],
    ];

    public function mount()
    {
        // Initialize filters
    }

    public function updatedFilterTool()
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom()
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo()
    {
        $this->resetPage();
    }

    public function getSavedImagesProperty()
    {
        $query = ImageJob::query()
            ->where('user_id', auth()->id())
            ->where('is_saved', true)
            ->whereNotNull('generated_images')
            ->where('status', 'completed');

        if ($this->filterTool) {
            $query->where('tool', $this->filterTool);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('created_at', '>=', $this->filterDateFrom);
        }

        if ($this->filterDateTo) {
            $query->whereDate('created_at', '<=', $this->filterDateTo);
        }

        return $query->latest()->paginate($this->perPage);
    }

    public function getAvailableToolsProperty()
    {
        return ImageJob::where('user_id', auth()->id())
            ->where('is_saved', true)
            ->distinct()
            ->pluck('tool')
            ->toArray();
    }

    public function viewImage($imageJobId, $imageIndex)
    {
        $imageJob = ImageJob::where('user_id', auth()->id())
            ->where('id', $imageJobId)
            ->first();

        if (!$imageJob || !$imageJob->generated_images) {
            return;
        }

        $images = $imageJob->generated_images;
        if (isset($images[$imageIndex])) {
            $this->selectedImage = [
                'job' => $imageJob,
                'index' => $imageIndex,
                'image' => $images[$imageIndex],
                'prompt' => $imageJob->input_json['prompt'] ?? '',
                'model' => $imageJob->input_json['model'] ?? '',
                'created_at' => $imageJob->created_at,
            ];
            $this->showImageModal = true;
        }
    }

    public function closeImageModal()
    {
        $this->showImageModal = false;
        $this->selectedImage = null;
    }

    public function downloadImage($imageJobId, $imageIndex): ?StreamedResponse
    {
        $imageJob = ImageJob::where('user_id', auth()->id())
            ->where('id', $imageJobId)
            ->first();

        if (!$imageJob || !$imageJob->generated_images) {
            session()->flash('error', 'Image not found.');
            return null;
        }

        $images = $imageJob->generated_images;
        if (!isset($images[$imageIndex])) {
            session()->flash('error', 'Image not found.');
            return null;
        }

        $imageData = $images[$imageIndex];
        $path = $imageData['path'] ?? null;

        if (!$path || !Storage::disk('public')->exists($path)) {
            session()->flash('error', 'Image file not found.');
            return null;
        }

        $filename = basename($path);
        
        return Storage::disk('public')->download($path, $filename);
    }

    public function deleteImage($imageJobId, $imageIndex)
    {
        $imageJob = ImageJob::where('user_id', auth()->id())
            ->where('id', $imageJobId)
            ->first();

        if (!$imageJob || !$imageJob->generated_images) {
            session()->flash('error', 'Image not found.');
            return;
        }

        $images = $imageJob->generated_images;
        if (!isset($images[$imageIndex])) {
            session()->flash('error', 'Image not found.');
            return;
        }

        // Delete from storage
        $path = $images[$imageIndex]['path'] ?? null;
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        // Remove from array
        unset($images[$imageIndex]);
        $images = array_values($images); // Re-index array

        // Update database
        if (empty($images)) {
            $imageJob->update([
                'generated_images' => null,
                'is_saved' => false,
            ]);
        } else {
            $imageJob->update([
                'generated_images' => $images,
            ]);
        }

        // Close modal if it was open
        if ($this->showImageModal) {
            $this->closeImageModal();
        }

        session()->flash('message', 'Image deleted successfully.');
    }

    public function render()
    {
        return view('livewire.ai-image-idea-suite.saved-images', [
            'savedImages' => $this->savedImages,
            'availableTools' => $this->availableTools,
        ]);
    }
}