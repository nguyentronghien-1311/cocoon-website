/* Shared frontend client for the server-side Cocoon shopping cart. */
(function () {
    'use strict';

    async function request(path, options) {
        const response = await fetch(path, Object.assign({
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'Accept': 'application/json' }
        }, options || {}));
        let payload;
        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('Máy chủ trả về dữ liệu không hợp lệ. Vui lòng thử lại.');
        }
        if (!response.ok || !payload || payload.success !== true) {
            throw new Error((payload && payload.message) || 'Không thể xử lý giỏ hàng.');
        }
        return payload;
    }

    function readLegacyItems() {
        try {
            const raw = localStorage.getItem('cocoon_cart_items');
            const parsed = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(parsed)) return [];
            return parsed.map(function (item) {
                return { id: Number(item && item.id), quantity: Math.floor(Number(item && item.quantity) || 1) };
            }).filter(function (item) {
                return Number.isInteger(item.id) && item.id > 0 && Number.isInteger(item.quantity) && item.quantity > 0;
            }).slice(0, 50);
        } catch (error) {
            return [];
        }
    }

    async function migrateLegacy(csrfToken) {
        let alreadyMigrated = false;
        try { alreadyMigrated = localStorage.getItem('cocoon_cart_migrated') === '1'; } catch (error) {}
        if (alreadyMigrated) return null;

        let hasLegacyKey = false;
        try { hasLegacyKey = localStorage.getItem('cocoon_cart_items') !== null; } catch (error) {}
        if (!hasLegacyKey) return null;

        const items = readLegacyItems();
        const result = await request('cart_import.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken || '' },
            body: JSON.stringify({ items: items })
        });
        try {
            localStorage.removeItem('cocoon_cart_items');
            localStorage.removeItem('cocoon_cart_count');
            localStorage.setItem('cocoon_cart_migrated', '1');
        } catch (error) {}
        return result;
    }

    async function getCart() {
        const result = await request('cart_get.php');
        return result.data;
    }

    async function mutate(action, data, csrfToken) {
        const allowed = ['add', 'update', 'remove', 'clear'];
        if (!allowed.includes(action)) throw new Error('Thao tác giỏ hàng không hợp lệ.');
        return request('cart_' + action + '.php', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken || '' },
            body: JSON.stringify(data || {})
        });
    }

    async function updateCount(element, csrfToken) {
        try {
            await migrateLegacy(csrfToken);
        } catch (error) {
            // Leave a legacy cart intact if the one-time migration failed.
            console.warn('Không thể đồng bộ giỏ hàng cũ:', error.message);
        }
        const cart = await getCart();
        if (element) element.textContent = String(cart.total_items || 0);
        try { localStorage.removeItem('cocoon_cart_count'); } catch (error) {}
        return cart;
    }

    window.CocoonCart = {
        getCart: getCart,
        mutate: mutate,
        migrateLegacy: migrateLegacy,
        updateCount: updateCount,
        request: request
    };
})();
