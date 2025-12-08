@extends('admin.layouts.app')

@section('title', 'Agenda - NEXA Skillmatching')

@section('content')
<style>
  /* Calendar container styles */
  .agenda-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }

  /* Header styles */
  .agenda-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .agenda-title-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .agenda-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    min-width: 250px;
  }

  .agenda-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  /* View buttons container - fixed width to prevent shifting */
  .view-buttons-container {
    display: flex;
    gap: 0.5rem;
    min-width: 200px;
    justify-content: center;
  }

  /* Calendar views */
  .calendar-view {
    display: none;
  }

  .calendar-view.active {
    display: block;
  }

  /* Month view */
  .month-view {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .month-day-header {
    background: #f9fafb;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
  }

  .month-day-cell {
    background: white;
    min-height: 10rem;
    padding: 0.5rem;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .month-day-cell:hover {
    background: #f9fafb;
  }

  .month-day-cell.has-events {
    cursor: pointer;
  }

  .month-day-number {
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    margin-bottom: 0.5rem;
  }

  .month-day-events {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .month-event {
    background: #3b82f6;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Week view */
  .week-view {
    display: grid;
    grid-template-columns: 80px repeat(7, 1fr);
    gap: 1px;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .week-time-column {
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
  }

  .week-time-slot {
    height: 4rem;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: flex-start;
  }

  .week-day-column {
    background: white;
  }

  .week-day-header {
    background: #f9fafb;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
  }

  .week-hour-slot {
    height: 4rem;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
    padding: 0.25rem;
  }

  .week-event {
    position: absolute;
    background: #3b82f6;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    width: calc(100% - 0.5rem);
    left: 0.25rem;
  }

  /* Day view */
  .day-view {
    display: grid;
    grid-template-columns: 80px 1fr;
    gap: 1px;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .day-time-column {
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
  }

  .day-time-slot {
    height: 4rem;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: flex-start;
  }

  .day-content-column {
    background: white;
  }

  .day-header {
    background: #f9fafb;
    padding: 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
  }

  .day-hour-slot {
    height: 4rem;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
    padding: 0.25rem;
  }

  .day-event {
    position: absolute;
    background: #3b82f6;
    color: white;
    padding: 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 500;
    width: calc(100% - 0.5rem);
    left: 0.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .day-event-time {
    font-size: 0.75rem;
    opacity: 0.9;
  }

  .day-event-title {
    font-weight: 600;
  }

  /* Modal styles */
  .kt-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
  }

  .kt-modal.open {
    display: flex;
  }

  .kt-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9998;
  }

  .kt-modal-dialog {
    position: relative;
    z-index: 9999;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-width: 56rem;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    display: none;
  }

  .kt-modal.open .kt-modal-dialog {
    display: block;
  }

  .kt-modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .kt-modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
  }

  .kt-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    transition: all 0.2s;
  }

  .kt-modal-close:hover {
    background: #f3f4f6;
    color: #111827;
  }

  .kt-modal-body {
    padding: 1.5rem;
  }

  .modal-event-item {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
  }

  .modal-event-item:last-child {
    border-bottom: none;
  }

  .modal-event-avatar {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: #3b82f6;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    flex-shrink: 0;
  }

  .modal-event-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
  }

  .modal-event-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .modal-event-name {
    font-weight: 600;
    font-size: 1rem;
    color: #111827;
  }

  .modal-event-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6b7280;
  }

  .modal-event-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .modal-event-detail i {
    width: 1rem;
    color: #9ca3af;
  }

  .modal-event-notes {
    margin-top: 0.5rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #374151;
    display: flex;
    gap: 0.5rem;
  }

  .modal-event-notes-icon {
    flex-shrink: 0;
    color: #9ca3af;
  }

  .modal-event-notes-text {
    flex: 1;
    word-break: break-words;
  }

  .modal-event-link {
    margin-top: 0.5rem;
  }

  .modal-event-link a {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .modal-event-link a:hover {
    text-decoration: underline;
  }

  /* Other month day styles */
  .month-day-cell.other-month {
    background: #f9fafb;
    color: #9ca3af;
  }

  .month-day-cell.today {
    background: #eff6ff;
  }

  .month-day-cell.today .month-day-number {
    color: #3b82f6;
    font-weight: 700;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .agenda-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 1rem;
    }

    .agenda-actions {
      width: 100%;
      justify-content: space-between;
    }

    .view-buttons-container {
      min-width: auto;
      flex: 1;
    }

    .month-day-cell {
      min-height: 5rem;
    }

    .week-view,
    .day-view {
      grid-template-columns: 60px repeat(7, 1fr);
    }

    .week-view {
      grid-template-columns: 60px repeat(7, 1fr);
    }

    .day-view {
      grid-template-columns: 60px 1fr;
    }
  }
</style>

<div class="kt-container-fixed">
    <div class="agenda-container">
        <!-- Header -->
        <div class="agenda-header">
            <div class="agenda-title-container">
                <div class="agenda-title" id="agenda-title">Agenda</div>
            </div>
            <div class="agenda-actions">
                <div class="view-buttons-container">
                    <button class="kt-btn kt-btn-outline view-btn" data-view="month" id="btn-month">
                        Maand
                    </button>
                    <button class="kt-btn kt-btn-outline view-btn" data-view="week" id="btn-week">
                        Week
                    </button>
                    <button class="kt-btn kt-btn-outline view-btn" data-view="day" id="btn-day">
                        Dag
                    </button>
                </div>
                <div class="flex gap-2">
                    <button class="kt-btn kt-btn-outline" id="btn-prev">
                        <i class="ki-filled ki-arrow-left"></i>
                    </button>
                    <button class="kt-btn kt-btn-outline" id="btn-today">
                        Vandaag
                    </button>
                    <button class="kt-btn kt-btn-outline" id="btn-next">
                        <i class="ki-filled ki-arrow-right"></i>
                    </button>
                </div>
                <a href="{{ route('admin.interviews.create') }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-plus me-2"></i>
                    Nieuwe interview
                </a>
            </div>
        </div>

        <!-- Calendar Views -->
        <div id="month-view" class="calendar-view active">
            <div class="month-view" id="month-calendar"></div>
        </div>

        <div id="week-view" class="calendar-view">
            <div class="week-view" id="week-calendar"></div>
        </div>

        <div id="day-view" class="calendar-view">
            <div class="day-view" id="day-calendar"></div>
        </div>
    </div>
</div>

<!-- Day Events Modal -->
<div class="kt-modal" id="day-modal">
    <div class="kt-modal-backdrop" onclick="closeDayModal()"></div>
    <div class="kt-modal-dialog">
        <div class="kt-modal-header">
            <h3 class="kt-modal-title" id="modal-day-title">Afspraken</h3>
            <button class="kt-modal-close" onclick="closeDayModal()" type="button">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="kt-modal-body">
            <div id="modal-day-events"></div>
        </div>
    </div>
</div>

<script>
// Global variables
let currentDate = new Date();
let currentView = 'month';
let events = [];

// Month names in Dutch
const monthNames = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
const dayNames = ['Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag', 'Zondag'];
const dayNamesShort = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];

