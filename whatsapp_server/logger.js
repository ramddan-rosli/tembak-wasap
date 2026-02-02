/**
 * Wasapi Logger
 * Logs to file when LOGGING=true in .env
 */

const fs = require('fs');
const path = require('path');

const LOG_DIR = path.join(__dirname, 'logs');
const LOGGING_ENABLED = process.env.LOGGING === 'true';
const TIMEZONE = process.env.TZ || 'UTC';

// Create logs directory if logging is enabled
if (LOGGING_ENABLED && !fs.existsSync(LOG_DIR)) {
    fs.mkdirSync(LOG_DIR, { recursive: true });
}

/**
 * Get current date/time in configured timezone
 */
function getLocalDate() {
    return new Date().toLocaleString('en-CA', {
        timeZone: TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });
}

/**
 * Get current date string for log filename (YYYY-MM-DD)
 */
function getDateString() {
    return new Date().toLocaleDateString('en-CA', {
        timeZone: TIMEZONE,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}

/**
 * Get current timestamp for log entries
 */
function getTimestamp() {
    return getLocalDate().replace(',', '');
}

/**
 * Get log file path for current date
 */
function getLogFilePath() {
    return path.join(LOG_DIR, `wasapi-${getDateString()}.log`);
}

/**
 * Write message to log file
 */
function writeToFile(level, args) {
    if (!LOGGING_ENABLED) return;

    const message = args.map(arg => {
        if (typeof arg === 'object') {
            try {
                return JSON.stringify(arg, null, 2);
            } catch {
                return String(arg);
            }
        }
        return String(arg);
    }).join(' ');

    const logLine = `[${getTimestamp()}] [${level}] ${message}\n`;

    try {
        fs.appendFileSync(getLogFilePath(), logLine);
    } catch (err) {
        // Fallback to original console if file write fails
        originalConsole.error('Failed to write to log file:', err.message);
    }
}

// Store original console methods
const originalConsole = {
    log: console.log.bind(console),
    error: console.error.bind(console),
    warn: console.warn.bind(console),
    info: console.info.bind(console),
    debug: console.debug.bind(console)
};

/**
 * Override console methods to also write to file
 */
function setupLogger() {
    if (!LOGGING_ENABLED) {
        console.log('[Wasapi] Logging to file is disabled');
        return;
    }

    console.log('[Wasapi] Logging to file is enabled');
    console.log(`[Wasapi] Log directory: ${LOG_DIR}`);
    console.log(`[Wasapi] Timezone: ${TIMEZONE}`);

    // Override console.log
    console.log = function (...args) {
        originalConsole.log(...args);
        writeToFile('INFO', args);
    };

    // Override console.error
    console.error = function (...args) {
        originalConsole.error(...args);
        writeToFile('ERROR', args);
    };

    // Override console.warn
    console.warn = function (...args) {
        originalConsole.warn(...args);
        writeToFile('WARN', args);
    };

    // Override console.info
    console.info = function (...args) {
        originalConsole.info(...args);
        writeToFile('INFO', args);
    };

    // Override console.debug
    console.debug = function (...args) {
        originalConsole.debug(...args);
        writeToFile('DEBUG', args);
    };
}

module.exports = {
    setupLogger,
    getLogFilePath,
    LOG_DIR,
    LOGGING_ENABLED,
    TIMEZONE
};
