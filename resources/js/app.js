import { formatQty } from './quantity';
import cookMode from './cook-mode';

// Livewire starts Alpine on DOMContentLoaded; this module runs before that,
// so the components below are registered in time.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('recipeDetail', ({ base, items }) => ({
        base: Math.max(1, base || 1),
        porsi: Math.max(1, base || 1),
        items,
        checked: [],

        amount(index) {
            const item = this.items[index];
            if (item.q === null || item.q === undefined) return item.unit || 'Secukupnya';
            return `${formatQty((item.q * this.porsi) / this.base)} ${item.unit || ''}`.trim();
        },

        readyPercent() {
            return this.items.length ? Math.round((this.checked.length / this.items.length) * 100) : 0;
        },

        dec() {
            if (this.porsi > 1) this.porsi--;
        },

        inc() {
            if (this.porsi < 50) this.porsi++;
        },
    }));

    window.Alpine.data('cookMode', cookMode);
});