// Interview base URL
const interviewBaseUrl = '/admin/interviews/';

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadEvents();
    setupEventListeners();
    renderCurrentView();
});

// Setup event listeners
function setupEventListeners() {
    // View buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchView(this.dataset.view);
        });
    });

    // Navigation buttons
    document.getElementById('btn-prev').addEventListener('click', function() {
        navigate(-1);
    });

    document.getElementById('btn-next').addEventListener('click', function() {
        navigate(1);
    });

    document.getElementById('btn-today').addEventListener('click', function() {
        goToToday();
    });

    // Modal backdrop click
    document.querySelector('.kt-modal-backdrop').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDayModal();
        }
    });

    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDayModal();
        }
    });
}

// Load events from API
function loadEvents() {
    const start = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
    const end = new Date(currentDate.getFullYear(), currentDate.getMonth() + 2, 0);

    fetch(`{{ route("agenda.events") }}?start=${start.toISOString()}&end=${end.toISOString()}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        events = data;
        renderCurrentView();
    })
    .catch(error => {
        console.error('Error loading events:', error);
        events = [];
        renderCurrentView();
    });
}

// Switch view
window.switchView = function(view) {
    currentView = view;
    
    // Update active button
    document.querySelectorAll('.view-btn').forEach(btn => {
        if (btn.dataset.view === view) {
            btn.classList.add('kt-btn-active');
        } else {
            btn.classList.remove('kt-btn-active');
        }
    });

    // Update active view
    document.querySelectorAll('.calendar-view').forEach(viewEl => {
        viewEl.classList.remove('active');
    });
    document.getElementById(`${view}-view`).classList.add('active');

    renderCurrentView();
};

// Navigate
function navigate(direction) {
    if (currentView === 'month') {
        currentDate.setMonth(currentDate.getMonth() + direction);
    } else if (currentView === 'week') {
        currentDate.setDate(currentDate.getDate() + (direction * 7));
    } else if (currentView === 'day') {
        currentDate.setDate(currentDate.getDate() + direction);
    }
    renderCurrentView();
}

// Go to today
function goToToday() {
    currentDate = new Date();
    renderCurrentView();
}

// Render current view
function renderCurrentView() {
    if (currentView === 'month') {
        renderMonthView();
    } else if (currentView === 'week') {
        renderWeekView();
    } else if (currentView === 'day') {
        renderDayView();
    }
    updateTitle();
}

// Update title
function updateTitle() {
    const titleEl = document.getElementById('agenda-title');
    if (currentView === 'month') {
        titleEl.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    } else if (currentView === 'week') {
        const weekStart = getWeekStart(currentDate);
        const weekNumber = getWeekNumber(weekStart);
        titleEl.textContent = `Week ${weekNumber}, ${monthNames[weekStart.getMonth()]} ${weekStart.getFullYear()}`;
    } else if (currentView === 'day') {
        titleEl.textContent = `Vandaag, ${currentDate.getDate()} ${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    }
}

// Get week start (Monday)
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
    return new Date(d.setDate(diff));
}

