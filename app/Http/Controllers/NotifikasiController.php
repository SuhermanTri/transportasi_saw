<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiController extends Controller
{
    /**
     * Tampilkan semua notifikasi.
     * TIDAK lagi auto-mark-all-read di sini — blade yang handle via AJAX setelah render.
     */
    public function index()
    {
        $notifikasi = Notifikasi::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('notifikasi.index', compact('notifikasi'));
    }

    /**
     * Mark satu notifikasi sebagai dibaca lalu redirect ke URL-nya.
     * Dipanggil saat user klik tombol "Lihat".
     */
    public function goto($id)
    {
        $notif = Notifikasi::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $notif->update(['status' => 'read']);

        return redirect($notif->url ?? route('notifikasi.index'));
    }

    /**
     * Mark satu notifikasi sebagai dibaca (legacy / API).
     */
    public function markRead($id)
    {
        Notifikasi::where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['status' => 'read']);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back();
    }

    /**
     * Mark semua notifikasi user sebagai dibaca.
     * Dipanggil via AJAX dari halaman notifikasi (setelah halaman render).
     */
    public function markAllRead()
    {
        Notifikasi::where('user_id', Auth::id())
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi ditandai dibaca.');
    }

    /**
     * Endpoint JSON untuk badge count — dipakai oleh AJAX polling di topbar.
     */
    public function unreadCount()
    {
        return response()->json(['count' => Notifikasi::unreadCount(Auth::id())]);
    }
}
