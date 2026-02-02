<?php

namespace App\Livewire\Blasts;

use App\Models\BlastSchedule;
use App\Models\WhatsappDevice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Create Blast Schedule')]
class Create extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|exists:whatsapp_devices,id')]
    public string $whatsapp_device_id = '';

    #[Validate('required|string|max:4096')]
    public string $message = '';

    #[Validate('nullable|file|max:10240|mimes:jpg,jpeg,png,gif,mp4,pdf,doc,docx')]
    public $media = null;

    #[Validate('required|date|after:now')]
    public string $scheduled_at = '';

    #[Validate('required|string')]
    public string $phone_numbers = '';

    public function mount(): void
    {
        $this->scheduled_at = now()->addMinutes(5)->format('Y-m-d\TH:i');
    }

    public function removeMedia(): void
    {
        $this->media = null;
    }

    public function create(): void
    {
        $this->validate();

        // Parse phone numbers
        $numbers = $this->parsePhoneNumbers($this->phone_numbers);

        if (empty($numbers)) {
            $this->addError('phone_numbers', 'Please enter at least one valid phone number.');
            return;
        }

        // Verify device belongs to user and is connected
        $device = Auth::user()->whatsappDevices()
            ->where('id', $this->whatsapp_device_id)
            ->where('status', 'connected')
            ->first();

        if (!$device) {
            $this->addError('whatsapp_device_id', 'Selected device is not connected.');
            return;
        }

        // Handle media upload
        $mediaPath = null;
        $mediaType = null;

        if ($this->media) {
            $mediaPath = $this->media->store('blasts', 'public');
            $mediaType = $this->media->getMimeType();
        }

        // Create blast schedule
        $blast = BlastSchedule::create([
            'user_id' => Auth::id(),
            'whatsapp_device_id' => $this->whatsapp_device_id,
            'name' => $this->name,
            'message' => $this->message,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'scheduled_at' => $this->scheduled_at,
            'status' => 'pending',
            'total_recipients' => count($numbers),
        ]);

        // Create recipients
        foreach ($numbers as $number) {
            $blast->recipients()->create([
                'phone_number' => $number,
                'status' => 'pending',
            ]);
        }

        session()->flash('success', 'Blast schedule created successfully!');
        $this->redirect(route('blasts.show', $blast));
    }

    protected function parsePhoneNumbers(string $input): array
    {
        $lines = preg_split('/[\r\n,;]+/', $input);
        $numbers = [];

        foreach ($lines as $line) {
            $number = preg_replace('/[^0-9+]/', '', trim($line));

            // Skip empty lines
            if (empty($number)) {
                continue;
            }

            // Remove leading + or 00
            $number = preg_replace('/^(\+|00)/', '', $number);

            // Add Malaysia country code if number starts with 0
            if (str_starts_with($number, '0')) {
                $number = '6' . $number;
            }

            // Validate minimum length (Malaysian numbers: 60123456789 = 11 digits)
            if (strlen($number) >= 10 && strlen($number) <= 15) {
                $numbers[] = $number;
            }
        }

        return array_unique($numbers);
    }

    public function getPhoneCountProperty(): int
    {
        return count($this->parsePhoneNumbers($this->phone_numbers));
    }

    public function render()
    {
        $devices = Auth::user()->whatsappDevices()
            ->where('status', 'connected')
            ->get();

        return view('livewire.blasts.create', [
            'devices' => $devices,
        ]);
    }
}
