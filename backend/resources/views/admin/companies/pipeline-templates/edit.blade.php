@extends('admin.layouts.app')

@section('title', 'Pipeline Template Bewerken - ' . $pipelineTemplate->name)

@section('content')

<div class="kt-container-fixed">
    <div class="flex items-center flex-wrap md:flex-nowrap lg:items-center justify-between gap-3 lg:gap-6 mb-5 lg:mb-10 mt-5">
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.companies.pipeline-templates.index', $company) }}" class="kt-btn kt-btn-outline">
                <i class="ki-filled ki-arrow-left me-2"></i>
                Terug
            </a>
            <h1 class="text-2xl font-semibold text-mono">Pipeline Template Bewerken</h1>
        </div>
    </div>

    @if(session('success'))
        <div class="kt-alert kt-alert-success mb-5">
            <div class="kt-alert-content">
                <i class="ki-filled ki-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <form action="{{ route('admin.companies.pipeline-templates.update', [$company, $pipelineTemplate]) }}" method="POST" id="pipeline-template-form">
        @csrf
        @method('PUT')

        <div class="kt-card mb-5">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Template Informatie</h3>
            </div>
            <div class="kt-card-content">
                <div class="flex flex-col gap-4">
                    <div>
                        <label class="kt-label mb-2">Naam</label>
                        <input type="text" name="name" value="{{ old('name', $pipelineTemplate->name) }}" class="kt-input" required>
                    </div>
                    <div>
                        <label class="kt-label mb-2">Beschrijving</label>
                        <textarea name="description" class="kt-input pt-1" rows="4">{{ old('description', $pipelineTemplate->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Stappen Configureren</h3>
                <p class="text-sm text-muted-foreground mt-1">Schakel stappen in/uit en pas de volgorde aan</p>
            </div>
            <div class="kt-card-content">
                <div id="stages-container" class="space-y-3">
                    @php
                        $existingStages = collect($pipelineTemplate->stages ?? [])->keyBy('stageType');
                        $sequence = 10;
                    @endphp

                    @foreach($stageTypes as $stageType)
                        @php
                            $existingStage = $existingStages->get($stageType->key);
                            $isEnabled = $existingStage !== null;
                            $stageLabel = $existingStage['label'] ?? $stageType->default_label;
                            $stageSequence = $existingStage['sequence'] ?? $sequence;
                            $isOptional = $existingStage['optional'] ?? false;
                        @endphp
                        <div class="stage-item border border-border rounded-lg p-4 {{ $isEnabled ? 'bg-background' : 'bg-muted/30 opacity-60' }}" data-stage-type="{{ $stageType->key }}">
                            <div class="flex items-start gap-4">
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <input type="checkbox" 
                                           class="stage-toggle kt-switch kt-switch-sm" 
                                           data-stage-type="{{ $stageType->key }}"
                                           {{ $isEnabled ? 'checked' : '' }}>
                                    <span class="text-sm font-medium text-muted-foreground">Inschakelen</span>
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h4 class="font-semibold text-base">{{ $stageType->default_label }}</h4>
                                        <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $stageType->category }}</span>
                                        @if($stageType->can_schedule)
                                            <span class="kt-badge kt-badge-sm kt-badge-info">Planbaar</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-muted-foreground mb-2">{{ $stageType->key }}</p>
                                    
                                    <div class="stage-config {{ $isEnabled ? '' : 'hidden' }}">
                                        <div class="flex flex-col gap-2">
                                            <div>
                                                <label class="kt-label mb-1 text-sm">Label</label>
                                                <input type="text" 
                                                       name="stages[{{ $stageType->key }}][label]" 
                                                       value="{{ $stageLabel }}" 
                                                       class="kt-input kt-input-sm stage-label-input"
                                                       {{ $isEnabled ? '' : 'disabled' }}>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <div>
                                                    <label class="kt-label mb-1 text-sm">Volgorde</label>
                                                    <input type="number" 
                                                           name="stages[{{ $stageType->key }}][sequence]" 
                                                           value="{{ $stageSequence }}" 
                                                           class="kt-input kt-input-sm stage-sequence-input w-24"
                                                           {{ $isEnabled ? '' : 'disabled' }}>
                                                </div>
                                                <div class="flex items-center gap-2 mt-6">
                                                    <input type="checkbox" 
                                                           name="stages[{{ $stageType->key }}][optional]" 
                                                           class="kt-switch kt-switch-sm"
                                                           {{ $isOptional ? 'checked' : '' }}
                                                           {{ $isEnabled ? '' : 'disabled' }}>
                                                    <label class="text-sm text-muted-foreground">Optioneel</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden inputs for stage data -->
                            <input type="hidden" name="stages[{{ $stageType->key }}][id]" value="stg_{{ $stageType->key }}">
                            <input type="hidden" name="stages[{{ $stageType->key }}][stageType]" value="{{ $stageType->key }}">
                        </div>
                        @php
                            if ($isEnabled) {
                                $sequence = $stageSequence + 10;
                            } else {
                                $sequence += 10;
                            }
                        @endphp
                    @endforeach
                </div>

                <div class="mt-6 pt-6 border-t border-border">
                    <div>
                        <label class="kt-label mb-2">Terminale stappen (gescheiden door komma)</label>
                        <input type="text" 
                               name="terminal_stages" 
                               value="{{ old('terminal_stages', implode(', ', $pipelineTemplate->terminal_stages ?? ['REJECTION', 'WITHDRAWN'])) }}" 
                               class="kt-input"
                               placeholder="REJECTION, WITHDRAWN">
                        <p class="text-xs text-muted-foreground mt-1">Stappen die het proces beëindigen</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-5">
            <a href="{{ route('admin.companies.pipeline-templates.index', $company) }}" class="kt-btn kt-btn-outline">
                Annuleren
            </a>
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check me-2"></i>
                Opslaan
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle stage toggle
    document.querySelectorAll('.stage-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const stageItem = this.closest('.stage-item');
            const stageConfig = stageItem.querySelector('.stage-config');
            const inputs = stageItem.querySelectorAll('input[type="text"], input[type="number"], input[type="checkbox"]:not(.stage-toggle)');
            
            if (this.checked) {
                stageItem.classList.remove('bg-muted/30', 'opacity-60');
                stageItem.classList.add('bg-background');
                stageConfig.classList.remove('hidden');
                inputs.forEach(input => input.disabled = false);
            } else {
                stageItem.classList.add('bg-muted/30', 'opacity-60');
                stageItem.classList.remove('bg-background');
                stageConfig.classList.add('hidden');
                inputs.forEach(input => input.disabled = true);
            }
        });
    });

    // Form submission - only include enabled stages
    document.getElementById('pipeline-template-form').addEventListener('submit', function(e) {
        const stages = [];
        
        document.querySelectorAll('.stage-item').forEach(function(item) {
            const toggle = item.querySelector('.stage-toggle');
            if (toggle && toggle.checked) {
                const stageType = toggle.dataset.stageType;
                const labelInput = item.querySelector('.stage-label-input');
                const sequenceInput = item.querySelector('.stage-sequence-input');
                const optionalCheckbox = item.querySelector('input[name*="[optional]"]');
                
                stages.push({
                    id: item.querySelector('input[name*="[id]"]').value,
                    stageType: item.querySelector('input[name*="[stageType]"]').value,
                    label: labelInput ? labelInput.value : '',
                    sequence: sequenceInput ? parseInt(sequenceInput.value) : 0,
                    optional: optionalCheckbox ? optionalCheckbox.checked : false
                });
            }
        });

        // Sort by sequence
        stages.sort((a, b) => a.sequence - b.sequence);

        // Clear existing stage inputs
        document.querySelectorAll('input[name^="stages["]').forEach(input => {
            if (input.type !== 'hidden' || !input.name.includes('[id]')) {
                input.remove();
            }
        });

        // Remove all existing stage hidden inputs except id
        document.querySelectorAll('input[name^="stages["][type="hidden"]').forEach(input => {
            if (!input.name.includes('[id]')) {
                input.remove();
            }
        });

        // Add new hidden inputs for enabled stages only
        const form = this;
        stages.forEach(function(stage, index) {
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = `stages[${index}][id]`;
            idInput.value = stage.id;
            form.appendChild(idInput);

            const stageTypeInput = document.createElement('input');
            stageTypeInput.type = 'hidden';
            stageTypeInput.name = `stages[${index}][stageType]`;
            stageTypeInput.value = stage.stageType;
            form.appendChild(stageTypeInput);

            const labelInput = document.createElement('input');
            labelInput.type = 'hidden';
            labelInput.name = `stages[${index}][label]`;
            labelInput.value = stage.label;
            form.appendChild(labelInput);

            const sequenceInput = document.createElement('input');
            sequenceInput.type = 'hidden';
            sequenceInput.name = `stages[${index}][sequence]`;
            sequenceInput.value = stage.sequence;
            form.appendChild(sequenceInput);

            if (stage.optional) {
                const optionalInput = document.createElement('input');
                optionalInput.type = 'hidden';
                optionalInput.name = `stages[${index}][optional]`;
                optionalInput.value = '1';
                form.appendChild(optionalInput);
            }
        });
    });
});
</script>
@endpush

@endsection

