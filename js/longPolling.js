/**
 * Production-Grade Long Polling Manager
 * Clean, optimized for real-time inbox updates
 * KaiMail - Temp Mail System
 */

class LongPollingManager {
    constructor(config = {}) {
        // Core state
        this.emailId = null;
        this.lastCheck = null;
        this.isActive = false;
        this.isPaused = false;

        // Connection management
        this.abortController = null;
        this.pollTimeout = null;

        // Retry & backoff strategy
        this.retryCount = 0;
        this.consecutiveFailures = 0;
        this.maxRetries = config.maxRetries || 15;
        this.baseRetryDelay = config.baseRetryDelay || 1000; // Start with 1s
        this.maxRetryDelay = config.maxRetryDelay || 30000; // Max 30s

        // Performance tuning
        this.longPollTimeout = config.longPollTimeout || 30000; // Server waits 30s
        this.requestTimeout = config.requestTimeout || 35000; // Total timeout 35s

        // Callbacks
        this.onNewMessages = config.onNewMessages || (() => {});
        this.onError = config.onError || (() => {});
        this.onStatusChange = config.onStatusChange || (() => {});

        // API Configuration
        this.basePath = config.basePath || '';
        this.pollEndpoint = config.pollEndpoint || '/api/poll.php';
        this.vnTimeZone = 'Asia/Ho_Chi_Minh';

        // Event listeners setup
        this._setupEventListeners();
    }

    /**
     * Setup browser event listeners
     */
    _setupEventListeners() {
        // Page visibility for smart pause/resume
        document.addEventListener('visibilitychange', () => this._handleVisibilityChange());

        // Network status monitoring
        window.addEventListener('online', () => this._handleOnline());
        window.addEventListener('offline', () => this._handleOffline());

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => this.destroy());
    }

    /**
     * Start long polling
     */
    start(emailId, lastCheck = null) {
        if (!emailId) {
            console.error('[LongPolling] No email ID provided');
            return;
        }

        // Stop existing polling if active
        if (this.isActive) {
            this.stop();
        }

        this.emailId = emailId;
        this.lastCheck = lastCheck || this._getCurrentTimestamp();
        this.isActive = true;
        this.isPaused = false;
        this.retryCount = 0;
        this.consecutiveFailures = 0;

        console.log(`[LongPolling] Started for email ID: ${emailId}`);
        this._updateStatus('active');
        this._poll();
    }

    /**
     * Stop long polling completely
     */
    stop() {
        this.isActive = false;
        this.isPaused = false;

        // Abort ongoing request
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }

        this._clearTimeouts();
        console.log('[LongPolling] Stopped');
        this._updateStatus('stopped');
    }

    /**
     * Pause polling (can be resumed without restart)
     */
    pause() {
        if (!this.isActive) return;

        this.isPaused = true;
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
        this._clearTimeouts();
        console.log('[LongPolling] Paused');
        this._updateStatus('paused');
    }

    /**
     * Resume paused polling
     */
    resume() {
        if (!this.isActive || !this.isPaused) return;

        this.isPaused = false;
        console.log('[LongPolling] Resumed');
        this._updateStatus('active');
        this._poll();
    }

    /**
     * Update the lastCheck timestamp (called after loading new messages)
     */
    updateLastCheck(timestamp) {
        this.lastCheck = timestamp;
    }

    /**
     * Main polling loop - the heart of the system
     */
    async _poll() {
        if (!this.isActive || this.isPaused) return;

        // Create abort controller for this request
        this.abortController = new AbortController();

        try {
            const pollUrl = `${this.basePath}${this.pollEndpoint}`;
            
            const response = await fetch(pollUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    email_id: this.emailId,
                    last_check: this.lastCheck,
                    timeout: this.longPollTimeout / 1000 // Convert to seconds
                }),
                signal: this.abortController.signal,
                timeout: this.requestTimeout
            });

            // Reset consecutive failures on successful request
            this.consecutiveFailures = 0;
            this.retryCount = 0;

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (data.messages && data.messages.length > 0) {
                // New messages found!
                const count = data.messages.length;
                this.onNewMessages(data.messages, count);
                
                // Update last check timestamp
                if (data.server_time) {
                    this.lastCheck = data.server_time;
                }
            }

            // Continue polling
            this._schedule();

        } catch (error) {
            if (error.name === 'AbortError') {
                // Request was cancelled, this is normal
                return;
            }

            // Network or server error
            this.consecutiveFailures++;
            this.retryCount++;

            if (this.retryCount <= this.maxRetries) {
                // Calculate exponential backoff
                const delay = Math.min(
                    this.baseRetryDelay * Math.pow(2, Math.min(this.consecutiveFailures - 1, 4)),
                    this.maxRetryDelay
                );

                console.warn(`[LongPolling] Error (attempt ${this.retryCount}/${this.maxRetries}):`, error.message, `- Retry in ${delay}ms`);
                this.onError(error, this.retryCount);
                this._updateStatus('reconnecting');

                // Schedule retry
                this.pollTimeout = setTimeout(() => {
                    if (this.isActive && !this.isPaused) {
                        this._poll();
                    }
                }, delay);
            } else {
                // Max retries exceeded
                console.error('[LongPolling] Max retries exceeded');
                this.onError(error, this.retryCount);
                this._updateStatus('failed');
                this.stop();
            }
        }
    }

    /**
     * Schedule next poll immediately
     */
    _schedule() {
        if (!this.isActive || this.isPaused) return;

        // Small delay to avoid hammering the server
        this.pollTimeout = setTimeout(() => {
            if (this.isActive && !this.isPaused) {
                this._poll();
            }
        }, 100);
    }

    /**
     * Handle page visibility change
     */
    _handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden
            this.pause();
        } else {
            // Page is visible again
            if (this.isActive) {
                this.resume();
            }
        }
    }

    /**
     * Handle online event
     */
    _handleOnline() {
        console.log('[LongPolling] Connection restored');
        if (this.isActive && this.isPaused) {
            this.resume();
        }
    }

    /**
     * Handle offline event
     */
    _handleOffline() {
        console.log('[LongPolling] Connection lost');
        this.pause();
    }

    /**
     * Update status callback
     */
    _updateStatus(status) {
        this.onStatusChange(status);
    }

    /**
     * Clear all timeouts
     */
    _clearTimeouts() {
        if (this.pollTimeout) {
            clearTimeout(this.pollTimeout);
            this.pollTimeout = null;
        }
    }

    /**
     * Get current timestamp in MySQL format
     */
    _getCurrentTimestamp() {
        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: this.vnTimeZone,
            hour12: false,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).formatToParts(new Date());

        const map = Object.fromEntries(parts.map((part) => [part.type, part.value]));
        return `${map.year}-${map.month}-${map.day} ${map.hour}:${map.minute}:${map.second}`;
    }

    /**
     * Cleanup on destroy
     */
    destroy() {
        this.stop();
        this._clearTimeouts();
        console.log('[LongPolling] Destroyed');
    }
}
