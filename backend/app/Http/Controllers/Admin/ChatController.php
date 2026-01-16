<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\TypingIndicator;
use App\Models\Chat;
use App\Models\Candidate;
use App\Models\User;
use App\Models\JobMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /**
     * Start a chat with a candidate
     */
    public function startChat(Request $request)
    {
        try {
            $request->validate([
                'candidate_id' => 'required|exists:candidates,id',
                'match_id' => 'nullable|exists:matches,id',
                'application_id' => 'nullable|exists:applications,id',
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $candidate = Candidate::findOrFail($request->candidate_id);
            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'User must belong to a company'], 400);
            }

            // Check if active chat already exists
            $existingChat = Chat::with(['candidate', 'user', 'company', 'latestMessage', 'match.vacancy', 'application.vacancy'])
                ->where('user_id', $user->id)
                ->where('candidate_id', $candidate->id)
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->first();

            if ($existingChat) {
                // Check if there are any messages in this chat
                $messageCount = $existingChat->messages()->count();
                
                // If no messages exist, create welcome message
                if ($messageCount === 0) {
                    // Get vacancy information if available
                    $vacancyTitle = null;
                    if ($existingChat->match && $existingChat->match->vacancy) {
                        $vacancyTitle = $existingChat->match->vacancy->title;
                    } elseif ($existingChat->application && $existingChat->application->vacancy) {
                        $vacancyTitle = $existingChat->application->vacancy->title;
                    }

                    // Create welcome message with vacancy and interest in conversation
                    if ($vacancyTitle) {
                        $welcomeMessage = "Hallo {$candidate->first_name}! Ik ben {$user->first_name} {$user->last_name} van {$company->name}. We hebben interesse in jouw profiel voor de vacature '{$vacancyTitle}'. Ik zou graag met je in gesprek willen gaan om te kijken of we een match kunnen maken. Heb je tijd voor een gesprek?";
                    } else {
                        $welcomeMessage = "Hallo {$candidate->first_name}! Ik ben {$user->first_name} {$user->last_name} van {$company->name}. We hebben interesse in jouw profiel en zouden graag met je in gesprek willen gaan. Heb je tijd voor een gesprek?";
                    }
                    
                    ChatMessage::create([
                        'chat_id' => $existingChat->id,
                        'sender_id' => $user->id,
                        'sender_type' => User::class,
                        'message' => $welcomeMessage,
                    ]);
                    
                    // Reload chat to include the new message
                    $existingChat->load('latestMessage');
                }
                
                return response()->json([
                    'success' => true,
                    'chat_id' => $existingChat->id,
                    'chat' => $this->formatChat($existingChat),
                ]);
            }

            // Create new chat
            $chat = Chat::create([
                'user_id' => $user->id,
                'candidate_id' => $candidate->id,
                'company_id' => $company->id,
                'match_id' => $request->match_id,
                'application_id' => $request->application_id,
                'is_active' => true,
            ]);
            
            // Reload with relationships
            $chat->load(['candidate', 'user', 'company', 'latestMessage', 'match.vacancy', 'application.vacancy']);

            // Check if there are any existing messages in this chat
            $messageCount = $chat->messages()->count();
            
            // Only send welcome message if this is the first message (no existing messages)
            if ($messageCount === 0) {
                // Get vacancy information if available
                $vacancyTitle = null;
                if ($chat->match && $chat->match->vacancy) {
                    $vacancyTitle = $chat->match->vacancy->title;
                } elseif ($chat->application && $chat->application->vacancy) {
                    $vacancyTitle = $chat->application->vacancy->title;
                }

                // Create welcome message with vacancy and interest in conversation
                if ($vacancyTitle) {
                    $welcomeMessage = "Hallo {$candidate->first_name}! Ik ben {$user->first_name} {$user->last_name} van {$company->name}. We hebben interesse in jouw profiel voor de vacature '{$vacancyTitle}'. Ik zou graag met je in gesprek willen gaan om te kijken of we een match kunnen maken. Heb je tijd voor een gesprek?";
                } else {
                    $welcomeMessage = "Hallo {$candidate->first_name}! Ik ben {$user->first_name} {$user->last_name} van {$company->name}. We hebben interesse in jouw profiel en zouden graag met je in gesprek willen gaan. Heb je tijd voor een gesprek?";
                }
                
                ChatMessage::create([
                    'chat_id' => $chat->id,
                    'sender_id' => $user->id,
                    'sender_type' => User::class,
                    'message' => $welcomeMessage,
                ]);
            }

            return response()->json([
                'success' => true,
                'chat_id' => $chat->id,
                'chat' => $this->formatChat($chat),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error starting chat: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to start chat: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get all active chats for the current user
     */
    public function getActiveChats(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json([]);
            }

            $chats = Chat::where('user_id', $user->id)
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->with(['candidate', 'user', 'company', 'latestMessage'])
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function($chat) {
                    return $this->formatChat($chat);
                });

            return response()->json($chats);
        } catch (\Exception $e) {
            \Log::error('Error loading active chats: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load chats: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get messages for a chat
     */
    public function getChatMessages(Request $request, $chatId)
    {
        $user = auth()->user();
        
        $chat = Chat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        $messages = $chat->messages()
            ->with(['sender'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($user) {
                $senderName = 'Unknown';
                if ($message->sender) {
                    $senderName = $message->sender->first_name . ' ' . $message->sender->last_name;
                }
                
                // Get avatar URL with error handling
                $avatarUrl = asset('assets/media/avatars/300-5.png'); // Default avatar
                try {
                    if ($message->isFromUser()) {
                        // Message from company user (backend) - get from user.photo route
                        if ($message->sender) {
                            // Reload sender to ensure we have the latest photo_blob status
                            $sender = \App\Models\User::find($message->sender->id);
                            if ($sender && $sender->photo_blob) {
                                try {
                                    $avatarUrl = route('user.photo', $sender->id);
                                } catch (\Exception $e) {
                                    // Route error, use default
                                    $avatarUrl = asset('assets/media/avatars/300-2.png');
                                    \Log::error('Error getting user avatar URL in Admin getChatMessages: ' . $e->getMessage());
                                }
                            } else {
                                $avatarUrl = asset('assets/media/avatars/300-2.png');
                            }
                        } else {
                            $avatarUrl = asset('assets/media/avatars/300-2.png');
                        }
                    } else {
                        // Message from candidate - get via User relation (candidates share email with users)
                        if ($message->sender) {
                            // Candidates share email with Users, so get the User record for photo
                            $candidateUser = \App\Models\User::where('email', $message->sender->email)->first();
                            if ($candidateUser && $candidateUser->photo_blob) {
                                try {
                                    $avatarUrl = route('user.photo', $candidateUser->id);
                                } catch (\Exception $e) {
                                    // Fallback to default
                                    $avatarUrl = asset('assets/media/avatars/300-5.png');
                                    \Log::error('Error getting candidate user avatar URL in Admin getChatMessages: ' . $e->getMessage());
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
                    \Log::error('Error getting avatar URL in Admin getChatMessages: ' . $e->getMessage());
                }
                
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $senderName,
                    'sender_avatar' => $avatarUrl,
                    'is_own' => $message->isFromUser() && $message->sender_id === $user->id,
                    'read_at' => $message->read_at ? $message->read_at->format('Y-m-d H:i:s') : null,
                    'is_read' => $message->read_at !== null,
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'time' => $message->created_at->format('H:i'),
                ];
            });

        // Mark messages as read
        $chat->messages()
            ->where('sender_type', Candidate::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /**
     * Send a message in a chat
     */
    public function sendChatMessage(Request $request, $chatId)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = auth()->user();
        
        $chat = Chat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        $message = ChatMessage::create([
            'chat_id' => $chat->id,
            'sender_id' => $user->id,
            'sender_type' => User::class,
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
                'sender_name' => $user->first_name . ' ' . $user->last_name,
                'is_own' => true,
                'read_at' => null,
                'is_read' => false,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'time' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * End a chat
     */
    public function endChat(Request $request, $chatId)
    {
        $user = auth()->user();
        
        $chat = Chat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        $chat->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get chat history for a match or application
     */
    public function getChatHistory(Request $request)
    {
        $request->validate([
            'match_id' => 'nullable|exists:job_matches,id',
            'application_id' => 'nullable|exists:applications,id',
        ]);

        $user = auth()->user();
        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'User must belong to a company'], 400);
        }

        $query = Chat::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('is_active', false)
            ->with(['candidate', 'user', 'company']);

        if ($request->match_id) {
            $query->where('match_id', $request->match_id);
        } elseif ($request->application_id) {
            $query->where('application_id', $request->application_id);
        } else {
            return response()->json(['error' => 'match_id or application_id required'], 400);
        }

        $chat = $query->first();

        if (!$chat) {
            return response()->json(['messages' => []]);
        }

        $messages = $chat->messages()
            ->with(['sender'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($user) {
                $senderName = $message->isFromUser() 
                    ? $message->sender->first_name . ' ' . $message->sender->last_name
                    : $message->sender->first_name . ' ' . $message->sender->last_name;
                
                return [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_id' => $message->sender_id,
                    'sender_type' => $message->sender_type,
                    'sender_name' => $senderName,
                    'is_from_user' => $message->isFromUser(),
                    'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                    'time' => $message->created_at->format('H:i'),
                    'date' => $message->created_at->format('d-m-Y'),
                ];
            });

        return response()->json([
            'chat' => [
                'id' => $chat->id,
                'user' => [
                    'id' => $chat->user->id,
                    'name' => $chat->user->first_name . ' ' . $chat->user->last_name,
                    'email' => $chat->user->email,
                ],
                'candidate' => [
                    'id' => $chat->candidate->id,
                    'name' => $chat->candidate->first_name . ' ' . $chat->candidate->last_name,
                    'email' => $chat->candidate->email,
                ],
                'company' => [
                    'id' => $chat->company->id,
                    'name' => $chat->company->name,
                ],
                'ended_at' => $chat->ended_at ? $chat->ended_at->format('Y-m-d H:i:s') : null,
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Set typing indicator
     */
    public function setChatTyping(Request $request, $chatId)
    {
        $user = auth()->user();
        
        $chat = Chat::where('id', $chatId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Store typing indicator in cache or session
        cache()->put("chat_typing_{$chatId}_{$user->id}", true, 3); // 3 seconds

        return response()->json(['success' => true]);
    }

    /**
     * Get unread chat count for current user
     */
    public function getUnreadCount(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['unread_count' => 0]);
        }
        
        // Count unread messages in active chats
        // For admin users: count messages from candidates
        // For candidate users: count messages from admins
        $userClass = get_class($user);
        
        $unreadCount = ChatMessage::whereHas('chat', function($query) use ($user) {
            $query->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('candidate_id', $user->id);
            })
            ->where('is_active', true);
        })
        ->where('sender_type', '!=', $userClass)
        ->whereNull('read_at')
        ->count();
        
        return response()->json(['unread_count' => $unreadCount]);
    }
    
    /**
     * Get typing indicators for a chat
     */
    public function getChatTyping(Request $request, $chatId)
    {
        $user = auth()->user();
        
        $chat = Chat::where('id', $chatId)
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('candidate_id', $user->id);
            })
            ->where('is_active', true)
            ->firstOrFail();

        $typingUsers = [];
        
        // Check if candidate is typing
        if (cache()->has("chat_typing_{$chatId}_{$chat->candidate_id}")) {
            $typingUsers[] = [
                'id' => $chat->candidate_id,
                'name' => $chat->candidate->first_name . ' ' . $chat->candidate->last_name,
                'type' => 'candidate',
            ];
        }
        
        // Check if user is typing
        if ($chat->user_id !== $user->id && cache()->has("chat_typing_{$chatId}_{$chat->user_id}")) {
            $typingUsers[] = [
                'id' => $chat->user_id,
                'name' => $chat->user->first_name . ' ' . $chat->user->last_name,
                'type' => 'user',
            ];
        }

        return response()->json($typingUsers);
    }

    /**
     * Get candidates with matches for the current user's company
     */
    public function getCandidatesWithMatches(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $company = $user->company;
            if (!$company) {
                return response()->json([]);
            }

            // Get matches with vacancies from this company, grouped by candidate
            $matches = JobMatch::whereHas('vacancy', function($query) use ($company) {
                $query->where('company_id', $company->id);
            })
            ->with(['candidate', 'vacancy'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('candidate_id')
            ->map(function($candidateMatches) {
                $candidate = $candidateMatches->first()->candidate;
                $latestMatch = $candidateMatches->first();
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->first_name . ' ' . $candidate->last_name,
                    'email' => $candidate->email,
                    'match_id' => $latestMatch->id,
                    'vacancy_title' => $latestMatch->vacancy ? $latestMatch->vacancy->title : null,
                ];
            })
            ->values()
            ->sortBy('name')
            ->values();

            return response()->json($matches);
        } catch (\Exception $e) {
            \Log::error('Error loading candidates with matches: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to load candidates: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Format chat for JSON response
     */
    private function formatChat($chat)
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
                        \Log::error('Error getting user avatar URL in formatChat: ' . $e->getMessage());
                    }
                }
            }
            
            $candidateAvatarUrl = asset('assets/media/avatars/300-5.png');
            if ($chat->candidate) {
                try {
                    // Candidates share email with Users, so get the User record for photo
                    $candidateUser = \App\Models\User::where('email', $chat->candidate->email)->first();
                    if ($candidateUser && $candidateUser->photo_blob) {
                        try {
                            $candidateAvatarUrl = route('user.photo', $candidateUser->id);
                        } catch (\Exception $e) {
                            // Fallback to default
                            \Log::error('Error getting candidate user avatar URL in formatChat: ' . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    // If route doesn't exist or user doesn't have access, keep default avatar
                    \Log::error('Error getting candidate avatar URL in formatChat: ' . $e->getMessage());
                }
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
            ];
        } catch (\Exception $e) {
            \Log::error('Error formatting chat: ' . $e->getMessage(), [
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
