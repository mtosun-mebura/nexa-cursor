<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Get all active chats for the current candidate (frontend user)
     * Returns chats where companies have messaged the candidate
     */
    public function getActiveChats(Request $request)
    {
        try {
            $candidateUser = auth()->user();
            if (!$candidateUser) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Get candidate record by email (candidates and users share email)
            $candidateRecord = \App\Models\Candidate::where('email', $candidateUser->email)->first();
            if (!$candidateRecord) {
                return response()->json([]);
            }

            // Load chats where this candidate is the recipient
            $chats = Chat::where('candidate_id', $candidateRecord->id)
                ->where('is_active', true)
                ->with(['candidate', 'user', 'company', 'latestMessage'])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function($chat) use ($candidateUser) {
                    return $this->formatChat($chat, $candidateUser);
                });

            return response()->json($chats);
        } catch (\Exception $e) {
            Log::error('Error loading active chats for candidate: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load chats: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get messages for a chat (frontend perspective)
     */
    public function getChatMessages(Request $request, $chatId)
    {
        $candidateUser = auth()->user();
        
        // Get candidate record by email
        $candidateRecord = \App\Models\Candidate::where('email', $candidateUser->email)->first();
        if (!$candidateRecord) {
            return response()->json(['error' => 'Candidate not found'], 404);
        }
        
        $chat = Chat::where('id', $chatId)
            ->where('candidate_id', $candidateRecord->id)
            ->where('is_active', true)
            ->firstOrFail();

        $messages = $chat->messages()
            ->with(['sender'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($candidateUser, $candidateRecord, $chat) {
                $senderName = 'Unknown';
                if ($message->sender) {
                    $senderName = $message->sender->first_name . ' ' . $message->sender->last_name;
                }
                
                // Get avatar URL with error handling
                $avatarUrl = asset('assets/media/avatars/300-5.png'); // Default avatar
                try {
                    if ($message->isFromUser()) {
                        // Message from company user (contact person) - get from backend
                        if ($message->sender) {
                            // Reload sender to ensure we have the latest photo_blob status
                            $sender = \App\Models\User::find($message->sender->id);
                            if ($sender && $sender->photo_blob) {
                                try {
                                    $avatarUrl = route('user.photo', $sender->id);
                                } catch (\Exception $e) {
                                    // Route error, use default
                                    $avatarUrl = asset('assets/media/avatars/300-2.png');
                                    Log::error('Error getting user avatar URL: ' . $e->getMessage());
                                }
                            } else {
                                $avatarUrl = asset('assets/media/avatars/300-2.png');
                            }
                        } else {
                            $avatarUrl = asset('assets/media/avatars/300-2.png');
                        }
                    } else {
                        // Message from candidate (self) - get from frontend user via email match
                        if ($message->sender) {
                            // Candidates share email with Users, so get the User record for photo
                            $candidateEmail = $message->sender->email ?? null;
                            if ($candidateEmail) {
                                $candidateUserForPhoto = \App\Models\User::where('email', $candidateEmail)->first();
                                if ($candidateUserForPhoto && $candidateUserForPhoto->photo_blob) {
                                    try {
                                        $avatarUrl = route('user.photo', $candidateUserForPhoto->id);
                                    } catch (\Exception $e) {
                                        // Route error, use default
                                        $avatarUrl = asset('assets/media/avatars/300-5.png');
                                        Log::error('Error getting candidate avatar URL: ' . $e->getMessage());
                                    }
                                } else {
                                    $avatarUrl = asset('assets/media/avatars/300-5.png');
                                }
                            } else {
                                $avatarUrl = asset('assets/media/avatars/300-5.png');
                            }
                        } else {
                            $avatarUrl = asset('assets/media/avatars/300-5.png');
                        }
                    }
                } catch (\Exception $e) {
                    // Use default avatar on any error
                    Log::error('Error getting avatar URL: ' . $e->getMessage());
                }
                
                // Get online status for the sender
                $isOnline = false;
                if ($message->sender) {
                    // Get the most recent message from this sender in this chat
                    $latestSenderMessage = ChatMessage::where('chat_id', $chat->id)
                        ->where('sender_id', $message->sender_id)
                        ->where('sender_type', $message->sender_type)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $mostRecentMessageTime = $latestSenderMessage ? $latestSenderMessage->created_at : $message->created_at;
                    
                    // Consider online ONLY if:
                    // - Most recent message from this sender was sent in last 3 minutes (very recent activity)
                    // This is more accurate than using updated_at which can be updated for other reasons
                    $minutesSinceLatestMessage = $mostRecentMessageTime ? $mostRecentMessageTime->diffInMinutes(now()) : 999;
                    
                    // Only show as online if message was sent in last 3 minutes
                    if ($minutesSinceLatestMessage < 3) {
                        $isOnline = true;
                    }
                }
                
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $senderName,
                    'sender_avatar' => $avatarUrl,
                    'is_own' => $message->isFromCandidate() && $message->sender_id == $candidateRecord->id,
                    'read_at' => $message->read_at ? $message->read_at->format('Y-m-d H:i:s') : null,
                    'is_read' => $message->read_at !== null,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'time' => $message->created_at->format('H:i'),
                    'user' => $message->sender ? [
                        'id' => $message->sender->id,
                        'is_online' => $isOnline,
                    ] : null,
                ];
            });

        // Mark messages as read (messages from company users)
        $chat->messages()
            ->where('sender_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send a message in a chat (frontend - candidate sending)
     */
    public function sendChatMessage(Request $request, $chatId)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $candidateUser = auth()->user();
        
        // Get candidate record by email
        $candidateRecord = \App\Models\Candidate::where('email', $candidateUser->email)->first();
        if (!$candidateRecord) {
            return response()->json(['error' => 'Candidate not found'], 404);
        }
        
        $chat = Chat::where('id', $chatId)
            ->where('candidate_id', $candidateRecord->id)
            ->where('is_active', true)
            ->firstOrFail();

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'sender_id' => $candidateRecord->id,
            'sender_type' => Candidate::class,
            'message' => $request->message,
        ]);

        $chat->touch(); // Update updated_at

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'sender_id' => $message->sender_id,
                'sender_type' => $message->sender_type,
                'sender_name' => $candidateUser->first_name . ' ' . $candidateUser->last_name,
                'is_own' => true,
                'read_at' => null,
                'is_read' => false,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'time' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Get unread count for candidate
     */
    /**
     * End a chat (frontend - candidate perspective)
     */
    public function endChat(Request $request, $chatId)
    {
        try {
            $candidateUser = auth()->user();
            if (!$candidateUser) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Get candidate record by email
            $candidateRecord = \App\Models\Candidate::where('email', $candidateUser->email)->first();
            if (!$candidateRecord) {
                return response()->json(['error' => 'Candidate not found'], 404);
            }

            $chat = Chat::where('id', $chatId)
                ->where('candidate_id', $candidateRecord->id)
                ->where('is_active', true)
                ->firstOrFail();

            $chat->update([
                'is_active' => false,
                'ended_at' => now(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error ending chat: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to end chat'], 500);
        }
    }

    public function getUnreadCount(Request $request)
    {
        $candidateUser = auth()->user();
        if (!$candidateUser) {
            return response()->json(['unread_count' => 0]);
        }
        
        // Get candidate record by email
        $candidateRecord = \App\Models\Candidate::where('email', $candidateUser->email)->first();
        if (!$candidateRecord) {
            return response()->json(['unread_count' => 0]);
        }
        
        // Count unread messages from company users in active chats
        $unreadCount = ChatMessage::whereHas('chat', function($query) use ($candidateRecord) {
                $query->where('candidate_id', $candidateRecord->id)
                      ->where('is_active', true);
            })
            ->where('sender_type', User::class)
            ->whereNull('read_at')
            ->count();
        
        return response()->json(['unread_count' => $unreadCount]);
    }

    /**
     * Format chat for frontend (candidate perspective)
     */
    private function formatChat($chat, $candidate)
    {
        try {
            // Get latest message - check if eager loaded first
            $latestMessage = $chat->relationLoaded('latestMessage') 
                ? $chat->latestMessage 
                : $chat->latestMessage()->first();
            
            // Get avatar URLs with proper error handling
            $userAvatarUrl = asset('assets/media/avatars/300-2.png');
            if ($chat->user) {
                // Reload user to ensure we have the latest photo_blob status
                $user = \App\Models\User::find($chat->user->id);
                if ($user && $user->photo_blob) {
                    try {
                        $userAvatarUrl = route('user.photo', $user->id);
                    } catch (\Exception $e) {
                        // Fallback to default
                        Log::error('Error getting user avatar URL in formatChat: ' . $e->getMessage());
                    }
                }
            }
            
            $candidateAvatarUrl = asset('assets/media/avatars/300-5.png');
            if ($candidate) {
                // Try to get candidate's user record for photo
                $candidateUser = \App\Models\User::where('email', $candidate->email)->first();
                if ($candidateUser && $candidateUser->photo_blob) {
                    try {
                        $candidateAvatarUrl = route('user.photo', $candidateUser->id);
                    } catch (\Exception $e) {
                        // Fallback to default
                    }
                }
            }
            
            // Count unread messages from company users in this chat
            // Only count messages that are truly unread (read_at is null)
            // This count should persist until the user actually opens the chat
            $unreadCount = 0;
            try {
                $unreadCount = \App\Models\ChatMessage::where('chat_id', $chat->id)
                    ->where('sender_type', \App\Models\User::class)
                    ->whereNull('read_at')
                    ->count();
            } catch (\Exception $e) {
                Log::error('Error counting unread messages in formatChat: ' . $e->getMessage());
            }
            
            return [
                'id' => $chat->id,
                'candidate' => $chat->candidate ? [
                    'id' => $chat->candidate->id,
                    'name' => $chat->candidate->first_name . ' ' . $chat->candidate->last_name,
                    'email' => $chat->candidate->email ?? '',
                    'avatar' => $candidateAvatarUrl,
                ] : null,
                'user' => $chat->user ? [
                    'id' => $chat->user->id,
                    'name' => $chat->user->first_name . ' ' . $chat->user->last_name,
                    'email' => $chat->user->email ?? '',
                    'avatar' => $userAvatarUrl,
                ] : null,
                'company' => $chat->company ? [
                    'id' => $chat->company->id,
                    'name' => $chat->company->name ?? '',
                ] : null,
                'last_message' => $latestMessage ? [
                    'message' => $latestMessage->message ?? '',
                    'created_at' => $latestMessage->created_at->format('Y-m-d H:i:s'),
                    'time' => $latestMessage->created_at->format('H:i'),
                ] : null,
                'updated_at' => $chat->updated_at ? $chat->updated_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                'unread_count' => $unreadCount,
            ];
        } catch (\Exception $e) {
            Log::error('Error formatting chat: ' . $e->getMessage(), [
                'chat_id' => $chat->id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            // Return minimal safe data
            return [
                'id' => $chat->id ?? 0,
                'candidate' => null,
                'user' => null,
                'company' => null,
                'last_message' => null,
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];
        }
    }
}

