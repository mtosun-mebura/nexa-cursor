{{-- Material Design Template voor CRUD pagina's --}}
{{-- Dit bestand bevat alle CSS classes die consistent gebruikt worden --}}

<style>
    /* Material Card */
    .material-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .material-card .card-header {
        background: linear-gradient(135deg, var(--primary-color, #4caf50) 0%, var(--primary-light, #66bb6a) 100%);
        color: white;
        padding: 20px 24px;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .material-card .card-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.25rem;
    }

    .material-card .card-body {
        padding: 24px;
    }

    /* Material Buttons */
    .material-btn {
        border: none;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;
        cursor: pointer;
        font-size: 0.875rem;
        height: 44px;
        min-height: 44px;
        line-height: 1;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .material-btn-primary {
        background: linear-gradient(135deg, var(--primary-color, #4caf50) 0%, var(--primary-light, #66bb6a) 100%);
        color: white;
    }

    .material-btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark, #43a047) 0%, var(--primary-hover, #5cb85c) 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
    }

    .material-btn-secondary {
        background: #f5f5f5;
        color: #333;
    }

    .material-btn-secondary:hover {
        background: #e0e0e0;
        transform: translateY(-1px);
    }

    .material-btn-info {
        background: linear-gradient(135deg, #2196f3 0%, #42a5f5 100%);
        color: white;
    }

    .material-btn-info:hover {
        background: linear-gradient(135deg, #1976d2 0%, #1e88e5 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
    }

    .material-btn-warning {
        background: linear-gradient(135deg, #ff9800 0%, #ffb74d 100%);
        color: white;
    }

    .material-btn-warning:hover {
        background: linear-gradient(135deg, #f57c00 0%, #ff8f00 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
    }

    .material-btn-danger {
        background: linear-gradient(135deg, #f44336 0%, #ef5350 100%);
        color: white;
    }

    .material-btn-danger:hover {
        background: linear-gradient(135deg, #d32f2f 0%, #e53935 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
    }

    /* Material Form Elements */
    .material-form-group {
        margin-bottom: 20px;
    }

    .material-form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #333;
        font-size: 0.875rem;
    }

    .material-form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: white;
    }

    .material-form-control:focus {
        outline: none;
        border-color: var(--primary-color, #4caf50);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    .material-form-control.is-invalid {
        border-color: #f44336;
    }

    .material-invalid-feedback {
        color: #f44336;
        font-size: 0.75rem;
        margin-top: 4px;
    }

    /* Material Alerts */
    .material-alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: none;
    }

    .material-alert-danger {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #f44336;
    }

    .material-alert-success {
        background: #e8f5e8;
        color: #2e7d32;
        border-left: 4px solid #4caf50;
    }

    .material-alert-info {
        background: #e3f2fd;
        color: #1565c0;
        border-left: 4px solid #2196f3;
    }

    .material-alert-secondary {
        background: #f5f5f5;
        color: #666;
        border-left: 4px solid #757575;
    }

    /* Material Section Titles */
    .material-section-title {
        color: #666;
        font-size: 1rem;
        font-weight: 600;
        margin: 24px 0 16px 0;
        padding-bottom: 8px;
        border-bottom: 2px solid #f0f0f0;
    }

    /* Material Form Actions */
    .material-form-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        margin-top: 32px;
        padding-top: 24px;
        border-top: 1px solid #f0f0f0;
    }

    .material-form-actions .material-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 44px;
        min-height: 44px;
        line-height: 1;
        flex-shrink: 0;
    }

    .material-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .material-header-actions .material-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 44px;
        min-height: 44px;
        line-height: 1;
        flex-shrink: 0;
    }

    /* Material Tables */
    .material-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .material-table thead {
        background: linear-gradient(135deg, var(--primary-color, #4caf50) 0%, var(--primary-light, #66bb6a) 100%);
        color: white;
    }

    .material-table th {
        padding: 16px;
        text-align: left;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .material-table td {
        padding: 16px;
        border-bottom: 1px solid #f0f0f0;
    }

    .material-table tbody tr:hover {
        background: #f8f9fa;
    }

    /* Material Info Tables */
    .material-info-table {
        width: 100%;
        border-collapse: collapse;
    }

    .material-info-table tr {
        border-bottom: 1px solid #f0f0f0;
    }

    .material-info-table tr:last-child {
        border-bottom: none;
    }

    .material-info-table td {
        padding: 12px 0;
        vertical-align: top;
    }

    .material-info-table td:first-child {
        width: 150px;
        font-weight: 600;
        color: #333;
    }

    .material-info-table td:last-child {
        color: #666;
    }

    /* Material Badges */
    .material-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .material-badge-primary {
        background: #e3f2fd;
        color: #1976d2;
    }

    .material-badge-success {
        background: #e8f5e8;
        color: #2e7d32;
    }

    .material-badge-secondary {
        background: #f5f5f5;
        color: #666;
    }

    .material-badge-warning {
        background: #fff3e0;
        color: #f57c00;
    }

    .material-badge-danger {
        background: #ffebee;
        color: #c62828;
    }

    /* Material Dividers */
    .material-divider {
        height: 1px;
        background: #f0f0f0;
        margin: 32px 0;
        border: none;
    }

    /* Material Links */
    .material-link {
        color: var(--primary-color, #4caf50);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .material-link:hover {
        color: var(--primary-dark, #43a047);
        text-decoration: underline;
    }

    .material-text-muted {
        color: #999;
    }

    /* Material Select */
    .material-form-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: white;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
        position: relative;
    }

    .material-form-select:focus {
        outline: none;
        border-color: var(--primary-color, #4caf50);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%234caf50' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    }

    .material-form-select:hover {
        border-color: #bdbdbd;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%234a5568' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    }

    .material-form-select.is-invalid {
        border-color: #f44336;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23f44336' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    }

    /* Material Select Container - Removed duplicate arrow */
    .material-select-container {
        position: relative;
        display: inline-block;
        width: 100%;
    }

    /* Material Select Options Styling */
    .material-form-select option {
        padding: 12px 16px;
        background: white;
        color: #333;
        font-size: 0.875rem;
        border: none;
        outline: none;
    }

    .material-form-select option:hover {
        background: var(--primary-color, #4caf50);
        color: white;
    }

    .material-form-select option:checked {
        background: var(--primary-color, #4caf50);
        color: white;
    }

    .material-form-select option:focus {
        background: var(--primary-color, #4caf50);
        color: white;
    }

    /* Material Select Placeholder */
    .material-form-select option[value=""] {
        color: #999;
        font-style: italic;
    }

    /* Remove default select styling */
    .material-form-select::-ms-expand {
        display: none;
    }

    .material-form-select::-webkit-select-placeholder {
        color: #999;
    }

    .material-form-select::-moz-placeholder {
        color: #999;
    }

    /* Material Textarea */
    .material-form-textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        background: white;
        resize: vertical;
        min-height: 100px;
    }

    .material-form-textarea:focus {
        outline: none;
        border-color: var(--primary-color, #4caf50);
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
    }

    /* Material Select Animation */
    .material-form-select {
        transform-origin: center;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .material-form-select:focus {
        transform: scale(1.02);
    }

    /* Material Select Loading State */
    .material-form-select.loading {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%234caf50' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M10 3.5a6.5 6.5 0 0 1 6.5 6.5'/%3e%3c/svg%3e");
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Material Select Success State */
    .material-form-select.success {
        border-color: #4caf50;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%234caf50' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m5 10 3 3 7-7'/%3e%3c/svg%3e");
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Material Select Enhancement
    const materialSelects = document.querySelectorAll('.material-form-select');
    
    materialSelects.forEach(select => {
        // Add focus and blur effects directly to select
        select.addEventListener('focus', function() {
            this.classList.add('focused');
        });

        select.addEventListener('blur', function() {
            this.classList.remove('focused');
        });

        // Add change effect
        select.addEventListener('change', function() {
            if (this.value) {
                this.classList.add('success');
                setTimeout(() => {
                    this.classList.remove('success');
                }, 1000);
            }
        });

        // Add loading state for dynamic options
        if (select.dataset.loading) {
            select.classList.add('loading');
            // Remove loading class when options are loaded
            setTimeout(() => {
                select.classList.remove('loading');
            }, 1000);
        }

        // Add keyboard navigation
        select.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.focus();
            }
        });

        // Add hover effect
        select.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-1px)';
        });

        select.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Auto-resize select based on content
    function autoResizeSelect(select) {
        const tempSelect = select.cloneNode(true);
        tempSelect.style.position = 'absolute';
        tempSelect.style.visibility = 'hidden';
        tempSelect.style.width = 'auto';
        document.body.appendChild(tempSelect);
        
        const width = tempSelect.offsetWidth;
        document.body.removeChild(tempSelect);
        
        select.style.width = Math.max(width + 20, select.offsetWidth) + 'px';
    }

    // Apply auto-resize to selects with long options
    materialSelects.forEach(select => {
        if (select.options.length > 0) {
            const longestOption = Array.from(select.options).reduce((longest, option) => {
                return option.text.length > longest.length ? option.text : longest;
            }, '');
            
            if (longestOption.length > 20) {
                autoResizeSelect(select);
            }
        }
    });
});
</script>
