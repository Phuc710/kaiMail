/**
 * KaiMail User Inbox (basic + optimized).
 * Flow: email -> Get Mail -> read OTP quickly.
 */

class KaiMailTime {
    constructor() {
        this.vnTimeZone = "Asia/Ho_Chi_Minh";
    }

    parse(value) {
        if (value instanceof Date) {
            return Number.isNaN(value.getTime()) ? null : value;
        }

        const raw = String(value || "").trim();
        if (raw === "") return null;

        const sqlMatch = raw.match(
            /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/
        );
        const hasZone = /[zZ]$|[+-]\d{2}:\d{2}$/.test(raw);

        if (sqlMatch && !hasZone) {
            const year = Number(sqlMatch[1]);
            const month = Number(sqlMatch[2]);
            const day = Number(sqlMatch[3]);
            const hour = Number(sqlMatch[4] || "0");
            const minute = Number(sqlMatch[5] || "0");
            const second = Number(sqlMatch[6] || "0");
            const utcMs = Date.UTC(year, month - 1, day, hour - 7, minute, second);
            return new Date(utcMs);
        }

        const parsed = new Date(raw);
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

    getVnParts(value) {
        const date = this.parse(value);
        if (!date) return null;

        const parts = new Intl.DateTimeFormat("en-GB", {
            timeZone: this.vnTimeZone,
            hour12: false,
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        }).formatToParts(date);

        const map = Object.fromEntries(parts.map((p) => [p.type, p.value]));
        return {
            year: map.year,
            month: map.month,
            day: map.day,
            hour: map.hour,
            minute: map.minute,
            second: map.second,
        };
    }

    formatRelative(value) {
        const date = this.parse(value);
        if (!date) return "";

        const diff = Date.now() - date.getTime();
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return "Vừa xong";
        if (minutes < 60) return `${minutes} phút trước`;
        if (hours < 24) return `${hours} giờ trước`;
        if (days < 7) return `${days} ngày trước`;

        const p = this.getVnParts(date);
        if (!p) return "";
        return `${p.day}/${p.month}/${p.year}`;
    }

    formatDateTime(value) {
        const p = this.getVnParts(value);
        if (!p) return "";
        return `${p.day}/${p.month}/${p.year} ${p.hour}:${p.minute}`;
    }

    nowSqlVN() {
        const p = this.getVnParts(new Date());
        if (!p) return "";
        return `${p.year}-${p.month}-${p.day} ${p.hour}:${p.minute}:${p.second}`;
    }
}

class KaiMailApi {
    constructor(baseUrl, webToken) {
        this.baseUrl = String(baseUrl || "").trim().replace(/\/+$/, "");
        this.webToken = String(webToken || "").trim();
        this.requestTimeoutMs = 12000;
    }

    buildUrl(path, query = {}) {
        const cleanPath = path.startsWith("/") ? path : `/${path}`;
        const url = `${this.baseUrl}${cleanPath}`;
        const params = new URLSearchParams();

        Object.entries(query).forEach(([k, v]) => {
            if (v === null || v === undefined || v === "") return;
            params.set(k, String(v));
        });

        const queryString = params.toString();
        return queryString === "" ? url : `${url}?${queryString}`;
    }

    buildHeaders() {
        const headers = { Accept: "application/json" };
        if (this.webToken !== "") {
            headers["X-WEB-UI-TOKEN"] = this.webToken;
        }
        return headers;
    }

    async getJson(path, query = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.requestTimeoutMs);
        try {
            const response = await fetch(this.buildUrl(path, query), {
                method: "GET",
                headers: this.buildHeaders(),
                credentials: "same-origin",
                cache: "no-store",
                signal: controller.signal,
            });

            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                data = null;
            }

            return { ok: response.ok, status: response.status, data };
        } catch (error) {
            if (error?.name === "AbortError") {
                throw new Error("Kết nối chậm, vui lòng thử lại");
            }
            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }

    fetchMessages(email, limit = 25) {
        return this.getJson("/api/messages.php", { email, limit });
    }

