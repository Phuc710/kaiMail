/**
 * KaiMail Admin Dashboard page logic (OOP).
 */
class AdminDashboardPage {
    constructor(core) {
        this.core = core;
        this.currentPage = 1;
        this.selectedIds = new Set();
        this.lastCheckTime = "";
        this.pollingActive = false;
        this.pollTimer = null;
        this.currentMessageEmailId = 0;
        this.currentMessageEmail = "";
        this.searchDebounceTimer = null;
    }

    init() {
        if (!document.getElementById("emailsTableBody")) {
            return;
        }

        this.bindEvents();
        this.bootstrap();
    }

    async bootstrap() {
        await this.loadInitialData();
        if (!this.lastCheckTime) {
            this.lastCheckTime = this.buildCurrentTimestamp();
        }
        this.startPolling();
    }

    bindEvents() {
        const searchInput = document.getElementById("searchInput");
        const expiryFilter = document.getElementById("expiryFilter");
        const selectAll = document.getElementById("selectAll");
        const deleteSelectedBtn = document.getElementById("deleteSelectedBtn");
        const emailsTableBody = document.getElementById("emailsTableBody");
        const messagesModalBody = document.getElementById("messagesModalBody");

        searchInput?.addEventListener("input", () => {
            if (this.searchDebounceTimer) clearTimeout(this.searchDebounceTimer);
            this.searchDebounceTimer = setTimeout(() => {
                this.currentPage = 1;
                this.loadEmails();
            }, 280);
        });

        expiryFilter?.addEventListener("change", () => {
            this.currentPage = 1;
            this.loadEmails();
        });

        selectAll?.addEventListener("change", (event) => {
            const checked = Boolean(event.target.checked);
            document.querySelectorAll(".email-checkbox").forEach((checkbox) => {
                checkbox.checked = checked;
                const id = Number(checkbox.getAttribute("data-id") || "0");
                if (!id) return;
                if (checked) this.selectedIds.add(id);
                else this.selectedIds.delete(id);
            });
            this.updateSelectionUi();
        });

        deleteSelectedBtn?.addEventListener("click", () => this.deleteSelected());

        emailsTableBody?.addEventListener("click", (event) => this.handleTableClick(event));
        emailsTableBody?.addEventListener("change", (event) => this.handleTableChange(event));
        messagesModalBody?.addEventListener("click", (event) => this.handleMessagesClick(event));

        window.addEventListener("beforeunload", () => this.stopPolling());
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) return;
            this.reloadData();
            if (this.pollingActive && this.pollTimer) {
                clearTimeout(this.pollTimer);
                this.pollLoop();
            }
        });
    }

    async loadInitialData() {
        await Promise.all([this.loadStats(), this.loadEmails()]);
    }

    async reloadData() {
        await Promise.all([this.loadStats(true), this.loadEmails(true)]);
    }

    setLoading(show) {
        const loading = document.getElementById("loadingState");
        if (loading) loading.classList.toggle("hidden", !show);
    }

    async loadStats(silent = false) {
        try {
            const { ok, data, status } = await this.core.fetchJson("/api/admin/stats.php");
            if (!ok || !data) {
                if (!silent) {
                    const message = data?.error || data?.message || `Không thể tải thống kê (HTTP ${status || 0})`;
                    this.core.showToast(message, "error");
                }
                return;
            }

            this.setStatValue("statTotalEmails", data.total_emails);
            this.setStatValue("statActiveEmails", data.active_emails);
            this.setStatValue("statTotalMessages", data.total_messages);

            if (data.server_time) {
                this.lastCheckTime = String(data.server_time);
            }
        } catch (error) {
            if (!silent) this.core.showToast("Không thể tải thống kê", "error");
        }
    }

    setStatValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (!element) return;
        element.textContent = Number(value || 0);
    }

    async loadEmails(silent = false) {
        const search = String(document.getElementById("searchInput")?.value || "").trim();
        const expiry = String(document.getElementById("expiryFilter")?.value || "");

        if (!silent) this.setLoading(true);

        try {
            const params = new URLSearchParams({
                filter: "active",
                page: String(this.currentPage),
                limit: "13",
            });

            if (search) params.set("search", search);
            if (expiry) params.set("expiry", expiry);

            const { ok, data } = await this.core.fetchJson(`/api/admin/emails.php?${params.toString()}`);
            if (!ok || !data) {
                this.renderEmails([]);
                this.renderPagination({ pages: 0 });
                if (!silent) this.core.showToast("Không thể tải danh sách email", "error");
                return;
            }

            if (data.server_time) {
                this.lastCheckTime = String(data.server_time);
            }

            this.renderEmails(Array.isArray(data.emails) ? data.emails : []);
            this.renderPagination(data);
        } catch (error) {
            if (!silent) this.core.showToast("Không thể tải danh sách email", "error");
        } finally {
            if (!silent) this.setLoading(false);
        }
    }

    renderEmails(emails) {
        const tbody = document.getElementById("emailsTableBody");
        if (!tbody) return;

        if (!emails.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="empty-row">Không có email nào</td>
                </tr>
            `;
            this.selectedIds.clear();
            this.updateSelectionUi();
            return;
        }

        const rowsHtml = emails.map((email) => {
            const id = Number(email.id || 0);
            const emailAddress = String(email.email || "");
            const emailEncoded = encodeURIComponent(emailAddress);
            const checked = this.selectedIds.has(id) ? "checked" : "";
            const unreadCount = Number(email.unread_count || 0);
            const createdAt = email.created_at ? this.core.formatDateTimeVN(email.created_at) : "-";
            const messageCount = Number(email.message_count || 0);

            return `
                <tr>
                    <td class="col-check">
                        <input type="checkbox" class="email-checkbox" data-id="${id}" ${checked}>
                    </td>
                    <td>
                        <div class="email-cell">
                            <span class="email-address">${this.core.escapeHtml(emailAddress)}</span>
                            <button class="btn-icon" data-action="copy-email" data-email="${emailEncoded}" title="Sao chép">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td>
                        <button class="btn-link" data-action="view-messages" data-email-id="${id}" data-email="${emailEncoded}">
                            ${messageCount}
                            ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ""}
                        </button>
                    </td>
                    <td>${this.core.escapeHtml(createdAt)}</td>
                    <td class="col-actions">
                        <button class="btn-icon" data-action="view-messages" data-email-id="${id}" data-email="${emailEncoded}" title="Xem tin nhắn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                        <button class="btn-icon danger" data-action="delete-email" data-email-id="${id}" title="Xóa email">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        }).join("");

        tbody.innerHTML = rowsHtml;
        this.updateSelectionUi();
    }

    renderPagination(data) {
        const pagination = document.getElementById("pagination");
        if (!pagination) return;

        const pages = Number(data.pages || 0);
        if (pages <= 1) {
            pagination.innerHTML = "";
            return;
        }

        let html = "";
        if (this.currentPage > 1) {
            html += `<button type="button" data-page="${this.currentPage - 1}">Trước</button>`;
        }

        for (let i = 1; i <= pages; i += 1) {
            if (i === 1 || i === pages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                const activeClass = i === this.currentPage ? "active" : "";
                html += `<button type="button" class="${activeClass}" data-page="${i}">${i}</button>`;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                html += `<button type="button" disabled>...</button>`;
            }
        }

        if (this.currentPage < pages) {
            html += `<button type="button" data-page="${this.currentPage + 1}">Sau</button>`;
        }

        pagination.innerHTML = html;
        pagination.querySelectorAll("button[data-page]").forEach((button) => {
            button.addEventListener("click", () => {
                this.currentPage = Number(button.getAttribute("data-page") || "1");
                this.loadEmails();
            });
        });
    }

    handleTableClick(event) {
        const actionEl = event.target.closest("[data-action]");
        if (!actionEl) return;

        const action = actionEl.getAttribute("data-action");
        if (action === "copy-email") {
            const email = decodeURIComponent(actionEl.getAttribute("data-email") || "");
            this.core.copyToClipboard(email);
            return;
        }

        if (action === "view-messages") {
            const emailId = Number(actionEl.getAttribute("data-email-id") || "0");
            const emailAddress = decodeURIComponent(actionEl.getAttribute("data-email") || "");
            this.viewMessages(emailId, emailAddress);
            return;
        }

        if (action === "delete-email") {
            const emailId = Number(actionEl.getAttribute("data-email-id") || "0");
            this.deleteEmail(emailId);
        }
    }

    handleTableChange(event) {
        const checkbox = event.target.closest(".email-checkbox");
        if (!checkbox) return;

        const id = Number(checkbox.getAttribute("data-id") || "0");
        if (!id) return;

        if (checkbox.checked) this.selectedIds.add(id);
        else this.selectedIds.delete(id);

        this.updateSelectionUi();
    }

    updateSelectionUi() {
        const deleteBtn = document.getElementById("deleteSelectedBtn");
        const selectAll = document.getElementById("selectAll");
        const checkboxes = Array.from(document.querySelectorAll(".email-checkbox"));
        const totalCheckboxes = checkboxes.length;
        const checkedCount = checkboxes.filter((box) => box.checked).length;

        if (deleteBtn) {
            if (this.selectedIds.size > 0) {
                deleteBtn.classList.remove("hidden");
                deleteBtn.querySelector("span").textContent = `Xóa ${this.selectedIds.size} email`;
            } else {
                deleteBtn.classList.add("hidden");
                deleteBtn.querySelector("span").textContent = "Xóa đã chọn";
            }
        }

        if (selectAll) {
            selectAll.checked = totalCheckboxes > 0 && checkedCount === totalCheckboxes;
        }
    }

    async deleteEmail(emailId) {
        if (!emailId) return;
        const confirmed = await this.core.confirmAction({
            title: "Xác nhận xóa email",
            text: "Bạn có chắc muốn xóa email này?",
            confirmButtonText: "Xóa email",
            cancelButtonText: "Hủy",
            icon: "warning",
        });
        if (!confirmed) return;

        try {
            const { ok, data } = await this.core.postJson("/api/admin/emails.php", {
                _method: "DELETE",
                ids: [emailId],
            });

            if (!ok) {
                this.core.showToast(data?.error || "Không thể xóa email", "error");
                return;
            }

            this.selectedIds.delete(emailId);
            this.core.showToast("Đã xóa email", "success");
            await this.reloadData();
        } catch (error) {
            this.core.showToast("Lỗi kết nối máy chủ", "error");
        }
    }

    async deleteSelected() {
        if (this.selectedIds.size < 1) return;
        const confirmed = await this.core.confirmAction({
            title: "Xác nhận xóa email",
            text: `Bạn có chắc muốn xóa ${this.selectedIds.size} email?`,
            confirmButtonText: "Xóa đã chọn",
            cancelButtonText: "Hủy",
            icon: "warning",
        });
        if (!confirmed) return;

        try {
            const ids = Array.from(this.selectedIds);
            const { ok, data } = await this.core.postJson("/api/admin/emails.php", {
                _method: "DELETE",
                ids,
            });

            if (!ok) {
                this.core.showToast(data?.error || "Không thể xóa email", "error");
                return;
            }

            this.selectedIds.clear();
            this.core.showToast(`Đã xóa ${ids.length} email`, "success");
            await this.reloadData();
        } catch (error) {
            this.core.showToast("Lỗi kết nối máy chủ", "error");
        }
    }

    async viewMessages(emailId, emailAddress) {
        if (!emailId) return;
        this.currentMessageEmailId = emailId;
        this.currentMessageEmail = emailAddress;

        const modalTitle = document.getElementById("messagesModalTitle");
        const modalBody = document.getElementById("messagesModalBody");
        modalTitle.textContent = emailAddress;
        modalBody.innerHTML = `<div class="loading-state"><div class="spinner"></div></div>`;

        this.core.openModal("messagesModal");

        try {
            const { ok, data } = await this.core.fetchJson(`/api/admin/messages.php?email_id=${emailId}`);
            if (!ok || !data) {
                modalBody.innerHTML = `<div class="error-state">Không thể tải danh sách tin nhắn</div>`;
                return;
            }

            const messages = Array.isArray(data.messages) ? data.messages : [];
            if (!messages.length) {
                modalBody.innerHTML = `<div class="empty-state-sm">Email này chưa có tin nhắn</div>`;
                return;
            }

            modalBody.innerHTML = `
                <div class="messages-list-admin">
                    ${messages.map((msg) => {
                        const id = Number(msg.id || 0);
                        const isUnread = Number(msg.is_read || 0) === 0;
                        const sender = this.core.escapeHtml(this.core.cleanEmail(msg.from_email, msg.from_name));
                        const subject = this.core.escapeHtml(msg.subject || "(Không có tiêu đề)");
                        const preview = this.core.escapeHtml(msg.preview || msg.body_text || "");
                        const time = this.core.escapeHtml(this.core.formatTimeVN(msg.received_at));

                        return `
                            <div class="message-item-admin ${isUnread ? "unread" : ""}" data-action="open-message" data-message-id="${id}">
                                <div class="message-info">
                                    <strong>${sender}</strong>
                                    <span>${subject}</span>
                                    <div class="message-preview">${preview}</div>
                                </div>
                                <div class="message-meta-right">
                                    <span>${time}</span>
                                    <button class="btn-icon danger" data-action="delete-message" data-message-id="${id}" data-email-id="${emailId}" title="Xóa tin nhắn">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join("")}
                </div>
            `;
        } catch (error) {
            modalBody.innerHTML = `<div class="error-state">Không thể tải danh sách tin nhắn</div>`;
        }
    }

    async viewMessage(messageId) {
        if (!messageId) return;

        try {
            const { ok, data } = await this.core.fetchJson(`/api/admin/messages.php?id=${messageId}`);
            if (!ok || !data) {
                this.core.showToast("Không thể mở nội dung email", "error");
                return;
            }

            const subjectEl = document.getElementById("viewMessageSubject");
            const fromEl = document.getElementById("viewMessageFrom");
            const bodyEl = document.getElementById("viewMessageBody");

            subjectEl.textContent = data.subject || "(Không có tiêu đề)";
            fromEl.innerHTML = `Từ: <strong>${this.core.escapeHtml(
                this.core.cleanEmail(data.from_email, data.from_name)
            )}</strong> • ${this.core.escapeHtml(this.core.formatDateTimeVN(data.received_at))}`;

            if (data.body_html) {
                bodyEl.innerHTML = data.body_html;
                bodyEl.style.whiteSpace = "";
            } else {
                bodyEl.textContent = data.body_text || "(Không có nội dung)";
                bodyEl.style.whiteSpace = "pre-wrap";
            }

            this.core.openModal("viewMessageModal");
        } catch (error) {
            this.core.showToast("Không thể mở nội dung email", "error");
        }
    }

    async deleteMessage(messageId, emailId) {
        if (!messageId || !emailId) return;
        const confirmed = await this.core.confirmAction({
            title: "Xác nhận xóa tin nhắn",
            text: "Bạn có chắc muốn xóa tin nhắn này?",
            confirmButtonText: "Xóa tin nhắn",
            cancelButtonText: "Hủy",
            icon: "warning",
        });
        if (!confirmed) return;

        try {
            const { ok, data } = await this.core.postJson("/api/admin/messages.php", {
                _method: "DELETE",
                ids: [messageId],
            });

            if (!ok) {
                this.core.showToast(data?.error || "Không thể xóa tin nhắn", "error");
                return;
            }

            this.core.showToast("Đã xóa tin nhắn", "success");
            await Promise.all([this.viewMessages(emailId, this.currentMessageEmail), this.reloadData()]);
        } catch (error) {
            this.core.showToast("Lỗi kết nối máy chủ", "error");
        }
    }

    handleMessagesClick(event) {
        const actionEl = event.target.closest("[data-action]");
        if (!actionEl) return;

        const action = actionEl.getAttribute("data-action");
        const messageId = Number(actionEl.getAttribute("data-message-id") || "0");
        const emailId = Number(actionEl.getAttribute("data-email-id") || "0") || this.currentMessageEmailId;

        if (action === "open-message") {
            this.viewMessage(messageId);
        } else if (action === "delete-message") {
            event.stopPropagation();
            this.deleteMessage(messageId, emailId);
        }
    }

    startPolling() {
        if (this.pollingActive) return;
        this.pollingActive = true;
        this.pollLoop();
    }

    stopPolling() {
        this.pollingActive = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
    }

    async pollLoop() {
        if (!this.pollingActive) return;

        if (document.hidden) {
            this.pollTimer = setTimeout(() => this.pollLoop(), 3000);
            return;
        }

        const currentLastCheck = this.lastCheckTime || this.buildCurrentTimestamp();
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 35000);

        try {
            const { ok, data } = await this.core.fetchJson(
                `/api/admin/poll.php?last_check=${encodeURIComponent(currentLastCheck)}`,
                { signal: controller.signal }
            );
            clearTimeout(timeoutId);

            if (!ok || !data) {
                this.pollTimer = setTimeout(() => this.pollLoop(), 3000);
                return;
            }

            if (data.last_check) {
                this.lastCheckTime = String(data.last_check);
            }

            if (data.has_updates) {
                await this.reloadData();
            }

            this.pollTimer = setTimeout(() => this.pollLoop(), 120);
        } catch (error) {
            clearTimeout(timeoutId);
            this.pollTimer = setTimeout(() => this.pollLoop(), 5000);
        }
    }

    buildCurrentTimestamp() {
        return this.core.getCurrentSqlDateTimeVN();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const boot = () => {
        if (!window.adminCore) return;
        const dashboard = new AdminDashboardPage(window.adminCore);
        dashboard.init();
        window.adminDashboard = dashboard;
    };

    if (window.adminCore) {
        boot();
    } else {
        window.addEventListener("admin-core-ready", boot, { once: true });
    }
});


