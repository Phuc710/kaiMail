/**
 * KaiMail Admin - Shared UI Core (không dùng cookie/session).
 */
class AdminCore {
    constructor(baseUrl) {
        this.baseUrl = (baseUrl || "").replace(/\/+$/, "");
        this.toastTimer = null;
        this.activeModalCount = 0;
        this.adminKeyStorage = "kaimail_admin_access_key";
        this.vnLocale = "vi-VN";
        this.vnTimeZone = "Asia/Ho_Chi_Minh";
    }

    async init() {
        await this.ensureAdminUiAuthenticated();
        this.bindLogout();
        this.bindModalSystem();
        this.bindCreateEmailForm();
        this.bindAddDomainForm();
        this.bindDomainDeleteButtons();
        this.bindMobileMenu();
    }

    buildUrl(path) {
        if (!path) return this.baseUrl;
        if (/^https?:\/\//i.test(path)) return path;
        const normalized = path.startsWith("/") ? path : `/${path}`;
        return `${this.baseUrl}${normalized}`;
    }

    getAdminAccessKey() {
        return (localStorage.getItem(this.adminKeyStorage) || "").trim();
    }

    setAdminAccessKey(value) {
        const normalized = String(value || "").trim();
        if (!normalized) return;
        localStorage.setItem(this.adminKeyStorage, normalized);
    }

    clearAdminAccessKey() {
        localStorage.removeItem(this.adminKeyStorage);
    }

    isLoginPage() {
        return window.location.pathname.includes("/adminkaishop/login");
    }

    isAdminUiPage() {
        return window.location.pathname.includes("/adminkaishop");
    }

    async ensureAdminUiAuthenticated() {
        if (!this.isAdminUiPage() || this.isLoginPage()) {
            return;
        }

        const adminKey = this.getAdminAccessKey();
        if (!adminKey) {
            window.location.href = this.buildUrl("/adminkaishop/login");
            return;
        }

        try {
            const response = await fetch(this.buildUrl("/api/admin/auth.php"), {
                method: "GET",
                headers: { "X-ADMIN-ACCESS-KEY": adminKey },
            });

            if (!response.ok) {
                this.clearAdminAccessKey();
                window.location.href = this.buildUrl("/adminkaishop/login");
            }
        } catch (error) {
            // Lỗi mạng tạm thời: giữ nguyên trang để người dùng thử lại.
        }
    }

    buildHeaders(path, existingHeaders = {}) {
        const headers = new Headers(existingHeaders || {});
        if (!headers.has("Content-Type")) {
            headers.set("Content-Type", "application/json");
        }

        if (path.startsWith("/api/admin/")) {
            const adminKey = this.getAdminAccessKey();
            if (adminKey !== "") {
                headers.set("X-ADMIN-ACCESS-KEY", adminKey);
            }
        }

        return headers;
    }

    async fetchJson(path, options = {}) {
        const requestOptions = { ...options };
        requestOptions.headers = this.buildHeaders(path, requestOptions.headers || {});

        const response = await fetch(this.buildUrl(path), requestOptions);
        let data = null;

        try {
            data = await response.json();
        } catch (error) {
            data = null;
        }

        if (response.status === 401 && this.isAdminUiPage() && !this.isLoginPage() && path.startsWith("/api/admin/")) {
            this.clearAdminAccessKey();
            window.location.href = this.buildUrl("/adminkaishop/login");
        }

        return { ok: response.ok, status: response.status, data };
    }

    async postJson(path, payload) {
        return this.fetchJson(path, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
    }

    showToast(message, type = "") {
        const toast = document.getElementById("toast");
        if (!toast) return;

        toast.textContent = this.localizeApiError(message);
        toast.className = `toast show ${type}`.trim();

        if (this.toastTimer) {
            clearTimeout(this.toastTimer);
        }

        this.toastTimer = setTimeout(() => {
            toast.className = "toast";
        }, 3000);
    }

    localizeApiError(message) {
        if (!message) return "Đã xảy ra lỗi";
        const text = String(message).trim();
        const lowered = text.toLowerCase();

        const dictionary = {
            unauthorized: "Không được phép truy cập",
            "method not allowed": "Phương thức không được hỗ trợ",
            "not found": "Không tìm thấy dữ liệu",
            "an error occurred": "Đã xảy ra lỗi",
            "internal server error": "Lỗi máy chủ nội bộ",
            "server error": "Lỗi máy chủ",
            "fatal error": "Lỗi nghiêm trọng",
            "email is required": "Email là bắt buộc",
            "invalid email format": "Định dạng email không hợp lệ",
            "email not found": "Email không tồn tại trong hệ thống",
            "email has expired": "Email đã hết hạn",
            "message not found": "Không tìm thấy tin nhắn",
            "polling failed": "Không thể đồng bộ hộp thư",
            "database connection failed": "Không thể kết nối cơ sở dữ liệu",
            "invalid json": "Dữ liệu JSON không hợp lệ",
            "missing required fields": "Thiếu trường dữ liệu bắt buộc",
            "email_id required": "Thiếu email_id"
        };

        return dictionary[lowered] || text;
    }

    escapeHtml(value) {
        if (value === null || value === undefined) return "";
        const div = document.createElement("div");
        div.textContent = String(value);
        return div.innerHTML;
    }

    async copyToClipboard(text) {
        const safeText = String(text || "");
        if (!safeText) return;

        try {
            await navigator.clipboard.writeText(safeText);
            this.showToast("Đã sao chép vào clipboard", "success");
            return;
        } catch (error) {
            const input = document.createElement("input");
            input.value = safeText;
            document.body.appendChild(input);
            input.select();
            document.execCommand("copy");
            document.body.removeChild(input);
            this.showToast("Đã sao chép vào clipboard", "success");
        }
    }

    parseDateInput(dateValue) {
        if (dateValue instanceof Date) {
            return Number.isNaN(dateValue.getTime()) ? null : dateValue;
        }

        const raw = String(dateValue || "").trim();
        if (raw === "") {
            return null;
        }

        // Chuẩn "YYYY-MM-DD HH:mm:ss" từ MySQL: hiểu là giờ VN (+07:00).
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
        if (Number.isNaN(parsed.getTime())) {
            return null;
        }
        return parsed;
    }

    getTimePartsVN(dateValue) {
        const parsedDate = this.parseDateInput(dateValue);
        if (!parsedDate) return null;

        const parts = new Intl.DateTimeFormat("en-GB", {
            timeZone: this.vnTimeZone,
            hour12: false,
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
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

    parseToGMT7(dateValue) {
        const vnParts = this.getTimePartsVN(dateValue);
        if (!vnParts) return new Date(NaN);

        const utcMs = Date.UTC(
            Number(vnParts.year),
            Number(vnParts.month) - 1,
            Number(vnParts.day),
            Number(vnParts.hour),
            Number(vnParts.minute),
            Number(vnParts.second)
        );
        return new Date(utcMs);
    }

    formatDateVN(dateValue) {
        const vnParts = this.getTimePartsVN(dateValue);
        if (!vnParts) return "";
        return `${vnParts.day}/${vnParts.month}/${vnParts.year}`;
    }

    formatDateTimeVN(dateValue) {
        const vnParts = this.getTimePartsVN(dateValue);
        if (!vnParts) return "";
        return `${vnParts.day}/${vnParts.month}/${vnParts.year} ${vnParts.hour}:${vnParts.minute}`;
    }

    getCurrentSqlDateTimeVN() {
        const nowParts = this.getTimePartsVN(new Date());
        if (!nowParts) return "";
        return `${nowParts.year}-${nowParts.month}-${nowParts.day} ${nowParts.hour}:${nowParts.minute}:${nowParts.second}`;
    }

    formatTimeVN(dateValue) {
        const date = this.parseDateInput(dateValue);
        if (!date) return "";
        const diff = Date.now() - date.getTime();
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return "Vừa xong";
        if (minutes < 60) return `${minutes} phút trước`;
        if (hours < 24) return `${hours} giờ trước`;
        if (days < 7) return `${days} ngày trước`;
        return this.formatDateVN(dateValue);
    }

    cleanEmail(email, name = null) {
        if (name && String(name).trim() && !String(name).includes("@")) {
            return String(name).trim();
        }

        const safeEmail = String(email || "").trim();
        if (!safeEmail) return "";

        if (safeEmail.includes("bounces+") && safeEmail.includes("=")) {
            const match = safeEmail.match(/([^=]+)=([^@]+)@/);
            if (match) {
                return `${match[1]}@${match[2]}`;
            }
        }

        return safeEmail;
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.remove("hidden");
        this.syncBodyScroll();
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        modal.classList.add("hidden");
        this.syncBodyScroll();
    }

    closeModalElement(modalElement) {
        if (!modalElement) return;
        modalElement.classList.add("hidden");
        this.syncBodyScroll();
    }

    syncBodyScroll() {
        this.activeModalCount = document.querySelectorAll(".modal:not(.hidden)").length;
        document.body.style.overflow = this.activeModalCount > 0 ? "hidden" : "";
    }

    bindModalSystem() {
        document.querySelectorAll("[data-modal-open]").forEach((button) => {
            button.addEventListener("click", () => {
                const targetId = button.getAttribute("data-modal-open");
                if (targetId) this.openModal(targetId);
            });
        });

        document.querySelectorAll("[data-modal-close]").forEach((button) => {
            button.addEventListener("click", () => {
                const targetId = button.getAttribute("data-modal-close");
                if (targetId) {
                    this.closeModal(targetId);
                } else {
                    this.closeModalElement(button.closest(".modal"));
                }
            });
        });

        document.querySelectorAll(".modal-backdrop").forEach((backdrop) => {
            backdrop.addEventListener("click", () => {
                this.closeModalElement(backdrop.closest(".modal"));
            });
        });

        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") return;
            const visibleModals = Array.from(document.querySelectorAll(".modal:not(.hidden)"));
            const latestModal = visibleModals[visibleModals.length - 1];
            if (latestModal) this.closeModalElement(latestModal);
        });
    }

    bindLogout() {
        const logoutBtn = document.getElementById("logoutBtn");
        if (!logoutBtn) return;

        logoutBtn.addEventListener("click", async () => {
            this.clearAdminAccessKey();
            try {
                await this.fetchJson("/api/admin/auth.php", { method: "DELETE" });
            } catch (error) {
                // Bỏ qua lỗi đăng xuất phía API.
            }
            window.location.href = this.buildUrl("/adminkaishop/login");
        });
    }

    bindCreateEmailForm() {
        const form = document.getElementById("createEmailForm");
        if (!form) return;

        const customEmailGroup = document.getElementById("customEmailGroup");
        form.querySelectorAll('input[name="name_type"]').forEach((radio) => {
            radio.addEventListener("change", () => {
                if (!customEmailGroup) return;
                const showCustom = radio.checked && radio.value === "custom";
                customEmailGroup.classList.toggle("hidden", !showCustom);
            });
        });

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) return;

            const formData = new FormData(form);
            const nameType = String(formData.get("name_type") || "vn");
            const customEmail = String(document.getElementById("customEmail")?.value || "").trim().toLowerCase();
            const selectedDomain = String(document.getElementById("domainSelect")?.value || "").trim();

            if (!selectedDomain) {
                this.showToast("Vui lòng chọn domain hoạt động", "error");
                return;
            }

            if (nameType === "custom" && !customEmail) {
                this.showToast("Vui lòng nhập tên email tùy chỉnh", "error");
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = "Đang tạo...";

            const payload = { name_type: nameType, domain: selectedDomain };
            if (nameType === "custom") {
                payload.email = customEmail;
            }

            try {
                const { ok, data } = await this.postJson("/api/admin/emails.php", payload);
                if (!ok) {
                    this.showToast(data?.error || "Không thể tạo email", "error");
                    return;
                }

                const created = Number(data?.created || 0);
                if (created > 0) {
                    this.showToast(created === 1 ? "Đã tạo email mới" : `Đã tạo ${created} email`, "success");
                } else if (Array.isArray(data?.errors) && data.errors.length > 0) {
                    this.showToast(String(data.errors[0]), "error");
                }

                form.reset();
                if (customEmailGroup) customEmailGroup.classList.add("hidden");
                this.closeModal("createModal");

                if (window.adminDashboard && typeof window.adminDashboard.reloadData === "function") {
                    await window.adminDashboard.reloadData();
                }
            } catch (error) {
                this.showToast("Lỗi kết nối máy chủ", "error");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = "Tạo email";
            }
        });
    }

    bindAddDomainForm() {
        const form = document.getElementById("addDomainForm");
        if (!form) return;

        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const submitBtn = form.querySelector('button[type="submit"]');
            if (!submitBtn) return;

            const domainName = String(document.getElementById("domainName")?.value || "").toLowerCase().trim();
            const statusInput = form.querySelector('input[name="domain_status"]:checked');
            const isActive = Number(statusInput?.value || "1");

            if (!domainName) {
                this.showToast("Vui lòng nhập domain", "error");
                return;
            }

            if (!/^[a-z0-9.-]+\.[a-z]{2,}$/.test(domainName)) {
                this.showToast("Domain không đúng định dạng", "error");
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = "Đang thêm...";

            try {
                const { ok, data } = await this.postJson("/api/admin/domains.php", {
                    domain: domainName,
                    is_active: isActive,
                });

                if (!ok) {
                    this.showToast(data?.error || "Không thể thêm domain", "error");
                    return;
                }

                this.showToast("Đã thêm domain thành công", "success");
                this.closeModal("addDomainModal");
                form.reset();
                setTimeout(() => window.location.reload(), 800);
            } catch (error) {
                this.showToast("Lỗi kết nối máy chủ", "error");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = "Thêm domain";
            }
        });
    }

