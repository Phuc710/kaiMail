/**
 * KaiMail - User JavaScript
 * Handles email input, inbox, and message viewing
 */

class KaiMail {
    constructor() {
        this.currentEmail = null;
        this.currentEmailId = null;
        this.refreshInterval = null;
        this.isLoading = false;
        this.longPollActive = false;
        this.lastCheck = null;
        this.vnTimeZone = 'Asia/Ho_Chi_Minh';
        this.vnLocale = 'vi-VN';
        // Get base path from config to avoid rewrite issues
        this.basePath = window.KAIMAIL_CONFIG?.baseUrl || '';
        // Remove trailing slash if present to avoid double slashes
        this.basePath = this.basePath.replace(/\/$/, '');
        this.init();
    }

    init() {
        // DOM Elements
        this.emailInput = document.getElementById('emailInput');
        this.getMailBtn = document.getElementById('getMailBtn');
        this.copyBtn = document.getElementById('copyBtn'); // Restored  
        this.inboxSection = document.getElementById('inboxSection');
        this.messagesList = document.getElementById('messagesList');
        this.emptyState = document.getElementById('emptyState');
        this.loadingState = document.getElementById('loadingState');
        this.unreadBadge = document.getElementById('unreadBadge');
        this.refreshBtn = document.getElementById('refreshBtn');
        this.modal = document.getElementById('emailModal');
        this.modalSubject = document.getElementById('modalSubject');
        this.modalFrom = document.getElementById('modalFrom');
        this.modalBody = document.getElementById('modalBody');
        this.closeModalBtn = document.getElementById('closeModal');

        // Event Listeners
        this.getMailBtn.addEventListener('click', () => this.getMail());
        this.emailInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.getMail();
        });
        // Smart paste - auto format email - Removed aggressive formatting
        // this.emailInput.addEventListener('paste', (e) => {
        //     setTimeout(() => this.formatEmailInput(), 10);
        // });
        // this.emailInput.addEventListener('input', () => this.formatEmailInput());
        this.copyBtn.addEventListener('click', () => this.copyEmail()); // Restored
        this.refreshBtn.addEventListener('click', () => this.handleManualRefresh());
        this.closeModalBtn.addEventListener('click', () => this.closeModal());
        this.modal.querySelector('.modal-backdrop').addEventListener('click', () => this.closeModal());

        // ESC to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.closeModal();
            }
        });

        // Check URL for email parameter (Query string or Path)
        const urlParams = new URLSearchParams(window.location.search);
        let emailParam = urlParams.get('email');

        if (!emailParam) {
            let path = window.location.pathname;

            let basePath = this.basePath;
            try {
                if (basePath.startsWith('http')) {
                    const url = new URL(basePath);
                    basePath = url.pathname;
                }
            } catch (e) { }

            // Remove trailing slash
            path = path.replace(/\/$/, '');
            basePath = basePath.replace(/\/$/, '');

            if (path.startsWith(basePath)) {
                const potentialEmail = path.substring(basePath.length).replace(/^\//, '');
                if (potentialEmail && potentialEmail.includes('@') && !['admin', 'index.php', 'api', 'css', 'js'].includes(potentialEmail.split('/')[0])) {
                    emailParam = potentialEmail;
                }
            }
        }

        if (emailParam) {
            // Keep full email in input (don't strip domain)
            this.emailInput.value = emailParam;
            this.getMail();
        }

        // Check localStorage for last email
        const lastEmail = localStorage.getItem('kaimail_email');
        if (lastEmail && !emailParam) {
            // Keep full email in input
            this.emailInput.value = lastEmail;
        }
    }

    formatEmailInput() {
        // Disabled aggressive formatting to allow full email paste
        // let value = this.emailInput.value.trim().toLowerCase();
        // this.emailInput.value = value;
    }

    parseDateInput(dateValue) {
        if (dateValue instanceof Date) {
            return Number.isNaN(dateValue.getTime()) ? null : dateValue;
        }

        const raw = String(dateValue || '').trim();
        if (raw === '') return null;

        // MySQL datetime "YYYY-MM-DD HH:mm:ss" được hiểu theo giờ VN (+07:00).
        const sqlMatch = raw.match(
            /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/
        );
        const hasZone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(raw);

        if (sqlMatch && !hasZone) {
            const year = Number(sqlMatch[1]);
            const month = Number(sqlMatch[2]);
            const day = Number(sqlMatch[3]);
            const hour = Number(sqlMatch[4] || '0');
            const minute = Number(sqlMatch[5] || '0');
            const second = Number(sqlMatch[6] || '0');
            const utcMs = Date.UTC(year, month - 1, day, hour - 7, minute, second);
            return new Date(utcMs);
        }

        const parsed = new Date(raw);
        if (Number.isNaN(parsed.getTime())) {
            return null;
        }
        return parsed;
    }

    getVnTimeParts(dateValue) {
        const parsedDate = this.parseDateInput(dateValue);
        if (!parsedDate) return null;

        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: this.vnTimeZone,
            hour12: false,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        }).formatToParts(parsedDate);

        const map = Object.fromEntries(parts.map((part) => [part.type, part.value]));
        return {
            year: map.year,
            month: map.month,
            day: map.day,
            hour: map.hour,
            minute: map.minute,
            second: map.second,
        };
    }

    getCurrentSqlDateTimeVN() {
        const nowParts = this.getVnTimeParts(new Date());
        if (!nowParts) return '';
        return `${nowParts.year}-${nowParts.month}-${nowParts.day} ${nowParts.hour}:${nowParts.minute}:${nowParts.second}`;
    }

    async getMail() {
        let email = this.emailInput.value.trim().toLowerCase();

        if (!email) {
            this.showToast('Vui lòng nhập email', 'error');
            this.emailInput.focus();
            return;
        }

        // Require full email with @
        if (!email.includes('@')) {
            this.showToast('Vui lòng nhập đầy đủ email (ví dụ: user@domain.com)', 'error');
            this.emailInput.focus();
            return;
        }

        this.getMailBtn.disabled = true;

        try {
            const response = await fetch(`${this.basePath}/api/emails.php?action=check&email=${encodeURIComponent(email)}`);
            const data = await response.json();

            if (!response.ok) {
                if (response.status === 404) {
                    this.showToast('Email không tồn tại trong hệ thống', 'error');
                } else if (response.status === 410) {
                    this.showToast('Email đã hết hạn', 'error');
                } else {
                    this.showToast(data.error || 'Đã xảy ra lỗi', 'error');
                }
                return;
            }

            // Email exists and active
            this.currentEmail = email;
            this.currentEmailId = data.id;
            localStorage.setItem('kaimail_email', email);

            // Update URL to path format: /kaiMail/email@domain.com
            const newUrl = `${this.basePath}/${email}`;
            window.history.replaceState({}, '', newUrl);

            // Show copy button
            this.copyBtn.style.display = 'flex';

            // Show inbox section
            this.inboxSection.classList.remove('hidden');

            // Load messages
            this.loadMessages();

            // Start long polling instead of simple interval
            this.startLongPolling();

        } catch (error) {
            console.error('Error:', error);
            this.showToast('Không thể kết nối đến máy chủ', 'error');
        } finally {
            this.getMailBtn.disabled = false;
            this.getMailBtn.innerHTML = `
                <span>Get Mail</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            `;
        }
    }

    async handleManualRefresh() {
        await this.loadMessages({ manual: true });
    }

    async loadMessages(options = {}) {
        const { manual = false } = options;

        if (!this.currentEmail) {
            if (manual) this.emailInput.focus();
            return false;
        }

        if (this.isLoading) return false;

        this.isLoading = true;
        this.refreshBtn.disabled = true;
        this.refreshBtn.classList.add('spinning');

        // Ensure spinner shows for at least 500ms
        const minSpinTime = new Promise(resolve => setTimeout(resolve, 500));

        try {
            const [response, _] = await Promise.all([
                fetch(`${this.basePath}/api/messages.php?email=${encodeURIComponent(this.currentEmail)}`),
                minSpinTime
            ]);

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to load messages');
            }

            this.renderMessages(data.messages || []);
            this.updateUnreadBadge(data.unread);

            // Update lastCheck from server time to ensure synchronization
            if (data.server_time) {
                this.lastCheck = data.server_time;
                // Update polling manager if active
                if (this.pollingManager) {
                    this.pollingManager.updateLastCheck(this.lastCheck);
                }
            }

            return true;

        } catch (error) {
            console.error('Error loading messages:', error);
            return false;
        } finally {
            this.isLoading = false;
            this.refreshBtn.disabled = false;
            this.refreshBtn.classList.remove('spinning');
            // Reset timer so we don't refresh again immediately if user just clicked
            this.startAutoRefresh();
        }
    }

    renderMessages(messages) {
        if (!messages || messages.length === 0) {
            this.messagesList.classList.add('hidden');
            this.loadingState.classList.add('hidden');
            this.emptyState.classList.remove('hidden');
            return;
        }

        this.emptyState.classList.add('hidden');
        this.loadingState.classList.add('hidden');
        this.messagesList.classList.remove('hidden');

        // Preserve scroll position if refreshing
        const scrollTop = this.messagesList.scrollTop;

        this.messagesList.innerHTML = messages.map(msg => `
            <div class="message-item ${msg.is_read ? '' : 'unread'}" data-id="${msg.id}">
                <div class="message-dot"></div>
                <div class="message-content">
                    <div class="message-sender">${this.getDisplayName(msg)}</div>
                    <div class="message-subject">${this.escapeHtml(msg.subject)}</div>
                    <div class="message-preview">${this.escapeHtml(msg.preview || '')}</div>
                </div>
                <div class="message-time">${this.formatTime(msg.received_at)}</div>
            </div>
        `).join('');

        this.messagesList.scrollTop = scrollTop;

        // Add click listeners
        this.messagesList.querySelectorAll('.message-item').forEach(item => {
            item.addEventListener('click', () => this.openMessage(item.dataset.id));
        });
    }

    async openMessage(id) {
        try {
            const response = await fetch(`${this.basePath}/api/messages.php?id=${id}`);
            const message = await response.json();

            if (!response.ok) {
                throw new Error(message.error || 'Failed to load message');
            }

            // Set subject and from info
            this.modalSubject.textContent = message.subject;
            this.modalFrom.innerHTML = `From: <strong>${this.getDisplayName(message)}</strong> &nbsp;|&nbsp; ${this.formatDateTime(message.received_at)}`;

            // Render body with same logic as admin
            const body = this.modalBody;
            if (message.body_html) {
                // HTML email - use .email-body class for proper CSS styling
                body.className = 'modal-body email-body';
                body.innerHTML = message.body_html;
            } else {
                // Plain text email
                body.className = 'modal-body text-only';
                body.textContent = message.body_text || '(No content)';
                body.style.whiteSpace = 'pre-wrap';
            }

            // Show modal
            this.modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Mark as read in UI
            const messageItem = this.messagesList.querySelector(`[data-id="${id}"]`);
            if (messageItem) {
                messageItem.classList.remove('unread');
            }

            // Update unread count
            const currentUnread = parseInt(this.unreadBadge.textContent) || 0;
            if (currentUnread > 0) {
                this.updateUnreadBadge(currentUnread - 1);
            }

        } catch (error) {
            console.error('Error opening message:', error);
            this.showToast('Không thể mở email', 'error');
        }
    }

    closeModal() {
        this.modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    copyEmail() {
        if (!this.currentEmail) return;

        navigator.clipboard.writeText(this.currentEmail).then(() => {
            this.showToast('Đã sao chép email vào clipboard', 'success');
        }).catch(() => {
            // Fallback
            const input = document.createElement('input');
            input.value = this.currentEmail;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            this.showToast('Đã sao chép email vào clipboard', 'success');
        });
    }

    updateUnreadBadge(count, animate = false) {
        if (count > 0) {
            this.unreadBadge.textContent = count;
            this.unreadBadge.classList.remove('hidden');

            // Add pulse animation if requested
            if (animate) {
                this.unreadBadge.classList.add('updated');
                setTimeout(() => {
                    this.unreadBadge.classList.remove('updated');
                }, 500);
            }
        } else {
            this.unreadBadge.classList.add('hidden');
        }
    }

    startLongPolling() {
        this.stopLongPolling();

        // Initialize Long Polling Manager
        if (!this.pollingManager) {
            this.pollingManager = new LongPollingManager({
                basePath: this.basePath,
                onNewMessages: (messages, count) => this.handleNewMessages(messages, count),
                onError: (error, retryCount) => {
                    console.error(`Long polling error (retry ${retryCount}):`, error);
                },
                onStatusChange: (status) => {
                    console.log('Long polling status:', status);
                }
            });
        }

        // Start polling
        // Use lastCheck from server if available, otherwise fallback to local time calculation
        if (!this.lastCheck) {
            this.lastCheck = this.getCurrentSqlDateTimeVN();
        }

        this.pollingManager.start(this.currentEmailId, this.lastCheck);
        this.longPollActive = true;
    }

    stopLongPolling() {
        this.longPollActive = false;
        if (this.pollingManager) {
            this.pollingManager.stop();
        }
    }

    /**
     * Handle new messages from long polling
     * Smart update - only prepend new messages, no full re-render
     */
    async handleNewMessages(messages, count) {
        if (!messages || messages.length === 0) return;

        // Update last check timestamp
        if (this.pollingManager) {
            this.lastCheck = this.getCurrentSqlDateTimeVN();
            this.pollingManager.updateLastCheck(this.lastCheck);
        }

        this.showToast(`${count} email mới`, 'success', 'Tin nhắn mới');

        // Smart UI update - prepend new messages with animation
        this.prependNewMessages(messages);

        // Update unread badge with animation
        const currentUnread = parseInt(this.unreadBadge.textContent) || 0;
        this.updateUnreadBadge(currentUnread + count, true);
    }

    /**
     * Prepend new messages to the list with smooth animation
     * No flash, no full re-render
     */
    prependNewMessages(messages) {
        if (!messages || messages.length === 0) return;

        // Ensure messages list is visible
        this.emptyState.classList.add('hidden');
        this.loadingState.classList.add('hidden');
        this.messagesList.classList.remove('hidden');

        // Preserve scroll position
        const scrollTop = this.messagesList.scrollTop;

        // Create HTML for new messages
        const newMessagesHtml = messages.map(msg => `
            <div class="message-item ${msg.is_read ? '' : 'unread'} new-message" data-id="${msg.id}">
                <div class="message-dot new-indicator"></div>
                <div class="message-content">
                    <div class="message-sender">${this.escapeHtml(msg.from_name || msg.from_email)}</div>
                    <div class="message-subject">${this.escapeHtml(msg.subject)}</div>
                    <div class="message-preview">${this.escapeHtml(msg.preview || '')}</div>
                </div>
                <div class="message-time">${this.formatTime(msg.received_at)}</div>
            </div>
        `).join('');

        // Prepend to list
        this.messagesList.insertAdjacentHTML('afterbegin', newMessagesHtml);

        // Restore scroll position
        this.messagesList.scrollTop = scrollTop;

        // Add click listeners to new messages
        const newItems = this.messagesList.querySelectorAll('.message-item.new-message');
        newItems.forEach(item => {
            item.addEventListener('click', () => this.openMessage(item.dataset.id));

            // Remove animation class after animation completes
            setTimeout(() => {
                item.classList.remove('new-message');
                item.querySelector('.message-dot')?.classList.remove('new-indicator');
            }, 1500);
        });
    }

    // Keep this for manual refresh button
    startAutoRefresh() {
        // Deprecated - using long polling now
        // Kept for compatibility
    }

    showToast(message, type = '', title = '') {
        const iconMap = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: iconMap[type] || 'info',
            title: title || message,
            text: title ? message : '',
            showConfirmButton: false,
            timer: type === 'error' ? 4500 : 3000,
            timerProgressBar: true,
            customClass: {
                popup: 'km-toast'
            },
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    }

    getDisplayName(msg) {
        let name = msg.from_name;
        const email = msg.from_email || '';

        // Prefer name if it exists and isn't just the email
        if (name && name !== email) {
            // Filter out ugly names like "Em7877" or "bounces+..."
            if (/^Em\d+$/i.test(name) || name.includes('bounces+')) {
                // fall through to email processing
            } else {
                return this.escapeHtml(name);
            }
        }

        // Smart email extraction
        if (email.includes('openai.com')) return 'OpenAI';
        if (email.includes('facebook.com')) return 'Facebook';
        if (email.includes('google.com')) return 'Google';

        // Extract name from email
        const match = email.match(/^([^@]+)/);
        if (match) {
            let part = match[1];
            // Remove +tag if present
            if (part.includes('+')) part = part.split('+')[0];
            // Capitalize
            return this.escapeHtml(part.charAt(0).toUpperCase() + part.slice(1));
        }

        return this.escapeHtml(email);
    }

    extractSender(email) {
        // Legacy, redirected to getDisplayName logic but simpler
        if (!email) return 'Unknown';
        if (email.includes('openai.com')) return 'OpenAI';
        return email.split('@')[0];
    }

    formatTime(dateStr) {
        const date = this.parseDateInput(dateStr);
        if (!date) return '';

        const diff = Date.now() - date.getTime();

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Vừa xong';
        if (minutes < 60) return `${minutes} phút trước`;
        if (hours < 24) return `${hours} giờ trước`;
        if (days < 7) return `${days} ngày trước`;

        const parts = this.getVnTimeParts(date);
        if (!parts) return '';
        return `${parts.day}/${parts.month}/${parts.year}`;
    }

    formatDateTime(dateStr) {
        const parts = this.getVnTimeParts(dateStr);
        if (!parts) return '';
        return `${parts.day}/${parts.month}/${parts.year} ${parts.hour}:${parts.minute}`;
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.kaimail = new KaiMail();
});

