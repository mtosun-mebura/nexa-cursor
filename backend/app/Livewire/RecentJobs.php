<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Vacancy;

class RecentJobs extends Component
{
    public $limit = 6;
    
    public function render()
    {
        $jobs = Vacancy::with(['company', 'category'])
            ->where('is_active', true)
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->limit($this->limit)
            ->get();
            
        return view('livewire.recent-jobs', [
            'jobs' => $jobs
        ]);
    }
}
