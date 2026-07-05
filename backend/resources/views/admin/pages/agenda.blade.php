@extends('admin.layouts.app')

@section('title', 'Agenda - NEXA Skillmatching')

@section('content')
<style>
  /* Calendar container styles */
  .agenda-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
  }

  .agenda-container--month {
    height: calc(100vh - 8.5rem);
    min-height: 32rem;
  }

  #month-view.active {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
  }

  /* Header styles */
  .agenda-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
    flex-shrink: 0;
  }

  .dark .agenda-header {
    border-bottom-color: #4b5563;
  }

  .agenda-title-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  #user-filter {
    font-size: 0.875rem;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    min-height: 38px;
    height: 38px;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    background: white;
    color: #374151;
    line-height: 1.5;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23374151' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 12px;
    cursor: pointer;
  }

  .dark #user-filter {
    background: #1f2937;
    border-color: #4b5563;
    color: #f9fafb;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23f9fafb' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 12px;
  }

  #user-filter:hover {
    border-color: #9ca3af;
  }

  .dark #user-filter:hover {
    border-color: #6b7280;
  }

  .agenda-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    min-width: 250px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  }

  .dark .agenda-title {
    color: #f9fafb;
    font-weight: 700;
  }

  .agenda-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
  }

  .agenda-nav-buttons {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
    align-items: center;
  }

  /* View buttons container - fixed width to prevent shifting */
  .view-buttons-container {
    display: flex;
    gap: 0.5rem;
    min-width: 200px;
    justify-content: center;
  }

  .view-buttons-container .view-btn.kt-btn-active {
    background-color: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
    font-weight: 600;
  }

  .view-buttons-container .view-btn.kt-btn-active:hover {
    background-color: #2563eb;
    border-color: #2563eb;
    color: #fff;
  }

  .dark .view-buttons-container .view-btn.kt-btn-active {
    background-color: #2563eb;
    border-color: #2563eb;
    color: #f9fafb;
  }

  .dark .view-buttons-container .view-btn.kt-btn-active:hover {
    background-color: #1d4ed8;
    border-color: #1d4ed8;
    color: #f9fafb;
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
    grid-template-rows: auto repeat(6, minmax(0, 1fr));
    flex: 1;
    min-height: 0;
    height: 100%;
    gap: 1px;
    background: #e5e7eb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .dark .month-view {
    background: #374151;
    border-color: #4b5563;
  }

  .month-day-header {
    background: #f9fafb;
    padding: 0.375rem 0.25rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.75rem;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
  }

  .dark .month-day-header {
    background: #374151;
    color: #d1d5db;
    border-bottom-color: #4b5563;
  }

  .month-day-cell {
    background: white;
    min-height: 0;
    padding: 0.25rem 0.375rem;
    position: relative;
    cursor: default;
    transition: background-color 0.2s;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .dark .month-day-cell {
    background: #1f2937;
    color: #f9fafb;
  }

  .month-day-cell:hover {
    background: #f9fafb;
  }

  .dark .month-day-cell:hover {
    background: #374151;
  }

  .month-day-number {
    font-weight: 600;
    font-size: 0.75rem;
    line-height: 1.2;
    color: #374151;
    margin-bottom: 0.125rem;
    flex-shrink: 0;
  }

  .dark .month-day-number {
    color: #d1d5db;
  }

  .month-day-events {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    flex: 1;
    min-height: 0;
    overflow: hidden;
  }

  .month-event {
    flex-shrink: 0;
    min-height: 1.125rem;
    padding: 0.0625rem 0.3125rem;
    border-radius: 0.1875rem;
    font-size: 0.625rem;
    font-weight: 500;
    line-height: 1.25;
    color: #fff;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
    transition: filter 0.15s;
    position: relative;
  }

  .month-event:hover {
    filter: brightness(0.92);
    z-index: 2;
  }

  .month-event-more {
    flex-shrink: 0;
    font-size: 0.625rem;
    font-weight: 600;
    color: #6b7280;
    line-height: 1.2;
    padding-top: 1px;
    cursor: pointer;
  }

  .month-event-more:hover {
    color: #374151;
    text-decoration: underline;
  }

  .dark .month-event-more {
    color: #9ca3af;
  }

  .dark .month-event-more:hover {
    color: #d1d5db;
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
    position: relative;
  }

  .dark .week-view {
    background: #374151;
    border-color: #4b5563;
  }

  .week-grid-corner {
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
  }

  .dark .week-grid-corner {
    background: #374151;
    border-bottom-color: #4b5563;
  }

  .week-cell-tl {
    border-top-left-radius: 0.5rem;
  }

  .week-cell-tr {
    border-top-right-radius: 0.5rem;
  }

  .week-cell-bl {
    border-bottom-left-radius: 0.5rem;
  }

  .week-cell-br {
    border-bottom-right-radius: 0.5rem;
  }

  .week-row-last {
    border-bottom: none !important;
  }

  .week-time-column {
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
  }

  .dark .week-time-column {
    background: #374151;
    border-right-color: #4b5563;
  }

  .week-time-slot {
    height: 3rem;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: flex-start;
    background: #f9fafb;
  }

  .dark .week-time-slot {
    border-bottom-color: #4b5563;
    color: #9ca3af;
    background: #374151;
  }

  .week-day-column {
    background: white;
  }

  .dark .week-day-column {
    background: #1f2937;
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

  .dark .week-day-header {
    background: #374151;
    color: #d1d5db;
    border-bottom-color: #4b5563;
  }

  .week-hour-slot {
    height: 3rem;
    border-bottom: 1px solid #e5e7eb;
    position: relative;
    padding: 0.25rem;
    background: white;
  }

  .dark .week-hour-slot {
    border-bottom-color: #4b5563;
    background: #1f2937;
  }

  .week-event {
    position: absolute;
    color: white;
    padding: 0.125rem 0.3125rem;
    border-radius: 0.1875rem;
    font-size: 0.6875rem;
    font-weight: 500;
    overflow: hidden;
    cursor: pointer;
    transition: filter 0.2s;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 0.0625rem;
    box-sizing: border-box;
  }

  .week-event--compact {
    flex-direction: row;
    align-items: center;
    padding: 0 0.3125rem;
    font-size: 0.625rem;
    line-height: 1.2;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .week-event:hover {
    filter: brightness(0.9);
    z-index: 2;
  }

  .week-event-time {
    font-size: 0.625rem;
    opacity: 0.95;
    line-height: 1.2;
  }

  .week-event-title {
    font-weight: 600;
    font-size: 0.625rem;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .week-day-events-container {
    position: relative;
    height: 100%;
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

  .dark .day-view {
    background: #374151;
    border-color: #4b5563;
  }

  .day-time-column {
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
  }

  .dark .day-time-column {
    background: #374151;
    border-right-color: #4b5563;
  }

  .day-time-slot {
    height: 3rem;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: flex-start;
    background: #f9fafb;
  }

  .dark .day-time-slot {
    border-bottom-color: #4b5563;
    color: #9ca3af;
    background: #374151;
  }

  .day-content-column {
    background: white;
  }

  .dark .day-content-column {
    background: #1f2937;
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

  .dark .day-header {
    background: #374151;
    color: #d1d5db;
    border-bottom-color: #4b5563;
  }

  .day-hour-slot {
    height: 3rem;
    border-bottom: 1px solid #e5e7eb;
    position: relative;
    padding: 0.25rem;
    background: white;
  }

  .dark .day-hour-slot {
    border-bottom-color: #4b5563;
    background: #1f2937;
  }

  .day-event {
    position: absolute;
    color: white;
    padding: 0.1875rem 0.4375rem;
    border-radius: 0.1875rem;
    font-size: 0.6875rem;
    font-weight: 500;
    line-height: 1.3;
    width: calc(100% - 0.5rem);
    max-height: calc(3rem - 0.375rem);
    min-height: 1.25rem;
    left: 0.25rem;
    top: 0.25rem;
    display: flex;
    align-items: center;
    overflow: hidden;
    box-sizing: border-box;
    cursor: pointer;
    transition: filter 0.2s;
  }

  .day-event:hover {
    filter: brightness(0.9);
  }

  .day-event-label {
    min-width: 0;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .day-event-time {
    font-size: 0.625rem;
    opacity: 0.9;
  }

  .day-event-title {
    font-weight: 600;
    font-size: 0.625rem;
  }

  #agenda-event-tooltip {
    position: fixed;
    z-index: 100000;
    display: none;
    max-width: 18rem;
    padding: 0.375rem 0.5rem;
    border-radius: 0.375rem;
    background: #111827;
    color: #f9fafb;
    font-size: 0.6875rem;
    font-weight: 500;
    line-height: 1.35;
    white-space: pre-line;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    pointer-events: none;
  }

  .dark #agenda-event-tooltip {
    background: #f9fafb;
    color: #111827;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
  }

  /* Agenda event modal — eigen classes, geen Metronic kt-modal-dialog */
  .agenda-event-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    pointer-events: none;
  }

  .agenda-event-modal.open {
    display: block;
    pointer-events: auto;
  }

  .agenda-event-modal__backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 1;
  }

  .agenda-event-modal__panel {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    width: min(32rem, calc(100vw - 2rem));
    max-height: min(90vh, calc(100vh - 2rem));
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border-radius: 0.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  }

  .dark .agenda-event-modal__panel {
    background: #1f2937;
    border: 1px solid #4b5563;
  }

  .agenda-event-modal__header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
  }

  .dark .agenda-event-modal__header {
    border-bottom-color: #4b5563;
  }

  .agenda-event-modal__title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
  }

  .dark .agenda-event-modal__title {
    color: #f9fafb;
  }

  .agenda-event-modal__close {
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
    flex-shrink: 0;
  }

  .agenda-event-modal__close:hover {
    background: #f3f4f6;
    color: #111827;
  }

  .dark .agenda-event-modal__close {
    color: #d1d5db;
  }

  .dark .agenda-event-modal__close:hover {
    background: #374151;
    color: #f9fafb;
  }

  .agenda-event-modal__body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
    min-height: 0;
  }

  .dark .agenda-event-modal__body {
    color: #f9fafb;
  }

  .modal-header-main {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
    flex: 1;
  }

  #modal-event-avatar {
    width: 2.75rem;
    height: 2.75rem;
    font-size: 0.875rem;
  }

  #modal-event-avatar.hidden {
    display: none;
  }

  .modal-event-item {
    padding: 0 0 0 1rem;
    border-left: 4px solid #3b82f6;
    border-bottom: none;
  }

  .modal-event-item + .modal-event-item {
    margin-top: 1.25rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e5e7eb;
  }

  .dark .modal-event-item + .modal-event-item {
    border-top-color: #4b5563;
  }

  .dark .modal-event-item {
    border-bottom-color: #4b5563;
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

  .dark .modal-event-name {
    color: #f9fafb;
  }

  .modal-event-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6b7280;
  }

  .dark .modal-event-details {
    color: #d1d5db;
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

  .dark .modal-event-notes {
    background: #374151;
    color: #d1d5db;
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

  .dark .month-day-cell.other-month {
    background: #111827;
    color: #6b7280;
  }

  .month-day-cell.today {
    background: #eff6ff;
  }

  .dark .month-day-cell.today {
    background: #1e3a8a;
  }

  .month-day-cell.today .month-day-number {
    color: #3b82f6;
    font-weight: 700;
  }

  .dark .month-day-cell.today .month-day-number {
    color: #60a5fa;
  }

  /* Responsive */
  @media (max-width: 1023px) {
    .agenda-header {
      flex-direction: column;
      align-items: stretch;
      gap: 1rem;
      padding-left: 0;
      padding-right: 0;
    }

    .agenda-title {
      min-width: 0;
      font-size: 1.25rem;
    }

    #user-filter {
      width: 100%;
      min-width: 0;
    }

    .agenda-actions {
      flex-direction: column;
      align-items: stretch;
      width: 100%;
      gap: 0.75rem;
    }

    .view-buttons-container {
      min-width: 0;
      width: 100%;
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .view-buttons-container .view-btn {
      width: 100%;
      justify-content: center;
      padding-inline: 0.375rem;
    }

    .agenda-nav-buttons {
      display: grid;
      grid-template-columns: minmax(2.75rem, auto) minmax(0, 1fr) minmax(2.75rem, auto);
      width: 100%;
      gap: 0.5rem;
    }

    .agenda-nav-buttons .kt-btn {
      width: 100%;
      min-width: 0;
      justify-content: center;
    }

    .agenda-actions > .kt-btn-primary {
      width: 100%;
      justify-content: center;
    }

    .month-day-cell {
      min-height: 0;
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

  @media (max-width: 480px) {
    .view-buttons-container .view-btn {
      font-size: 0.8125rem;
      padding-inline: 0.25rem;
    }

    #btn-today {
      font-size: 0.8125rem;
      padding-inline: 0.5rem;
    }
  }
</style>

<div class="kt-container-fixed">
    <div class="agenda-container">
        <!-- Header -->
        <div class="agenda-header">
            <div class="agenda-title-container">
                <div class="agenda-title" id="agenda-title">Agenda</div>
                @if(auth()->user()->hasRole('super-admin') && isset($users) && $users->count() > 0)
                <div class="mt-2">
                    <select id="user-filter" class="kt-select kt-select-sm" style="min-width: 200px;">
                        <option value="" selected>Alle gebruikers</option>
                        @foreach($users as $user)
                            @php $userAgendaColor = $user->agenda_color ?: $user->resolvedAgendaColor(); @endphp
                            <option value="{{ $user->id }}" data-agenda-color="{{ $userAgendaColor }}">{{ $user->first_name }} {{ $user->last_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div class="agenda-actions">
                <div class="view-buttons-container">
                    <button class="kt-btn kt-btn-outline view-btn kt-btn-active" data-view="month" id="btn-month" aria-pressed="true">
                        Maand
                    </button>
                    <button class="kt-btn kt-btn-outline view-btn" data-view="week" id="btn-week" aria-pressed="false">
                        Week
                    </button>
                    <button class="kt-btn kt-btn-outline view-btn" data-view="day" id="btn-day" aria-pressed="false">
                        Dag
                    </button>
                </div>
                <div class="agenda-nav-buttons">
                    <button class="kt-btn kt-btn-outline" id="btn-prev" type="button" aria-label="Vorige periode">
                        <i class="ki-filled ki-arrow-left"></i>
                    </button>
                    <button class="kt-btn kt-btn-outline" id="btn-today" type="button">
                        Vandaag
                    </button>
                    <button class="kt-btn kt-btn-outline" id="btn-next" type="button" aria-label="Volgende periode">
                        <i class="ki-filled ki-arrow-right"></i>
                    </button>
                </div>
                @if(Route::has('admin.skillmatching.interviews.create'))
                <a href="{{ route('admin.skillmatching.interviews.create') }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-plus me-2"></i>
                    Nieuwe interview
                </a>
                @endif
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
<div class="agenda-event-modal" id="day-modal" aria-hidden="true">
    <div class="agenda-event-modal__backdrop" onclick="closeDayModal()"></div>
    <div class="agenda-event-modal__panel" role="dialog" aria-modal="true" aria-labelledby="modal-day-title">
        <div class="agenda-event-modal__header">
            <div class="modal-header-main">
                <div id="modal-event-avatar" class="modal-event-avatar hidden" aria-hidden="true"></div>
                <h3 class="agenda-event-modal__title" id="modal-day-title">Afspraken</h3>
            </div>
            <button class="agenda-event-modal__close" onclick="closeDayModal()" type="button" aria-label="Sluiten">
                <i class="ki-filled ki-cross"></i>
            </button>
        </div>
        <div class="agenda-event-modal__body">
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

function resolveEventColor(event) {
    if (event && event.color) {
        return event.color;
    }
    if (event && event.extendedProps && event.extendedProps.agenda_color) {
        return event.extendedProps.agenda_color;
    }
    return '#3b82f6';
}

function applyEventColor(el, event) {
    const color = resolveEventColor(event);
    el.style.backgroundColor = color;
    el.style.borderColor = color;
    el.dataset.eventColor = color;

    const state = event && event.extendedProps ? event.extendedProps.color_state : null;
    if (state === 'completed') {
        const isAgendaChip = el.classList.contains('month-event')
            || el.classList.contains('week-event')
            || el.classList.contains('day-event');
        el.style.opacity = isAgendaChip ? '0.6' : '0.35';
        el.style.filter = 'grayscale(1) saturate(0.2)';
        el.style.color = 'rgba(255, 255, 255, 0.75)';
    } else if (state === 'active') {
        el.style.opacity = '1';
        el.style.filter = 'none';
        el.style.fontWeight = '600';
    } else if (state === 'upcoming') {
        el.style.opacity = '0.92';
        el.style.filter = 'none';
    } else {
        el.style.opacity = '';
        el.style.filter = '';
    }
}

function getEventDateParts(dateString) {
    const value = String(dateString || '');
    const match = value.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        return {
            year: parseInt(match[1], 10),
            month: parseInt(match[2], 10) - 1,
            day: parseInt(match[3], 10),
        };
    }

    const date = new Date(value);
    return {
        year: date.getFullYear(),
        month: date.getMonth(),
        day: date.getDate(),
    };
}

function getSelectedUserId() {
    const userFilter = document.getElementById('user-filter');
    if (!userFilter) {
        return null;
    }

    const value = String(userFilter.value || '').trim();
    if (value === '' || !/^\d+$/.test(value)) {
        return null;
    }

    return value;
}

// Interview base URL (Skillmatching module; empty when module inactive)
const interviewBaseUrl = @json(Route::has('admin.skillmatching.interviews.index') ? rtrim(route('admin.skillmatching.interviews.index'), '/') . '/' : '');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    ensureDayModalInBody();
    ensureAgendaTooltip();
    setupAgendaTooltipListeners();
    updateViewButtonState();
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

    // User filter (for super-admin)
    const userFilter = document.getElementById('user-filter');
    if (userFilter) {
        userFilter.addEventListener('change', function() {
            loadEvents();
        });
    }

    // Modal backdrop click
    const dayModalBackdrop = document.querySelector('#day-modal .agenda-event-modal__backdrop');
    if (dayModalBackdrop) {
        dayModalBackdrop.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDayModal();
            }
        });
    }

    // ESC key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDayModal();
        }
    });
}

