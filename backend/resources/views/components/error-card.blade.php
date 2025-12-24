@if(isset($errors) && $errors->any())
    <div class="kt-card min-w-full mb-5" style="background-color: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25);">
        <div class="kt-card-content p-5 lg:px-7 lg:py-6">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <i class="ki-filled ki-information-5 text-xl" style="color: rgb(239, 68, 68);"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold mb-2" style="color: rgb(220, 38, 38);">
                        Er zijn fouten opgetreden
                    </h3>
                    <ul class="list-disc list-inside space-y-1 text-sm" style="color: rgb(185, 28, 28);">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif





