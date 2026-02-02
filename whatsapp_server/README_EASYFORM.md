# Wasapi WhatsApp Integration

This WhatsApp server has been integrated with Wasapi Laravel backend to enable conversational forms via WhatsApp.

## Setup Instructions

### 1. Install Dependencies

```bash
cd whatsapp_server
npm install
```

### 2. Configure Environment Variables

Create `.env` file:

```bash
cp .env.example .env
```

Edit `.env` and set:

```env
LARAVEL_BASE_URL=http://localhost:8000
WHATSAPP_LICENSE_KEY=your-secret-token-here
```

**Important:** Make sure `WHATSAPP_LICENSE_KEY` matches the value in Laravel's `.env` file!

### 3. Start the WhatsApp Server

```bash
node app.js
```

The server will start on port 8000 and display:
```
WAZIPER IS LIVE
[Wasapi] WhatsApp integration loaded
[Wasapi] Laravel webhook URL: http://localhost:8000/webhooks/whatsapp/*
```

### 4. Configure Laravel

In your Laravel project's `.env`, add:

```env
WHATSAPP_SERVER_URL=http://localhost:8000
WHATSAPP_LICENSE_KEY=your-secret-token-here
```

### 5. Run Migrations

```bash
# From Laravel root
php artisan migrate
php artisan tenants:migrate
```

## How It Works

### Architecture

```
WhatsApp User → WhatsApp Server (Baileys) → Laravel Webhook → Auto-Response System
                                                           ↓
                                         Match Keyword → Send Reply or Trigger Bot
```

### Message Flow

1. **User sends WhatsApp message** → Baileys receives it
2. **WhatsApp server** → Calls Laravel webhook at `/webhooks/whatsapp/incoming`
3. **Laravel** → Finds matching auto-response by keyword
4. **If match found:**
   - **Simple reply:** Laravel calls WhatsApp server `/send_message`
   - **Bot trigger:** Start conversational flow (TODO)

### API Endpoints

The WhatsApp server exposes these endpoints (used by Laravel):

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/get_qrcode?access_token=xxx&instance_id=xxx` | GET | Get QR code for new connection |
| `/instance?access_token=xxx&instance_id=xxx` | GET | Get instance info (connected status) |
| `/send_message?access_token=xxx&instance_id=xxx` | POST | Send WhatsApp message |
| `/logout?access_token=xxx&instance_id=xxx` | GET | Logout/disconnect instance |

### Laravel Webhooks

The WhatsApp server calls these Laravel webhooks:

| Webhook | Purpose |
|---------|---------|
| `POST /webhooks/whatsapp/incoming` | Incoming message notification |
| `POST /webhooks/whatsapp/status` | Connection status updates |

## Testing

### 1. Test Server Connectivity

```bash
curl http://localhost:8000
```

Expected response:
```json
{"status":"success","message":"Welcome to WAZIPER"}
```

### 2. Test Laravel Connection

From Laravel root:

```bash
php artisan tinker
```

```php
$service = new \App\Services\WhatsAppService();
$service->isServerReachable(); // Should return true
```

### 3. Connect WhatsApp Account

1. Go to `http://your-workspace.localhost:8000/whatsapp-accounts`
2. Click "Add WhatsApp Account"
3. Scan QR code with your phone
4. Wait for connection status to change to "Connected"

### 4. Set Up Auto-Response

1. Go to WhatsApp account → "Auto Responses"
2. Add keyword: `hello`
3. Response: `Hi! Welcome to our service.`
4. Send "hello" from another WhatsApp number
5. You should receive the auto-reply!

## Troubleshooting

### WhatsApp Server Not Receiving Messages

**Check:**
- WhatsApp server is running (`node app.js`)
- `.env` file has correct `LARAVEL_BASE_URL`
- Laravel is accessible from WhatsApp server

**Test webhook manually:**
```bash
curl -X POST http://localhost:8000/webhooks/whatsapp/incoming \
  -H "X-Webhook-Token: your-secret-token-here" \
  -H "Content-Type: application/json" \
  -d '{
    "instance_id": "test_instance",
    "from": "60123456789",
    "message": "hello"
  }'
```

### Laravel Not Calling WhatsApp Server

**Check:**
- Laravel `.env` has correct `WHATSAPP_SERVER_URL`
- WhatsApp server is reachable from Laravel
- Access token matches in both systems

**Test from Laravel:**
```php
$service = new \App\Services\WhatsAppService();
$result = $service->sendMessage('instance_id', '60123456789', 'Test message');
dd($result);
```

### Connection Issues

**Common issues:**
1. **QR code expired:** Click "Refresh QR Code" button
2. **Phone already connected:** Logout from WhatsApp app → Web/Desktop
3. **Session deleted:** Delete account and create new one

## Running in Production

### Option 1: PM2 (Recommended)

```bash
npm install -g pm2
cd whatsapp_server
pm2 start app.js --name "Wasapi-whatsapp"
pm2 save
pm2 startup
```

### Option 2: Systemd Service

Create `/etc/systemd/system/Wasapi-whatsapp.service`:

```ini
[Unit]
Description=Wasapi WhatsApp Server
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/Wasapi/whatsapp_server
ExecStart=/usr/bin/node app.js
Restart=always
Environment=NODE_ENV=production

[Install]
WantedBy=multi-user.target
```

Then:
```bash
sudo systemctl enable Wasapi-whatsapp
sudo systemctl start Wasapi-whatsapp
```

## Next Steps

- [ ] Implement bot conversation flow execution
- [ ] Add WhatsApp Send node to flow builder
- [ ] Support media messages (images, videos, documents)
- [ ] Add broadcast messaging feature
- [ ] Implement message templates

## Support

For issues, check Laravel logs:
```bash
php artisan pail
```

And WhatsApp server logs (if using PM2):
```bash
pm2 logs Wasapi-whatsapp
```

for whatsapp api using get
```bash
http://localhost:3300/sendMessage?access_token=ramddan&instance_id=THN9CTGVHSNY&chat_id=60138333107@s.whatsapp.net&message=hello
```