// Load events from API
function loadEvents() {
    const start = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1, 0, 0, 0, 0);
    const end = new Date(currentDate.getFullYear(), currentDate.getMonth() + 2, 0, 23, 59, 59, 999);
    
    const userId = getSelectedUserId();
    
    let url = `{{ route("admin.agenda.events") }}?start=${start.toISOString()}&end=${end.toISOString()}`;
    if (userId) {
        url += `&user_id=${userId}`;
    }

    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => {
        console.log('Events response status:', response.status);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        // Ensure data is an array
        if (Array.isArray(data)) {
            console.log('Events loaded:', data.length, 'events');
            events = data;
        } else {
            console.warn('Events data is not an array:', data);
            events = [];
        }
        renderCurrentView();
    })
    .catch(error => {
        console.error('Error loading events:', error);
        events = [];
        renderCurrentView();
    });
}

// Switch view
function updateViewButtonState() {
    document.querySelectorAll('.view-btn').forEach(btn => {
        const isActive = btn.dataset.view === currentView;
        btn.classList.toggle('kt-btn-active', isActive);
        btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    const agendaContainer = document.querySelector('.agenda-container');
    if (agendaContainer) {
        agendaContainer.classList.toggle('agenda-container--month', currentView === 'month');
    }
}

window.switchView = function(view) {
    currentView = view;
    updateViewButtonState();

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
const MONTH_MAX_VISIBLE_EVENTS = 4;
const WEEK_EVENT_MIN_HEIGHT = 22;
const WEEK_EVENT_COMPACT_THRESHOLD = 40;

function eventFullTooltip(event) {
    return `${formatTime(event.start)} - ${formatTime(event.end)} ${event.title}`;
}

function eventCompactLabel(event) {
    const time = formatTime(event.start);
    let label = String(event.title || '').trim();
    label = label.replace(/^Rit:\s*/i, '').replace(/\s*\([^)]*\)\s*$/, '').trim();
    if (label.length > 16) {
        label = label.slice(0, 14) + '…';
    }
    return label ? `${time} ${label}` : time;
}

function dayEventLabel(event) {
    const timeRange = `${formatTime(event.start)} - ${formatTime(event.end)}`;
    let label = String(event.title || '').trim();
    label = label.replace(/^Rit:\s*/i, '').replace(/\s*\([^)]*\)\s*$/, '').trim();
    return label ? `${timeRange} ${label}` : timeRange;
}

let agendaTooltipEl = null;
let agendaTooltipAnchor = null;

function ensureAgendaTooltip() {
    if (agendaTooltipEl) {
        return agendaTooltipEl;
    }
    agendaTooltipEl = document.createElement('div');
    agendaTooltipEl.id = 'agenda-event-tooltip';
    agendaTooltipEl.setAttribute('role', 'tooltip');
    document.body.appendChild(agendaTooltipEl);
    return agendaTooltipEl;
}

function positionAgendaTooltip(clientX, clientY) {
    const tip = ensureAgendaTooltip();
    if (tip.style.display === 'none') {
        return;
    }

    tip.style.left = '0';
    tip.style.top = '0';
    tip.style.display = 'block';

    const tipRect = tip.getBoundingClientRect();
    const margin = 8;
    const offset = 12;
    let left = clientX + offset;
    let top = clientY + offset;

    if (left + tipRect.width > window.innerWidth - margin) {
        left = clientX - tipRect.width - offset;
    }
    if (top + tipRect.height > window.innerHeight - margin) {
        top = clientY - tipRect.height - offset;
    }

    left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));
    top = Math.max(margin, Math.min(top, window.innerHeight - tipRect.height - margin));

    tip.style.top = `${top}px`;
    tip.style.left = `${left}px`;
}

