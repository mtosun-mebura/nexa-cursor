<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\Notification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    use TenantFilter;
    
    public function index()
    {
        $query = Notification::with('user');
        $this->applyTenantFilter($query);
        $notifications = $query->paginate(10);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function create()
    {
        $users = \App\Models\User::where('company_id', $this->getTenantId())->get();
        return view('admin.notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:match,interview,application,system,email,reminder',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'read_at' => 'nullable|date',
            'action_url' => 'nullable|url',
            'data' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        // Check if user belongs to the same company
        $user = \App\Models\User::find($request->user_id);
        if (!$user || $user->company_id !== $this->getTenantId()) {
            abort(403, 'Je kunt alleen notificaties maken voor gebruikers in je eigen bedrijf.');
        }

        $data = $request->all();
        $data['company_id'] = $this->getTenantId();
        
        Notification::create($data);
        return redirect()->route('admin.notifications.index')->with('success', 'Notificatie succesvol aangemaakt.');
    }

    public function show(Notification $notification)
    {
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        return view('admin.notifications.show', compact('notification'));
    }

    public function edit(Notification $notification)
    {
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        $users = \App\Models\User::where('company_id', $this->getTenantId())->get();
        return view('admin.notifications.edit', compact('notification', 'users'));
    }

    public function update(Request $request, Notification $notification)
    {
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:match,interview,application,system,email,reminder',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'read_at' => 'nullable|date',
            'action_url' => 'nullable|url',
            'data' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        // Check if user belongs to the same company
        $user = \App\Models\User::find($request->user_id);
        if (!$user || $user->company_id !== $this->getTenantId()) {
            abort(403, 'Je kunt alleen notificaties bewerken voor gebruikers in je eigen bedrijf.');
        }

        $notification->update($request->all());
        return redirect()->route('admin.notifications.index')->with('success', 'Notificatie succesvol bijgewerkt.');
    }

    public function destroy(Notification $notification)
    {
        // Check if user can access this resource
        if (!$this->canAccessResource($notification)) {
            abort(403, 'Je hebt geen toegang tot deze notificatie.');
        }
        
        $notification->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'Notificatie succesvol verwijderd.');
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification)
    {
        // Check if the notification belongs to the authenticated user and same company
        if ($notification->user_id !== auth()->id() || !$this->canAccessResource($notification)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read for the authenticated user
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', auth()->id())
            ->where('company_id', $this->getTenantId())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }
}
