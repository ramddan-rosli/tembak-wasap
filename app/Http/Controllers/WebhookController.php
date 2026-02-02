<?php

namespace App\Http\Controllers;

use App\Models\WhatsappDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle WhatsApp status webhook from the Node.js server.
     */
    public function whatsappStatus(Request $request): JsonResponse
    {
        Log::info('WhatsApp webhook received', $request->all());

        $instanceId = $request->input('instance_id');
        $status = $request->input('status');

        if (!$instanceId) {
            return response()->json(['error' => 'Instance ID required'], 400);
        }

        $device = WhatsappDevice::where('instance_id', $instanceId)->first();

        if (!$device) {
            Log::warning("WhatsApp webhook: Device not found for instance {$instanceId}");
            return response()->json(['error' => 'Device not found'], 404);
        }

        // Map status from webhook to our status
        $mappedStatus = match ($status) {
            'connected' => 'connected',
            'disconnected', 'close', 'logout' => 'disconnected',
            'qr', 'connecting', 'reconnecting' => 'connecting',
            default => $device->status,
        };

        $updateData = [
            'status' => $mappedStatus,
        ];

        // Update phone number if connected
        if ($status === 'connected') {
            $updateData['last_connected_at'] = now();
            $updateData['qr_code'] = null;

            if ($request->has('phone_number')) {
                $updateData['phone_number'] = $request->input('phone_number');
            }
        }

        // Clear data on disconnect
        if (in_array($status, ['disconnected', 'close', 'logout'])) {
            $updateData['qr_code'] = null;
        }

        $device->update($updateData);

        Log::info("WhatsApp device {$device->id} status updated to {$mappedStatus}");

        return response()->json(['success' => true]);
    }
}