function showAgendaTooltip(text, anchorEl, clientX, clientY) {
    if (!text || !anchorEl) {
        return;
    }
    const tip = ensureAgendaTooltip();
    tip.textContent = text;
    tip.style.display = 'block';
    agendaTooltipAnchor = anchorEl;
    positionAgendaTooltip(clientX, clientY);
}

function hideAgendaTooltip() {
    if (!agendaTooltipEl) {
        return;
    }
    agendaTooltipEl.style.display = 'none';
    agendaTooltipAnchor = null;
}

function setAgendaEventTooltip(el, text) {
    if (!el) {
        return;
    }
    el.dataset.agendaTooltip = text || '';
    el.removeAttribute('title');
}

function setupAgendaTooltipListeners() {
    const container = document.querySelector('.agenda-container');
    if (!container || container.dataset.agendaTooltipBound === '1') {
        return;
    }
    container.dataset.agendaTooltipBound = '1';

    const tooltipSelector = '.month-event, .week-event, .day-event, .month-event-more';

    container.addEventListener('mousemove', function(e) {
        const el = e.target.closest(tooltipSelector);
        if (el && container.contains(el) && el.dataset.agendaTooltip) {
            if (agendaTooltipAnchor !== el) {
                showAgendaTooltip(el.dataset.agendaTooltip, el, e.clientX, e.clientY);
            } else {
                positionAgendaTooltip(e.clientX, e.clientY);
            }
            return;
        }
        if (agendaTooltipAnchor) {
            hideAgendaTooltip();
        }
    });

    container.addEventListener('mouseleave', function() {
        hideAgendaTooltip();
    });

    container.addEventListener('click', function(e) {
        if (e.target.closest(tooltipSelector)) {
            hideAgendaTooltip();
        }
    });

    window.addEventListener('scroll', hideAgendaTooltip, true);
    window.addEventListener('resize', hideAgendaTooltip);
}

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
        const eventsContainer = document.createElement('div');
        eventsContainer.className = 'month-day-events';

        const visibleEvents = dayEvents.slice(0, MONTH_MAX_VISIBLE_EVENTS);
        visibleEvents.forEach(event => {
            const eventEl = document.createElement('div');
            eventEl.className = 'month-event';
            const tooltip = eventFullTooltip(event);
            setAgendaEventTooltip(eventEl, tooltip);
            eventEl.setAttribute('role', 'button');
            eventEl.setAttribute('tabindex', '0');
            eventEl.setAttribute('aria-label', tooltip);
            eventEl.textContent = eventCompactLabel(event);
            applyEventColor(eventEl, event);
            eventEl.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openEventModal(event);
            });
            eventsContainer.appendChild(eventEl);
        });

        const hiddenCount = dayEvents.length - visibleEvents.length;
        if (hiddenCount > 0) {
            const moreEl = document.createElement('div');
            moreEl.className = 'month-event-more';
            moreEl.textContent = `+${hiddenCount}`;
            setAgendaEventTooltip(moreEl, dayEvents.slice(MONTH_MAX_VISIBLE_EVENTS).map(eventFullTooltip).join('\n'));
            moreEl.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                openDayModal(year, month, day);
            });
            eventsContainer.appendChild(moreEl);
        }

        cell.appendChild(eventsContainer);
    }

    return cell;
}

