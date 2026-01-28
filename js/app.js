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
        this.toast = document.getElementById('toast');

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
        this.refreshBtn.addEventListener('click', () => this.loadMessages());
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

    async getMail() {
        let email = this.emailInput.value.trim().toLowerCase();

        if (!email) {
            this.showToast('Vui lòng nhập email', 'error');
            this.emailInput.focus();
            return;
        }

        // Require full email with @
        if (!email.includes('@')) {
            this.showToast('Vui lòng nhập email đầy đủ (ví dụ: user@domain.com)', 'error');
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
                    this.showToast(data.error || 'Có lỗi xảy ra', 'error');
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

            // Show current email
            // this.emailDisplay.textContent = email;
            // this.currentEmailDiv.classList.remove('hidden');
            this.inboxSection.classList.remove('hidden');

            // Load messages
            this.loadMessages();

            // Start long polling instead of simple interval
            this.startLongPolling();

        } catch (error) {
            console.error('Error:', error);
            this.showToast('Không thể kết nối server', 'error');
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

    async loadMessages() {
        if (!this.currentEmail || this.isLoading) return;

        this.isLoading = true;
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

            this.renderMessages(data.messages);
            this.updateUnreadBadge(data.unread);

        } catch (error) {
            console.error('Error loading messages:', error);
        } finally {
            this.isLoading = false;
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
                    <div class="message-sender">${this.escapeHtml(msg.from_name || this.extractSender(msg.from_email))}</div>
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

            this.modalSubject.textContent = message.subject;
            this.modalFrom.innerHTML = `From: <strong>${this.escapeHtml(message.from_email)}</strong> &nbsp;•&nbsp; ${this.formatDateTime(message.received_at)}`;

            // Render body
            if (message.body_html) {
                this.modalBody.className = 'modal-body';
                this.modalBody.innerHTML = message.body_html;
            } else {
                this.modalBody.className = 'modal-body text-only';
                this.modalBody.textContent = message.body_text || '(No content)';
            }

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
            this.showToast('✓ Email copied to clipboard!', 'success');
        }).catch(() => {
            // Fallback
            const input = document.createElement('input');
            input.value = this.currentEmail;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            this.showToast('✓ Email copied to clipboard!', 'success');
        });
    }

    updateUnreadBadge(count) {
        if (count > 0) {
            this.unreadBadge.textContent = count;
            this.unreadBadge.classList.remove('hidden');
        } else {
            this.unreadBadge.classList.add('hidden');
        }
    }

    startLongPolling() {
        this.stopLongPolling();
        this.longPollActive = true;
        this.lastCheck = new Date().toISOString().slice(0, 19).replace('T', ' ');
        this.pollForMessages();
    }

    stopLongPolling() {
        this.longPollActive = false;
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    async pollForMessages() {
        if (!this.longPollActive || !this.currentEmailId) return;

        try {
            const response = await fetch(
                `${this.basePath}/api/poll.php?email_id=${this.currentEmailId}&last_check=${encodeURIComponent(this.lastCheck)}`
            );

            const data = await response.json();

            if (response.ok && data.has_new && data.messages.length > 0) {
                // New messages arrived - update UI
                this.lastCheck = data.last_check;

                // Reload full message list to get proper ordering
                await this.loadMessages();

                // Show notification
                this.showToast(`📬 ${data.count} new message${data.count > 1 ? 's' : ''}!`, 'success');
            } else if (response.ok) {
                // No new messages, update timestamp
                this.lastCheck = data.last_check;
            }
        } catch (error) {
            console.error('Long polling error:', error);
            // Fall back to interval polling on error
            await new Promise(resolve => setTimeout(resolve, 5000));
        }

        // Continue polling if still active
        if (this.longPollActive) {
            // Small delay before next poll to prevent rapid requests
            setTimeout(() => this.pollForMessages(), 100);
        }
    }

    // Keep this for manual refresh button
    startAutoRefresh() {
        // Deprecated - using long polling now
        // Kept for compatibility
    }

    showToast(message, type = '') {
        this.toast.textContent = message;
        this.toast.className = `toast show ${type}`;

        setTimeout(() => {
            this.toast.className = 'toast';
        }, 3000);
    }

    extractSender(email) {
        if (!email) return 'Unknown';
        const match = email.match(/@([^.]+)/);
        if (match) {
            return match[1].charAt(0).toUpperCase() + match[1].slice(1);
        }
        return email.split('@')[0];
    }

    formatTime(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;

        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Vừa xong';
        if (minutes < 60) return `${minutes} phút trước`;
        if (hours < 24) return `${hours} giờ trước`;
        if (days < 7) return `${days} ngày trước`;

        return date.toLocaleDateString('vi-VN');
    }

    formatDateTime(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
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
