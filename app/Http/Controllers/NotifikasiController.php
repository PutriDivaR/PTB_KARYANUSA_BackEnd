<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Client;

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

        // Kirim FCM
        $toUser = User::find($request->to_user);

        if ($toUser && $toUser->fcm_token) {
            $this->sendFCM(
                $toUser->fcm_token,
                $request->title,
                $request->message
            );
        }


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

        \Log::info("AUTH USER CHECK", [
            'user' => $user
        ]);

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $notif = Notifikasi::where('to_user', intval($user->user_id))
            ->orderBy('created_at', 'desc')
            ->get();

            \Log::info("NOTIF QUERY", [
            'user_id' => $user->user_id,
            'count' => $notif->count(),
            'data' => $notif
        ]);


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
        $credentialsPath = storage_path('app/firebase-service-account..json');

        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();

        $accessToken = $client->getAccessToken()['access_token'];

        $projectId = json_decode(
            file_get_contents($credentialsPath),
            true
        )['project_id'];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            "message" => [
                "token" => $token,
                "data" => [
                    "title" => $title,
                    "body" => $body,
                    "type" => "share_kursus",
                    "screen" => "notifikasi"
                ]
            ]
        ];

        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        \Log::info("FCM_HTTP_V1", ['response' => $response]);

        curl_close($ch);
    }
}