// Render week view
function renderWeekView() {
    const container = document.getElementById('week-calendar');
    container.innerHTML = '';

    const weekStart = getWeekStart(currentDate);
    const startHour = 0;
    const endHour = 23;
    
    // Row 1: Empty corner cell + 7 day headers
    const cornerCell = document.createElement('div');
    cornerCell.className = 'week-grid-corner week-cell-tl';
    cornerCell.setAttribute('aria-hidden', 'true');
    container.appendChild(cornerCell);

    // Day headers
    for (let i = 0; i < 7; i++) {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + i);
        const header = document.createElement('div');
        header.className = 'week-day-header';
        if (i === 6) {
            header.classList.add('week-cell-tr');
        }
        header.innerHTML = `<div>${dayNamesShort[i]}</div><div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">${day.getDate()}</div>`;
        container.appendChild(header);
    }

    // Create a wrapper for each day column with events container
    // Store references to day containers for event positioning
    const dayContainers = [];

    // Time slots (7:00 to 18:00) - each row has time + 7 day cells
    for (let hour = startHour; hour <= endHour; hour++) {
        const isLastRow = hour === endHour;

        // Time label (first column)
        const timeSlot = document.createElement('div');
        timeSlot.className = 'week-time-slot';
        if (isLastRow) {
            timeSlot.classList.add('week-row-last', 'week-cell-bl');
        }
        timeSlot.textContent = `${String(hour).padStart(2, '0')}:00`;
        container.appendChild(timeSlot);

        // Day columns (7 cells per row)
        for (let i = 0; i < 7; i++) {
            const hourSlot = document.createElement('div');
            hourSlot.className = 'week-hour-slot';
            if (isLastRow) {
                hourSlot.classList.add('week-row-last');
                if (i === 6) {
                    hourSlot.classList.add('week-cell-br');
                }
            }
            hourSlot.dataset.dayIndex = i;
            hourSlot.dataset.hour = hour;
            container.appendChild(hourSlot);
        }
    }

    // Use requestAnimationFrame to ensure DOM is rendered before positioning
    requestAnimationFrame(() => {
        positionWeekEvents(container, weekStart, startHour, endHour);
    });
}

