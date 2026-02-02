/**
 * Wasapi WhatsApp Server Configuration
 */

require('dotenv').config();

const config = {
    debug: false,
    access_token: process.env.WHATSAPP_LICENSE_KEY || 'your-secret-token',
    system_url: process.env.SYSTEM_URL || 'http://localhost:8000',
    database: {
        connectionLimit: 10,
        host: process.env.DB_HOST || "127.0.0.1",
        port: process.env.DB_PORT || 3306,
        user: process.env.DB_USER || "root",
        password: process.env.DB_PASSWORD || "",
        database: process.env.DB_DATABASE || "database_name",
        charset: "utf8mb4",
        waitForConnections: true
    },
    cors: {
        origin: '*',
        optionsSuccessStatus: 200
    }
};

module.exports = config;
