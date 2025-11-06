<?php

namespace App\Livewire\UserActivity;

use App\Models\AiActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $activityType = '';
    public $model = '';
    public $status = '';
    public $startDate = '';
    public $endDate = '';
    public $perPage = 10;
    public $showDetailModal = false;
    public $selectedLog = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'activityType' => ['except' => ''],
        'model' => ['except' => ''],
        'status' => ['except' => ''],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount()
    {
        $this->endDate = now()->format('Y-m-d');
        $this->startDate = now()->subDays(30)->format('Y-m-d');
    }

    public function showDetail($logId)
    {
        $this->selectedLog = AiActivityLog::where('user_id', auth()->id())->findOrFail($logId);
        $this->showDetailModal = true;
    }

    public function resetFilters()
    {
        $this->reset(['search', 'activityType', 'model', 'status', 'startDate', 'endDate']);
        $this->resetPage();
    }

    public function getActivityTypesProperty()
    {
        $types = AiActivityLog::where('user_id', auth()->id())
            ->distinct('activity_type')
            ->pluck('activity_type')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
            
        return array_combine($types, $types);
    }

    public function getModelsProperty()
    {
        $models = AiActivityLog::where('user_id', auth()->id())
            ->whereNotNull('model')
            ->distinct('model')
            ->pluck('model')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
            
        return array_combine($models, $models);
    }

    public function getStatusesProperty()
    {
        return ['success', 'error', 'pending'];
    }

    public function render()
    {
        $query = AiActivityLog::query()
            ->where('user_id', auth()->id())
            ->when($this->search, function (Builder $query) {
                $search = "%{$this->search}%";
                return $query->where(function (Builder $query) use ($search) {
                    $query->where('prompt', 'like', $search)
                        ->orWhere('output', 'like', $search);
                });
            })
            ->when($this->activityType, fn ($q) => $q->where('activity_type', $this->activityType))
            ->when($this->model, fn ($q) => $q->where('model', $this->model))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->startDate, function ($q) {
                return $q->whereDate('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($q) {
                return $q->whereDate('created_at', '<=', $this->endDate);
            })
            ->latest('created_at');

        $logs = $query->paginate($this->perPage);

        return view('livewire.user-activity.index', [
            'logs' => $logs,
            'activityTypes' => $this->activityTypes,
            'models' => $this->models,
            'statuses' => $this->statuses,
        ]);
    }

    public function showDetails($logId)
    {
        $this->selectedLog = AiActivityLog::where('user_id', auth()->id())->findOrFail($logId);
        $this->showDetailModal = true;
    }
}