// Get week number
function getWeekNumber(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
}

// Render month view
function renderMonthView() {
    const container = document.getElementById('month-calendar');
    container.innerHTML = '';

    // Day headers
    dayNamesShort.forEach(day => {
        const header = document.createElement('div');
        header.className = 'month-day-header';
        header.textContent = day;
        container.appendChild(header);
    });

    // Get first day of month and days in month
    const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = (firstDay.getDay() + 6) % 7; // Monday = 0

    // Previous month days
    const prevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 0);
    const daysInPrevMonth = prevMonth.getDate();
    for (let i = startingDayOfWeek - 1; i >= 0; i--) {
        const day = daysInPrevMonth - i;
        const cell = createMonthDayCell(day, currentDate.getMonth() - 1, currentDate.getFullYear(), true);
        container.appendChild(cell);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
        const cell = createMonthDayCell(day, currentDate.getMonth(), currentDate.getFullYear(), false);
        container.appendChild(cell);
    }

    // Next month days
    const remainingCells = 42 - (startingDayOfWeek + daysInMonth);
    for (let day = 1; day <= remainingCells; day++) {
        const cell = createMonthDayCell(day, currentDate.getMonth() + 1, currentDate.getFullYear(), true);
        container.appendChild(cell);
    }
}

// Create month day cell
function createMonthDayCell(day, month, year, isOtherMonth) {
    const cell = document.createElement('div');
    cell.className = 'month-day-cell';
    if (isOtherMonth) {
        cell.classList.add('other-month');
    }

    const date = new Date(year, month, day);
    const today = new Date();
    if (date.toDateString() === today.toDateString()) {
        cell.classList.add('today');
    }

    const dayNumber = document.createElement('div');
    dayNumber.className = 'month-day-number';
    dayNumber.textContent = day;
    cell.appendChild(dayNumber);

    // Get events for this day
    const dayEvents = getEventsForDay(year, month, day);
    if (dayEvents.length > 0) {
        cell.classList.add('has-events');
        cell.onclick = () => openDayModal(year, month, day);

        const eventsContainer = document.createElement('div');
        eventsContainer.className = 'month-day-events';
        dayEvents.forEach(event => {
            const eventEl = document.createElement('div');
            eventEl.className = 'month-event';
            eventEl.textContent = event.title;
            eventsContainer.appendChild(eventEl);
        });
        cell.appendChild(eventsContainer);
    }

    return cell;
}

