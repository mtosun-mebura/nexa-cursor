<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Vacancy;
use App\Models\Category;
use App\Models\Company;

class JobSearchBar extends Component
{
    public $query = '';
    public $location = '';
    public $category = '';
    public $radius = 25;
    
    public $categories = [];
    public $suggestions = [];
    public $showSuggestions = false;
    
    public function mount()
    {
        $this->categories = Category::orderBy('name')->get();
    }
    
    public function updatedQuery()
    {
        if (strlen($this->query) >= 2) {
            $this->suggestions = Vacancy::where('title', 'like', '%' . $this->query . '%')
                ->orWhere('description', 'like', '%' . $this->query . '%')
                ->limit(5)
                ->get();
            $this->showSuggestions = true;
        } else {
            $this->suggestions = [];
            $this->showSuggestions = false;
        }
    }
    
    public function selectSuggestion($suggestion)
    {
        $this->query = $suggestion['title'];
        $this->showSuggestions = false;
    }
    
    public function search()
    {
        $params = [];
        
        if ($this->query) {
            $params['q'] = $this->query;
        }
        
        if ($this->location) {
            $params['location'] = $this->location;
        }
        
        if ($this->category) {
            $params['category'] = $this->category;
        }
        
        if ($this->radius) {
            $params['radius'] = $this->radius;
        }
        
        return redirect()->route('jobs.index', $params);
    }
    
    public function render()
    {
        return view('livewire.job-search-bar');
    }
}
