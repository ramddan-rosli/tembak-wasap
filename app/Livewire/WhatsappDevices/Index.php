<?php

namespace App\Livewire\WhatsappDevices;

use App\Models\WhatsappDevice;
use App\Services\WhatsappService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('WhatsApp Devices')]
class Index extends Component
{
    public bool $showAddModal = false;
    public bool $showQrModal = false;
    public string $newDeviceName = '';
    public ?int $selectedDeviceId = null;
    public ?string $qrCode = null;
    public ?string $qrError = null;

    public function openAddModal(): void
    {
        $this->newDeviceName = '';
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->newDeviceName = '';
    }

    public function addDevice(): void
    {
        $this->validate([
            'newDeviceName' => 'required|string|max:255',
        ]);

        $device = Auth::user()->whatsappDevices()->create([
            'name' => $this->newDeviceName,
            'status' => 'disconnected',
        ]);

        $this->closeAddModal();
        $this->connectDevice($device->id);
    }

    public function connectDevice(int $deviceId): void
    {
        $device = Auth::user()->whatsappDevices()->findOrFail($deviceId);
        $this->selectedDeviceId = $device->id;
        $this->qrCode = null;
        $this->qrError = null;
        $this->showQrModal = true;

        // Update status to connecting
        $device->update(['status' => 'connecting']);

        $this->fetchQrCode();
    }

    /**
     * Get the selected device from database (fresh data)
     */
    public function getSelectedDevice(): ?WhatsappDevice
    {
        if (!$this->selectedDeviceId) {
            return null;
        }
        return WhatsappDevice::find($this->selectedDeviceId);
    }

    public function fetchQrCode(): void
    {
        $device = $this->getSelectedDevice();
        if (!$device) {
            return;
        }

        $service = new WhatsappService();

        // Call login to start/reset the session and get QR code
        $result = $service->login($device->instance_id);

        if ($result['success']) {
            $data = $result['data'];

            // Check if already connected (has user data)
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['data']['id'])) {
                $device->update([
                    'status' => 'connected',
                    'phone_number' => $data['data']['id'] ?? null,
                    'last_connected_at' => now(),
                ]);
                $this->closeQrModal();
                session()->flash('success', 'Device connected successfully!');
                return;
            }

            // Get QR code (server returns 'base64' field)
            if (isset($data['base64'])) {
                $this->qrCode = $this->formatQrCode($data['base64']);
            } elseif (isset($data['qr_code'])) {
                $this->qrCode = $this->formatQrCode($data['qr_code']);
            } elseif (isset($data['qr'])) {
                $this->qrCode = $this->formatQrCode($data['qr']);
            } else {
                // QR not ready yet, will be fetched on poll
                $this->qrError = $data['message'] ?? 'Waiting for QR code...';
            }
        } else {
            $this->qrError = $result['error'] ?? 'Failed to connect. Make sure WhatsApp server is running.';
        }
    }

    /**
     * Poll device status from database - auto-detect changes from webhook
     */
    public function pollDeviceStatus(): void
    {
        $device = $this->getSelectedDevice();
        if (!$device) {
            return;
        }

        // Refresh from database to get latest status from webhook
        $device->refresh();

        // Auto-close modal if connected
        if ($device->status === 'connected') {
            $this->closeQrModal();
            session()->flash('success', 'Device connected successfully!');
            return;
        }

        // Auto-update QR code if changed in database
        if ($device->qr_code) {
            $qrCode = $this->formatQrCode($device->qr_code);
            if ($qrCode !== $this->qrCode) {
                $this->qrCode = $qrCode;
                $this->qrError = null;
            }
        }

        // If no QR code yet and no error, show waiting message
        if (!$this->qrCode && !$this->qrError && $device->status === 'connecting') {
            $this->qrError = 'Waiting for QR code...';
        }
    }

    /**
     * Format QR code to ensure it has the data URI prefix
     */
    protected function formatQrCode(?string $qrCode): ?string
    {
        if (!$qrCode) {
            return null;
        }

        // If already has data URI prefix, return as is
        if (str_starts_with($qrCode, 'data:image/')) {
            return $qrCode;
        }

        // Add data URI prefix for PNG
        return 'data:image/png;base64,' . $qrCode;
    }

    public function refreshQrCode(): void
    {
        $device = $this->getSelectedDevice();
        if (!$device) {
            return;
        }

        $this->qrError = null;

        $service = new WhatsappService();
        $result = $service->getQrCode($device->instance_id);

        if ($result['success']) {
            $data = $result['data'];

            if (isset($data['base64'])) {
                $this->qrCode = $this->formatQrCode($data['base64']);
            } elseif (isset($data['status']) && $data['status'] === 'error') {
                $this->qrError = $data['message'] ?? 'Failed to get QR code';
            }
        } else {
            $this->qrError = $result['error'] ?? 'Failed to get QR code';
        }
    }

    public function closeQrModal(): void
    {
        $this->showQrModal = false;
        $this->selectedDeviceId = null;
        $this->qrCode = null;
        $this->qrError = null;
    }

    public function disconnectDevice(int $deviceId): void
    {
        $device = Auth::user()->whatsappDevices()->findOrFail($deviceId);

        $service = new WhatsappService();
        $service->logout($device->instance_id);

        $device->update([
            'status' => 'disconnected',
            'phone_number' => null,
            'qr_code' => null,
        ]);

        session()->flash('success', 'Device disconnected successfully!');
    }

    public function deleteDevice(int $deviceId): void
    {
        $device = Auth::user()->whatsappDevices()->findOrFail($deviceId);

        // Logout first if connected
        if ($device->status === 'connected') {
            $service = new WhatsappService();
            $service->logout($device->instance_id);
        }

        $device->delete();
        session()->flash('success', 'Device deleted successfully!');
    }

    public function render()
    {
        $devices = Auth::user()->whatsappDevices()->latest()->get();
        $selectedDevice = $this->getSelectedDevice();

        return view('livewire.whatsapp-devices.index', [
            'devices' => $devices,
            'selectedDevice' => $selectedDevice,
        ]);
    }
}
