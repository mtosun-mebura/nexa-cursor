@extends('frontend.layouts.dashboard')

@section('title', 'Agenda - NEXA Skillmatching')

@section('content')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<style>
  .fc {
    font-family: inherit;
  }

  /* Clean toolbar styling - more compact */
  .fc-toolbar {
    margin-bottom: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .fc-toolbar-title {
    font-size: 1.125rem;
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
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
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

  /* Event styling: hoogte volgt duur in week/dag-view (geen vaste max) */
  .fc-event {
    border-radius: 0.25rem;
    border: 1px solid #ffffff !important;
    padding: 0.25rem 0.375rem 0.2rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin: 0 !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    transition: all 0.2s ease;
    min-height: 2rem;
    display: flex;
    align-items: flex-start;
    overflow: hidden !important;
    position: relative;
    top: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    background: #3b82f6 !important;
    color: white !important;
  }

  .fc-event:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
  }

  .fc-event-title {
    font-weight: 600;
    line-height: 1.1;
    font-size: 0.75rem;
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
    min-height: 1.25rem;
    top: 0 !important;
  }

  /* Time grid: hoogte door FullCalendar op basis van start/end (zoals backend) */
  .fc-timegrid-event {
    margin: 0 !important;
    top: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    height: 100% !important;
    min-height: 2rem !important;
    overflow: hidden !important;
  }
  .fc-timegrid-event .fc-event {
    min-height: 0 !important;
    height: 100% !important;
  }
  .fc-timegrid-event .fc-event-title {
    white-space: normal;
    word-break: break-word;
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
    font-size: 0.8rem;
    padding: 0.375rem;
  }

  /* Clean headers – rij dubbel zo hoog, teksten met Hoofdletter */
  .fc-col-header-cell {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    padding: 0.75rem 0.5rem;
    font-size: 0.8rem;
    border-bottom: 1px solid #e5e7eb;
    min-height: 5.5rem;
    text-align: center;
    text-transform: capitalize;
  }
  .fc-col-header .fc-scrollgrid-sync-inner {
    min-height: 4rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 0.25rem;
    text-transform: capitalize;
  }
  .fc-col-header-day,
  .fc-col-header-line {
    font-weight: 600;
    font-size: 1rem;
    line-height: 1.2;
    text-transform: capitalize;
    padding-bottom: 0.25rem;
  }
  .fc-col-header-date {
    font-size: 0.75rem;
    color: #6b7280;
    line-height: 1.2;
    text-transform: capitalize;
  }
  .dark .fc-col-header-date {
    color: #9ca3af;
  }

  /* Clean time slots with proper height for events */
  .fc-timegrid-slot {
    height: 3rem !important;
    border-bottom: 1px solid #f1f5f9;
    min-height: 3rem !important;
    vertical-align: top !important;
  }

  .fc-timegrid-slot-label {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
    padding: 0.5rem;
    vertical-align: top !important;
    display: block;
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
  .dark .fc-toolbar {
    background: #1f2937;
    border-bottom-color: #4b5563;
  }

  .dark .fc-toolbar-title {
    color: #f9fafb;
  }

  .dark .fc-button {
    background: #4b5563;
    border-color: #4b5563;
    color: #f9fafb;
  }

  .dark .fc-button:hover {
    background: #6b7280;
    border-color: #6b7280;
  }

  .dark .fc-button:focus {
    box-shadow: 0 0 0 3px rgba(75, 85, 99, 0.3);
  }

  .dark .fc-button-primary:not(:disabled):active,
  .dark .fc-button-primary:not(:disabled).fc-button-active {
    background: #374151;
    border-color: #374151;
  }

  .dark .fc-daygrid-day-number {
    color: #d1d5db;
  }

  .dark .fc-col-header-cell {
    background: #374151;
    color: #d1d5db;
    border-bottom-color: #4b5563;
  }

  .dark .fc-timegrid-axis {
    background: #374151;
    border-right-color: #4b5563;
  }

  .dark .fc-timegrid-slot-label {
    color: #9ca3af;
  }

  .dark .fc-scrollgrid {
    border-color: #4b5563;
    background: #1f2937;
  }

  .dark .fc-daygrid-day {
    border-right-color: #4b5563;
    border-bottom-color: #4b5563;
    background: #1f2937;
  }

  .dark .fc-daygrid-day:hover {
    background-color: #374151;
  }

  .dark .fc-timegrid-slot.fc-timegrid-slot-minor {
    background-color: #1f2937;
  }

  .dark .fc-timegrid-slot.fc-timegrid-slot-major {
    background-color: #111827;
    border-bottom-color: #4b5563;
  }

  .dark .fc-event {
    color: white;
    border: 1px solid #ffffff !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  }

  .dark .fc-event:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
  }

  .dark .fc-event-title {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
  }

  .dark .fc-daygrid-event-harness {
    border-color: #ffffff !important;
  }

  .dark .modern-calendar {
    background: #1f2937;
    border-color: #4b5563;
  }

  .dark .card {
    background: #1f2937;
    border-color: #4b5563;
  }

  .dark .text-muted {
    color: #9ca3af !important;
  }

  .dark .text-muted-dark {
    color: #9ca3af !important;
  }

  .dark .bg-gradient-to-br {
    background: linear-gradient(to bottom right, #374151, #1f2937) !important;
  }

  .dark .from-gray-50 {
    background: #374151 !important;
  }

  .dark .to-gray-100 {
    background: #1f2937 !important;
  }

  .dark .from-gray-800 {
    background: #111827 !important;
  }

  .dark .to-gray-900 {
    background: #0f172a !important;
  }

  .dark .border-gray-200 {
    border-color: #4b5563 !important;
  }

  .dark .border-gray-700 {
    border-color: #374151 !important;
  }

  .dark .text-gray-800 {
    color: #f9fafb !important;
  }

  .dark .text-gray-200 {
    color: #f9fafb !important;
  }

  .dark .text-gray-700 {
    color: #d1d5db !important;
  }

  .dark .text-gray-300 {
    color: #d1d5db !important;
  }

  .dark .text-gray-600 {
    color: #9ca3af !important;
  }

  .dark .text-gray-400 {
    color: #9ca3af !important;
  }

  .dark .bg-white {
    background: #1f2937 !important;
  }

  .dark .bg-gray-800 {
    background: #1f2937 !important;
  }

  .dark .text-gray-400 {
    color: #9ca3af !important;
  }

  .dark .hover\:text-gray-600:hover {
    color: #d1d5db !important;
  }

  .dark .dark\:text-gray-500 {
    color: #9ca3af !important;
  }

  .dark .dark\:hover\:text-gray-300:hover {
    color: #d1d5db !important;
  }

  /* Dark mode link styles for better readability */
  .dark a {
    color: #d1d5db !important; /* Light gray for links */
  }

  .dark a:hover {
    color: #f9fafb !important; /* White on hover */
  }

  .dark a:visited {
    color: #d1d5db !important; /* Light gray for visited links */
  }

  .dark a:active {
    color: #f9fafb !important; /* White for active links */
  }

  /* Specific calendar link styles */
  .dark .fc-daygrid-day-number {
    color: #60a5fa !important; /* Light blue for day numbers */
  }

  .dark .fc-daygrid-day-number:hover {
    color: #93c5fd !important; /* Lighter blue on hover */
  }

  .dark .fc-col-header-cell a {
    color: #60a5fa !important; /* Light blue for header links */
  }

  .dark .fc-col-header-cell a:hover {
    color: #93c5fd !important; /* Lighter blue on hover */
  }

  /* Button links in dark mode */
  .dark .btn {
    color: #f9fafb !important;
  }

  .dark .btn:hover {
    color: #ffffff !important;
  }

  /* Text links in content */
  .dark .text-blue-600 {
    color: #60a5fa !important;
  }

  .dark .text-blue-500 {
    color: #60a5fa !important;
  }

  .dark .text-blue-400 {
    color: #93c5fd !important;
  }

  /* Underlined links */
  .dark a[style*="text-decoration: underline"] {
    color: #60a5fa !important;
    text-decoration: underline !important;
  }

  .dark a[style*="text-decoration: underline"]:hover {
    color: #93c5fd !important;
  }

  /* Additional dark mode styles for calendar elements */
  .dark .fc-scrollgrid {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-sync-table {
    background: #1f2937 !important;
  }

  .dark .fc-scrollgrid-section {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-section-header {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-section-body {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-section-footer {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  /* Dark mode for all-day slot */
  .dark .fc-timegrid-slot-label {
    background: #1f2937 !important;
    color: #9ca3af !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-axis {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-axis-cushion {
    background: #374151 !important;
    color: #9ca3af !important;
  }

  /* Dark mode for day grid */
  .dark .fc-daygrid-body {
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day-frame {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-daygrid-day-bg {
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day-events {
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day-top {
    background: #1f2937 !important;
  }

  /* Dark mode for time grid */
  .dark .fc-timegrid-body {
    background: #1f2937 !important;
  }

  .dark .fc-timegrid-slot {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-minor {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-major {
    background: #111827 !important;
    border-color: #4b5563 !important;
  }

  /* Dark mode for table elements */
  .dark .fc table {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc table td {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc table th {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #d1d5db !important;
  }

  /* Dark mode for scrollbars */
  .dark .fc-scroller::-webkit-scrollbar {
    background: #1f2937 !important;
  }

  .dark .fc-scroller::-webkit-scrollbar-thumb {
    background: #4b5563 !important;
  }

  .dark .fc-scroller::-webkit-scrollbar-track {
    background: #1f2937 !important;
  }

  /* Dark mode for more elements */
  .dark .fc-list {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-list-day-cushion {
    background: #374151 !important;
    color: #d1d5db !important;
  }

  .dark .fc-list-event {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-list-event:hover {
    background: #374151 !important;
  }

  /* Dark mode for popover */
  .dark .fc-popover {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
  }

  .dark .fc-popover-header {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
  }

  .dark .fc-popover-body {
    background: #1f2937 !important;
    color: #f9fafb !important;
  }

  /* Dark mode for more day elements */
  .dark .fc-daygrid-day-number {
    color: #60a5fa !important;
    background: transparent !important;
  }

  .dark .fc-daygrid-day-number:hover {
    color: #93c5fd !important;
  }

  /* Dark mode for today highlighting */
  .dark .fc-daygrid-day.fc-day-today {
    background: #374151 !important;
  }

  .dark .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    color: #fbbf24 !important;
    font-weight: 600 !important;
  }

  /* Dark mode for other states */
  .dark .fc-daygrid-day.fc-day-past {
    background: #111827 !important;
  }

  .dark .fc-daygrid-day.fc-day-future {
    background: #1f2937 !important;
  }

  /* Dark mode for event containers */
  .dark .fc-event-container {
    background: transparent !important;
  }

  .dark .fc-event-container:hover {
    background: rgba(75, 85, 99, 0.1) !important;
  }

  /* Additional comprehensive dark mode styles for frontend with !important overrides */
  .dark .fc-toolbar {
    background: #1f2937 !important;
    border-bottom-color: #4b5563 !important;
  }

  .dark .fc-button {
    background: #4b5563 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
  }

  .dark .fc-button:hover {
    background: #6b7280 !important;
    border-color: #6b7280 !important;
  }

  .dark .fc-button:focus {
    box-shadow: 0 0 0 3px rgba(75, 85, 99, 0.3) !important;
  }

  .dark .fc-button-primary:not(:disabled):active,
  .dark .fc-button-primary:not(:disabled).fc-button-active {
    background: #374151 !important;
    border-color: #374151 !important;
  }

  .dark .fc-daygrid-day-number {
    color: #60a5fa !important;
  }

  .dark .fc-col-header-cell {
    background: #374151 !important;
    color: #d1d5db !important;
    border-bottom-color: #4b5563 !important;
  }

  .dark .fc-timegrid-axis {
    background: #374151 !important;
    border-right-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-label {
    color: #9ca3af !important;
  }

  .dark .fc-scrollgrid {
    border-color: #4b5563 !important;
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day {
    border-right-color: #4b5563 !important;
    border-bottom-color: #4b5563 !important;
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day:hover {
    background-color: #374151 !important;
  }

  .dark .fc-timegrid-slot.fc-timegrid-slot-minor {
    background-color: #1f2937 !important;
  }

  .dark .fc-timegrid-slot.fc-timegrid-slot-major {
    background-color: #111827 !important;
    border-bottom-color: #4b5563 !important;
  }

  .dark .fc-event {
    color: white !important;
    border: 1px solid #ffffff !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
  }

  .dark .fc-event:hover {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4) !important;
  }

  .dark .fc-event-title {
    color: white !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5) !important;
  }

  .dark .fc-daygrid-event-harness {
    border-color: #ffffff !important;
  }

  .dark .card {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .text-muted {
    color: #9ca3af !important;
  }

  .dark .text-muted-dark {
    color: #9ca3af !important;
  }

  .dark .bg-gradient-to-br {
    background: linear-gradient(to bottom right, #374151, #1f2937) !important;
  }

  .dark .from-gray-50 {
    background: #374151 !important;
  }

  .dark .to-gray-100 {
    background: #1f2937 !important;
  }

  .dark .from-gray-800 {
    background: #111827 !important;
  }

  .dark .to-gray-900 {
    background: #0f172a !important;
  }

  .dark .border-gray-200 {
    border-color: #4b5563 !important;
  }

  .dark .border-gray-700 {
    border-color: #374151 !important;
  }

  .dark .text-gray-800 {
    color: #f9fafb !important;
  }

  .dark .text-gray-200 {
    color: #f9fafb !important;
  }

  .dark .text-gray-700 {
    color: #d1d5db !important;
  }

  .dark .text-gray-300 {
    color: #d1d5db !important;
  }

  .dark .text-gray-600 {
    color: #9ca3af !important;
  }

  .dark .text-gray-400 {
    color: #9ca3af !important;
  }

  .dark .bg-white {
    background: #1f2937 !important;
  }

  .dark .bg-gray-800 {
    background: #1f2937 !important;
  }

  .dark .hover\:text-gray-600:hover {
    color: #d1d5db !important;
  }

  .dark .dark\:text-gray-500 {
    color: #9ca3af !important;
  }

  .dark .dark\:hover\:text-gray-300:hover {
    color: #d1d5db !important;
  }

  /* Force dark mode overrides for all calendar elements */
  .dark .fc {
    background: #1f2937 !important;
    color: #f9fafb !important;
  }

  .dark .fc table {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc table td {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc table th {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #d1d5db !important;
  }

  .dark .fc-scrollgrid-sync-table {
    background: #1f2937 !important;
  }

  .dark .fc-scrollgrid-section {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-section-header {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-section-body {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid-section-footer {
    background: #374151 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-daygrid-body {
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day-frame {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-daygrid-day-bg {
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day-events {
    background: #1f2937 !important;
  }

  .dark .fc-daygrid-day-top {
    background: #1f2937 !important;
  }

  .dark .fc-timegrid-body {
    background: #1f2937 !important;
  }

  .dark .fc-timegrid-slot {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-minor {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-major {
    background: #111827 !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-segment {
    background: #1f2937 !important;
  }

  .dark .fc-timegrid-event-harness {
    background: transparent !important;
  }

  .dark .fc-timegrid-event-harness-inset {
    background: transparent !important;
  }

  .dark .fc-event-harness {
    background: transparent !important;
  }

  .dark .fc-event-harness-inset {
    background: transparent !important;
  }

  .dark .fc-event-container {
    background: transparent !important;
  }

  .dark .fc-event-container:hover {
    background: rgba(75, 85, 99, 0.1) !important;
  }

  /* Dark mode modal styles */
  .dark #appointment-modal {
    background-color: rgba(0, 0, 0, 0.8);
  }

  .dark #appointment-modal .bg-white {
    background: #1f2937 !important;
    color: #f9fafb;
  }

  .dark #appointment-modal .dark\:bg-gray-800 {
    background: #1f2937 !important;
  }

  .dark #appointment-modal .text-gray-400 {
    color: #9ca3af !important;
  }

  .dark #appointment-modal .hover\:text-gray-600:hover {
    color: #d1d5db !important;
  }

  .dark #appointment-modal .dark\:text-gray-500 {
    color: #9ca3af !important;
  }

  .dark #appointment-modal .dark\:hover\:text-gray-300:hover {
    color: #d1d5db !important;
  }

  .dark #appointment-modal .text-gray-600 {
    color: #9ca3af !important;
  }

  .dark #appointment-modal .text-gray-700 {
    color: #d1d5db !important;
  }

  .dark #appointment-modal .text-gray-300 {
    color: #d1d5db !important;
  }

  .dark #appointment-modal .bg-gray-50 {
    background: #374151 !important;
  }

  .dark #appointment-modal .dark\:bg-gray-800 {
    background: #374151 !important;
  }

  .dark #appointment-modal .text-gray-600 {
    color: #9ca3af !important;
  }

  .dark #appointment-modal .dark\:text-gray-400 {
    color: #9ca3af !important;
  }

  .dark #appointment-modal .text-green-600 {
    color: #10b981 !important;
  }

  .dark #appointment-modal .text-gray-800 {
    color: #f9fafb !important;
  }

  .dark #appointment-modal .text-gray-200 {
    color: #f9fafb !important;
  }


  /* Dark mode button styles */
  .dark .btn {
    background: #4b5563;
    border-color: #4b5563;
    color: #f9fafb;
  }

  .dark .btn:hover {
    background: #6b7280;
    border-color: #6b7280;
  }

  .dark .btn-primary {
    background: #3b82f6;
    border-color: #3b82f6;
  }

  .dark .btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
  }

  .dark .btn-outline {
    background: transparent;
    border-color: #4b5563;
    color: #f9fafb;
  }

  .dark .btn-outline:hover {
    background: #4b5563;
    border-color: #4b5563;
  }

  /* Dark mode card styles */
  .dark .card {
    background: #1f2937;
    border-color: #4b5563;
    color: #f9fafb;
  }

  /* Dark mode text styles */
  .dark h1, 
  .dark h2, 
  .dark h3, 
  .dark h4, 
  .dark h5, 
  .dark h6 {
    color: #f9fafb !important;
  }

  .dark p {
    color: #d1d5db !important;
  }

  .dark .text-sm {
    color: #9ca3af !important;
  }

  /* Dark mode section styles */
  .dark section {
    color: #f9fafb;
  }

  .dark .text-2xl {
    color: #f9fafb !important;
  }

  .dark .font-semibold {
    color: #f9fafb !important;
  }

  .dark .leading-tight {
    color: #f9fafb !important;
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

  /* Event harness: geen vaste hoogte – FullCalendar zet hoogte via inline style op basis van start/end */
  .fc-timegrid-event-harness {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    overflow: hidden !important;
  }
  
  .fc-timegrid-event-harness-inset {
    margin: 4px 0 0 4px !important;
    padding: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
    min-height: 100% !important;
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
  
  /* Day grid event harness –zelfde border als timegrid (wit, afgerond) */
  .fc-daygrid-event-harness {
    margin-left: 4px !important;
    margin-right: 4px !important;
    margin-bottom: 2px !important;
    border: 1px solid #ffffff !important;
    border-radius: 0.25rem !important;
    width: calc(100% - 8px) !important;
  }
  
  .dark .fc-daygrid-event-harness {
    border: 1px solid #ffffff !important;
    border-radius: 0.25rem !important;
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
      min-height: 2rem;
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
      min-height: 2rem;
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
      min-height: 1.5rem;
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

  /* ULTRA-SPECIFIC DARK MODE OVERRIDES - MUST BE LAST */
  .dark .fc,
  .dark .fc * {
    background: #1f2937 !important;
    color: #f9fafb !important;
  }

  .dark .fc-toolbar,
  .dark .fc-toolbar * {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-button,
  .dark .fc-button * {
    background: #4b5563 !important;
    color: #f9fafb !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-button:hover {
    background: #6b7280 !important;
    border-color: #6b7280 !important;
  }

  .dark .fc-col-header-cell,
  .dark .fc-col-header-cell * {
    background: #374151 !important;
    color: #d1d5db !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-daygrid-day,
  .dark .fc-daygrid-day * {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-daygrid-day-number {
    color: #60a5fa !important;
  }

  .dark .fc-timegrid-slot,
  .dark .fc-timegrid-slot * {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-timegrid-slot-major {
    background: #111827 !important;
  }

  .dark .fc-timegrid-axis,
  .dark .fc-timegrid-axis * {
    background: #374151 !important;
    color: #9ca3af !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-scrollgrid,
  .dark .fc-scrollgrid * {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-color: #4b5563 !important;
  }

  .dark .fc-event,
  .dark .fc-event * {
    color: white !important;
    border-color: #ffffff !important;
  }

  .dark .fc-event-title {
    color: white !important;
  }

  .dark .card {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
    color: #f9fafb !important;
  }

  .dark .modern-calendar {
    background: #1f2937 !important;
    border-color: #4b5563 !important;
  }

  /* Override any remaining light mode styles */
  .dark .fc table,
  .dark .fc table td,
  .dark .fc table th {
    background: #1f2937 !important;
    color: #f9fafb !important;
    border-color: #4b5563 !important;
  }

  .dark .fc table th {
    background: #374151 !important;
    color: #d1d5db !important;
  }
</style>

@section('content')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />

<style>
  /* Agenda layout zoals backend */
  .agenda-container {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
  }
  .agenda-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.5rem 0;
    border-bottom: 1px solid #e5e7eb;
  }
  .dark .agenda-header {
    border-bottom-color: #4b5563;
  }
  .agenda-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  }
  .dark .agenda-title {
    color: #f9fafb;
  }
  .agenda-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
  }
  .view-buttons-container {
    display: flex;
    gap: 0.5rem;
    min-width: 180px;
    justify-content: center;
  }
  .agenda-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #374151;
    cursor: pointer;
    transition: all 0.2s;
  }
  .dark .agenda-btn {
    border-color: #4b5563;
    background: #1f2937;
    color: #f9fafb;
  }
  .agenda-btn:hover {
    border-color: #9ca3af;
    background: #f9fafb;
  }
  .dark .agenda-btn:hover {
    border-color: #6b7280;
    background: #374151;
  }
  .agenda-btn.active {
    border-color: #3b82f6;
    background: #3b82f6;
    color: white;
  }
  .dark .agenda-btn.active {
    border-color: #3b82f6;
    background: #3b82f6;
    color: white;
  }
  .agenda-nav-group {
    display: flex;
    gap: 0.5rem;
    align-items: center;
  }
</style>

<div class="agenda-container">
  <div class="agenda-header">
    <div>
      <h1 class="agenda-title kt-page-title text-gray-900 dark:text-white" id="agenda-title">Agenda</h1>
    </div>
    <div class="agenda-actions">
      <div class="view-buttons-container">
        <button type="button" class="agenda-btn view-btn active" data-view="dayGridMonth" id="btn-month">Maand</button>
        <button type="button" class="agenda-btn view-btn" data-view="timeGridWeek" id="btn-week">Week</button>
        <button type="button" class="agenda-btn view-btn" data-view="timeGridDay" id="btn-day">Dag</button>
      </div>
      <div class="agenda-nav-group">
        <button type="button" class="agenda-btn" id="btn-prev" title="Vorige"><i class="ki-filled ki-arrow-left"></i></button>
        <button type="button" class="agenda-btn" id="btn-today">Vandaag</button>
        <button type="button" class="agenda-btn" id="btn-next" title="Volgende"><i class="ki-filled ki-arrow-right"></i></button>
      </div>
    </div>
  </div>

  <div class="card p-6">
    <div id="calendar" class="modern-calendar"></div>
  </div>
</div>

<script>var currentAppointment = null;</script>
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

  function getWeekNumber(date) {
    var d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    var dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    var yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
  }

  const calendar = new FullCalendar.Calendar(calendarEl, {
    locale: 'nl',
    firstDay: 1,

    initialView: 'dayGridMonth',
    headerToolbar: false,

    buttonText: {
      today: 'Vandaag',
      month: 'Maand',
      week: 'Week',
      day: 'Dag'
    },

    slotMinTime: '07:00:00',
    slotMaxTime: '20:00:00',
    slotDuration: '01:00:00',
    slotLabelInterval: '01:00:00',
    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

    dayHeaderContent: function(arg) {
      var dayName = arg.date.toLocaleDateString('nl-NL', { weekday: 'short' });
      var dayNum = arg.date.getDate();
      return { html: '<div class="fc-col-header-line fc-col-header-day">' + dayName + '</div><div class="fc-col-header-date">' + dayNum + '</div>' };
    },

    datesSet: function(arg) {
      var titleEl = document.getElementById('agenda-title');
      if (!titleEl) return;
      if (arg.view.type === 'timeGridWeek') {
        var start = arg.view.currentStart;
        var weekNum = getWeekNumber(start);
        var monthName = start.toLocaleDateString('nl-NL', { month: 'long' });
        var year = start.getFullYear();
        titleEl.textContent = 'Week ' + weekNum + ', ' + monthName + ' ' + year;
      } else {
        titleEl.textContent = arg.view.title;
      }
    },

    // Event settings - afspraken uit backend (geen dummy data)
    events: function(info, successCallback, failureCallback) {
      const startStr = info.start instanceof Date ? info.start.toISOString() : (info.startStr || String(info.start));
      const endStr = info.end instanceof Date ? info.end.toISOString() : (info.endStr || String(info.end));
      const url = '{{ route("agenda.events") }}?start=' + encodeURIComponent(startStr) + '&end=' + encodeURIComponent(endStr);

      fetch(url, {
        method: 'GET',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json',
        }
      })
      .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
      })
      .then(data => {
        const events = Array.isArray(data) ? data : (data && data.error ? [] : []);
        successCallback(events);
      })
      .catch(error => {
        console.error('Error fetching agenda events:', error);
        successCallback([]);
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
    allDaySlot: false,

    // Event overlap
    eventOverlap: false,

    // Event constraint
    eventConstraint: {
      start: '07:00',
      end: '20:00'
    }
  });

  calendar.render();
  window.calendar = calendar;

  // Titel bij eerste render
  var titleEl = document.getElementById('agenda-title');
  if (titleEl && calendar.view) titleEl.textContent = calendar.view.title;

  // Knoppen zoals backend: Maand / Week / Dag
  document.getElementById('btn-month').addEventListener('click', function() {
    calendar.changeView('dayGridMonth');
    document.querySelectorAll('.view-btn').forEach(function(b) { b.classList.remove('active'); });
    this.classList.add('active');
  });
  document.getElementById('btn-week').addEventListener('click', function() {
    calendar.changeView('timeGridWeek');
    document.querySelectorAll('.view-btn').forEach(function(b) { b.classList.remove('active'); });
    this.classList.add('active');
  });
  document.getElementById('btn-day').addEventListener('click', function() {
    calendar.changeView('timeGridDay');
    document.querySelectorAll('.view-btn').forEach(function(b) { b.classList.remove('active'); });
    this.classList.add('active');
  });

  document.getElementById('btn-prev').addEventListener('click', function() { calendar.prev(); });
  document.getElementById('btn-next').addEventListener('click', function() { calendar.next(); });
  document.getElementById('btn-today').addEventListener('click', function() { calendar.today(); });

  // Bij view change actieve knop bijwerken
  calendar.on('datesSet', function(arg) {
    var v = arg.view.type;
    document.querySelectorAll('.view-btn').forEach(function(b) {
      var want = b.getAttribute('data-view');
      b.classList.toggle('active', (want === 'dayGridMonth' && v === 'dayGridMonth') || (want === 'timeGridWeek' && v === 'timeGridWeek') || (want === 'timeGridDay' && v === 'timeGridDay'));
    });
  });
});

// Appointment details modal
function showAppointmentDetails(event) {
  const appointment = {
    id: event.id,
    title: event.title,
    start: event.start,
    end: event.end,
    extendedProps: event.extendedProps || {}
  };
  currentAppointment = appointment;

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
    <div id="appointment-summary-actions" class="flex space-x-3">
      <button type="button" onclick="expandAppointmentDetails()" class="btn btn-primary flex-1 flex items-center justify-center text-center">Details</button>
    </div>
    <div id="appointment-details-expanded" class="hidden mt-4"></div>
  `;

  document.getElementById('appointment-content').innerHTML = content;
  document.getElementById('appointment-modal').classList.remove('hidden');
  document.getElementById('appointment-modal').classList.add('flex');
}

function hideAppointmentModal() {
  document.getElementById('appointment-modal').classList.add('hidden');
  document.getElementById('appointment-modal').classList.remove('flex');
  currentAppointment = null;
}

// Details uitklappen in dezelfde popup (popup niet sluiten)
function expandAppointmentDetails() {
  if (!currentAppointment) return;
  const appointment = currentAppointment;
  const startDate = appointment.start instanceof Date ? appointment.start : new Date(appointment.start);
  const endDate = appointment.end instanceof Date ? appointment.end : new Date(appointment.end);
  const ext = appointment.extendedProps || {};

  const detailsHtml = `
    <div class="border-t border-gray-200 dark:border-gray-600 pt-4 mt-4 space-y-4">
      <h4 class="font-medium text-base">Afspraak &amp; adres</h4>
      <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2 text-sm">
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Datum:</span><span class="text-left min-w-0">${startDate.toLocaleDateString('nl-NL', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span></div>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Tijd:</span><span class="text-left min-w-0">${startDate.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })} – ${endDate.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' })}</span></div>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Status:</span><span class="text-left text-green-600 min-w-0">${ext.status || 'Gepland'}</span></div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2 text-sm">
        <h5 class="font-medium mb-1">Bedrijf &amp; adres</h5>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Bedrijf:</span><span class="text-left min-w-0">${ext.company_name || '—'}</span></div>
        <div class="flex gap-2">
          <span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Adres:</span>
          <span class="text-left min-w-0">${(function(){ var br = '<br>'; var p = [ext.company_street || '', ext.company_postal_code || '', ext.company_city || ''].filter(Boolean); if (p.length) return p.join(br); var a = (ext.company_address || '—').trim(); if (typeof a !== 'string' || !a) return a; if (a.indexOf(',') !== -1){ var parts = a.split(/,\\s*/).map(function(s){ return s.trim(); }); if (parts.length === 3) return parts[0] + br + parts[2] + br + parts[1]; return parts.join(br); } var m = a.match(/^(.+?)\\s*(\\d{4}\\s*[A-Za-z]{2})\\s*(.+)$/); if (m) return m[1].trim() + br + m[2].replace(/\\s+/g,' ') + br + m[3].trim(); return a; })()}</span>
        </div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg space-y-2 text-sm">
        <h5 class="font-medium mb-1">Contact</h5>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Contactpersoon:</span><span class="text-left min-w-0">${ext.interviewer_name || '—'}</span></div>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Telefoon:</span><span class="text-left min-w-0">${ext.company_phone || '—'}</span></div>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">E-mail:</span><span class="text-left min-w-0">${ext.interviewer_email || '—'}</span></div>
        <div class="flex gap-2"><span class="text-gray-600 dark:text-gray-400 shrink-0 w-[7.5rem]">Functie:</span><span class="text-left min-w-0">${ext.vacancy_title || '—'}</span></div>
      </div>
      ${(ext.notes && ext.notes.trim()) ? `<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg"><h5 class="font-medium mb-1">Notities</h5><p class="text-sm text-gray-600 dark:text-gray-400">${ext.notes}</p></div>` : ''}
      <div class="flex justify-end pt-2">
        <button type="button" onclick="hideAppointmentModal()" class="btn btn-primary px-5 py-2.5 font-medium shadow-sm">Sluiten</button>
      </div>
    </div>
  `;

  const expandedEl = document.getElementById('appointment-details-expanded');
  const summaryActions = document.getElementById('appointment-summary-actions');
  if (expandedEl) {
    expandedEl.innerHTML = detailsHtml;
    expandedEl.classList.remove('hidden');
  }
  if (summaryActions) summaryActions.classList.add('hidden');
}

</script>

<!-- Appointment Detail Modal - blur achtergrond -->
<style>
  #appointment-modal {
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    background-color: rgba(0, 0, 0, 0.25);
  }
</style>
<div id="appointment-modal" class="fixed inset-0 hidden items-center justify-center z-50">
  <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 relative shadow-xl">
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
  
  // Geen headerToolbar – we gebruiken eigen header zoals backend
  
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
