<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    // Kirim notifikasi (share kursus)
    public function send(Request $request)
    {
        $request->validate([
            'from_user' => 'required|exists:users,user_id',
            'to_user' => 'required|exists:users,user_id',
            'type' => 'required|string',
            'title' => 'required|string',
            'message' => 'required|string',
            'related_id' => 'nullable|integer'
        ]);

        // Simpan ke database
        $notif = Notifikasi::create($request->all());

        // Kirim FCM ke user tujuan
        $user = User::find($request->to_user);

        if ($user && $user->fcm_token) {
            $this->sendFCM(
                $user->fcm_token,
                $request->title,
                $request->message
            );
        }

        return response()->json([
            'success' => true,
            'notif' => $notif
        ]);
    }

    // Ambil notifikasi user login
    public function getUserNotif(Request $request)
    {
        $user = $request->user();

        $notif = Notifikasi::where('to_user', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notif);
    }

    // Tandai notification sebagai read
    public function markRead($id)
    {
        $notif = Notifikasi::findOrFail($id);
        $notif->is_read = true;
        $notif->save();

        return response()->json(['message' => 'Notification marked read']);
    }

    // FCM Sender
    private function sendFCM($token, $title, $body)
    {
        $data = [
            "to" => $token,
            "notification" => [
                "title" => $title,
                "body" => $body
            ],
            "data" => [
                "screen" => "notifikasi"
            ]
        ];

        $headers = [
            'Authorization: key=' . env('FCM_SERVER_KEY'),
            'Content-Type: application/json'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/fcm/send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_exec($ch);
        curl_close($ch);
    }
}
