<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Http\Request;
use Google\Client;

class NotifikasiController extends Controller
{
    // ================================
    // 1️⃣ KIRIM NOTIFIKASI (dari user ke user)
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

            \Log::info("NOTIF_CREATED", [
                'notif_id' => $notif->notif_id,
                'type' => $notif->type,
                'to_user' => $notif->to_user
            ]);

            // Kirim FCM
            $toUser = User::find($request->to_user);

            if ($toUser && $toUser->fcm_token) {
                \Log::info("SENDING_FCM", [
                    'to_user' => $toUser->user_id,
                    'token_preview' => substr($toUser->fcm_token, 0, 20) . '...'
                ]);
                
                $this->sendFCM(
                    $toUser->fcm_token,
                    $notif
                );
            } else {
                \Log::warning("FCM_TOKEN_NOT_FOUND", [
                    'to_user' => $request->to_user,
                    'has_user' => !!$toUser,
                    'has_token' => $toUser ? !!$toUser->fcm_token : false
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $notif
            ], 201);

        } catch (\Exception $e) {
            \Log::error("NOTIF_SEND_ERROR", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // ✅ KIRIM NOTIFIKASI SISTEM
    // ⚠️ PERBAIKAN: Tambah parameter fromUser
    // ================================
    public function sendSystemNotification($fromUser, $toUser, $type, $title, $message, $relatedId = null)
    {
        try {
            \Log::info("SYSTEM_NOTIF_START", [
                'from_user' => $fromUser,
                'to_user' => $toUser,
                'type' => $type,
                'title' => $title
            ]);

            // ✅ Simpan ke database dengan from_user yang valid
            $notif = Notifikasi::create([
                'from_user' => $fromUser, // ✅ PERBAIKAN: gunakan user_id yang valid
                'to_user' => $toUser,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_id' => $relatedId,
                'is_read' => 0
            ]);

            \Log::info("SYSTEM_NOTIF_SAVED", [
                'notif_id' => $notif->notif_id,
                'from_user' => $fromUser,
                'to_user' => $toUser
            ]);

            // Kirim FCM
            $user = User::find($toUser);

            if ($user && $user->fcm_token) {
                \Log::info("SYSTEM_NOTIF_FCM_SENDING", [
                    'user_id' => $user->user_id,
                    'fcm_token_preview' => substr($user->fcm_token, 0, 30) . '...'
                ]);

                $fcmResult = $this->sendFCM($user->fcm_token, $notif);
                
                \Log::info("SYSTEM_NOTIF_FCM_RESULT", ['success' => $fcmResult]);
            } else {
                \Log::warning("SYSTEM_NOTIF_NO_FCM", [
                    'user_id' => $toUser,
                    'has_user' => !!$user,
                    'has_token' => $user ? !!$user->fcm_token : false,
                    'fcm_token' => $user ? $user->fcm_token : null
                ]);
            }

            return true;

        } catch (\Exception $e) {
            \Log::error("SYSTEM_NOTIF_ERROR", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
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
        try {
            \Log::info("FCM_SEND_START", [
                'notif_id' => $notif->notif_id,
                'token_preview' => substr($token, 0, 30) . '...'
            ]);

            $credentialsPath = storage_path('app/firebase-service-account.json');

            if (!file_exists($credentialsPath)) {
                \Log::error("FCM_CREDENTIALS_NOT_FOUND", [
                    'path' => $credentialsPath
                ]);
                return false;
            }

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
                    "notification" => [
                        "title" => $notif->title,
                        "body" => $notif->message
                    ],
                    "data" => [
                        "title" => $notif->title,
                        "body" => $notif->message,
                        "type" => $notif->type,
                        "related_id" => (string) ($notif->related_id ?? ''),
                        "notif_id" => (string) $notif->notif_id
                    ]
                ]
            ];

            \Log::info("FCM_PAYLOAD", $payload);

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
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            \Log::info("FCM_HTTP_V1_RESPONSE", [
                'http_code' => $httpCode,
                'response' => $response,
                'notif_id' => $notif->notif_id
            ]);
            
            curl_close($ch);

            return $httpCode === 200;

        } catch (\Exception $e) {
            \Log::error("FCM_SEND_ERROR", [
                'error' => $e->getMessage(),
                'notif_id' => $notif->notif_id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
}
