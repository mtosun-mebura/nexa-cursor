@extends('admin.layouts.app')

@section('title', 'Agenda - NEXA Skillmatching')

@section('content')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<style>
  .fc {
    font-family: inherit;
  }

  /* Clean toolbar styling */
  .fc-toolbar {
    margin-bottom: 1.5rem;
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .fc-toolbar-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #374151;
  }

  .dark .fc-toolbar-title {
    color: #f9fafb;
  }

  /* Clean button styling */
  .fc-button {
    background: #3b82f6;
    border: 1px solid #3b82f6;
    color: white;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin: 0 0.125rem;
    transition: all 0.2s ease;
  }

  .fc-button:hover {
    background: #2563eb;
    border-color: #2563eb;
  }

  .fc-button:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
  }

  .fc-button-primary:not(:disabled):active,
  .fc-button-primary:not(:disabled).fc-button-active {
    background: #1d4ed8;
    border-color: #1d4ed8;
  }

  /* Clean event styling with proper height and no margins */
  .fc-event {
    border-radius: 0.375rem;
    border: 1px solid #ffffff !important;
    padding: 0.3rem 0.5rem 0.2rem 0.6rem;
    font-size: 0.8rem;
    font-weight: 600;
    margin: 0 !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    min-height: 3.5rem !important;
    max-height: 3.5rem !important;
    display: flex;
    align-items: center;
    overflow: hidden !important;
    position: relative;
    top: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    background: #3b82f6 !important;
    color: white !important;
  }

  .fc-event:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  }

  .fc-event-title {
    font-weight: 600;
    line-height: 1.2;
    font-size: 0.8rem;
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    word-wrap: normal;
    hyphens: none;
  }

  .fc-daygrid-event {
    margin: 0 !important;
    min-height: 1.5rem;
    top: 0 !important;
  }

  .fc-timegrid-event {
    margin: 0 !important;
    min-height: 3.5rem !important;
    max-height: 3.5rem !important;
    top: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow: hidden !important;
  }

  /* Clean event colors */
  .fc-event {
    background: #3b82f6 !important;
    color: white !important;
    border: 1px solid #ffffff !important;
  }

  .fc-event.fc-event-interview {
    background: #3b82f6 !important;
    color: white !important;
    border: 1px solid #ffffff !important;
  }
  
  .fc-event.fc-event-meeting {
    background: #10b981 !important;
    color: white !important;
    border: 1px solid #ffffff !important;
  }
  
  .fc-event.fc-event-call {
    background: #f59e0b !important;
    color: white !important;
    border: 1px solid #ffffff !important;
  }
  
  .fc-event.fc-event-assessment {
    background: #ef4444 !important;
    color: white !important;
    border: 1px solid #ffffff !important;
  }

  /* Clean day numbers */
  .fc-daygrid-day-number {
    color: #374151;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 0.5rem;
  }

  /* Clean headers */
  .fc-col-header-cell {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    padding: 0.75rem 0.5rem;
    font-size: 0.875rem;
    border-bottom: 1px solid #e5e7eb;
  }

  /* Clean time slots with proper height for events */
  .fc-timegrid-slot {
    height: 4rem !important;
    border-bottom: 1px solid #f1f5f9;
    min-height: 4rem !important;
  }

  .fc-timegrid-slot-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
    padding: 0.5rem;
  }

  .fc-timegrid-axis {
    background: #f8fafc;
    border-right: 1px solid #e5e7eb;
  }

  /* Clean scrollgrid */
  .fc-scrollgrid {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    overflow: hidden;
  }

  .fc-scrollgrid-sync-table {
    border-radius: 0.5rem;
  }

  /* Clean day cells */
  .fc-daygrid-day {
    border-right: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
    padding: 0 !important;
    min-height: 2rem;
  }

  .fc-daygrid-day:hover {
    background-color: #f8fafc;
  }

  /* Clean table rows with proper height */
  .fc-timegrid tr {
    padding: 0 !important;
    height: 4rem !important;
    min-height: 4rem !important;
  }

  .fc-timegrid td {
    padding: 0 !important;
    vertical-align: top;
    height: 4rem !important;
    min-height: 4rem !important;
  }

  .fc-daygrid tr {
    padding: 0 !important;
  }

  .fc-daygrid td {
    padding: 0 !important;
    vertical-align: top;
  }

  /* Clean business hours */
  .fc-timegrid-slot.fc-timegrid-slot-minor {
    background-color: #ffffff;
  }

  .fc-timegrid-slot.fc-timegrid-slot-major {
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
  }

  /* Comprehensive Dark Mode Styles */
  [data-theme="dark"] .fc-toolbar {
    background: #1f2937;
    border-bottom-color: #4b5563;
  }

  [data-theme="dark"] .fc-toolbar-title {
    color: #f9fafb;
  }

  [data-theme="dark"] .fc-button {
    background: #4b5563;
    border-color: #4b5563;
    color: #f9fafb;
  }

  [data-theme="dark"] .fc-button:hover {
    background: #6b7280;
    border-color: #6b7280;
  }

  [data-theme="dark"] .fc-button:focus {
    box-shadow: 0 0 0 3px rgba(75, 85, 99, 0.3);
  }

  [data-theme="dark"] .fc-button-primary:not(:disabled):active,
  [data-theme="dark"] .fc-button-primary:not(:disabled).fc-button-active {
    background: #374151;
    border-color: #374151;
  }

  [data-theme="dark"] .fc-daygrid-day-number {
    color: #d1d5db;
  }

  [data-theme="dark"] .fc-col-header-cell {
    background: #374151;
    color: #d1d5db;
    border-bottom-color: #4b5563;
  }

  [data-theme="dark"] .fc-timegrid-axis {
    background: #374151;
    border-right-color: #4b5563;
  }

  [data-theme="dark"] .fc-timegrid-slot-label {
    color: #9ca3af;
  }

  [data-theme="dark"] .fc-scrollgrid {
    border-color: #4b5563;
    background: #1f2937;
  }

  [data-theme="dark"] .fc-daygrid-day {
    border-right-color: #4b5563;
    border-bottom-color: #4b5563;
    background: #1f2937;
  }

  [data-theme="dark"] .fc-daygrid-day:hover {
    background-color: #374151;
  }

  [data-theme="dark"] .fc-timegrid-slot.fc-timegrid-slot-minor {
    background-color: #1f2937;
  }

  [data-theme="dark"] .fc-timegrid-slot.fc-timegrid-slot-major {
    background-color: #111827;
    border-bottom-color: #4b5563;
  }

  [data-theme="dark"] .fc-event {
    color: white;
    border: 1px solid #ffffff !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  }

  [data-theme="dark"] .fc-event:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
  }

  [data-theme="dark"] .fc-event-title {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  }

  [data-theme="dark"] .fc-daygrid-event-harness {
    border-color: #ffffff !important;
  }

  [data-theme="dark"] .modern-calendar {
    background: #1f2937;
    border-color: #4b5563;
  }

  [data-theme="dark"] .card {
    background: #1f2937;
    border-color: #4b5563;
  }

  [data-theme="dark"] .text-muted {
    color: #9ca3af !important;
  }

  [data-theme="dark"] .text-muted-dark {
    color: #9ca3af !important;
  }

  [data-theme="dark"] .bg-gradient-to-br {
    background: linear-gradient(to bottom right, #374151, #1f2937) !important;
  }

  [data-theme="dark"] .from-gray-50 {
    background: #374151 !important;
  }

  [data-theme="dark"] .to-gray-100 {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .from-gray-800 {
    background: #111827 !important;
  }

  [data-theme="dark"] .to-gray-900 {
    background: #0f172a !important;
  }

  [data-theme="dark"] .border-gray-200 {
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .border-gray-700 {
    border-color: #374151 !important;
  }

  [data-theme="dark"] .text-gray-800 {
    color: #f9fafb !important;
  }

  [data-theme="dark"] .text-gray-200 {
    color: #f9fafb !important;
  }

  [data-theme="dark"] .text-gray-700 {
    color: #d1d5db !important;
  }

  [data-theme="dark"] .text-gray-300 {
    color: #d1d5db !important;
  }

  [data-theme="dark"] .text-gray-600 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] .text-gray-400 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] .bg-white {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .bg-gray-800 {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .text-gray-400 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] .hover\:text-gray-600:hover {
    color: #d1d5db !important;
  }

  [data-theme="dark"] .dark\:text-gray-500 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] .dark\:hover\:text-gray-300:hover {
    color: #d1d5db !important;
  }

  /* Dark mode modal styles */
  [data-theme="dark"] #appointment-modal {
    background-color: rgba(0, 0, 0, 0.8);
  }

  [data-theme="dark"] #appointment-modal .bg-white {
    background: #1f2937 !important;
    color: #f9fafb;
  }

  [data-theme="dark"] #appointment-modal .dark\:bg-gray-800 {
    background: #1f2937 !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-400 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] #appointment-modal .hover\:text-gray-600:hover {
    color: #d1d5db !important;
  }

  [data-theme="dark"] #appointment-modal .dark\:text-gray-500 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] #appointment-modal .dark\:hover\:text-gray-300:hover {
    color: #d1d5db !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-600 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-700 {
    color: #d1d5db !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-300 {
    color: #d1d5db !important;
  }

  [data-theme="dark"] #appointment-modal .bg-gray-50 {
    background: #374151 !important;
  }

  [data-theme="dark"] #appointment-modal .dark\:bg-gray-800 {
    background: #374151 !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-600 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] #appointment-modal .dark\:text-gray-400 {
    color: #9ca3af !important;
  }

  [data-theme="dark"] #appointment-modal .text-green-600 {
    color: #10b981 !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-800 {
    color: #f9fafb !important;
  }

  [data-theme="dark"] #appointment-modal .text-gray-200 {
    color: #f9fafb !important;
  }


  /* Dark mode button styles */
  [data-theme="dark"] .btn {
    background: #4b5563;
    border-color: #4b5563;
    color: #f9fafb;
  }

  [data-theme="dark"] .btn:hover {
    background: #6b7280;
    border-color: #6b7280;
  }

  [data-theme="dark"] .btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
  }

  [data-theme="dark"] .btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
  }

  [data-theme="dark"] .btn-outline {
    background: transparent;
    border-color: #4b5563;
    color: #f9fafb;
  }

  [data-theme="dark"] .btn-outline:hover {
    background: #4b5563;
    border-color: #4b5563;
  }

  /* Dark mode card styles */
  [data-theme="dark"] .card {
    background: #1f2937;
    border-color: #4b5563;
    color: #f9fafb;
  }

  /* Dark mode text styles */
  [data-theme="dark"] h1, 
  [data-theme="dark"] h2, 
  [data-theme="dark"] h3, 
  [data-theme="dark"] h4, 
  [data-theme="dark"] h5, 
  [data-theme="dark"] h6 {
    color: #f9fafb !important;
  }

  [data-theme="dark"] p {
    color: #d1d5db !important;
  }

  [data-theme="dark"] .text-sm {
    color: #9ca3af !important;
  }

  /* Dark mode section styles */
  [data-theme="dark"] section {
    color: #f9fafb;
  }

  [data-theme="dark"] .text-2xl {
    color: #f9fafb !important;
  }

  [data-theme="dark"] .font-semibold {
    color: #f9fafb !important;
  }

  [data-theme="dark"] .leading-tight {
    color: #f9fafb !important;
  }

  /* Dark mode link styles for better readability */
  [data-theme="dark"] a {
    color: #60a5fa !important; /* Light blue for links */
  }

  [data-theme="dark"] a:hover {
    color: #93c5fd !important; /* Lighter blue on hover */
  }

  [data-theme="dark"] a:visited {
    color: #a78bfa !important; /* Light purple for visited links */
  }

  [data-theme="dark"] a:active {
    color: #fbbf24 !important; /* Light yellow for active links */
  }

  /* Specific calendar link styles */
  [data-theme="dark"] .fc-daygrid-day-number {
    color: #60a5fa !important; /* Light blue for day numbers */
  }

  [data-theme="dark"] .fc-daygrid-day-number:hover {
    color: #93c5fd !important; /* Lighter blue on hover */
  }

  [data-theme="dark"] .fc-col-header-cell a {
    color: #60a5fa !important; /* Light blue for header links */
  }

  [data-theme="dark"] .fc-col-header-cell a:hover {
    color: #93c5fd !important; /* Lighter blue on hover */
  }

  /* Button links in dark mode */
  [data-theme="dark"] .btn {
    color: #f9fafb !important;
  }

  [data-theme="dark"] .btn:hover {
    color: #ffffff !important;
  }

  /* Text links in content */
  [data-theme="dark"] .text-blue-600 {
    color: #60a5fa !important;
  }

  [data-theme="dark"] .text-blue-500 {
    color: #60a5fa !important;
  }

  [data-theme="dark"] .text-blue-400 {
    color: #93c5fd !important;
  }

  /* Underlined links */
  [data-theme="dark"] a[style*="text-decoration: underline"] {
    color: #60a5fa !important;
    text-decoration: underline !important;
  }

  [data-theme="dark"] a[style*="text-decoration: underline"]:hover {
    color: #93c5fd !important;
  }

  /* Additional dark mode styles for calendar elements */
  [data-theme="dark"] .fc-scrollgrid {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-scrollgrid-sync-table {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .fc-scrollgrid-section {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-scrollgrid-section-header {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-scrollgrid-section-body {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-scrollgrid-section-footer {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  /* Dark mode for all-day slot */
  [data-theme="dark"] .fc-timegrid-slot-label {
    background: #1f2937 !important;
    color: #9ca3af !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-timegrid-axis {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-timegrid-axis-cushion {
    background: #374151 !important;
    color: #9ca3af !important;
  }

  /* Dark mode for day grid */
  [data-theme="dark"] .fc-daygrid-body {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .fc-daygrid-day-frame {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-daygrid-day-bg {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .fc-daygrid-day-events {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .fc-daygrid-day-top {
    background: #1f2937 !important;
  }

  /* Dark mode for time grid */
  [data-theme="dark"] .fc-timegrid-body {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .fc-timegrid-slot {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-timegrid-slot-minor {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-timegrid-slot-major {
    background: #111827 !important;
    border-color: #4b5563 !important;
  }

  /* Dark mode for table elements */
  [data-theme="dark"] .fc table {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc table td {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc table th {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #d1d5db !important;
  }

  /* Dark mode for scrollbars */
  [data-theme="dark"] .fc-scroller::-webkit-scrollbar {
    background: #1f2937 !important;
  }

  [data-theme="dark"] .fc-scroller::-webkit-scrollbar-thumb {
    background: #4b5563 !important;
  }

  [data-theme="dark"] .fc-scroller::-webkit-scrollbar-track {
    background: #1f2937 !important;
  }

  /* Dark mode for more elements */
  [data-theme="dark"] .fc-list {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-list-day-cushion {
    background: #374151 !important;
    color: #d1d5db !important;
  }

  [data-theme="dark"] .fc-list-event {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  [data-theme="dark"] .fc-list-event:hover {
    background: #374151 !important;
  }

  /* Dark mode for popover */
  [data-theme="dark"] .fc-popover {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
  }

  [data-theme="dark"] .fc-popover-header {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
  }

  [data-theme="dark"] .fc-popover-body {
    background: #1f2937 !important;
    color: #f9fafb !important;
  }

  /* Dark mode for more day elements */
  [data-theme="dark"] .fc-daygrid-day-number {
    color: #60a5fa !important;
    background: transparent !important;
  }

  [data-theme="dark"] .fc-daygrid-day-number:hover {
    color: #93c5fd !important;
  }

  /* Dark mode for today highlighting */
  [data-theme="dark"] .fc-daygrid-day.fc-day-today {
    background: #374151 !important;
  }

  [data-theme="dark"] .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    color: #fbbf24 !important;
    font-weight: 600 !important;
  }

  /* Dark mode for other states */
  [data-theme="dark"] .fc-daygrid-day.fc-day-past {
    background: #111827 !important;
  }

  [data-theme="dark"] .fc-daygrid-day.fc-day-future {
    background: #1f2937 !important;
  }

  /* Dark mode for event containers */
  [data-theme="dark"] .fc-event-container {
    background: transparent !important;
  }

  [data-theme="dark"] .fc-event-container:hover {
    background: rgba(75, 85, 99, 0.1) !important;
  }

  /* FullCalendar specific overrides for proper height */
  .fc-timegrid-slot-minor {
    height: 4rem !important;
    min-height: 4rem !important;
  }

  .fc-timegrid-slot-major {
    height: 4rem !important;
    min-height: 4rem !important;
  }

  .fc-timegrid-slot-segment {
    height: 4rem !important;
    min-height: 4rem !important;
  }

  /* Ensure events fit properly in time slots */
  .fc-timegrid-event-harness {
    height: 4rem !important;
    min-height: 4rem !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow: hidden !important;
  }
  
  .fc-timegrid-event-harness-inset {
    height: 4rem !important;
    min-height: 4rem !important;
    margin: 4px 0 0 4px !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow: hidden !important;
  }

  /* Remove all margins from event containers */
  .fc-event-harness {
    margin: 0 !important;
    padding: 0 !important;
  }
  
  .fc-event-harness-inset {
    margin: 0 !important;
    padding: 0 !important;
  }
  
  /* Day grid event harness with side margins, border and rounded corners */
  .fc-daygrid-event-harness {
    margin-left: 4px !important;
    margin-right: 4px !important;
    margin-bottom: 2px !important;
    border: 1px solid #000000 !important;
    border-radius: 0.375rem !important;
    width: calc(100% - 8px) !important;
  }
  
  /* Dark mode border for day grid event harness */
  .dark .fc-daygrid-event-harness {
    border: 1px solid #ffffff !important;
    border-radius: 0.375rem !important;
    width: calc(100% - 8px) !important;
  }

  .dark .fc-button {
    background: #4b5563;
    border-color: #4b5563;
  }

  .dark .fc-button:hover {
    background: #6b7280;
    border-color: #6b7280;
  }

  /* Clean calendar container */
  .modern-calendar {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
  }

  .dark .modern-calendar {
    background: #1f2937;
    border-color: #4b5563;
  }

  /* Clean responsive design */
  @media (max-width: 1024px) {
    .fc-toolbar {
      flex-direction: column;
      gap: 1rem;
      padding: 1rem 0;
    }

    .fc-toolbar-chunk {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
    }

    .fc-button {
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      margin: 0.25rem;
    }

    .fc-toolbar-title {
      font-size: 1.25rem;
      text-align: center;
    }

    .fc-event {
      font-size: 0.75rem;
      padding: 0.25rem 0.5rem;
      min-height: 2.5rem !important;
      max-height: 2.5rem !important;
    }

    .fc-event-title {
      font-size: 0.75rem;
    }

    .fc-timegrid-slot {
      height: 3rem !important;
      min-height: 3rem !important;
    }

    .fc-timegrid tr {
      height: 3rem !important;
      min-height: 3rem !important;
    }

    .fc-timegrid td {
      height: 3rem !important;
      min-height: 3rem !important;
    }

    .modern-calendar {
      padding: 1rem;
    }
  }

  @media (max-width: 768px) {
    .fc-toolbar {
      flex-direction: column;
      gap: 0.75rem;
      padding: 0.75rem 0;
    }

    .fc-toolbar-chunk {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
    }

    .fc-button {
      padding: 0.4rem 0.8rem;
      font-size: 0.8rem;
      margin: 0.2rem;
    }

    .fc-toolbar-title {
      font-size: 1.1rem;
      text-align: center;
    }

    .fc-event {
      font-size: 0.7rem;
      padding: 0.2rem 0.4rem;
      min-height: 2rem !important;
      max-height: 2rem !important;
    }

    .fc-event-title {
      font-size: 0.7rem;
    }

    .fc-timegrid-slot {
      height: 2.5rem !important;
      min-height: 2.5rem !important;
    }

    .fc-timegrid tr {
      height: 2.5rem !important;
      min-height: 2.5rem !important;
    }

    .fc-timegrid td {
      height: 2.5rem !important;
      min-height: 2.5rem !important;
    }

    .fc-timegrid-slot-label {
      font-size: 0.7rem;
      padding: 0.25rem;
    }

    .fc-col-header-cell {
      font-size: 0.8rem;
      padding: 0.5rem 0.25rem;
    }

    .modern-calendar {
      padding: 0.5rem;
    }
  }

  @media (max-width: 480px) {
    .fc-toolbar {
      flex-direction: column;
      gap: 0.5rem;
      padding: 0.5rem 0;
    }

    .fc-toolbar-chunk {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
    }

    .fc-button {
      padding: 0.3rem 0.6rem;
      font-size: 0.75rem;
      margin: 0.1rem;
    }

    .fc-toolbar-title {
      font-size: 1rem;
      text-align: center;
    }

    .fc-event {
      font-size: 0.65rem;
      padding: 0.15rem 0.3rem;
      min-height: 1.8rem !important;
      max-height: 1.8rem !important;
    }

    .fc-event-title {
      font-size: 0.65rem;
    }

    .fc-timegrid-slot {
      height: 2rem !important;
      min-height: 2rem !important;
    }

    .fc-timegrid tr {
      height: 2rem !important;
      min-height: 2rem !important;
    }

    .fc-timegrid td {
      height: 2rem !important;
      min-height: 2rem !important;
    }

    .fc-timegrid-slot-label {
      font-size: 0.65rem;
      padding: 0.2rem;
    }

    .fc-col-header-cell {
      font-size: 0.75rem;
      padding: 0.4rem 0.2rem;
    }

    .fc-daygrid-day-number {
      font-size: 0.8rem;
      padding: 0.3rem;
    }

    .modern-calendar {
      padding: 0.25rem;
    }
  }
</style>

@section('content')
<section class="flex flex-wrap items-center justify-between gap-3">
  <div>
    <h1 class="text-2xl font-semibold leading-tight">Agenda</h1>
    <p class="text-sm text-muted dark:text-muted-dark">Bekijk al je afspraken en interviews in een overzichtelijke kalender.</p>
  </div>
</section>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />

<!-- FullCalendar Container -->
<div class="card p-8">
  <div id="calendar" class="modern-calendar"></div>

</div>

<!-- FullCalendar JavaScript -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded, initializing calendar...');

  const calendarEl = document.getElementById('calendar');
  console.log('Calendar element:', calendarEl);

  if (!calendarEl) {
    console.error('Calendar element not found!');
    return;
  }

  const calendar = new FullCalendar.Calendar(calendarEl, {
    // Locale settings
    locale: 'nl',
    firstDay: 1, // Start week on Monday

    // Initial view - responsive
    initialView: window.innerWidth < 768 ? 'dayGridMonth' : 'timeGridWeek',

    // Header toolbar - responsive
    headerToolbar: {
      left: window.innerWidth < 480 ? 'prev,next' : 'prev,next today',
      center: 'title',
      right: window.innerWidth < 480 ? 'dayGridMonth,timeGridWeek' : 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    // Button text
    buttonText: {
      today: 'Vandaag',
      month: 'Maand',
      week: 'Week',
      day: 'Dag'
    },

    // Time grid settings
    slotMinTime: '08:00:00',
    slotMaxTime: '20:00:00',
    slotDuration: '01:00:00',
    slotLabelInterval: '01:00:00',

    // Event settings
    events: function(info, successCallback, failureCallback) {
      console.log('Fetching events for:', info.start, 'to', info.end);

      // Fallback events if API fails
      const fallbackEvents = [
        {
          id: 1,
          title: 'Interview met Jan de Vries',
          start: new Date().toISOString(),
          end: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
          color: '#3b82f6',
          extendedProps: {
            candidate_name: 'Jan de Vries',
            location: 'Kantoor Amsterdam',
            type: 'interview',
            status: 'scheduled'
          }
        },
        {
          id: 2,
          title: 'Uren inleveren',
          start: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
          end: new Date(Date.now() + 24 * 60 * 60 * 1000 + 30 * 60 * 1000).toISOString(),
          color: '#10b981',
          extendedProps: {
            candidate_name: 'Sarah van Dijk',
            location: 'Online',
            type: 'meeting',
            status: 'scheduled'
          }
        }
      ];

      // Try to fetch events from Laravel backend
      fetch('{{ route("agenda.events") }}', {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
        }
      })
      .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        console.log('Events received:', data);
        if (Array.isArray(data) && data.length > 0) {
          successCallback(data);
        } else {
          console.log('No events from API, using fallback');
          successCallback(fallbackEvents);
        }
      })
      .catch(error => {
        console.error('Error fetching events:', error);
        console.log('Using fallback events');
        successCallback(fallbackEvents);
      });
    },

    // Event click handler
    eventClick: function(info) {
      console.log('Event clicked:', info.event);
      showAppointmentDetails(info.event);
    },

    // Date click handler (for creating new events)
    dateClick: function(info) {
      console.log('Date clicked:', info.dateStr);
    },

    // Event display settings
    eventDisplay: 'block',
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },

    // Height settings
    height: 'auto',

    // Responsive settings
    aspectRatio: 1.8,

    // Event colors
    eventColor: '#3b82f6',

    // Business hours
    businessHours: {
      daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
      startTime: '09:00',
      endTime: '17:00'
    },

    // Weekends
    weekends: true,

    // All day slot
    allDaySlot: true,

    // Event overlap
    eventOverlap: false,

    // Event constraint
    eventConstraint: {
      start: '08:00',
      end: '20:00'
    }
  });

  console.log('Calendar created, rendering...');
  calendar.render();
  console.log('Calendar rendered successfully');

  // Test if calendar is working
  setTimeout(() => {
    const calendarContainer = document.querySelector('.fc');
    if (calendarContainer) {
      console.log('✅ Calendar container found and rendered');
    } else {
      console.error('❌ Calendar container not found');
    }
  }, 1000);

  // Global calendar reference
  window.calendar = calendar;
});

// Appointment details modal
function showAppointmentDetails(event) {
  const appointment = {
    id: event.id,
    title: event.title,
    start: event.start,
    end: event.end,
    extendedProps: event.extendedProps
  };

  const content = `
    <div class="mb-4">
      <h3 class="text-lg font-semibold mb-2">${appointment.title}</h3>
      <div class="space-y-2 text-sm text-muted dark:text-muted-dark">
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          ${appointment.start.toLocaleTimeString('nl-NL', {hour: '2-digit', minute: '2-digit'})} - ${appointment.end.toLocaleTimeString('nl-NL', {hour: '2-digit', minute: '2-digit'})}
        </div>
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          ${appointment.extendedProps.location || 'Locatie niet opgegeven'}
        </div>
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          ${appointment.extendedProps.candidate_name || 'Kandidaat niet opgegeven'}
        </div>
      </div>
    </div>
    <div class="flex space-x-3">
      <button onclick="viewAppointmentDetails('${appointment.id}')" class="btn btn-primary flex-1">Details</button>
    </div>
  `;

  document.getElementById('appointment-content').innerHTML = content;
  document.getElementById('appointment-modal').classList.remove('hidden');
  document.getElementById('appointment-modal').classList.add('flex');
}

function hideAppointmentModal() {
  document.getElementById('appointment-modal').classList.add('hidden');
  document.getElementById('appointment-modal').classList.remove('flex');
}

</script>

<!-- Appointment Detail Modal -->
<div id="appointment-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative">
    <button onclick="hideAppointmentModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>

    <div id="appointment-content">
      <!-- Content will be loaded here -->
    </div>
  </div>
</div>

<!-- FullCalendar JavaScript -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  console.log('DOM loaded, initializing calendar...');

  const calendarEl = document.getElementById('calendar');
  console.log('Calendar element:', calendarEl);

  if (!calendarEl) {
    console.error('Calendar element not found!');
    return;
  }

  const calendar = new FullCalendar.Calendar(calendarEl, {
    // Locale settings
    locale: 'nl',
    firstDay: 1, // Start week on Monday

    // Initial view - responsive
    initialView: window.innerWidth < 768 ? 'dayGridMonth' : 'timeGridWeek',

    // Header toolbar - responsive
    headerToolbar: {
      left: window.innerWidth < 480 ? 'prev,next' : 'prev,next today',
      center: 'title',
      right: window.innerWidth < 480 ? 'dayGridMonth,timeGridWeek' : 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    // Button text
    buttonText: {
      today: 'Vandaag',
      month: 'Maand',
      week: 'Week',
      day: 'Dag'
    },

    // Time grid settings
    slotMinTime: '08:00:00',
    slotMaxTime: '20:00:00',
    slotDuration: '01:00:00',
    slotLabelInterval: '01:00:00',

    // Event settings
    events: function(info, successCallback, failureCallback) {
      console.log('Fetching events for:', info.start, 'to', info.end);

      // Fallback events if API fails
      const fallbackEvents = [
        {
          id: 1,
          title: 'Interview met Jan de Vries',
          start: new Date().toISOString(),
          end: new Date(Date.now() + 60 * 60 * 1000).toISOString(),
          color: '#3b82f6',
          extendedProps: {
            candidate_name: 'Jan de Vries',
            location: 'Kantoor Amsterdam',
            type: 'interview',
            status: 'scheduled'
          }
        },
        {
          id: 2,
          title: 'Uren inleveren',
          start: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString(),
          end: new Date(Date.now() + 24 * 60 * 60 * 1000 + 30 * 60 * 1000).toISOString(),
          color: '#10b981',
          extendedProps: {
            candidate_name: 'Sarah van Dijk',
            location: 'Online',
            type: 'meeting',
            status: 'scheduled'
          }
        }
      ];

      // Try to fetch events from Laravel backend
      fetch('{{ route("agenda.events") }}', {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
        }
      })
      .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        console.log('Events received:', data);
        if (Array.isArray(data) && data.length > 0) {
          successCallback(data);
        } else {
          console.log('No events from API, using fallback');
          successCallback(fallbackEvents);
        }
      })
      .catch(error => {
        console.error('Error fetching events:', error);
        console.log('Using fallback events');
        successCallback(fallbackEvents);
      });
    },

    // Event click handler
    eventClick: function(info) {
      console.log('Event clicked:', info.event);
      showAppointmentDetails(info.event);
    },

    // Date click handler (for creating new events)
    dateClick: function(info) {
      console.log('Date clicked:', info.dateStr);
    },

    // Event display settings
    eventDisplay: 'block',
    eventTimeFormat: {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    },

    // Height settings
    height: 'auto',

    // Responsive settings
    aspectRatio: 1.8,

    // Event colors
    eventColor: '#3b82f6',

    // Business hours
    businessHours: {
      daysOfWeek: [1, 2, 3, 4, 5], // Monday - Friday
      startTime: '09:00',
      endTime: '17:00'
    },

    // Weekends
    weekends: true,

    // All day slot
    allDaySlot: true,

    // Event overlap
    eventOverlap: false,

    // Event constraint
    eventConstraint: {
      start: '08:00',
      end: '20:00'
    }
  });

  console.log('Calendar created, rendering...');
  calendar.render();
  console.log('Calendar rendered successfully');

  // Test if calendar is working
  setTimeout(() => {
    const calendarContainer = document.querySelector('.fc');
    if (calendarContainer) {
      console.log('✅ Calendar container found and rendered');
    } else {
      console.error('❌ Calendar container not found');
    }
  }, 1000);

  // Global calendar reference
  window.calendar = calendar;
});

// Appointment details modal
function showAppointmentDetails(event) {
  const appointment = {
    id: event.id,
    title: event.title,
    start: event.start,
    end: event.end,
    extendedProps: event.extendedProps
  };

  const content = `
    <div class="mb-4">
      <h3 class="text-lg font-semibold mb-2">${appointment.title}</h3>
      <div class="space-y-2 text-sm text-muted dark:text-muted-dark">
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          ${appointment.start.toLocaleTimeString('nl-NL', {hour: '2-digit', minute: '2-digit'})} - ${appointment.end.toLocaleTimeString('nl-NL', {hour: '2-digit', minute: '2-digit'})}
        </div>
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          ${appointment.extendedProps.location || 'Locatie niet opgegeven'}
        </div>
        <div class="flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          ${appointment.extendedProps.candidate_name || 'Kandidaat niet opgegeven'}
        </div>
      </div>
    </div>
    <div class="flex space-x-3">
      <button onclick="viewAppointmentDetails('${appointment.id}')" class="btn btn-primary flex-1">Details</button>
    </div>
  `;

  document.getElementById('appointment-content').innerHTML = content;
  document.getElementById('appointment-modal').classList.remove('hidden');
  document.getElementById('appointment-modal').classList.add('flex');
}

function hideAppointmentModal() {
  document.getElementById('appointment-modal').classList.add('hidden');
  document.getElementById('appointment-modal').classList.remove('flex');
}

</script>

<!-- Responsive JavaScript for real-time updates -->
<script>
// Responsive handler for real-time updates
function handleResponsiveUpdate() {
  const width = window.innerWidth;
  const calendar = window.calendar;
  
  if (!calendar) return;
  
  // Update aspect ratio based on screen size
  let newAspectRatio = 1.8;
  if (width < 768) {
    newAspectRatio = 1.2;
  }
  
  // Update calendar options
  calendar.setOption('aspectRatio', newAspectRatio);
  
  // Update dayMaxEvents based on screen size
  let dayMaxEvents = 3;
  if (width < 768) {
    dayMaxEvents = 2;
  }
  calendar.setOption('dayMaxEvents', dayMaxEvents);
  
  // Update eventMaxStack based on screen size
  let eventMaxStack = 2;
  if (width < 480) {
    eventMaxStack = 1;
  }
  calendar.setOption('eventMaxStack', eventMaxStack);
  
  // Update header toolbar based on screen size
  let headerToolbar = {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  };
  
  if (width < 480) {
    headerToolbar = {
      left: 'prev,next',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    };
  }
  
  calendar.setOption('headerToolbar', headerToolbar);
  
  // Force calendar to re-render with new settings
  calendar.render();
  
  console.log('Responsive update applied:', {
    width,
    aspectRatio: newAspectRatio,
    dayMaxEvents,
    eventMaxStack
  });
}

// Add resize event listener
window.addEventListener('resize', function() {
  // Debounce resize events to avoid excessive updates
  clearTimeout(window.resizeTimeout);
  window.resizeTimeout = setTimeout(handleResponsiveUpdate, 150);
});

// Initial responsive setup after calendar is loaded
setTimeout(handleResponsiveUpdate, 2000);
</script>

<!-- LocalStorage for view persistence -->
<script>
// LocalStorage functions for view persistence
window.getInitialView = function() {
  const savedView = localStorage.getItem('agenda-view');
  if (savedView) {
    console.log('✅ Restored saved view:', savedView);
    return savedView;
  }
  // Default responsive view
  const defaultView = window.innerWidth < 768 ? 'dayGridMonth' : 'timeGridWeek';
  console.log('📱 Using default view:', defaultView);
  return defaultView;
};

window.saveView = function(view) {
  localStorage.setItem('agenda-view', view);
  console.log('💾 View saved to localStorage:', view);
};

// Override initialView after calendar is created
setTimeout(function() {
  if (window.calendar) {
    const savedView = localStorage.getItem('agenda-view');
    if (savedView && savedView !== window.calendar.view.type) {
      console.log('🔄 Changing to saved view:', savedView);
      window.calendar.changeView(savedView);
    }
    
    // Add event listener for view changes
    const originalChangeView = window.calendar.changeView.bind(window.calendar);
    window.calendar.changeView = function(viewType) {
      originalChangeView(viewType);
      window.saveView(viewType);
    };
  }
}, 1000);

// Appointment action functions
function viewAppointmentDetails(appointmentId) {
  console.log('Viewing details for appointment:', appointmentId);
  
  // Close the current modal
  hideAppointmentModal();
  
  // Show detailed modal with more information
  showDetailsModal(appointmentId);
}


// Show details modal with more information
function showDetailsModal(appointmentId) {
  const detailsContent = `
    <div class="mb-4">
      <h3 class="text-lg font-semibold mb-4">Afspraak Details</h3>
      <div class="space-y-4">
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
          <h4 class="font-medium mb-2">Afspraak Details</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Datum:</span>
              <span>${new Date(appointment.start).toLocaleDateString('nl-NL', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Tijd:</span>
              <span>${new Date(appointment.start).toLocaleTimeString('nl-NL', {hour: '2-digit', minute: '2-digit'})} - ${new Date(appointment.end).toLocaleTimeString('nl-NL', {hour: '2-digit', minute: '2-digit'})}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Status:</span>
              <span class="text-green-600">${appointment.extendedProps.status || 'Gepland'}</span>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
          <h4 class="font-medium mb-2">Bedrijfsinformatie</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Bedrijf:</span>
              <span>${appointment.extendedProps.company_name || 'Onbekend bedrijf'}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Adres:</span>
              <span>${appointment.extendedProps.company_address || 'Adres niet beschikbaar'}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Contactpersoon:</span>
              <span>${appointment.extendedProps.interviewer_name || 'Onbekend'}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Telefoon:</span>
              <span>${appointment.extendedProps.company_phone || 'Niet beschikbaar'}</span>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
          <h4 class="font-medium mb-2">Afspraak Contact</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Jouw contactpersoon:</span>
              <span>${appointment.extendedProps.interviewer_name || 'Onbekend'}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Email:</span>
              <span>${appointment.extendedProps.interviewer_email || 'Niet beschikbaar'}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Functie:</span>
              <span>${appointment.extendedProps.vacancy_title || 'Onbekende functie'}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600 dark:text-gray-400">Locatie:</span>
              <span>${appointment.extendedProps.location || 'Locatie niet opgegeven'}</span>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
          <h4 class="font-medium mb-2">Notities</h4>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            ${appointment.extendedProps.notes || 'Geen notities beschikbaar.'}
          </p>
        </div>
      </div>
    </div>
    <div class="flex space-x-3">
      <button onclick="hideDetailsModal()" class="btn btn-primary flex-1">Sluiten</button>
    </div>
  `;
  
  document.getElementById('appointment-content').innerHTML = detailsContent;
  document.getElementById('appointment-modal').classList.remove('hidden');
  document.getElementById('appointment-modal').classList.add('flex');
}

// Hide details modal
function hideDetailsModal() {
  document.getElementById('appointment-modal').classList.add('hidden');
  document.getElementById('appointment-modal').classList.remove('flex');
}

// Add ESC key functionality to close modal
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    const modal = document.getElementById('appointment-modal');
    if (modal && !modal.classList.contains('hidden')) {
      hideAppointmentModal();
    }
  }
});
</script>
@endsection
