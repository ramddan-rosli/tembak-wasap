/**
 * Wasapi WhatsApp Server
 * Simplified version - only send message, attachment and connect to whatsapp
 */

// Load environment variables first
require('dotenv').config();

// Setup logger (must be after dotenv)
const { setupLogger } = require('./logger.js');
setupLogger();

const fs = require('fs');
const path = require('path');
const WASAPI = require("./wasapi/wasapi.js");
const WasapiDB = require("./wasapi-db.js");

// Session directory
const SESSION_DIR = path.join(__dirname, 'sessions');

// GET /instance - Get connection status/info
WASAPI.app.get('/instance', WASAPI.cors, async (req, res) => {
    const access_token = req.query.access_token;
    const instance_id = req.query.instance_id;

    await WASAPI.instance(access_token, instance_id, false, res, async (client) => {
        await WASAPI.get_info(instance_id, res);
    });
});

// GET /login - Start fresh login (clears old session)
WASAPI.app.get('/login', WASAPI.cors, async (req, res) => {
    const access_token = req.query.access_token;
    const instance_id = req.query.instance_id;

    // Force relogin - clears old session and starts fresh
    await WASAPI.instance(access_token, instance_id, true, res, async () => {
        await WASAPI.get_qrcode(instance_id, res);
    });
});

// GET /get_qrcode - Get QR code (doesn't reset session)
WASAPI.app.get('/get_qrcode', WASAPI.cors, async (req, res) => {
    const access_token = req.query.access_token;
    const instance_id = req.query.instance_id;

    // Don't force relogin - just get existing session/QR
    await WASAPI.instance(access_token, instance_id, false, res, async () => {
        await WASAPI.get_qrcode(instance_id, res);
    });
});

// GET /logout - Disconnect WhatsApp account
WASAPI.app.get('/logout', WASAPI.cors, async (req, res) => {
    const access_token = req.query.access_token;
    const instance_id = req.query.instance_id;

    // Validate access token
    const EXPECTED_KEY = process.env.WHATSAPP_LICENSE_KEY || 'your-secret-token';
    if (!access_token || access_token !== EXPECTED_KEY) {
        return res.json({ status: 'error', message: "The authentication process has failed" });
    }

    WASAPI.logout(instance_id, res);
});

// POST /send_message - Send message via WhatsApp
WASAPI.app.post('/send_message', WASAPI.cors, async (req, res) => {
    const access_token = req.query.access_token;
    const instance_id = req.query.instance_id;

    await WASAPI.instance(access_token, instance_id, false, res, async (client) => {
        await WASAPI.send_message(instance_id, access_token, req, res);
    });
});

WASAPI.app.get('/sendMessage', WASAPI.cors, async (req, res) => {
    const access_token = req.query.access_token;
    const instance_id = req.query.instance_id;

    await WASAPI.instance(access_token, instance_id, false, res, async (client) => {
        await WASAPI.sendMessage(instance_id, access_token, req, res);
    });
});

// GET / - Health check
WASAPI.app.get('/', WASAPI.cors, async (req, res) => {
    return res.json({ status: 'success', message: "Wasapi WhatsApp Server is running" });
});

/**
 * Check if session files exist for an instance
 */
function hasSessionFiles(instance_id) {
    const sessionPath = path.join(SESSION_DIR, instance_id);
    if (!fs.existsSync(sessionPath)) {
        return false;
    }
    // Check if there are actual credential files
    const files = fs.readdirSync(sessionPath);
    return files.some(f => f.includes('creds') || f.includes('app-state'));
}

// Start server
const PORT = process.env.PORT || 3300;
WASAPI.server.listen(PORT, async () => {
    console.log(`[Wasapi] WhatsApp Server is running on port ${PORT}`);

    // Create sessions directory if it doesn't exist
    if (!fs.existsSync(SESSION_DIR)) {
        fs.mkdirSync(SESSION_DIR, { recursive: true });
    }

    // Test database connection
    const dbConnected = await WasapiDB.testConnection();

    if (dbConnected) {
        // Reconnect all active instances from database
        console.log("[Wasapi] Checking WhatsApp instances for reconnection...");
        const instances = await WasapiDB.getAllInstances();

        let reconnected = 0;
        let skipped = 0;

        for (const instance of instances) {
            try {
                // Check if session files exist - only reconnect if we have credentials
                if (hasSessionFiles(instance.instance_id)) {
                    console.log(`[Wasapi] Reconnecting instance: ${instance.instance_id} (status: ${instance.status})`);
                    await WASAPI.session(instance.instance_id);
                    reconnected++;
                } else {
                    console.log(`[Wasapi] Skipping instance: ${instance.instance_id} (no session files)`);
                    skipped++;
                }
            } catch (error) {
                console.error(`[Wasapi] Failed to reconnect ${instance.instance_id}:`, error.message);
            }
        }

        console.log(`[Wasapi] Reconnection complete. Reconnected: ${reconnected}, Skipped: ${skipped}, Total sessions: ${Object.keys(WASAPI.sessions || {}).length}`);
    } else {
        console.warn("[Wasapi] Database not connected. Skipping auto-reconnect.");
    }
});
