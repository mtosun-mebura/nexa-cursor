<div class="w-full" x-data="{ showSuggestions: @entangle('showSuggestions') }">
    <form wire:submit.prevent="search" class="space-y-6">
        <!-- Main Search Input -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-6 w-6 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" 
                   wire:model.live.debounce.300ms="query"
                   placeholder="Wat voor baan zoek je? (bijv. 'Frontend Developer', 'Marketing Manager')"
                   class="input pl-12 pr-4 py-4 text-lg"
                   autocomplete="off"
                   role="combobox"
                   aria-expanded="false"
                   aria-haspopup="listbox"
                   aria-label="Zoek naar vacatures">
            
            <!-- Search Suggestions Dropdown -->
            <div x-show="showSuggestions && $wire.suggestions.length > 0" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute z-20 mt-2 w-full card shadow-soft-lg max-h-60 rounded-xl py-2 overflow-auto focus:outline-none"
                 role="listbox">
                @foreach($suggestions as $suggestion)
                    <button type="button"
                            wire:click="selectSuggestion({{ $suggestion->toArray() }})"
                            class="w-full text-left px-4 py-3 hover:bg-card/50 transition-colors duration-200 first:rounded-t-xl last:rounded-b-xl group"
                            role="option">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="font-medium text-slate-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $suggestion->title }}
                                </div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ $suggestion->company->name ?? 'Bedrijf onbekend' }}
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($suggestion->employment_type)
                                    <span class="badge badge-primary text-xs">
                                        {{ ucfirst($suggestion->employment_type) }}
                                    </span>
                                @endif
                                @if($suggestion->location)
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        📍 {{ $suggestion->location }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
        
        <!-- Advanced Filters -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Location -->
            <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <input type="text" 
                   wire:model="location"
                   placeholder="Locatie (bijv. 'Amsterdam', 'Utrecht')"
                   class="input pl-10">
        </div>
        
        <!-- Category -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <select wire:model="category" class="input pl-10 appearance-none">
                <option value="">Alle Categorieën</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
        
        <!-- Radius -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"></path>
                </svg>
            </div>
            <select wire:model="radius" class="input pl-10 appearance-none">
                <option value="5">5 km</option>
                <option value="10">10 km</option>
                <option value="25">25 km</option>
                <option value="50">50 km</option>
                <option value="100">100 km</option>
                <option value="0">Nederland</option>
            </select>
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                <svg class="h-5 w-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>
        </div>
        
        <!-- Quick Filters -->
        <div class="flex flex-wrap gap-3">
            <span class="text-sm text-slate-600 dark:text-slate-400 font-medium">Populaire filters:</span>
            <button 
                type="button"
                wire:click="$set('employment_type', 'fulltime')"
                class="badge badge-primary hover:scale-105 transition-transform duration-200 cursor-pointer"
            >
                💼 Fulltime
            </button>
            <button 
                type="button"
                wire:click="$set('employment_type', 'parttime')"
                class="badge badge-primary hover:scale-105 transition-transform duration-200 cursor-pointer"
            >
                ⏰ Parttime
            </button>
            <button 
                type="button"
                wire:click="$set('remote_work', true)"
                class="badge badge-primary hover:scale-105 transition-transform duration-200 cursor-pointer"
            >
                🏠 Remote
            </button>
            <button 
                type="button"
                wire:click="$set('employment_type', 'freelance')"
                class="badge badge-primary hover:scale-105 transition-transform duration-200 cursor-pointer"
            >
                🚀 Freelance
            </button>
        </div>
        
        <!-- Search Button -->
        <div class="flex justify-center">
            <button type="submit" 
                    class="btn btn-primary btn-lg px-12 py-4 flex items-center space-x-3 group">
                <svg class="w-6 h-6 group-hover:scale-110 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="text-lg font-semibold">Zoek Vacatures</span>
            </button>
        </div>
    </form>
</div>