/**
 * KaiMail - Admin JavaScript
 * Common functions for admin pages
 */

// ============================================
// Toast Notifications
// ============================================
function showToast(message, type = '') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    toast.textContent = message;
    toast.className = `toast show ${type}`;

    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}

// ============================================
// Escape HTML
// ============================================
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ============================================
// Copy to Clipboard
// ============================================
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('✓ Đã copy!', 'success');
    }).catch(() => {
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showToast('✓ Đã copy!', 'success');
    });
}

// ============================================
// Logout Handler
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await fetch('../api/admin/auth.php', { method: 'DELETE' });
                window.location.href = '/adminkaishop/login';
            } catch (error) {
                window.location.href = '/adminkaishop/login';
            }
        });
    }

    // Create Email Modal
    const createEmailBtn = document.getElementById('createEmailBtn');
    if (createEmailBtn) {
        createEmailBtn.addEventListener('click', () => {
            document.getElementById('createModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }

    // Modal backdrop close
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', function () {
            this.closest('.modal').classList.add('hidden');
            document.body.style.overflow = '';
        });
    });

    // Create Email Form
    const createEmailForm = document.getElementById('createEmailForm');
    if (createEmailForm) {
        // Toggle custom email input
        const nameTypeRadios = createEmailForm.querySelectorAll('input[name="name_type"]');
        const customEmailGroup = document.getElementById('customEmailGroup');

        nameTypeRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'custom' && radio.checked) {
                    customEmailGroup.classList.remove('hidden');
                } else {
                    customEmailGroup.classList.add('hidden');
                }
            });
        });

        createEmailForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const nameType = formData.get('name_type');
            const expiryType = formData.get('expiry_type');
            const customEmail = document.getElementById('customEmail')?.value?.trim() || '';

            const btn = e.target.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Đang tạo...';

            try {
                // Get selected domain from dropdown
                const domainSelect = document.getElementById('domainSelect');
                const selectedDomain = domainSelect ? domainSelect.value : '';

                const body = {
                    name_type: nameType,
                    expiry_type: expiryType,
                    domain: selectedDomain
                };

                if (nameType === 'custom') {
                    if (!customEmail) {
                        showToast('Vui lòng nhập email', 'error');
                        return;
                    }
                    body.email = customEmail.toLowerCase();
                }

                // Validation: check domain is selected
                if (!selectedDomain) {
                    showToast('Vui lòng chọn domain', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Tạo Email';
                    return;
                }

                const response = await fetch('../api/admin/emails.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });

                const data = await response.json();

                if (response.ok) {
                    // Handle bulk creation response
                    if (data.created > 0) {
                        const emailList = data.emails.map(e => e.email).join(', ');
                        const message = data.created === 1
                            ? `✓ Email created: ${emailList}`
                            : `✓ Created ${data.created} emails`;
                        showToast(message, 'success');

                    } else if (data.errors && data.errors.length > 0) {
                        showToast(`❌ ${data.errors[0]}`, 'error');
                    }

                    closeCreateModal();

                    // Reload if on emails page
                    if (typeof loadEmails === 'function') {
                        loadEmails();
                    }
                    // Reload stats if on dashboard
                    if (typeof loadStats === 'function') {
                        loadStats();
                    }
                } else {
                    showToast(data.error || 'Không thể tạo email', 'error');
                }

            } catch (error) {
                showToast('Lỗi kết nối server', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Tạo Email';
            }
        });
    }
});

function closeCreateModal() {
    const modal = document.getElementById('createModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';

        // Reset form
        const form = document.getElementById('createEmailForm');
        if (form) form.reset();

        const customGroup = document.getElementById('customEmailGroup');
        if (customGroup) customGroup.classList.add('hidden');
    }
}
// ============================================
// Time Formatting Utilities (GMT+7)
// ============================================

/**
 * Parse date string and convert to GMT+7
 */
function parseToGMT7(dateStr) {
    const date = new Date(dateStr);
    // Convert to GMT+7 by adjusting for timezone offset
    const utc = date.getTime() + (date.getTimezoneOffset() * 60000);
    const gmt7 = new Date(utc + (3600000 * 7));
    return gmt7;
}

/**
 * Get current time in GMT+7
 */
function getCurrentGMT7() {
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    return new Date(utc + (3600000 * 7));
}

/**
 * Format time smartly (relative for recent, absolute for older)
 */
function formatTimeVN(dateStr) {
    const date = parseToGMT7(dateStr);
    const now = getCurrentGMT7();
    const diff = now - date;
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Vừa xong';
    if (minutes < 60) return `${minutes} phút trước`;
    if (hours < 24) return `${hours} giờ trước`;
    if (days < 7) return `${days} ngày trước`;

    // Older than 7 days: show full date
    return formatDateVN(dateStr);
}

/**
 * Format date only (DD/MM/YYYY)
 */
function formatDateVN(dateStr) {
    const date = parseToGMT7(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

/**
 * Format full datetime (DD/MM/YYYY HH:mm)
 */
function formatDateTimeVN(dateStr) {
    const date = parseToGMT7(dateStr);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * Format time only (HH:mm)
 */
function formatTimeOnlyVN(dateStr) {
    const date = parseToGMT7(dateStr);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

// ============================================
// Email Formatting Utilities
// ============================================

/**
 * Clean up email display - remove bounce prefixes and technical parts
 */
function cleanEmail(email, name = null) {
    // If name exists and is not empty, use it
    if (name && name.trim() && !name.includes('@')) {
        return name.trim();
    }

    // Clean up email address
    if (!email) return '';

    // Remove bounce prefix: bounces+20216706-9e85-hoatrinh791=kaishop.id.vn@em7877.tm.openai.com
    // Extract the actual recipient: hoatrinh791@kaishop.id.vn
    if (email.includes('bounces+') && email.includes('=')) {
        const match = email.match(/([^=]+)=([^@]+)@/);
        if (match) {
            return match[1] + '@' + match[2];
        }
    }

    // Remove long technical domains, keep only username
    if (email.includes('@em') || email.includes('@tm.') || email.includes('.openai.com')) {
        const username = email.split('@')[0];
        // If username is too long or technical, shorten it
        if (username.length > 30 || username.includes('+')) {
            return username.split('+')[0] + '@...';
        }
        return username;
    }

    return email;
}
