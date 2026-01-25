<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Interview;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all interviews where interviewer_email is set but interviewer_user_id is null
        $interviews = Interview::whereNotNull('interviewer_email')
            ->whereNull('interviewer_user_id')
            ->get();

        $updated = 0;
        $notFound = 0;

        foreach ($interviews as $interview) {
            // Find user by email
            $user = User::where('email', $interview->interviewer_email)->first();
            
            if ($user) {
                // Update interviewer_user_id
                $interview->interviewer_user_id = $user->id;
                
                // Also update user_id for backward compatibility if it's null
                if (is_null($interview->user_id)) {
                    $interview->user_id = $user->id;
                }
                
                $interview->save();
                $updated++;
            } else {
                $notFound++;
                \Log::warning('Interview interviewer user not found by email', [
                    'interview_id' => $interview->id,
                    'interviewer_email' => $interview->interviewer_email,
                ]);
            }
        }

        \Log::info('Populated interviewer_user_id from email', [
            'total_interviews' => $interviews->count(),
            'updated' => $updated,
            'not_found' => $notFound,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally clear interviewer_user_id if needed
        // Interview::whereNotNull('interviewer_user_id')->update(['interviewer_user_id' => null]);
    }
};