function positionWeekEvents(container, weekStart, startHour, endHour) {
    // Get all hour slots and measure the actual height
    const hourSlots = container.querySelectorAll('.week-hour-slot');
    if (hourSlots.length === 0) return;
    
    const firstHourSlot = hourSlots[0];
    const hourHeight = firstHourSlot.offsetHeight;
    
    // Get header height from the first week-day-header
    const headerEl = container.querySelector('.week-day-header');
    const headerHeight = headerEl ? headerEl.offsetHeight : 48;

    // Process events for each day
    for (let dayIndex = 0; dayIndex < 7; dayIndex++) {
        const day = new Date(weekStart);
        day.setDate(weekStart.getDate() + dayIndex);
        const dayEvents = getEventsForDay(day.getFullYear(), day.getMonth(), day.getDate());
        
        if (dayEvents.length === 0) continue;
        
        // Sort events by start time
        dayEvents.sort((a, b) => new Date(a.start) - new Date(b.start));
        
        // Calculate overlapping groups for side-by-side display
        const columns = [];
        dayEvents.forEach(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end);
            
            let placed = false;
            for (let col = 0; col < columns.length; col++) {
                const lastEventInCol = columns[col][columns[col].length - 1];
                const lastEnd = new Date(lastEventInCol.end);
                if (eventStart >= lastEnd) {
                    columns[col].push(event);
                    event._column = col;
                    placed = true;
                    break;
                }
            }
            if (!placed) {
                event._column = columns.length;
                columns.push([event]);
            }
        });
        
        const totalColumns = columns.length;
        
        // Get the first hour slot for this day to calculate position
        const firstDaySlot = container.querySelector(`.week-hour-slot[data-day-index="${dayIndex}"][data-hour="${startHour}"]`);
        if (!firstDaySlot) continue;
        
        const containerRect = container.getBoundingClientRect();
        const slotRect = firstDaySlot.getBoundingClientRect();
        const slotLeft = slotRect.left - containerRect.left;
        const slotWidth = slotRect.width;
        const slotTop = slotRect.top - containerRect.top;
        
        // Position each event
        dayEvents.forEach(event => {
            const eventStart = new Date(event.start);
            const eventEnd = new Date(event.end);
            const startHourDecimal = eventStart.getHours() + eventStart.getMinutes() / 60;
            const endHourDecimal = eventEnd.getHours() + eventEnd.getMinutes() / 60;
            
            // Skip events outside our time range
            if (endHourDecimal <= startHour || startHourDecimal >= endHour + 1) return;
            
            // Clamp to visible range
            const visibleStart = Math.max(startHourDecimal, startHour);
            const visibleEnd = Math.min(endHourDecimal, endHour + 1);
            
            const topOffset = (visibleStart - startHour) * hourHeight;
            const height = (visibleEnd - visibleStart) * hourHeight;
            const displayHeight = Math.max(height - 4, WEEK_EVENT_MIN_HEIGHT);
            const isCompact = displayHeight < WEEK_EVENT_COMPACT_THRESHOLD;
            
            // Calculate horizontal position within the day column
            const colWidth = 100 / totalColumns;
            const leftPercent = event._column * colWidth;
            const widthPercent = colWidth - 4; // Small gap
            
            const eventEl = document.createElement('div');
            eventEl.className = 'week-event';
            if (isCompact) {
                eventEl.classList.add('week-event--compact');
            }
            const tooltip = eventFullTooltip(event);
            setAgendaEventTooltip(eventEl, tooltip);
            eventEl.setAttribute('aria-label', tooltip);
            eventEl.style.position = 'absolute';
            eventEl.style.top = `${slotTop + topOffset + 2}px`;
            eventEl.style.height = `${displayHeight}px`;
            eventEl.style.left = `${slotLeft + (leftPercent / 100 * slotWidth) + 2}px`;
            eventEl.style.width = `${(widthPercent / 100 * slotWidth)}px`;
            applyEventColor(eventEl, event);
            if (isCompact) {
                eventEl.textContent = eventCompactLabel(event);
            } else {
                const shortTitle = String(event.title || '').replace(/^Rit:\s*/i, '').trim();
                eventEl.innerHTML = `
                    <div class="week-event-time">${formatTime(event.start)} - ${formatTime(event.end)}</div>
                    <div class="week-event-title">${shortTitle}</div>
                `;
            }
            eventEl.addEventListener('click', function(e) {
                e.stopPropagation();
                openEventModal(event);
            });
            
            container.appendChild(eventEl);
        });
    }
}

