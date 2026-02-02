/**
 * Wasapi Database Functions
 * Handles database operations for WhatsApp integration
 *
 * Table: whatsapp_devices
 * Columns: id, user_id, instance_id, name, phone_number, status, qr_code, last_connected_at, created_at, updated_at
 */

const mysql = require('mysql2/promise');
const config = require("./config.js");

// Create connection pool
const pool = mysql.createPool(config.database);

const WasapiDB = {
    /**
     * Get all active WhatsApp devices for reconnection on server startup
     */
    getAllInstances: async function() {
        try {
            const [rows] = await pool.query(
                "SELECT id, instance_id, user_id, name, status FROM whatsapp_devices ORDER BY last_connected_at DESC"
            );
            console.log(`[Wasapi DB] Found ${rows.length} active devices to check for reconnection`);
            return rows;
        } catch (err) {
            console.error('[Wasapi DB] Error fetching devices:', err.message);
            return [];
        }
    },

    /**
     * Get device by instance_id
     */
    getInstance: async function(instance_id) {
        try {
            const [rows] = await pool.query(
                "SELECT * FROM whatsapp_devices WHERE instance_id = ?",
                [instance_id]
            );
            return rows.length > 0 ? rows[0] : null;
        } catch (err) {
            console.error('[Wasapi DB] Error fetching device:', err.message);
            return null;
        }
    },

    /**
     * Check if device exists
     */
    instanceExists: async function(instance_id) {
        try {
            const [rows] = await pool.query(
                "SELECT instance_id FROM whatsapp_devices WHERE instance_id = ?",
                [instance_id]
            );
            return rows.length > 0;
        } catch (err) {
            console.error('[Wasapi DB] Error checking device exists:', err.message);
            return false;
        }
    },

    /**
     * Update device data
     */
    updateInstance: async function(instance_id, data) {
        try {
            const updateData = {
                ...data,
                updated_at: new Date()
            };

            await pool.query(
                "UPDATE whatsapp_devices SET ? WHERE instance_id = ?",
                [updateData, instance_id]
            );
            return true;
        } catch (err) {
            console.error('[Wasapi DB] Error updating device:', err.message);
            return false;
        }
    },

    /**
     * Update device connection status
     */
    updateConnectionStatus: async function(instance_id, status, additionalData = {}) {
        // Map status values to match Laravel enum (disconnected, connecting, connected)
        const statusMap = {
            'qr': 'connecting',
            'connecting': 'connecting',
            'reconnecting': 'connecting',
            'connected': 'connected',
            'disconnected': 'disconnected',
            'close': 'disconnected',
            'logout': 'disconnected'
        };

        const mappedStatus = statusMap[status] || 'disconnected';

        const data = {
            status: mappedStatus,
            ...additionalData
        };

        if (mappedStatus === 'connected') {
            data.last_connected_at = new Date();
            data.qr_code = null;
        } else if (mappedStatus === 'disconnected') {
            data.qr_code = null;
        }

        return this.updateInstance(instance_id, data);
    },

    /**
     * Save QR code for device
     */
    saveQRCode: async function(instance_id, qr_code) {
        return this.updateInstance(instance_id, {
            status: 'connecting',
            qr_code: qr_code
        });
    },

    /**
     * Test database connection
     */
    testConnection: async function() {
        try {
            await pool.query("SELECT 1");
            console.log('[Wasapi DB] Database connection successful');
            return true;
        } catch (err) {
            console.error('[Wasapi DB] Database connection failed:', err.message);
            return false;
        }
    },

    /**
     * Send webhook notification to Laravel for WhatsApp status change
     */
    sendWebhookNotification: async function(instance_id, status, reason = null, errorCode = null) {
        try {
            const device = await this.getInstance(instance_id);
            if (!device) {
                console.log(`[Wasapi DB] Device not found for notification: ${instance_id}`);
                return false;
            }

            // Use API route for webhook
            const webhookUrl = (config.system_url || 'http://localhost:8000') + '/api/webhooks/whatsapp/status';

            const payload = {
                instance_id: instance_id,
                status: status,
                phone_number: device.phone_number || null,
                reason: reason,
                error_code: errorCode
            };

            console.log(`[Wasapi DB] Sending webhook: ${status} for ${instance_id}`);

            const response = await fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                const data = await response.json();
                console.log(`[Wasapi DB] Webhook sent: ${data.success ? 'success' : 'failed'}`);
                return true;
            } else {
                const errorText = await response.text();
                console.error(`[Wasapi DB] Webhook failed: HTTP ${response.status} - ${errorText}`);
                return false;
            }
        } catch (err) {
            console.error('[Wasapi DB] Error sending webhook:', err.message);
            return false;
        }
    },

    // Alias for backward compatibility
    sendTelegramNotification: function(instance_id, status, reason, errorCode) {
        return this.sendWebhookNotification(instance_id, status, reason, errorCode);
    }
};

module.exports = WasapiDB;