    fetchMessageById(id) {
        return this.getJson("/api/messages.php", { id });
    }
}

class KaiMailUserPage {
    constructor() {
        this.config = window.KAIMAIL_CONFIG || {};
        this.baseUrl = String(this.config.baseUrl || "").trim();
        this.webToken = String(this.config.userToken || "").trim();
        this.basePath = this.extractBasePath(this.baseUrl);
        this.storageKey = "kaimail_email";

        this.time = new KaiMailTime();
        this.api = new KaiMailApi(this.baseUrl, this.webToken);

        this.state = {
            currentEmail: "",
            currentEmailId: 0,
            loading: false,
            unread: 0,
            renderedIds: new Set(),
            lastCheck: "",
            cooldowns: {}, // { key: nextAllowedTimestamp }
            clickStats: {}, // { key: { count: 0, last: 0 } }
        };

        this.poller = null;

        this.bindDom();
        // Fixed UI: Always show inbox section from start
        if (this.inboxSection) {
            this.inboxSection.classList.remove("hidden");
        }
    }

    bindDom() {
        this.emailInput = document.getElementById("emailInput");
        this.getMailBtn = document.getElementById("getMailBtn");
        this.copyBtn = document.getElementById("copyBtn");
        this.refreshBtn = document.getElementById("refreshBtn");
        this.inboxSection = document.getElementById("inboxSection");
        this.messagesList = document.getElementById("messagesList");
        this.emptyState = document.getElementById("emptyState");
        this.loadingState = document.getElementById("loadingState");
        this.unreadBadge = document.getElementById("unreadBadge");

        this.modal = document.getElementById("emailModal");
        this.modalSubject = document.getElementById("modalSubject");
        this.modalFrom = document.getElementById("modalFrom");
        this.modalBody = document.getElementById("modalBody");
        this.closeModalBtn = document.getElementById("closeModal");

        this.defaultGetBtnHtml = this.getMailBtn ? this.getMailBtn.innerHTML : "";
    }

    ready() {
        return Boolean(
            this.emailInput &&
            this.getMailBtn &&
            this.copyBtn &&
            this.refreshBtn &&
            this.inboxSection &&
            this.messagesList &&
            this.emptyState &&
            this.unreadBadge &&
            this.modal &&
            this.modalSubject &&
            this.modalFrom &&
            this.modalBody &&
            this.closeModalBtn
        );
    }

