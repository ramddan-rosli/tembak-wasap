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
#[Title('Edit Blast Schedule')]
class Edit extends Component
{
    use WithFileUploads;

    public BlastSchedule $blast;

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

    #[Validate('required|integer|min:1|max:300')]
    public int $delay_min = 5;

    #[Validate('required|integer|min:1|max:300|gte:delay_min')]
    public int $delay_max = 15;

    #[Validate('required|string')]
    public string $phone_numbers = '';

    public bool $hasExistingMedia = false;
    public bool $removeExistingMedia = false;

    public function mount(BlastSchedule $blast): void
    {
        abort_if($blast->user_id !== Auth::id(), 403);
        abort_if($blast->status === 'completed', 403);

        $this->blast = $blast;
        $this->name = $blast->name;
        $this->whatsapp_device_id = (string) $blast->whatsapp_device_id;
        $this->message = $blast->message;
        $this->scheduled_at = $blast->scheduled_at->format('Y-m-d\TH:i');
        $this->delay_min = $blast->delay_min;
        $this->delay_max = $blast->delay_max;
        $this->hasExistingMedia = $blast->hasMedia();

        // Load existing recipients as phone numbers
        $this->phone_numbers = $blast->recipients()
            ->pluck('phone_number')
            ->implode("\n");
    }

    public function removeMedia(): void
    {
        $this->media = null;
    }

    public function removeCurrentMedia(): void
    {
        $this->removeExistingMedia = true;
        $this->hasExistingMedia = false;
    }

    public function update(): void
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

        // Handle media
        $mediaPath = $this->blast->media_path;
        $mediaType = $this->blast->media_type;

        if ($this->removeExistingMedia && !$this->media) {
            if ($mediaPath) {
                Storage::disk('public')->delete($mediaPath);
            }
            $mediaPath = null;
            $mediaType = null;
        }

        if ($this->media) {
            // Delete old media if exists
            if ($this->blast->media_path) {
                Storage::disk('public')->delete($this->blast->media_path);
            }
            $mediaPath = $this->media->store('blasts', 'public');
            $mediaType = $this->media->getMimeType();
        }

        // Update blast schedule
        $this->blast->update([
            'whatsapp_device_id' => $this->whatsapp_device_id,
            'name' => $this->name,
            'message' => $this->message,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'delay_min' => $this->delay_min,
            'delay_max' => $this->delay_max,
            'scheduled_at' => $this->scheduled_at,
            'total_recipients' => count($numbers),
        ]);

        // Sync recipients: delete old and create new
        $this->blast->recipients()->delete();
        foreach ($numbers as $number) {
            $this->blast->recipients()->create([
                'phone_number' => $number,
                'status' => 'pending',
            ]);
        }

        session()->flash('success', 'Blast schedule updated successfully!');
        $this->redirect(route('blasts.show', $this->blast));
    }

    protected function parsePhoneNumbers(string $input): array
    {
        $lines = preg_split('/[\r\n,;]+/', $input);
        $numbers = [];

        foreach ($lines as $line) {
            $number = preg_replace('/[^0-9+]/', '', trim($line));

            if (empty($number)) {
                continue;
            }

            $number = preg_replace('/^(\+|00)/', '', $number);

            if (str_starts_with($number, '0')) {
                $number = '6' . $number;
            }

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

        return view('livewire.blasts.edit', [
            'devices' => $devices,
        ]);
    }
}
