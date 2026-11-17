const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Konfirmasi hapus memakai dialog, bukan window.confirm().
 * Pakai: <form data-confirm="Hapus program ini?">.
 */
function openConfirmDialog(form) {
    const dialog = document.getElementById('confirm-dialog');

    if (!dialog) {
        if (window.confirm(form.dataset.confirm)) {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        }

        return;
    }

    dialog.querySelector('[data-confirm-message]').textContent = form.dataset.confirm;
    dialog.returnValue = '';
    dialog.showModal();

    dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm') {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    }, { once: true });
}

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }

    if (form.dataset.confirm && form.dataset.confirmed !== 'true') {
        event.preventDefault();
        openConfirmDialog(form);

        return;
    }

    const button = event.submitter;

    if (button && button.classList.contains('btn')) {
        // Ditunda agar nilai tombol tetap ikut terkirim.
        setTimeout(() => {
            button.classList.add('is-loading');
            button.disabled = true;
        });
    }
});

/**
 * Pratinjau gambar sebelum diunggah.
 * Pakai: <input type="file" data-preview="id-img">.
 */
document.addEventListener('change', (event) => {
    const input = event.target;

    if (!(input instanceof HTMLInputElement) || !input.dataset.preview) {
        return;
    }

    const preview = document.getElementById(input.dataset.preview);
    const [file] = input.files ?? [];

    if (!preview || !file || !file.type.startsWith('image/')) {
        return;
    }

    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
    preview.closest('[data-preview-frame]')?.classList.remove('hidden');
});

/**
 * Angka naik perlahan dari 0. Pakai: <span data-count-up>42</span>.
 */
function runCountUps() {
    document.querySelectorAll('[data-count-up]:not([data-counted])').forEach((element) => {
        element.dataset.counted = 'true';

        const target = Number.parseInt(element.textContent.replace(/\D/g, ''), 10);

        if (!Number.isFinite(target) || target === 0 || prefersReducedMotion()) {
            return;
        }

        const duration = 800;
        const start = performance.now();
        const formatter = new Intl.NumberFormat('id-ID');

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const eased = 1 - (1 - progress) ** 3;

            element.textContent = formatter.format(Math.round(target * eased));

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    });
}

document.addEventListener('DOMContentLoaded', runCountUps);
document.addEventListener('livewire:navigated', runCountUps);

/**
 * Kalender rentang tanggal (tanpa jam). Dipakai oleh komponen <x-date-range>.
 */
const pad = (number) => String(number).padStart(2, '0');
const toIsoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
const parseIsoDate = (value) => {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
};

function dateRange({ start = '', end = '' } = {}) {
    const initial = start ? parseIsoDate(start) : new Date();

    return {
        start,
        end,
        hover: null,
        open: false,
        today: toIsoDate(new Date()),
        viewYear: initial.getFullYear(),
        viewMonth: initial.getMonth(),

        toggle() {
            this.open = !this.open;
        },

        clear() {
            this.start = '';
            this.end = '';
        },

        shiftMonth(step) {
            const date = new Date(this.viewYear, this.viewMonth + step, 1);
            this.viewYear = date.getFullYear();
            this.viewMonth = date.getMonth();
        },

        monthLabel() {
            return new Date(this.viewYear, this.viewMonth, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
        },

        cells() {
            const firstWeekday = (new Date(this.viewYear, this.viewMonth, 1).getDay() + 6) % 7;
            const totalDays = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const cells = Array.from({ length: firstWeekday }, (_, index) => ({ key: `blank-${index}`, day: '', date: null }));

            for (let day = 1; day <= totalDays; day++) {
                const date = toIsoDate(new Date(this.viewYear, this.viewMonth, day));
                cells.push({ key: date, day, date });
            }

            return cells;
        },

        pick(date) {
            if (!this.start || this.end || date < this.start) {
                this.start = date;
                this.end = '';

                return;
            }

            this.end = date;
            this.open = false;
        },

        isEdge(date) {
            return date !== null && (date === this.start || date === this.end);
        },

        isInRange(date) {
            if (!date || !this.start) {
                return false;
            }

            const rangeEnd = this.end || (this.open ? this.hover : null);

            return rangeEnd !== null && date > this.start && date < rangeEnd;
        },

        displayLabel() {
            const format = (value) => parseIsoDate(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });

            if (!this.start) {
                return '';
            }

            if (!this.end || this.end === this.start) {
                return format(this.start);
            }

            return `${format(this.start)} – ${format(this.end)}`;
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('dateRange', dateRange);
});

/**
 * Unggah banyak gambar dengan pratinjau. Dipakai oleh komponen <x-multi-image-upload>.
 * Gambar lama tetap di depan (urutan sama), gambar baru ditambahkan di belakang.
 */
function multiImageUpload({ existing = [], max = 10 } = {}) {
    return {
        existing,
        max,
        files: [],
        warning: '',

        total() {
            return this.existing.length + this.files.length;
        },

        previews() {
            return [
                ...this.existing.map((image) => ({ key: `existing-${image.path}`, url: image.url, existing: image })),
                ...this.files.map((item) => ({ key: item.key, url: item.url, file: item })),
            ];
        },

        add(event) {
            const selected = [...(event.target.files ?? [])].filter((file) => file.type.startsWith('image/'));
            const slots = this.max - this.existing.length - this.files.length;

            this.warning = selected.length > slots ? `Maksimal ${this.max} gambar. ${selected.length - slots} gambar tidak ditambahkan.` : '';

            selected.slice(0, Math.max(0, slots)).forEach((file) => {
                this.files.push({ key: `new-${Date.now()}-${Math.random()}`, url: URL.createObjectURL(file), file });
            });

            this.syncInput();
        },

        remove(image) {
            if (image.existing) {
                this.existing = this.existing.filter((item) => item !== image.existing);
            } else {
                URL.revokeObjectURL(image.url);
                this.files = this.files.filter((item) => item !== image.file);
                this.syncInput();
            }

            this.warning = '';
        },

        syncInput() {
            const transfer = new DataTransfer();
            this.files.forEach((item) => transfer.items.add(item.file));
            this.$refs.input.files = transfer.files;
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('multiImageUpload', multiImageUpload);
});
