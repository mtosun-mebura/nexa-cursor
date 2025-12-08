<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\TypingIndicator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * Get available candidates for chat
     */
    public function getCandidates(Request $request)
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        
        // Get candidates (users with candidate role)
        $query = User::whereHas('roles', function($q) {
            $q->where('name', 'candidate');
        });
        
        if ($isSuperAdmin) {
            // Super admin can see all candidates
            $tenantId = session('selected_tenant');
            if ($tenantId) {
                $query->where('company_id', $tenantId);
            }
        } else {
            // Company admin: only candidates with matches to their company's vacancies
            $companyId = $user->company_id;
            if ($companyId) {
                $candidateIds = \App\Models\JobMatch::whereHas('vacancy', function($vq) use ($companyId) {
                    $vq->where('company_id', $companyId);
                })->pluck('user_id')->unique();
                
                if ($candidateIds->isNotEmpty()) {
                    $query->whereIn('id', $candidateIds);
                } else {
                    return response()->json([]);
                }
            } else {
                return response()->json([]);
            }
        }
        
        $candidates = $query->orderBy('first_name')->orderBy('last_name')->get()->map(function($candidate) {
            return [
                'id' => $candidate->id,
                'name' => trim(($candidate->first_name ?? '') . ' ' . ($candidate->last_name ?? '')),
                'email' => $candidate->email,
            ];
        });
        
        return response()->json($candidates);
    }

    /**
     * Get all chat rooms for the current user
     */
    public function getRooms(Request $request)
    {
        $user = auth()->user();
        
        // Get all rooms where user is a participant (accepted or pending)
        $rooms = ChatRoom::whereHas('participants', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['candidate', 'latestMessage.user', 'acceptedParticipants.user'])
        ->orderBy('last_message_at', 'desc')
        ->get()
        ->map(function($room) use ($user) {
            $participant = $room->participants()->where('user_id', $user->id)->first();
            return [
                'id' => $room->id,
                'candidate' => [
                    'id' => $room->candidate->id,
                    'name' => $room->candidate->first_name . ' ' . $room->candidate->last_name,
                    'email' => $room->candidate->email,
                ],
                'status' => $participant->status ?? 'pending',
                'last_message' => $room->latestMessage ? [
                    'message' => $room->latestMessage->message,
                    'user_id' => $room->latestMessage->user_id,
                    'created_at' => $room->latestMessage->created_at->format('Y-m-d H:i:s'),
                ] : null,
                'participants_count' => $room->acceptedParticipants->count(),
                'created_at' => $room->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($rooms);
    }

    /**
     * Get messages for a specific chat room
     */
    public function getMessages(Request $request, $roomId)
    {
        $user = auth()->user();
        
        // Check if user is a participant
        $participant = ChatParticipant::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$participant) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        // Mark messages as read
        ChatMessage::where('chat_room_id', $roomId)
            ->where('user_id', '!=', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        // Update last_read_at for participant
        $participant->update(['last_read_at' => now()]);

        $messages = ChatMessage::where('chat_room_id', $roomId)
            ->with('user:id,first_name,last_name,email')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($user) {
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'user_id' => $message->user_id,
                    'user_name' => $message->user->first_name . ' ' . $message->user->last_name,
                    'is_own' => $message->user_id === $user->id,
                    'is_read' => $message->is_read,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'time' => $message->created_at->format('H:i'),
                ];
            });

        return response()->json($messages);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, $roomId)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = auth()->user();
        
        // Check if user is an accepted participant
        $participant = ChatParticipant::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$participant) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        $message = ChatMessage::create([
            'chat_room_id' => $roomId,
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        // Update room's last_message_at
        ChatRoom::where('id', $roomId)->update(['last_message_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'message' => $message->message,
                'user_id' => $message->user_id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'is_own' => true,
                'is_read' => false,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'time' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Create or get a chat room with a candidate
     */
    public function createOrGetRoom(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:users,id',
        ]);

        $user = auth()->user();
        $candidateId = $request->candidate_id;

        // Check if candidate has candidate role
        $candidate = User::where('id', $candidateId)
            ->whereHas('roles', function($q) {
                $q->where('name', 'candidate');
            })
            ->first();

        if (!$candidate) {
            return response()->json(['error' => 'User is not a candidate'], 400);
        }

        // Check if room already exists
        $existingRoom = ChatRoom::where('candidate_id', $candidateId)
            ->whereHas('participants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->first();

        if ($existingRoom) {
            return response()->json([
                'success' => true,
                'room_id' => $existingRoom->id,
            ]);
        }

        // Create new room
        DB::beginTransaction();
        try {
            $room = ChatRoom::create([
                'candidate_id' => $candidateId,
            ]);

            // Add current user as participant with pending status (join request)
            ChatParticipant::create([
                'chat_room_id' => $room->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'room_id' => $room->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create room'], 500);
        }
    }

    /**
     * Get pending join requests for rooms
     */
    public function getJoinRequests(Request $request)
    {
        $user = auth()->user();
        
        // If user is a candidate, show requests for their rooms
        // If user is an admin, show their own pending requests
        if ($user->hasRole('candidate')) {
            $requests = ChatParticipant::where('status', 'pending')
                ->whereHas('chatRoom', function($q) use ($user) {
                    $q->where('candidate_id', $user->id);
                })
                ->with(['user:id,first_name,last_name,email', 'chatRoom:id,candidate_id'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Admin users see their own pending requests
            $requests = ChatParticipant::where('status', 'pending')
                ->where('user_id', $user->id)
                ->with(['user:id,first_name,last_name,email', 'chatRoom:id,candidate_id,candidate'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        $mapped = $requests->map(function($participant) use ($user) {
            if ($user->hasRole('candidate')) {
                // For candidates: show who wants to join
                return [
                    'id' => $participant->id,
                    'room_id' => $participant->chat_room_id,
                    'user' => [
                        'id' => $participant->user->id,
                        'name' => $participant->user->first_name . ' ' . $participant->user->last_name,
                        'email' => $participant->user->email,
                    ],
                    'created_at' => $participant->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $participant->created_at->diffForHumans(),
                ];
            } else {
                // For admins: show which candidate's chat they want to join
                $candidate = $participant->chatRoom->candidate;
                return [
                    'id' => $participant->id,
                    'room_id' => $participant->chat_room_id,
                    'user' => [
                        'id' => $candidate->id,
                        'name' => $candidate->first_name . ' ' . $candidate->last_name,
                        'email' => $candidate->email,
                    ],
                    'created_at' => $participant->created_at->format('Y-m-d H:i:s'),
                    'time_ago' => $participant->created_at->diffForHumans(),
                ];
            }
        });

        return response()->json($mapped);
    }

    /**
     * Accept or decline a join request
     */
    public function handleJoinRequest(Request $request, $participantId)
    {
        $request->validate([
            'action' => 'required|in:accept,decline',
        ]);

        $user = auth()->user();
        
        $participant = ChatParticipant::where('id', $participantId)
            ->whereHas('chatRoom', function($q) use ($user) {
                $q->where('candidate_id', $user->id);
            })
            ->first();

        if (!$participant) {
            return response()->json(['error' => 'Join request not found'], 404);
        }

        if ($request->action === 'accept') {
            $participant->update([
                'status' => 'accepted',
                'joined_at' => now(),
            ]);
        } else {
            $participant->update([
                'status' => 'declined',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Set typing indicator
     */
    public function setTyping(Request $request, $roomId)
    {
        $user = auth()->user();
        
        // Check if user is a participant
        $participant = ChatParticipant::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$participant) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        TypingIndicator::updateOrCreate(
            [
                'chat_room_id' => $roomId,
                'user_id' => $user->id,
            ],
            [
                'updated_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Get typing indicators for a room
     */
    public function getTyping(Request $request, $roomId)
    {
        $user = auth()->user();
        
        // Check if user is a participant
        $participant = ChatParticipant::where('chat_room_id', $roomId)
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->first();

        if (!$participant) {
            return response()->json(['error' => 'Not authorized'], 403);
        }

        // Get typing indicators from last 3 seconds
        $typing = TypingIndicator::where('chat_room_id', $roomId)
            ->where('user_id', '!=', $user->id)
            ->where('updated_at', '>', now()->subSeconds(3))
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(function($indicator) {
                return [
                    'user_id' => $indicator->user_id,
                    'user_name' => $indicator->user->first_name . ' ' . $indicator->user->last_name,
                ];
            });

        return response()->json($typing);
    }

    /**
     * Get room details
     */
    public function getRoom(Request $request, $roomId)
    {
        $user = auth()->user();
        
        $room = ChatRoom::where('id', $roomId)
            ->whereHas('participants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['candidate', 'acceptedParticipants.user'])
            ->first();

        if (!$room) {
            return response()->json(['error' => 'Room not found'], 404);
        }

        $participant = $room->participants()->where('user_id', $user->id)->first();

        return response()->json([
            'id' => $room->id,
            'candidate' => [
                'id' => $room->candidate->id,
                'name' => $room->candidate->first_name . ' ' . $room->candidate->last_name,
                'email' => $room->candidate->email,
            ],
            'status' => $participant->status ?? 'pending',
            'participants' => $room->acceptedParticipants->map(function($p) {
                return [
                    'id' => $p->user->id,
                    'name' => $p->user->first_name . ' ' . $p->user->last_name,
                    'email' => $p->user->email,
                ];
            }),
        ]);
    }
}