// Render day view
function renderDayView() {
    const container = document.getElementById('day-calendar');
    container.innerHTML = '';

    // Row 1: Empty corner cell + Day header
    const cornerCell = document.createElement('div');
    cornerCell.className = 'day-time-slot';
    cornerCell.style.borderBottom = '1px solid var(--border, #e5e7eb)';
    container.appendChild(cornerCell);

    // Day header
    const dayHeader = document.createElement('div');
    dayHeader.className = 'day-header';
    dayHeader.textContent = `${dayNames[currentDate.getDay() === 0 ? 6 : currentDate.getDay() - 1]}, ${currentDate.getDate()} ${monthNames[currentDate.getMonth()]}`;
    container.appendChild(dayHeader);

    // Time slots (00:00 to 23:00) - each row has time + day cell
    for (let hour = 0; hour <= 23; hour++) {
        // Time label (first column)
        const timeSlot = document.createElement('div');
        timeSlot.className = 'day-time-slot';
        timeSlot.textContent = `${String(hour).padStart(2, '0')}:00`;
        container.appendChild(timeSlot);

        // Hour slot (second column)
        const hourSlot = document.createElement('div');
        hourSlot.className = 'day-hour-slot';
        
        const hourEvents = getEventsForHour(currentDate.getFullYear(), currentDate.getMonth(), currentDate.getDate(), hour);
        hourEvents.forEach(event => {
            const eventEl = document.createElement('div');
            eventEl.className = 'day-event';
            const tooltip = eventFullTooltip(event);
            setAgendaEventTooltip(eventEl, tooltip);
            eventEl.setAttribute('aria-label', tooltip);
            applyEventColor(eventEl, event);
            eventEl.innerHTML = `<div class="day-event-label">${dayEventLabel(event)}</div>`;
            eventEl.addEventListener('click', function(e) {
                e.stopPropagation();
                openEventModal(event);
            });
            hourSlot.appendChild(eventEl);
        });
        
        container.appendChild(hourSlot);
    }
}

