<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $baseUrl;
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.server_url');
        $this->accessToken = config('whatsapp.access_token');
    }

    /**
     * Get QR code for connecting WhatsApp device.
     */
    public function getQrCode(string $instanceId): array
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/get_qrcode', [
                'access_token' => $this->accessToken,
                'instance_id' => $instanceId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to get QR code'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp getQrCode error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Start login process for WhatsApp device.
     */
    public function login(string $instanceId): array
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/login', [
                'access_token' => $this->accessToken,
                'instance_id' => $instanceId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to start login'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp login error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get instance/connection status.
     */
    public function getStatus(string $instanceId): array
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/instance', [
                'access_token' => $this->accessToken,
                'instance_id' => $instanceId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to get status'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp getStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Logout/disconnect WhatsApp device.
     */
    public function logout(string $instanceId): array
    {
        try {
            $response = Http::timeout(30)->get($this->baseUrl . '/logout', [
                'access_token' => $this->accessToken,
                'instance_id' => $instanceId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to logout'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp logout error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a text message.
     */
    public function sendMessage(string $instanceId, string $chatId, string $message, ?string $mediaUrl = null, ?string $filename = null): array
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'message' => $message,
            ];

            if ($mediaUrl) {
                $payload['media_url'] = $mediaUrl;
                $payload['caption'] = $message;
                unset($payload['message']);

                if ($filename) {
                    $payload['filename'] = $filename;
                }
            }

            $response = Http::timeout(60)
                ->withQueryParameters([
                    'access_token' => $this->accessToken,
                    'instance_id' => $instanceId,
                ])
                ->post($this->baseUrl . '/send_message', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'success' => true,
                        'data' => $data,
                    ];
                }
                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Unknown error',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Failed to send message'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp sendMessage error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