    bindDomainDeleteButtons() {
        document.querySelectorAll("[data-domain-delete]").forEach((button) => {
            button.addEventListener("click", async () => {
                const id = Number(button.getAttribute("data-domain-delete") || "0");
                const domainName = button.getAttribute("data-domain-name") || "";
                await this.deleteDomain(id, domainName);
            });
        });
    }

    async deleteDomain(id, domainName = "") {
        if (!id) return;

        const confirmed = window.confirm(
            `Bạn có chắc muốn xóa domain "${domainName}"?\nChỉ xóa được domain chưa có email.`
        );
        if (!confirmed) return;

        try {
            const { ok, data } = await this.postJson("/api/admin/domains.php", {
                _method: "DELETE",
                id,
            });

            if (!ok) {
                this.showToast(data?.error || "Không thể xóa domain", "error");
                return;
            }

            this.showToast("Đã xóa domain", "success");
            setTimeout(() => window.location.reload(), 600);
        } catch (error) {
            this.showToast("Lỗi kết nối máy chủ", "error");
        }
    }

    bindMobileMenu() {
        const menuBtn = document.getElementById("mobileMenuBtn");
        const sidebar = document.getElementById("adminSidebar");
        const overlay = document.getElementById("sidebarOverlay");

        if (!menuBtn || !sidebar || !overlay) return;

        const closeSidebar = () => {
            sidebar.classList.remove("show");
            overlay.classList.remove("show");
        };

        menuBtn.addEventListener("click", () => {
            sidebar.classList.toggle("show");
            overlay.classList.toggle("show");
        });

        overlay.addEventListener("click", closeSidebar);
        sidebar.querySelectorAll(".nav-item").forEach((item) => {
            item.addEventListener("click", closeSidebar);
        });
    }
}

document.addEventListener("DOMContentLoaded", async () => {
    const baseUrl = document.documentElement.dataset.baseUrl || "";
    const core = new AdminCore(baseUrl);
    window.adminCore = core;
    await core.init();
    window.dispatchEvent(new CustomEvent("admin-core-ready"));
});
