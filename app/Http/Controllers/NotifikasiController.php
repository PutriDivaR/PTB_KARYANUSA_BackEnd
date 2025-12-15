<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\Request;
use Google\Client;

class NotifikasiController extends Controller
{
    // ================================
    // 1️⃣ KIRIM NOTIFIKASI 
    // ================================
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
            // Simpan ke database
            $notif = Notifikasi::create([
                'from_user' => $request->from_user,
                'to_user' => $request->to_user,
                'type' => $request->type,
                'title' => $request->title,
                'message' => $request->message,
                'related_id' => $request->related_id,
                'is_read' => 0
            ]);

            // Kirim FCM
            $toUser = User::find($request->to_user);

            if ($toUser && $toUser->fcm_token) {
                $this->sendFCM(
                    $toUser->fcm_token,
                    $notif
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

    // ================================
    // 2️⃣ AMBIL NOTIFIKASI USER LOGIN
    // ================================
    public function getUserNotif(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $notif = Notifikasi::where('to_user', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notif);
    }

    // ================================
    // 3️⃣ MARK NOTIFIKASI SEBAGAI READ
    // ================================
    public function markRead(Request $request, $id)
    {
        $user = $request->user();

        $notif = Notifikasi::where('notif_id', $id)
            ->where('to_user', $user->user_id)
            ->firstOrFail();

        $notif->update(['is_read' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    // ================================
    // 4️⃣ FCM SENDER
    // ================================
    private function sendFCM($token, Notifikasi $notif)
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

        // DATA PENTING UNTUK ANDROID
        $payload = [
            "message" => [
                "token" => $token,
                "data" => [
                    "title" => $notif->title,
                    "body" => $notif->message,
                    "type" => $notif->type,
                    "related_id" => (string) $notif->related_id,
                    "notif_id" => (string) $notif->notif_id
                ]
            ]
        ];

        $headers = [
            "Authorization: Bearer {$accessToken}",
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        \Log::info("FCM_HTTP_V1", ['response' => $response]);
        curl_close($ch);
    }
}
