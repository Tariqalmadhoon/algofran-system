<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationCenter extends Component
{
    use WithPagination;

    public string $scope = 'unread';

    public function mount(): void
    {
        Gate::authorize('notifications.view');
    }

    public function updatedScope(): void
    {
        $this->resetPage();
    }

    public function markRead(string $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $url = data_get($notification->data, 'url');
        if ($url) {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        session()->flash('success', 'تم تعليم جميع الإشعارات كمقروءة.');
    }

    public function delete(string $id): void
    {
        auth()->user()->notifications()->whereKey($id)->delete();
    }

    public function render(): View
    {
        $query = auth()->user()->notifications()->latest();
        if ($this->scope === 'unread') {
            $query->whereNull('read_at');
        }

        return view('livewire.notification-center', [
            'notifications' => $query->paginate(15),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'totalCount' => auth()->user()->notifications()->count(),
        ]);
    }
}