// Render week view
function renderWeekView() {
    const container = document.getElementById('week-calendar');
    container.innerHTML = '';

    const weekStart = getWeekStart(currentDate);
    
    // Time column header
    const timeHeader = document.createElement('div');
    timeHeader.className = 'week-time-column';
    container.appendChild(timeHeader);

    // Day headers
    for (let i = 0; i < 7; i++) {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + i);
        const header = document.createElement('div');
        header.className = 'week-day-header';
        header.innerHTML = `<div>${dayNamesShort[i]}</div><div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">${day.getDate()}</div>`;
        container.appendChild(header);
    }

    // Time slots (7:00 to 18:00)
    for (let hour = 7; hour <= 18; hour++) {
        // Time label
        const timeSlot = document.createElement('div');
        timeSlot.className = 'week-time-slot';
        timeSlot.textContent = `${String(hour).padStart(2, '0')}:00`;
        timeHeader.appendChild(timeSlot);

        // Day columns
        for (let i = 0; i < 7; i++) {
            const day = new Date(weekStart);
            day.setDate(weekStart.getDate() + i);
            const hourSlot = document.createElement('div');
            hourSlot.className = 'week-hour-slot';
            
            const hourEvents = getEventsForHour(day.getFullYear(), day.getMonth(), day.getDate(), hour);
            hourEvents.forEach(event => {
                const eventEl = document.createElement('div');
                eventEl.className = 'week-event';
                eventEl.textContent = event.title;
                eventEl.style.top = '0.25rem';
                hourSlot.appendChild(eventEl);
            });
            
            container.appendChild(hourSlot);
        }
    }
}

// Render day view
function renderDayView() {
    const container = document.getElementById('day-calendar');
    container.innerHTML = '';

    // Time column
    const timeColumn = document.createElement('div');
    timeColumn.className = 'day-time-column';
    container.appendChild(timeColumn);

    // Day header
    const dayHeader = document.createElement('div');
    dayHeader.className = 'day-header';
    dayHeader.textContent = `${dayNames[currentDate.getDay() === 0 ? 6 : currentDate.getDay() - 1]}, ${currentDate.getDate()} ${monthNames[currentDate.getMonth()]}`;
    container.appendChild(dayHeader);

    // Time slots (7:00 to 18:00)
    for (let hour = 7; hour <= 18; hour++) {
        // Time label
        const timeSlot = document.createElement('div');
        timeSlot.className = 'day-time-slot';
        timeSlot.textContent = `${String(hour).padStart(2, '0')}:00`;
        timeColumn.appendChild(timeSlot);

        // Hour slot
        const hourSlot = document.createElement('div');
        hourSlot.className = 'day-hour-slot';
        
        const hourEvents = getEventsForHour(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), hour);
        hourEvents.forEach(event => {
            const eventEl = document.createElement('div');
            eventEl.className = 'day-event';
            eventEl.innerHTML = `
                <div class="day-event-time">${formatTime(event.start)} - ${formatTime(event.end)}</div>
                <div class="day-event-title">${event.title}</div>
            `;
            hourSlot.appendChild(eventEl);
        });
        
        container.appendChild(hourSlot);
    }
}

// Get events for a specific day
function getEventsForDay(year, month, day) {
    return events.filter(event => {
        const eventDate = new Date(event.start);
        return eventDate.getFullYear() === year &&
               eventDate.getMonth() === month &&
               eventDate.getDate() === day;
    });
}

// Get events for a specific hour
function getEventsForHour(year, month, day, hour) {
    return events.filter(event => {
        const eventDate = new Date(event.start);
        return eventDate.getFullYear() === year &&
               eventDate.getMonth() === month &&
               eventDate.getDate() === day &&
               eventDate.getHours() === hour;
    });
}