// Get events for a specific day
function getEventsForDay(year, month, day) {
    return events.filter(event => {
        const parts = getEventDateParts(event.start);
        return parts.year === year &&
               parts.month === month &&
               parts.day === day;
    });
}

// Get events for a specific hour
function getEventsForHour(year, month, day, hour) {
    return events.filter(event => {
        const parts = getEventDateParts(event.start);
        if (parts.year !== year || parts.month !== month || parts.day !== day) {
            return false;
        }

        const timeMatch = String(event.start || '').match(/T(\d{2}):(\d{2})/);
        if (timeMatch) {
            return parseInt(timeMatch[1], 10) === hour;
        }

        return new Date(event.start).getHours() === hour;
    });
}

// Format time
function formatTime(dateString) {
    const timeMatch = String(dateString || '').match(/T(\d{2}):(\d{2})/);
    if (timeMatch) {
        return `${timeMatch[1]}:${timeMatch[2]}`;
    }

    const date = new Date(dateString);
    return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}

function getEventDisplayName(event) {
    const isRide = event.extendedProps && event.extendedProps.event_kind === 'ride';
    return isRide
        ? (event.extendedProps.candidate_name || 'Klant')
        : (event.extendedProps.candidate_name || 'Onbekend');
}

function populateModalHeaderAvatar(event) {
    const avatarEl = document.getElementById('modal-event-avatar');
    if (!avatarEl) {
        return;
    }

    if (!event) {
        avatarEl.classList.add('hidden');
        avatarEl.setAttribute('aria-hidden', 'true');
        avatarEl.innerHTML = '';
        avatarEl.style.backgroundColor = '';
        return;
    }

    const eventColor = resolveEventColor(event);
    const displayName = getEventDisplayName(event);

    avatarEl.style.backgroundColor = eventColor;
    avatarEl.classList.remove('hidden');
    avatarEl.setAttribute('aria-hidden', 'false');

    if (event.extendedProps.user_photo_token) {
        avatarEl.innerHTML = `<img src="/secure-photo/${event.extendedProps.user_photo_token}" alt="" onerror="this.parentElement.textContent='${getInitials(displayName)}'">`;
    } else {
        avatarEl.textContent = getInitials(displayName);
    }
}

