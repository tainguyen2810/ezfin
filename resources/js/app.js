import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

window.Alpine = Alpine;
Alpine.plugin(focus);

// Thông báo dạng toast: window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } }))
Alpine.data('toasts', (initial = []) => ({
    items: [],
    init() {
        initial.forEach((toast) => this.add(toast));
    },
    add({ message, type = 'success' }) {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), 5000);
    },
    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },
}));

// Ô nhập tiền: hiển thị 1.000.000, gửi lên server số nguyên 1000000.
Alpine.data('moneyInput', (initial = '') => ({
    raw: String(initial ?? '').replace(/\D/g, ''),
    get display() {
        return this.raw === '' ? '' : Number(this.raw).toLocaleString('vi-VN');
    },
    update(event) {
        this.raw = event.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 15);
        event.target.value = this.display;
    },
}));

// Khay so sánh sản phẩm, giữ lựa chọn khi chuyển trang (tối đa 3).
Alpine.store('compare', {
    max: 3,
    items: JSON.parse(sessionStorageSafeGet('ezfin.compare') ?? '[]'),
    has(id) {
        return this.items.some((item) => item.id === id);
    },
    toggle(item) {
        if (this.has(item.id)) {
            this.items = this.items.filter((i) => i.id !== item.id);
        } else if (this.items.length < this.max) {
            this.items.push(item);
        } else {
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: `Chỉ so sánh tối đa ${this.max} sản phẩm.`, type: 'warning' } }));
        }
        sessionStorageSafeSet('ezfin.compare', JSON.stringify(this.items));
    },
    clear() {
        this.items = [];
        sessionStorageSafeSet('ezfin.compare', '[]');
    },
});

function sessionStorageSafeGet(key) {
    try {
        return window.sessionStorage.getItem(key);
    } catch {
        return null;
    }
}

function sessionStorageSafeSet(key, value) {
    try {
        window.sessionStorage.setItem(key, value);
    } catch {
        // Trình duyệt chặn lưu trữ: khay vẫn hoạt động trong trang hiện tại.
    }
}

Alpine.start();