    init() {
        if (!this.ready()) return;

        this.getMailBtn.addEventListener("click", () => this.openInboxFromInput());
        this.emailInput.addEventListener("keydown", (event) => {
            if (event.key !== "Enter") return;
            event.preventDefault();
            this.openInboxFromInput();
        });
        this.copyBtn.addEventListener("click", () => this.copyEmail());
        this.refreshBtn.addEventListener("click", () => {
            if (this.checkSpam("refresh")) {
                this.loadMessages({ manual: true });
            }
        });

        this.messagesList.addEventListener("click", (event) => {
            const item = event.target.closest(".message-item");
            if (!item) return;
            const id = Number(item.getAttribute("data-id") || "0");
            if (id > 0) this.openMessage(id);
        });

        this.closeModalBtn.addEventListener("click", () => this.closeModal());
        this.modal.querySelector(".modal-backdrop")?.addEventListener("click", () => this.closeModal());
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && !this.modal.classList.contains("hidden")) {
                this.closeModal();
            }
        });
        window.addEventListener("beforeunload", () => this.stopPolling());

        const initialEmail = this.resolveInitialEmail();
        if (initialEmail !== "") {
            this.emailInput.value = initialEmail;
            this.openInbox(initialEmail, true);
            return;
        }

        const cachedEmail = String(localStorage.getItem(this.storageKey) || "").trim();
        if (cachedEmail !== "") {
            this.emailInput.value = cachedEmail;
        }
    }

    async openInboxFromInput() {
        if (!this.checkSpam("get_mail")) return;
        const email = this.normalizeEmail(this.emailInput.value);
        await this.openInbox(email, false);
    }

    async openInbox(email, autoOpen = false) {
        if (!this.isValidEmail(email)) {
            this.toast("Vui lòng nhập email đầy đủ (ví dụ: user@domain.com)", "error");
            this.emailInput.focus();
            return false;
        }

        this.state.currentEmail = email;
        this.showInbox(true);
        this.showCopyButton(true);
        this.setGetMailLoading(true);

        const loaded = await this.loadMessages({ manual: !autoOpen, showLoading: true, limit: 25 });
        this.setGetMailLoading(false);

        if (!loaded) return false;

        localStorage.setItem(this.storageKey, email);
        this.updateUrl(email);
        this.startPolling();
        return true;
    }

    async loadMessages({ manual = false, showLoading = false, limit = 25 } = {}) {
        const email = this.state.currentEmail;
        if (email === "") {
            if (manual) this.emailInput.focus();
            return false;
        }

        if (this.state.loading) return false;
        this.state.loading = true;

        this.setRefreshLoading(true);
        if (showLoading) this.showLoading(true);

        try {
            const { ok, status, data } = await this.api.fetchMessages(email, limit);
            if (!ok) {
                if (status === 404) {
                    this.toast("email not found", "error");
                    this.showEmpty(true);
                    return false;
                }
                throw new Error(data?.error || `Không thể tải hộp thư (HTTP ${status || 0})`);
            }

            this.state.currentEmailId = Number(data?.email_id || 0);
            this.state.lastCheck = String(data?.server_time || "").trim();
            this.state.unread = Number(data?.unread || 0);
            this.state.renderedIds = new Set();

            const messages = Array.isArray(data?.messages) ? data.messages : [];
            this.renderMessages(messages, true);
            this.setUnread(this.state.unread);
            return true;
        } catch (error) {
            this.toast(error?.message || "Không thể tải hộp thư", "error");
            this.showEmpty(true);
            return false;
        }
        finally {
            this.state.loading = false;
            this.setRefreshLoading(false);
            this.showLoading(false);
            // Ensure something is visible if no messages
            if (this.state.renderedIds.size === 0) {
                this.showEmpty(true);
            }
        }
    }

    renderMessages(messages, reset = false) {
        const list = Array.isArray(messages) ? messages : [];

        if (reset) {
            this.messagesList.innerHTML = "";
            this.state.renderedIds = new Set();
        }

        if (reset && list.length === 0) {
            this.showEmpty(true);
            this.showMessageList(false);
            return;
        }

        if (list.length > 0) {
            const rows = [];
            list.forEach((msg) => {
                const id = Number(msg?.id || 0);
                if (id < 1 || this.state.renderedIds.has(id)) return;
                this.state.renderedIds.add(id);
                rows.push(this.buildMessageRow(msg));
            });

            if (rows.length > 0) {
                if (reset) {
                    this.messagesList.innerHTML = rows.join("");
                } else {
                    this.messagesList.insertAdjacentHTML("afterbegin", rows.join(""));
                }
            }
        }

        this.showEmpty(this.state.renderedIds.size === 0);
        this.showMessageList(this.state.renderedIds.size > 0);
    }

    buildMessageRow(msg) {
        const id = Number(msg?.id || 0);
        const unread = Number(msg?.is_read || 0) === 0;
        const sender = this.escapeHtml(this.getDisplayName(msg));
        const subject = this.escapeHtml(String(msg?.subject || "(Không có tiêu đề)"));
        const preview = this.escapeHtml(String(msg?.preview || ""));
        const timeText = this.escapeHtml(this.time.formatRelative(msg?.received_at));

        return `
            <div class="message-item ${unread ? "unread" : ""}" data-id="${id}">
                <div class="message-dot"></div>
                <div class="message-content">
                    <div class="message-sender">${sender}</div>
                    <div class="message-subject">${subject}</div>
                    <div class="message-preview">${preview}</div>
                </div>
                <div class="message-time">${timeText}</div>
            </div>
        `;
    }

    async openMessage(id) {
        try {
            const { ok, data } = await this.api.fetchMessageById(id);
            if (!ok || !data) {
                throw new Error(data?.error || "Không thể mở email");
            }

            const sender = this.getDisplayName(data);
            const receivedAt = this.time.formatDateTime(data?.received_at);

            this.modalSubject.textContent = String(data?.subject || "(Không có tiêu đề)");
            this.modalFrom.innerHTML = `Người gửi: <strong>${this.escapeHtml(sender)}</strong> &nbsp;|&nbsp; ${this.escapeHtml(receivedAt)}`;

            if (String(data?.body_html || "").trim() !== "") {
                this.modalBody.className = "modal-body email-body";
                this.modalBody.innerHTML = String(data.body_html);
            } else {
                this.modalBody.className = "modal-body text-only";
                this.modalBody.textContent = String(data?.body_text || "(Không có nội dung)");
            }

            this.modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";

            const row = this.messagesList.querySelector(`.message-item[data-id="${id}"]`);
            if (row && row.classList.contains("unread")) {
                row.classList.remove("unread");
                if (this.state.unread > 0) {
                    this.state.unread -= 1;
                    this.setUnread(this.state.unread);
                }
            }
        } catch (error) {
            this.toast(error?.message || "Không thể mở email", "error");
        }
    }

    closeModal() {
        this.modal.classList.add("hidden");
        document.body.style.overflow = "";
    }

    async copyEmail() {
        const email = this.state.currentEmail;
        if (email === "") return;

        try {
            await navigator.clipboard.writeText(email);
            this.toast("Đã sao chép email", "success");
        } catch (error) {
            const input = document.createElement("input");
            input.value = email;
            document.body.appendChild(input);
            input.select();
            document.execCommand("copy");
            document.body.removeChild(input);
            this.toast("Đã sao chép email", "success");
        }
    }

    startPolling() {
        if (this.state.currentEmailId < 1 || typeof window.LongPollingManager !== "function") return;

        if (!this.poller) {
            this.poller = new window.LongPollingManager({
                basePath: this.baseUrl,
                webToken: this.webToken,
                onNewMessages: (messages, _count, payload) => this.handleNewMessages(messages, payload),
                onError: (err) => console.error("polling error:", err),
            });
        } else {
            this.poller.stop();
        }

        if (this.state.lastCheck === "") {
            this.state.lastCheck = this.time.nowSqlVN();
        }

        this.poller.start(this.state.currentEmailId, this.state.lastCheck);
    }

    stopPolling() {
        if (!this.poller) return;
        this.poller.stop();
    }

    handleNewMessages(messages, payload) {
        const list = Array.isArray(messages) ? messages : [];
        if (list.length === 0) return;

        this.renderMessages(list, false);

        const unreadAdded = list.reduce((sum, msg) => {
            return sum + (Number(msg?.is_read || 0) === 0 ? 1 : 0);
        }, 0);
        if (unreadAdded > 0) {
            this.state.unread += unreadAdded;
            this.setUnread(this.state.unread);
        }

        this.state.lastCheck = String(payload?.last_check || payload?.server_time || "").trim() || this.time.nowSqlVN();
        if (this.poller) {
            this.poller.updateLastCheck(this.state.lastCheck);
        }
    }

    setUnread(count) {
        const n = Number(count || 0);
        if (n > 0) {
            this.unreadBadge.textContent = String(n);
            this.unreadBadge.classList.remove("hidden");
            return;
        }
        this.unreadBadge.textContent = "0";
        this.unreadBadge.classList.add("hidden");
    }

    showInbox(show) {
        // Section is now fixed, no more hiding
        if (this.inboxSection) {
            this.inboxSection.classList.remove("hidden");
        }
    }

    showMessageList(show) {
        this.messagesList.classList.toggle("hidden", !show);
    }

    showEmpty(show) {
        this.emptyState.classList.toggle("hidden", !show);
    }

    showLoading(show) {
        // Home page no longer shows a spinner overlay while fetching.
        // Keep current content visible to avoid visual flicker.
        if (this.loadingState) {
            this.loadingState.classList.add("hidden");
        }
    }

    showCopyButton(show) {
        this.copyBtn.style.display = show ? "inline-flex" : "none";
    }

    setGetMailLoading(loading) {
        this.getMailBtn.disabled = Boolean(loading);
        if (loading) {
            this.getMailBtn.innerHTML = "<span>Đang xem...</span>";
            return;
        }
        this.getMailBtn.innerHTML = this.defaultGetBtnHtml;
    }

    setRefreshLoading(loading) {
        this.refreshBtn.disabled = Boolean(loading);
        this.refreshBtn.classList.toggle("spinning", Boolean(loading));
    }

    toast(message, type = "info") {
        const iconMap = { success: "success", error: "error", warning: "warning", info: "info" };
        const text = this.localizeError(message);

        if (window.Swal && typeof window.Swal.fire === "function") {
            window.Swal.fire({
                toast: true,
                position: "top-end",
                icon: iconMap[type] || "info",
                title: text,
                showConfirmButton: false,
                timer: type === "error" ? 5000 : 2500,
                timerProgressBar: true,
                customClass: { popup: "km-toast" },
            });
            return;
        }

        alert(text);
    }

    localizeError(message) {
        if (!message) return "Đã xảy ra lỗi";
        const raw = String(message).trim();
        const key = raw.toLowerCase();
        const map = {
            unauthorized: "Không được phép truy cập",
            forbidden: "Truy cập bị từ chối",
            ratelimitexceeded: "Bạn đang thao tác quá nhanh, vui lòng đợi",
            "method not allowed": "Phương thức không được hỗ trợ",
            "not found": "Không tìm thấy dữ liệu",
            "an error occurred": "Đã xảy ra lỗi",
            "internal server error": "Lỗi máy chủ nội bộ",
            "server error": "Lỗi máy chủ",
            "email is required": "Email là bắt buộc",
            "email not found": "Mail này không tồn tại",
            "email has expired": "Mail này đã hết hạn",
            "message not found": "Không tìm thấy tin nhắn",
            "polling failed": "Không thể đồng bộ hộp thư",
            "database connection failed": "Không thể kết nối cơ sở dữ liệu",
            "invalid json": "Dữ liệu JSON không hợp lệ",
            "missing required fields": "Thiếu dữ liệu bắt buộc",
            "email_id required": "Thiếu email_id",
        };
        const result = map[key] || raw;
        // fallback for hardcoded strings that might have been passed
        if (result === raw && key.includes("tồn tại")) return "Mail này không tồn tại";
        if (result === raw && key.includes("hết hạn")) return "Mail này đã hết hạn";
        return result;
    }

    getDisplayName(msg) {
        const fromName = String(msg?.from_name || "").trim();
        const fromEmail = String(msg?.from_email || "").trim();

        if (fromName !== "" && fromName !== fromEmail && !/^Em\d+$/i.test(fromName) && !fromName.includes("bounces+")) {
            return fromName;
        }

        if (fromEmail.includes("openai.com")) return "OpenAI";
        if (fromEmail.includes("facebook.com")) return "Facebook";
        if (fromEmail.includes("google.com")) return "Google";

        const match = fromEmail.match(/^([^@]+)/);
        if (match) {
            const local = match[1].split("+")[0];
            if (local !== "") {
                return local.charAt(0).toUpperCase() + local.slice(1);
            }
        }
        return fromEmail || "Không rõ";
    }

    resolveInitialEmail() {
        const queryEmail = new URLSearchParams(window.location.search).get("email");
        if (this.isValidEmail(queryEmail)) return this.normalizeEmail(queryEmail);

        const path = window.location.pathname.replace(/\/+$/, "");
        const base = this.basePath.replace(/\/+$/, "");

        if (!path.includes("@")) return "";

        if (base !== "" && path.startsWith(base)) {
            const raw = path.slice(base.length).replace(/^\/+/, "");
            const email = decodeURIComponent(raw);
            if (email.includes("/") || !this.isValidEmail(email)) return "";
            return this.normalizeEmail(email);
        }

        if (base === "") {
            const raw = path.replace(/^\/+/, "");
            const email = decodeURIComponent(raw);
            if (email.includes("/") || !this.isValidEmail(email)) return "";
            return this.normalizeEmail(email);
        }

        return "";
    }

    updateUrl(email) {
        const encodedEmail = encodeURIComponent(email).replace(/%40/g, "@");
        const base = this.basePath.replace(/\/+$/, "");
        const nextPath = `${base}/${encodedEmail}`.replace(/\/{2,}/g, "/");
        window.history.replaceState({}, "", nextPath);
    }

    extractBasePath(baseUrl) {
        const raw = String(baseUrl || "").trim();
        if (raw === "") return "";
        if (/^https?:\/\//i.test(raw)) {
            try {
                return new URL(raw).pathname.replace(/\/+$/, "");
            } catch (error) {
                return "";
            }
        }
        if (!raw.startsWith("/")) return `/${raw}`.replace(/\/+$/, "");
        return raw.replace(/\/+$/, "");
    }

    normalizeEmail(value) {
        return String(value || "").trim().toLowerCase();
    }

    isValidEmail(value) {
        const email = this.normalizeEmail(value);
        if (email === "") return false;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    escapeHtml(value) {
        const div = document.createElement("div");
        div.textContent = String(value ?? "");
        return div.innerHTML;
    }

    checkSpam(key) {
        const now = Date.now();
        const nextAllowed = this.state.cooldowns[key] || 0;

        // Still in cooldown lockout?
        if (now < nextAllowed) {
            const remaining = Math.ceil((nextAllowed - now) / 1000);
            this.toast(`Từ từ thôi! Vui lòng đợi ${remaining}s nữa nhé`, "warning");
            return false;
        }

        // Track clicks to detect spam
        const stats = this.state.clickStats[key] || { count: 0, last: 0 };
        if (now - stats.last > 2500) {
            stats.count = 0; // Reset after 2.5s of activity
        }
        stats.count += 1;
        stats.last = now;
        this.state.clickStats[key] = stats;

        // Trigger cooldown if clicking more than 5 times in short window
        if (stats.count >= 5) {
            const seconds = 5; // Reduced to 5s as requested
            this.state.cooldowns[key] = now + seconds * 1000;
            this.updateButtonCooldownUi(key, seconds);
            this.toast("Từ từ thôi! Thử lại sau 5s", "error");
            return false;
        }

        return true;
    }

    checkCooldown(key, seconds) {
        // This is now legacy, using checkSpam instead but keeping for reference if needed
        return this.checkSpam(key);
    }

    updateButtonCooldownUi(key, seconds) {
        let btn = null;
        let originalHtml = "";

        if (key === "get_mail") {
            btn = this.getMailBtn;
            originalHtml = this.defaultGetBtnHtml;
        } else if (key === "refresh") {
            btn = this.refreshBtn;
            originalHtml = btn.innerHTML;
        }

        if (!btn) return;

        let remaining = seconds;
        const timer = setInterval(() => {
            remaining -= 1;
            if (remaining <= 0) {
                clearInterval(timer);
                btn.disabled = false;
                if (key === "get_mail") {
                    btn.innerHTML = originalHtml;
                }
                return;
            }
            btn.disabled = true;
            if (key === "get_mail") {
                btn.innerHTML = `<span>Đợi ${remaining}s...</span>`;
            }
        }, 1000);

        btn.disabled = true;
        if (key === "get_mail") {
            btn.innerHTML = `<span>Đợi ${remaining}s...</span>`;
        }
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const app = new KaiMailUserPage();
    app.init();
    window.kaimail = app;
});
