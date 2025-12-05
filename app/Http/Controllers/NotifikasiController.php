<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotifikasiController extends Controller
{
    // Kirim notifikasi (share kursus)
public function sendNotification(Request $request)
{
    $request->validate([
        'from_user' => 'required|integer',
        'to_user' => 'required|integer',
        'type' => 'required|string',
        'title' => 'required|string',
        'message' => 'required|string',
        'related_id' => 'nullable|integer'
    ]);

    try {
        $notif = Notifikasi::create([
            'from_user' => $request->from_user,
            'to_user' => $request->to_user,
            'type' => $request->type,
            'title' => $request->title,
            'message' => $request->message,
            'related_id' => $request->related_id,
            'is_read' => false
        ]);

        return response()->json([
            'success' => true,
            'data' => $notif
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
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
