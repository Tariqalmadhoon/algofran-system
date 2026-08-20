<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'], 'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'], 'website' => ['nullable', 'max:0'],
        ]);
        unset($data['website']);
        ContactMessage::query()->create($data);

        return back()->with('success', 'وصلت رسالتك بنجاح، وسيتواصل معك فريق المركز قريبًا.');
    }
}
