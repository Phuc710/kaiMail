/**
 * KaiMail Admin Login page logic (OOP).
 */
class AdminAccessKeyStore {
    constructor(storageKey) {
        this.storageKey = String(storageKey || "kaimail_admin_access_key");
    }

    normalize(rawValue) {
        const raw = String(rawValue || "").trim();
        const envPrefix = "ADMIN_ACCESS_KEY=";

        if (raw.startsWith(envPrefix)) {
            return raw.slice(envPrefix.length).trim();
        }

        return raw;
    }

    get() {
        return this.normalize(sessionStorage.getItem(this.storageKey));
    }

    set(value) {
        const normalized = this.normalize(value);
        if (normalized === "") {
            return;
        }

        sessionStorage.setItem(this.storageKey, normalized);
    }

    clear() {
        sessionStorage.removeItem(this.storageKey);
    }
}

class AdminAuthApiClient {
    constructor(baseUrl, authEndpoint) {
        this.baseUrl = String(baseUrl || "").replace(/\/+$/, "");
        this.authEndpoint = this.normalizePath(authEndpoint || "/api/admin/auth.php");
    }

    normalizePath(path) {
        const raw = String(path || "").trim();
        if (raw === "") {
            return "/api/admin/auth.php";
        }

        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }

        return raw.startsWith("/") ? raw : `/${raw}`;
    }

    buildUrl(path) {
        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        return `${this.baseUrl}${path}`;
    }

    async verify(accessKey) {
        if (!accessKey) {
            return { ok: false, status: 0, message: "Thieu khoa truy cap" };
        }

        try {
            const response = await fetch(this.buildUrl(this.authEndpoint), {
                method: "GET",
                headers: {
                    "X-ADMIN-ACCESS-KEY": accessKey,
                },
            });

            if (response.ok) {
                return { ok: true, status: response.status, message: "Xac thuc thanh cong" };
            }

            const data = await this.readJsonSafe(response);
            return {
                ok: false,
                status: response.status,
                message: String(data?.message || data?.error || "Khoa truy cap khong dung"),
            };
        } catch (error) {
            return { ok: false, status: 0, message: "Khong the ket noi may chu" };
        }
    }

    async login(password) {
        if (!password) {
            return { ok: false, status: 0, message: "Vui long nhap khoa truy cap" };
        }

        try {
            const response = await fetch(this.buildUrl(this.authEndpoint), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ password }),
            });

            const data = await this.readJsonSafe(response);
            if (!response.ok || !data?.success) {
                return {
                    ok: false,
                    status: response.status,
                    message: String(data?.message || data?.error || "Khoa truy cap khong dung"),
                };
            }

            return { ok: true, status: response.status, message: "Dang nhap thanh cong" };
        } catch (error) {
            return { ok: false, status: 0, message: "Khong the ket noi may chu" };
        }
    }

    async readJsonSafe(response) {
        try {
            return await response.json();
        } catch (error) {
            return null;
        }
    }
}

class AdminLoginPageController {
    constructor(options) {
        this.form = options.form;
        this.passwordInput = options.passwordInput;
        this.errorMessage = options.errorMessage;
        this.statusMessage = options.statusMessage;
        this.submitButton = options.submitButton;
        this.submitText = options.submitText;
        this.redirectUrl = options.redirectUrl;
        this.keyStore = options.keyStore;
        this.authApi = options.authApi;
    }

    init() {
        if (!this.form || !this.passwordInput || !this.submitButton || !this.submitText) {
            return;
        }

        this.form.addEventListener("submit", (event) => this.handleSubmit(event));
        this.tryAutoLogin();
    }

    async tryAutoLogin() {
        const storedKey = this.keyStore.get();
        if (storedKey === "") {
            return;
        }

        this.showStatus("Dang kiem tra phien dang nhap...");

        const result = await this.authApi.verify(storedKey);
        if (result.ok) {
            this.redirectToDashboard();
            return;
        }

        if (result.status === 401) {
            this.keyStore.clear();
        }

        this.hideStatus();
    }

    async handleSubmit(event) {
        event.preventDefault();

        const rawPassword = this.passwordInput.value;
        const password = this.keyStore.normalize(rawPassword);
        if (password === "") {
            this.showError("Vui long nhap khoa truy cap");
            return;
        }

        this.setLoading(true, "Dang dang nhap...");
        this.hideError();
        this.showStatus("Dang xac thuc voi may chu...");

        const loginResult = await this.authApi.login(password);
        if (!loginResult.ok) {
            this.setLoading(false, "Dang nhap");
            this.hideStatus();
            this.showError(loginResult.message || "Dang nhap that bai");
            return;
        }

        this.keyStore.set(password);

        // Re-verify by GET so policy errors (HTTPS/IP/strict mode) are shown immediately.
        const verifyResult = await this.authApi.verify(password);
        if (verifyResult.ok) {
            this.redirectToDashboard();
            return;
        }

        if (verifyResult.status === 401) {
            this.keyStore.clear();
        }

        this.setLoading(false, "Dang nhap");
        this.hideStatus();
        this.showError(verifyResult.message || "Dang nhap that bai");
    }

    setLoading(isLoading, label) {
        this.submitButton.disabled = isLoading;
        this.submitText.textContent = label;
    }

    showError(message) {
        const text = String(message || "Da xay ra loi");
        const swal = window.Swal;
        if (swal && typeof swal.fire === "function") {
            swal.fire({
                icon: "error",
                title: "Dang nhap that bai",
                text,
                confirmButtonText: "Dong",
            });
            return;
        }

        if (!this.errorMessage) {
            return;
        }

        this.errorMessage.textContent = text;
        this.errorMessage.classList.remove("hidden");
    }

    hideError() {
        if (!this.errorMessage) {
            return;
        }

        this.errorMessage.textContent = "";
        this.errorMessage.classList.add("hidden");
    }

    showStatus(message) {
        if (!this.statusMessage) {
            return;
        }

        this.statusMessage.textContent = String(message || "");
        this.statusMessage.classList.remove("hidden");
    }

    hideStatus() {
        if (!this.statusMessage) {
            return;
        }

        this.statusMessage.textContent = "";
        this.statusMessage.classList.add("hidden");
    }

    redirectToDashboard() {
        window.location.assign(this.redirectUrl);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const html = document.documentElement;
    const body = document.body;

    const baseUrl = String(html?.dataset.baseUrl || "").trim();
    const authEndpoint = String(body?.dataset.authEndpoint || "/api/admin/auth.php").trim();
    const adminHomePath = String(body?.dataset.adminHome || "/adminkaishop").trim();
    const storageKey = String(body?.dataset.storageKey || "kaimail_admin_access_key").trim();

    const keyStore = new AdminAccessKeyStore(storageKey);
    const authApi = new AdminAuthApiClient(baseUrl, authEndpoint);

    const controller = new AdminLoginPageController({
        form: document.getElementById("loginForm"),
        passwordInput: document.getElementById("passwordInput"),
        errorMessage: document.getElementById("errorMsg"),
        statusMessage: document.getElementById("loginStatus"),
        submitButton: document.getElementById("loginSubmitBtn"),
        submitText: document.getElementById("loginSubmitText"),
        redirectUrl: `${baseUrl}${adminHomePath}`,
        keyStore,
        authApi,
    });

    controller.init();
});