// Build event details for the modal body
function buildEventModalItem(event) {
    const eventItem = document.createElement('div');
    eventItem.className = 'modal-event-item';
    const eventColor = resolveEventColor(event);
    eventItem.style.borderLeftColor = eventColor;

    const isRide = event.extendedProps && event.extendedProps.event_kind === 'ride';

    const content = document.createElement('div');
    content.className = 'modal-event-content';

    const details = document.createElement('div');
    details.className = 'modal-event-details';

    const timeDetail = document.createElement('div');
    timeDetail.className = 'modal-event-detail';
    timeDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" /></svg><span>${formatTime(event.start)} - ${formatTime(event.end)}</span>`;

    const locationDetail = document.createElement('div');
    locationDetail.className = 'modal-event-detail';
    locationDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="m11.54 22.351.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" /></svg><span>${event.extendedProps.location || 'Locatie niet opgegeven'}</span>`;

    details.appendChild(timeDetail);
    details.appendChild(locationDetail);

    if (isRide) {
        if (event.extendedProps.driver_name) {
            const driverDetail = document.createElement('div');
            driverDetail.className = 'modal-event-detail';
            driverDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg><span>Chauffeur: ${event.extendedProps.driver_name}</span>`;
            details.appendChild(driverDetail);
        }
        if (event.extendedProps.status) {
            const statusDetail = document.createElement('div');
            statusDetail.className = 'modal-event-detail';
            statusDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" /></svg><span>Status: ${event.extendedProps.status}</span>`;
            details.appendChild(statusDetail);
        }
    } else {
        const vacancyDetail = document.createElement('div');
        vacancyDetail.className = 'modal-event-detail';
        vacancyDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 0 1 3-3h3a3 3 0 0 1 3 3v.205c.933.085 1.857.197 2.774.334 1.454.218 2.476 1.483 2.476 2.917v3.033c0 1.211-.734 2.352-1.936 2.752A24.726 24.726 0 0 1 12 15.75c-2.73 0-5.357-.442-7.814-1.259-1.202-.4-1.936-1.541-1.936-2.752V8.706c0-1.434 1.022-2.7 2.476-2.917A48.814 48.814 0 0 1 7.5 5.455V5.25Zm7.5 0v.09a49.488 49.488 0 0 0-6 0v-.09a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5Zm-3 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" /><path d="M3 18.4v-2.796a4.3 4.3 0 0 0 .713.31A26.226 26.226 0 0 0 12 17.25c2.892 0 5.68-.468 8.287-1.335.252-.084.49-.189.713-.311V18.4c0 1.452-1.047 2.728-2.523 2.923-2.12.282-4.282.427-6.477.427a49.19 49.19 0 0 1-6.477-.427C4.047 21.128 3 19.852 3 18.4Z" /></svg><span>${event.extendedProps.vacancy_title || 'Onbekende functie'}</span>`;
        details.appendChild(vacancyDetail);

        if (event.extendedProps.interviewer_name) {
            const interviewerDetail = document.createElement('div');
            interviewerDetail.className = 'modal-event-detail';
            interviewerDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 1 1 9 0 4.5 4.5 0 0 1-9 0ZM3.751 20.105a8.25 8.25 0 0 1 16.498 0 .75.75 0 0 1-.437.695A18.683 18.683 0 0 1 12 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 0 1-.437-.695Z" clip-rule="evenodd" /></svg><span>${event.extendedProps.interviewer_name}</span>`;
            details.appendChild(interviewerDetail);
        }
    }

    const companyDetail = document.createElement('div');
    companyDetail.className = 'modal-event-detail';
    companyDetail.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width: 1rem; height: 1rem; flex-shrink: 0;"><path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 0 0 1.5v16.5h-.75a.75.75 0 0 0 0 1.5H15v-18a.75.75 0 0 0 0-1.5H3ZM6.75 19.5v-2.25a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75v2.25a.75.75 0 0 1-.75.75h-3a.75.75 0 0 1-.75-.75ZM6 6.75A.75.75 0 0 1 6.75 6h.75a.75.75 0 0 1 0 1.5h-.75A.75.75 0 0 1 6 6.75ZM6.75 9a.75.75 0 0 0 0 1.5h.75a.75.75 0 0 0 0-1.5h-.75ZM6 12.75a.75.75 0 0 1 .75-.75h.75a.75.75 0 0 1 0 1.5h-.75a.75.75 0 0 1-.75-.75ZM10.5 6a.75.75 0 0 0 0 1.5h.75a.75.75 0 0 0 0-1.5h-.75Zm-.75 3.75A.75.75 0 0 1 10.5 9h.75a.75.75 0 0 1 0 1.5h-.75a.75.75 0 0 1-.75-.75ZM10.5 12a.75.75 0 0 0 0 1.5h.75a.75.75 0 0 0 0-1.5h-.75ZM16.5 6.75v15h5.25a.75.75 0 0 0 0-1.5H21v-12a.75.75 0 0 0 0-1.5h-4.5Zm1.5 4.5a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75h-.008a.75.75 0 0 1-.75-.75v-.008Zm.75 2.25a.75.75 0 0 0-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 0 0 .75-.75v-.008a.75.75 0 0 0-.75-.75h-.008ZM18 17.25a.75.75 0 0 1 .75-.75h.008a.75.75 0 0 1 .75.75v.008a.75.75 0 0 1-.75.75h-.008a.75.75 0 0 1-.75-.75v-.008Z" clip-rule="evenodd" /></svg><span>${event.extendedProps.company_name || 'Onbekend bedrijf'}</span>`;
    details.appendChild(companyDetail);

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

    if (!isRide && interviewBaseUrl) {
        const link = document.createElement('div');
        link.className = 'modal-event-link';
        const interviewId = String(event.id).replace(/^interview-/, '');
        link.innerHTML = `<a href="${interviewBaseUrl}${interviewId}">Bekijk details</a>`;
        content.appendChild(link);
    }

    eventItem.appendChild(content);

    return eventItem;
}

function ensureDayModalInBody() {
    const modal = document.getElementById('day-modal');
    if (modal && modal.parentElement !== document.body) {
        document.body.appendChild(modal);
    }
}

function showEventModal(title, event) {
    hideAgendaTooltip();
    ensureDayModalInBody();

    const modal = document.getElementById('day-modal');
    if (!modal) {
        return;
    }

    const titleEl = document.getElementById('modal-day-title');
    if (titleEl) {
        titleEl.textContent = title;
    }

    populateModalHeaderAvatar(event || null);

    modal.style.removeProperty('display');
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

// Open modal for a single selected event
window.openEventModal = function(event) {
    if (!event) {
        return;
    }

    const isRide = event.extendedProps && event.extendedProps.event_kind === 'ride';
    const title = isRide
        ? ('Rit – ' + (event.extendedProps.candidate_name || 'Klant'))
        : (event.title || 'Afspraak');

    const eventsContainer = document.getElementById('modal-day-events');
    eventsContainer.innerHTML = '';
    eventsContainer.appendChild(buildEventModalItem(event));
    showEventModal(title, event);
};

// Open day modal (all events on a day — klik op lege dagcel)
window.openDayModal = function(year, month, day) {
    const dayEvents = getEventsForDay(year, month, day);
    if (dayEvents.length === 0) return;

    const date = new Date(year, month, day);
    const title = `${dayNames[date.getDay() === 0 ? 6 : date.getDay() - 1]}, ${day} ${monthNames[month]} ${year}`;

    const eventsContainer = document.getElementById('modal-day-events');
    eventsContainer.innerHTML = '';

    dayEvents.forEach(event => {
        eventsContainer.appendChild(buildEventModalItem(event));
    });

    showEventModal(title, null);
};

// Close day modal
window.closeDayModal = function() {
    const modal = document.getElementById('day-modal');
    if (modal) {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        modal.style.removeProperty('display');
        document.body.style.overflow = '';
    }
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
