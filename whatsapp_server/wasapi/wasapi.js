/**
 * Wasapi WhatsApp Server - Core Module
 * Compatible with baileys v7
 */

const fs = require('fs');
const http = require('http');
const path = require('path');
const qrimg = require('qr-image');
const express = require('express');
const rimraf = require('rimraf');
const bodyParser = require('body-parser');
const cors = require('cors');
const P = require('pino');
const app = express();
const server = http.createServer(app);
const config = require("./../config.js");
const Common = require("./common.js");
const WasapiDB = require("../wasapi-db.js");

const sessions = {};
const new_sessions = {};
const reconnectAttempts = {}; // Track reconnect attempts per instance
const SESSION_DIR = path.resolve(__dirname, '../sessions');

app.use(bodyParser.urlencoded({
	extended: true,
	limit: '50mb'
}));
app.use(bodyParser.json({ limit: '50mb' }));

const {
	default: makeWASocket,
	useMultiFileAuthState,
	DisconnectReason,
	fetchLatestBaileysVersion,
} = require('baileys');

const WASAPI = {
	app: app,
	server: server,
	sessions: sessions,
	cors: cors(config.cors),

	/**
	 * Properly close socket connection
	 */
	closeSocket: async function (instance_id) {
		const sock = sessions[instance_id];
		if (sock) {
			try {
				// Remove all event listeners to prevent memory leaks
				if (sock.ev) {
					sock.ev.removeAllListeners();
				}

				// Close the WebSocket connection
				if (sock.ws) {
					sock.ws.close();
				}

				// End the socket properly
				if (typeof sock.end === 'function') {
					await sock.end();
				}

				console.log(`[Wasapi] Socket closed properly: ${instance_id}`);
			} catch (err) {
				console.error(`[Wasapi] Error closing socket ${instance_id}:`, err.message);
			}

			// Remove from sessions
			delete sessions[instance_id];
		}
	},

	/**
	 * Create WhatsApp socket connection
	 */
	makeWASocket: async function (instance_id) {
		// Use absolute path for session storage
		const sessionPath = path.join(SESSION_DIR, instance_id);
		const { state, saveCreds } = await useMultiFileAuthState(sessionPath);

		// Get latest Baileys version for compatibility
		const { version } = await fetchLatestBaileysVersion();

		const WA = makeWASocket({
			version,
			auth: state,
			printQRInTerminal: false,
			logger: P({ level: 'silent' }),
			browser: ['WasApi', 'Chrome', '121.0.0'],
			connectTimeoutMs: 60000,
			defaultQueryTimeoutMs: 60000,
			keepAliveIntervalMs: 30000,
			markOnlineOnConnect: true,
			syncFullHistory: false,
			generateHighQualityLinkPreview: true,
			getMessage: async (key) => {
				return { conversation: '' };
			},
		});

		WA.ev.on('connection.update', async (update) => {
			await WASAPI.handleConnectionUpdate(instance_id, update, WA);
		});

		WA.ev.on('creds.update', saveCreds);

		return WA;
	},

	/**
	 * Handle connection updates with proper reconnect logic
	 */
	handleConnectionUpdate: async function (instance_id, update, WA) {
		const { connection, lastDisconnect, qr } = update;

		// Handle QR Code
		if (qr !== undefined) {
			WA.qrcode = qr;
			if (new_sessions[instance_id] === undefined) {
				new_sessions[instance_id] = new Date().getTime() / 1000 + 300;
			}
			console.log(`[Wasapi] QR generated for ${instance_id}`);

			// Convert QR to base64 PNG image and save to database
			const qrImage = qrimg.imageSync(qr, { type: 'png' });
			const qrBase64 = 'data:image/png;base64,' + qrImage.toString('base64');
			await WasapiDB.saveQRCode(instance_id, qrBase64);

			// Send Telegram notification (only once per QR session)
			if (new_sessions[instance_id] !== undefined && new_sessions[instance_id] > 0) {
				new_sessions[instance_id] = -1;
				WasapiDB.sendWebhookNotification(instance_id, 'qr');
			}
		}

		// Handle connection state changes
		if (connection === 'close') {
			const statusCode = lastDisconnect?.error?.output?.statusCode || 0;
			const shouldReconnect = statusCode !== DisconnectReason.loggedOut;

			console.log(`[Wasapi] Connection closed for ${instance_id}, code: ${statusCode}`);

			// Update database
			await WasapiDB.updateConnectionStatus(instance_id, 'disconnected');

			// Handle different disconnect reasons
			if (statusCode === DisconnectReason.loggedOut) {
				// 401: User logged out - clean up completely
				console.log(`[Wasapi] Logged out (401): ${instance_id} - Cleaning up`);
				await WASAPI.cleanupSession(instance_id);
				WasapiDB.sendWebhookNotification(instance_id, 'disconnected', 'loggedOut', 401);
				reconnectAttempts[instance_id] = 0;

			} else if (statusCode === DisconnectReason.connectionReplaced) {
				// 440: Another session took over
				console.log(`[Wasapi] Connection replaced (440): ${instance_id}`);
				await WASAPI.closeSocket(instance_id);
				WasapiDB.sendWebhookNotification(instance_id, 'disconnected', 'connectionReplaced', 440);
				reconnectAttempts[instance_id] = 0;

			} else if (statusCode === DisconnectReason.badSession) {
				// 500: Bad session - clean up
				console.log(`[Wasapi] Bad session (500): ${instance_id} - Cleaning up`);
				await WASAPI.cleanupSession(instance_id);
				WasapiDB.sendWebhookNotification(instance_id, 'disconnected', 'badSession', 500);
				reconnectAttempts[instance_id] = 0;

			} else if (statusCode === DisconnectReason.multideviceMismatch) {
				// 411: Multi-device mismatch
				console.log(`[Wasapi] Multi-device mismatch (411): ${instance_id} - Cleaning up`);
				await WASAPI.cleanupSession(instance_id);
				WasapiDB.sendWebhookNotification(instance_id, 'disconnected', 'multideviceMismatch', 411);
				reconnectAttempts[instance_id] = 0;

			} else if (shouldReconnect) {
				// Reconnect with exponential backoff
				await WASAPI.handleReconnect(instance_id, statusCode);
			} else {
				// Unknown reason, don't reconnect
				console.log(`[Wasapi] Disconnect (${statusCode}): ${instance_id} - Not reconnecting`);
				await WASAPI.closeSocket(instance_id);
				WasapiDB.sendWebhookNotification(instance_id, 'disconnected', 'unknown', statusCode);
				reconnectAttempts[instance_id] = 0;
			}

		} else if (connection === 'open') {
			console.log(`[Wasapi] Connected: ${instance_id}`, WA.user);

			if (WA.user?.name === undefined && WA.user?.id) {
				WA.user.name = Common.get_phone(WA.user.id);
			}

			sessions[instance_id] = WA;
			reconnectAttempts[instance_id] = 0; // Reset reconnect counter

			// Remove QR code
			if (sessions[instance_id].qrcode !== undefined) {
				delete sessions[instance_id].qrcode;
				delete new_sessions[instance_id];
			}

			// Update database with connection info
			if (WA.user) {
				const phoneNumber = WA.user.id?.split(':')[0] || '';
				const phoneName = WA.user.name || phoneNumber;

				console.log(`[Wasapi] Connected as: ${phoneName} (${phoneNumber})`);

				await WasapiDB.updateConnectionStatus(instance_id, 'connected', {
					phone_number: phoneNumber
				});

				// Send webhook notification
				WasapiDB.sendWebhookNotification(instance_id, 'connected');
			}

		} else if (connection === 'connecting') {
			console.log(`[Wasapi] Connecting: ${instance_id}`);
		}
	},

	/**
	 * Handle reconnection with exponential backoff
	 */
	handleReconnect: async function (instance_id, statusCode) {
		// Check if instance still exists in database
		const exists = await WasapiDB.instanceExists(instance_id);
		if (!exists) {
			console.log(`[Wasapi] Instance ${instance_id} no longer exists - not reconnecting`);
			await WASAPI.cleanupSession(instance_id);
			return;
		}

		// Initialize reconnect attempt counter
		if (!reconnectAttempts[instance_id]) {
			reconnectAttempts[instance_id] = 0;
		}

		reconnectAttempts[instance_id]++;
		const attempts = reconnectAttempts[instance_id];
		const maxAttempts = 10;

		if (attempts > maxAttempts) {
			console.log(`[Wasapi] Max reconnect attempts (${maxAttempts}) reached for ${instance_id} - giving up`);
			await WASAPI.closeSocket(instance_id);
			WasapiDB.sendWebhookNotification(instance_id, 'disconnected', 'maxReconnectReached', statusCode);
			reconnectAttempts[instance_id] = 0;
			return;
		}

		// Exponential backoff: 2^attempt seconds, max 60s
		const delay = Math.min(Math.pow(2, attempts) * 1000, 60000);

		console.log(`[Wasapi] Reconnect attempt ${attempts}/${maxAttempts} for ${instance_id} in ${delay/1000}s (code: ${statusCode})`);
		WasapiDB.sendWebhookNotification(instance_id, 'reconnecting', `attempt_${attempts}`, statusCode);

		// Close old socket properly before reconnecting
		await WASAPI.closeSocket(instance_id);

		// Wait with exponential backoff
		await Common.sleep(delay);

		// Reconnect
		try {
			sessions[instance_id] = await WASAPI.makeWASocket(instance_id);
		} catch (err) {
			console.error(`[Wasapi] Reconnect failed for ${instance_id}:`, err.message);
			// Will retry on next disconnect
		}
	},

	/**
	 * Cleanup session - remove from memory and delete session folder
	 */
	cleanupSession: async function (instance_id) {
		// Close socket properly first
		await WASAPI.closeSocket(instance_id);

		// Remove from tracking
		delete new_sessions[instance_id];
		delete reconnectAttempts[instance_id];

		// Delete session folder using absolute path
		const sessionPath = path.join(SESSION_DIR, instance_id);
		if (fs.existsSync(sessionPath)) {
			try {
				rimraf.sync(sessionPath);
				console.log(`[Wasapi] Session folder deleted: ${instance_id}`);
			} catch (err) {
				console.error(`[Wasapi] Error deleting session folder ${instance_id}:`, err.message);
			}
		}
	},

	/**
	 * Get or create session
	 */
	session: async function (instance_id, reset) {
		if (reset) {
			await WASAPI.cleanupSession(instance_id);
		}

		if (!sessions[instance_id]) {
			sessions[instance_id] = await WASAPI.makeWASocket(instance_id);
		}

		return sessions[instance_id];
	},

	/**
	 * Validate access token and initialize session
	 */
	instance: async function (access_token, instance_id, login, res, callback) {
		// Check instance ID
		if (instance_id === undefined) {
			if (res) {
				return res.json({ status: 'error', message: "The Instance ID must be provided" });
			}
			return callback(false);
		}

		// Validate access token
		const EXPECTED_KEY = process.env.WHATSAPP_LICENSE_KEY || config.access_token;
		if (!access_token || access_token !== EXPECTED_KEY) {
			if (res) {
				return res.json({ status: 'error', message: "The authentication process has failed" });
			}
			return callback(false);
		}

		// Check if instance exists in database
		const instance = await WasapiDB.getInstance(instance_id);
		if (!instance) {
			if (res) {
				return res.json({ status: 'error', message: "The Instance ID provided was not found" });
			}
			return callback(false);
		}

		// Force relogin if requested
		if (login) {
			await WASAPI.cleanupSession(instance_id);
		}

		sessions[instance_id] = await WASAPI.session(instance_id, false);
		return callback(sessions[instance_id]);
	},

	/**
	 * Generate QR code for login
	 */
	get_qrcode: async function (instance_id, res) {
		const client = sessions[instance_id];
		if (client === undefined) {
			return res.json({ status: 'error', message: "The WhatsApp session could not be found" });
		}

		if (client.qrcode !== undefined && !client.qrcode) {
			return res.json({ status: 'error', message: "It seems that you have logged in successfully" });
		}

		// Wait for QR code (max 10 seconds)
		for (let i = 0; i < 10; i++) {
			if (client.qrcode === undefined) {
				await Common.sleep(1000);
			} else {
				break;
			}
		}

		if (client.qrcode === undefined || client.qrcode === false) {
			return res.json({ status: 'error', message: "The system cannot generate a WhatsApp QR code" });
		}

		const code = qrimg.imageSync(client.qrcode, { type: 'png' });
		return res.json({
			status: 'success',
			message: 'Success',
			base64: 'data:image/png;base64,' + code.toString('base64')
		});
	},

	/**
	 * Get connected account info
	 */
	get_info: async function (instance_id, res) {
		const client = sessions[instance_id];
		if (client !== undefined && client.user !== undefined) {
			if (client.user.avatar === undefined) {
				await Common.sleep(1500);
			}
			client.user.avatar = Common.get_avatar(client.user?.name || 'user');
			return res.json({ status: 'success', message: "Success", data: client.user });
		} else {
			return res.json({ status: 'error', message: "Error", relogin: true });
		}
	},

	/**
	 * Logout and cleanup session
	 */
	logout: async function (instance_id, res) {
		// Update database
		await WasapiDB.updateConnectionStatus(instance_id, 'disconnected');

		if (sessions[instance_id]) {
			try {
				// Logout from WhatsApp properly
				if (typeof sessions[instance_id].logout === 'function') {
					await sessions[instance_id].logout();
				}
			} catch (e) {
				console.error(`[Wasapi] Error during logout ${instance_id}:`, e.message);
			}

			// Clean up session
			await WASAPI.cleanupSession(instance_id);

			if (res !== undefined) {
				return res.json({ status: 'success', message: 'Success' });
			}
		} else {
			if (res !== undefined) {
				return res.json({ status: 'error', message: 'This account seems to have logged out before.' });
			}
		}
	},

	/**
	 * Send message (text or media) - POST version
	 */
	send_message: async function (instance_id, access_token, req, res) {
		const { chat_id, media_url, caption, message, filename } = req.body;

		if (!chat_id) {
			return res.json({ status: 'error', message: "Missing chat_id" });
		}

		const client = sessions[instance_id];
		if (!client) {
			return res.json({ status: 'error', message: "Session not found or disconnected" });
		}

		const text = caption || message || "";

		try {
			let result;

			if (media_url) {
				// Send media message
				console.log(`[Wasapi] Sending media from URL: ${media_url}`);
				const mime = Common.ext2mime(media_url);
				const post_type = Common.post_type(mime, 1);
				const fname = filename || Common.get_file_name(media_url);
				console.log(`[Wasapi] Media type: ${post_type}, mime: ${mime}, filename: ${fname}`);

				let payload;
				switch (post_type) {
					case "videoMessage":
						payload = { video: { url: media_url }, caption: text };
						break;
					case "imageMessage":
						payload = { image: { url: media_url }, caption: text };
						break;
					case "audioMessage":
						payload = { audio: { url: media_url } };
						break;
					default:
						payload = { document: { url: media_url }, fileName: fname, caption: text };
				}

				result = await client.sendMessage(chat_id, payload);
			} else {
				// Send text message
				result = await client.sendMessage(chat_id, { text });
			}

			console.log(`[Wasapi] Message sent -> ${chat_id}`);
			return res.json({
				status: 'success',
				message: 'Message sent successfully',
				data: result,
			});
		} catch (err) {
			console.error(`[Wasapi] Send failed -> ${chat_id}:`, err.message);
			return res.json({
				status: 'error',
				message: `Failed to send: ${err.message}`,
			});
		}
	},

	/**
	 * Send message (text or media) - GET version
	 */
	sendMessage: async function (instance_id, access_token, req, res) {
		const { chat_id, media_url, caption, message, filename } = req.query;

		if (!chat_id) {
			return res.json({ status: 'error', message: "Missing chat_id" });
		}

		const client = sessions[instance_id];
		if (!client) {
			return res.json({ status: 'error', message: "Session not found or disconnected" });
		}

		const text = caption || message || "";

		try {
			let result;

			if (media_url) {
				// Send media message
				console.log(`[Wasapi] Sending media from URL: ${media_url}`);
				const mime = Common.ext2mime(media_url);
				const post_type = Common.post_type(mime, 1);
				const fname = filename || Common.get_file_name(media_url);
				console.log(`[Wasapi] Media type: ${post_type}, mime: ${mime}, filename: ${fname}`);

				let payload;
				switch (post_type) {
					case "videoMessage":
						payload = { video: { url: media_url }, caption: text };
						break;
					case "imageMessage":
						payload = { image: { url: media_url }, caption: text };
						break;
					case "audioMessage":
						payload = { audio: { url: media_url } };
						break;
					default:
						payload = { document: { url: media_url }, fileName: fname, caption: text };
				}

				result = await client.sendMessage(chat_id, payload);
			} else {
				// Send text message
				result = await client.sendMessage(chat_id, { text });
			}

			console.log(`[Wasapi] Message sent -> ${chat_id}`);
			return res.json({
				status: 'success',
				message: 'Message sent successfully',
				data: result,
			});
		} catch (err) {
			console.error(`[Wasapi] Send failed -> ${chat_id}:`, err.message);
			return res.json({
				status: 'error',
				message: `Failed to send: ${err.message}`,
			});
		}
	},
};

module.exports = WASAPI;