// Format time
function formatTime(dateString) {
    const date = new Date(dateString);
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

// Open day modal
window.openDayModal = function(year, month, day) {
    const dayEvents = getEventsForDay(year, month, day);
    if (dayEvents.length === 0) return;

    const date = new Date(year, month, day);
    const titleEl = document.getElementById('modal-day-title');
    titleEl.textContent = `${dayNames[date.getDay() === 0 ? 6 : date.getDay() - 1]}, ${day} ${monthNames[month]} ${year}`;

    const eventsContainer = document.getElementById('modal-day-events');
    eventsContainer.innerHTML = '';

    dayEvents.forEach(event => {
        const eventItem = document.createElement('div');
        eventItem.className = 'modal-event-item';
        
        const avatar = document.createElement('div');
        avatar.className = 'modal-event-avatar';
        if (event.extendedProps.user_id) {
            avatar.innerHTML = `<img src="/user-photo/${event.extendedProps.user_id}" alt="" onerror="this.parentElement.innerHTML='${getInitials(event.extendedProps.candidate_name)}'">`;
        } else {
            avatar.textContent = getInitials(event.extendedProps.candidate_name);
        }
        
        const content = document.createElement('div');
        content.className = 'modal-event-content';
        
        const name = document.createElement('div');
        name.className = 'modal-event-name';
        name.textContent = event.extendedProps.candidate_name || 'Onbekend';
        
        const details = document.createElement('div');
        details.className = 'modal-event-details';
        
        const timeDetail = document.createElement('div');
        timeDetail.className = 'modal-event-detail';
        timeDetail.innerHTML = `<i class="ki-filled ki-time"></i><span>${formatTime(event.start)} - ${formatTime(event.end)}</span>`;
        
        const locationDetail = document.createElement('div');
        locationDetail.className = 'modal-event-detail';
        locationDetail.innerHTML = `<i class="ki-filled ki-geolocation"></i><span>${event.extendedProps.location || 'Locatie niet opgegeven'}</span>`;
        
        const vacancyDetail = document.createElement('div');
        vacancyDetail.className = 'modal-event-detail';
        vacancyDetail.innerHTML = `<i class="ki-filled ki-briefcase"></i><span>${event.extendedProps.vacancy_title || 'Onbekende functie'}</span>`;
        
        const companyDetail = document.createElement('div');
        companyDetail.className = 'modal-event-detail';
        companyDetail.innerHTML = `<i class="ki-filled ki-abstract-26"></i><span>${event.extendedProps.company_name || 'Onbekend bedrijf'}</span>`;
        
        if (event.extendedProps.interviewer_name) {
            const interviewerDetail = document.createElement('div');
            interviewerDetail.className = 'modal-event-detail';
            interviewerDetail.innerHTML = `<i class="ki-filled ki-profile-user"></i><span>${event.extendedProps.interviewer_name}</span>`;
            details.appendChild(interviewerDetail);
        }
        
        details.appendChild(timeDetail);
        details.appendChild(locationDetail);
        details.appendChild(vacancyDetail);
        details.appendChild(companyDetail);
        
        content.appendChild(name);
        content.appendChild(details);
        
        if (event.extendedProps.notes) {
            const notes = document.createElement('div');
            notes.className = 'modal-event-notes';
            notes.innerHTML = `
                <div class="modal-event-notes-icon">
                    <i class="ki-filled ki-note-edit"></i>
                </div>
                <div class="modal-event-notes-text">${event.extendedProps.notes}</div>
            `;
            content.appendChild(notes);
        }
        
        const link = document.createElement('div');
        link.className = 'modal-event-link';
        link.innerHTML = `<a href="${interviewBaseUrl}${event.id}">Bekijk details</a>`;
        content.appendChild(link);
        
        eventItem.appendChild(avatar);
        eventItem.appendChild(content);
        eventsContainer.appendChild(eventItem);
    });

    const modal = document.getElementById('day-modal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    // ESC key handler
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            closeDayModal();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
};

// Close day modal
window.closeDayModal = function() {
    const modal = document.getElementById('day-modal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
};

// Get initials
function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

// Go to day view
window.goToDayView = function(year, month, day) {
    currentDate = new Date(year, month, day);
    switchView('day');
};
</script>
@endsection
