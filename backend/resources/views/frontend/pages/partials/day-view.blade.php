<div class="day-view">
  <!-- Time slots and calendar grid -->
  <div class="grid grid-cols-2 gap-0 border border-border dark:border-border-dark rounded-lg overflow-hidden">
    <!-- Time column -->
    <div class="bg-gray-50 dark:bg-gray-800 border-r border-border dark:border-border-dark">
      <div class="h-12 border-b border-border dark:border-border-dark flex items-center justify-center text-sm font-medium text-muted dark:text-muted-dark">
        Tijd
      </div>
      @php
        $timeSlots = [
          'Hele dag',
          '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', 
          '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'
        ];
      @endphp
      @foreach($timeSlots as $time)
        <div class="h-16 border-b border-border dark:border-border-dark flex items-center justify-center text-sm text-muted dark:text-muted-dark">
          {{ $time }}
        </div>
      @endforeach
    </div>
    
    <!-- Single day column -->
    <div class="bg-white dark:bg-gray-900">
      <!-- Day header -->
      <div class="h-12 border-b border-border dark:border-border-dark flex items-center justify-center text-sm font-medium bg-brand-600 text-white">
        <div class="text-center">
          <div class="font-medium">{{ $currentDate->format('l') }}</div>
          <div class="text-xs text-brand-100">{{ $currentDate->format('j F Y') }}</div>
        </div>
      </div>
      
      <!-- Time slots for this day -->
      @foreach($timeSlots as $timeSlot)
        @php
          $slotAppointments = $appointments->filter(function($apt) use ($timeSlot) {
            if ($timeSlot === 'Hele dag') {
              return $apt['start_time']->format('H:i') === '00:00';
            }
            return $apt['start_time']->format('H:i') === $timeSlot;
          });
        @endphp
        
        <div class="h-16 border-b border-border dark:border-border-dark relative bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800">
          @foreach($slotAppointments as $appointment)
            <div class="appointment-item absolute inset-1 cursor-pointer p-3 rounded text-sm bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors shadow-sm"
                 data-appointment-id="{{ $appointment['id'] }}">
              <div class="font-medium">{{ $appointment['candidate_name'] }}</div>
              <div class="text-blue-600 dark:text-blue-400 text-xs mt-1">{{ $appointment['start_time']->format('H:i') }} - {{ $appointment['end_time']->format('H:i') }}</div>
              <div class="text-xs mt-1">{{ $appointment['location'] }}</div>
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
</div>

