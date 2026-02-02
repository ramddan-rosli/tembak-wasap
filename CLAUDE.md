# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Tembak-Wasap is a WhatsApp bulk messaging application with two main components:
- **Laravel 12 backend** (PHP 8.2+) - User management, scheduling, tracking, and Livewire-based UI
- **Node.js WhatsApp server** - Uses Baileys library for WhatsApp Web automation

## Development Commands

### Full Setup
```bash
composer install && php artisan key:generate && php artisan migrate
npm install && npm run build
cd whatsapp_server && npm install
```

### Running Development Servers
Run these concurrently in separate terminals:
```bash
php artisan serve                    # Laravel on port 8000
php artisan queue:listen             # Queue worker (required for blast sending)
npm run dev                          # Vite asset compilation
cd whatsapp_server && node app.js   # WhatsApp server on port 3300
```

Or use the composer script (starts all except WhatsApp server):
```bash
composer dev
```

### Database
```bash
php artisan migrate                  # Run migrations
php artisan migrate:fresh            # Reset and re-run migrations
```

### Tests
```bash
php artisan test                     # Run all tests
php artisan test --filter=TestName   # Run specific test
```

## Architecture

### Communication Flow
```
User Browser ↔ Laravel (Livewire) ↔ WhatsApp Server (Node.js) ↔ WhatsApp Web (Baileys)
                      ↓                       ↓
                 MySQL DB ←────── Webhooks ───┘
```

1. **Device Connection**: Livewire component calls Laravel's `WhatsappService` → Node.js `/login` endpoint → Baileys generates QR → Stored in DB → Livewire polling displays QR → User scans → Baileys notifies connected → Webhook updates Laravel DB
2. **Message Sending**: Queue job `ProcessBlastSchedule` dispatches individual `SendWhatsappMessage` jobs with staggered delays → Each job calls WhatsappService → Node.js Baileys sends message

### Key Laravel Files
- `app/Services/WhatsappService.php` - HTTP client for Node.js server (login, logout, sendMessage, getStatus)
- `app/Http/Controllers/WebhookController.php` - Receives status updates from Node.js
- `app/Jobs/ProcessBlastSchedule.php` - Orchestrates blast sending
- `app/Jobs/SendWhatsappMessage.php` - Sends individual messages with retry logic
- `app/Livewire/WhatsappDevices/Index.php` - Device management with QR code polling
- `config/whatsapp.php` - Server URL, access token, message delay settings

### Key Node.js Files (whatsapp_server/)
- `app.js` - Express server with routes: `/login`, `/logout`, `/get_qrcode`, `/send_message`, `/instance`
- `wasapi/wasapi.js` - Core Baileys wrapper (socket management, connection handling, reconnection logic)
- `wasapi-db.js` - MySQL operations and webhook notifications to Laravel

## WhatsApp Server (Node.js) Details

### API Endpoints
All endpoints require `access_token` query parameter matching `WHATSAPP_LICENSE_KEY`.

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/login?instance_id=` | GET | Force relogin - clears old session, starts fresh, generates new QR |
| `/get_qrcode?instance_id=` | GET | Get QR code without resetting session |
| `/logout?instance_id=` | GET | Disconnect and cleanup session |
| `/send_message?instance_id=` | POST | Send text or media message |
| `/instance?instance_id=` | GET | Get current connection status |

### Send Message Body (POST /send_message)
```json
{
  "chat_id": "60123456789@s.whatsapp.net",
  "message": "Text message content",
  "media_url": "https://example.com/image.jpg",
  "caption": "Media caption",
  "filename": "document.pdf"
}
```

### Baileys Connection Handling (wasapi.js)
- `sessions{}` - In-memory map of active WhatsApp sockets by instance_id
- `makeWASocket(instance_id)` - Creates Baileys socket with multi-file auth state
- `handleConnectionUpdate()` - Handles QR generation, connection open/close events
- `handleReconnect()` - Exponential backoff: `2^attempt * 1000ms`, max 60s, max 10 attempts
- `cleanupSession()` - Closes socket, removes from memory, deletes session folder

### Disconnect Reason Codes
| Code | Reason | Action |
|------|--------|--------|
| 401 | loggedOut | Full cleanup, user logged out from phone |
| 440 | connectionReplaced | Another device took over the session |
| 500 | badSession | Invalid session state, needs cleanup |
| 411 | multideviceMismatch | Device type mismatch |
| Other | Various | Exponential backoff reconnect |

### Webhook Notifications
Node.js sends POST to Laravel `/api/webhooks/whatsapp/status`:
```json
{
  "instance_id": "uuid",
  "status": "connected|disconnected|qr|connecting|reconnecting",
  "phone_number": "60123456789",
  "reason": "loggedOut|connectionReplaced|...",
  "error_code": 401
}
```

### Startup Behavior
On server start, `app.js` loads all devices from database and attempts to reconnect each one using existing session files in `sessions/{instance_id}/`.

### Database Tables
- `whatsapp_devices` - Stores device connections (instance_id, status, qr_code, phone_number)
- `blast_schedules` - Campaign definitions (message, media_path, scheduled_at, sent_count)
- `blast_recipients` - Individual recipients per blast (phone_number, status, error_message)

### Session Management
- Each WhatsApp account has a unique `instance_id` (UUID)
- Baileys credentials stored in `whatsapp_server/sessions/{instance_id}/`
- Sessions persist across server restarts; cleaned up on logout or max reconnect attempts

## Environment Variables

Both systems share the same MySQL database and authentication token:

**Laravel `.env`**
```
WHATSAPP_SERVER_URL=http://localhost:3300
WHATSAPP_ACCESS_TOKEN=your-secret-token-here
WHATSAPP_MESSAGE_DELAY=2
```

**Node.js `whatsapp_server/.env`**
```
SYSTEM_URL=http://localhost:8000
WHATSAPP_LICENSE_KEY=your-secret-token-here  # Must match WHATSAPP_ACCESS_TOKEN
```

## Queue System

Uses database driver. The queue worker is required for:
- Dispatching blast campaigns at scheduled times
- Sending messages with configurable delays (prevents rate limiting)
- Automatic retry (3 attempts with 10-second backoff)

## Real-time Updates

Livewire components use polling to detect database changes. The Node.js server sends webhook POST requests to `/api/webhooks/whatsapp/status` when device status changes (connected, disconnected, QR generated).
