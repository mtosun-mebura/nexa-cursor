<div class="month-view">
  <div class="grid grid-cols-7 gap-1 mb-4">
    @php
      $days = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
    @endphp
    
    @foreach($days as $day)
      <div class="text-center text-sm font-medium text-muted dark:text-muted-dark py-2">
        {{ $day }}
      </div>
    @endforeach
  </div>
  
  <div class="grid grid-cols-7 gap-1">
    @php
      $startOfMonth = $currentDate->copy()->startOfMonth();
      $startOfWeek = $startOfMonth->copy()->startOfWeek();
      $endOfMonth = $currentDate->copy()->endOfMonth();
      $endOfWeek = $endOfMonth->copy()->endOfWeek();
      $weeks = ceil($endOfWeek->diffInDays($startOfWeek) / 7);
    @endphp
    
    @for($week = 0; $week < $weeks; $week++)
      @for($day = 0; $day < 7; $day++)
        @php
          $currentDay = $startOfWeek->copy()->addWeeks($week)->addDays($day);
          $isCurrentMonth = $currentDay->month === $currentDate->month;
          $isToday = $currentDay->isToday();
          $dayAppointments = $appointments->filter(function($apt) use ($currentDay) {
            return $apt['start_time']->isSameDay($currentDay);
          });
        @endphp
        
        <div class="min-h-20 p-1 border border-border dark:border-border-dark rounded {{ $isCurrentMonth ? 'bg-card dark:bg-card-dark' : 'bg-gray-50 dark:bg-gray-800/50' }} {{ $isToday ? 'ring-2 ring-brand-500' : '' }}">
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-medium {{ $isCurrentMonth ? ($isToday ? 'text-brand-600 dark:text-brand-400' : 'text-text dark:text-text-dark') : 'text-muted dark:text-muted-dark' }}">
              {{ $currentDay->day }}
            </span>
            @if($isToday)
              <div class="w-1.5 h-1.5 rounded-full bg-brand-500"></div>
            @endif
          </div>
          
          <div class="space-y-0.5">
            @foreach($dayAppointments->take(2) as $appointment)
              <div class="appointment-item cursor-pointer p-1 rounded text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                   data-appointment-id="{{ $appointment['id'] }}">
                <div class="font-medium truncate">{{ $appointment['candidate_name'] }}</div>
                <div class="text-blue-600 dark:text-blue-400">{{ $appointment['start_time']->format('H:i') }}</div>
              </div>
            @endforeach
            
            @if($dayAppointments->count() > 2)
              <div class="text-xs text-muted dark:text-muted-dark text-center">
                +{{ $dayAppointments->count() - 2 }} meer
              </div>
            @endif
          </div>
        </div>
      @endfor
    @endfor
  </div>
</div>

