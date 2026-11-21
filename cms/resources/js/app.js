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
// Sama persis dengan macro Carbon "toIndonesianDate" di AppServiceProvider (mis. "5 Agu 2026").
const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const formatIndonesianDate = (date) => `${date.getDate()} ${MONTHS_SHORT[date.getMonth()]} ${date.getFullYear()}`;
const parseIsoDate = (value) => {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
};

function dateRange({ start = '', end = '', min = '', max = '' } = {}) {
    const initial = start ? parseIsoDate(start) : (min ? parseIsoDate(min) : new Date());

    return {
        start,
        end,
        min,
        max,
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

        /**
         * Batasi tanggal yang bisa dipilih (mis. periode program). Bila batas punya tanggal
         * mulai, rentang terpilih langsung diisi sama dengan batas tersebut; bila tanpa batas
         * sama sekali, rentang terpilih dikosongkan; selain itu tanggal terpilih yang berada
         * di luar batas digeser ke batas terdekat.
         */
        setBounds({ min = '', max = '' } = {}) {
            this.min = min || '';
            this.max = max || '';

            if (!this.min && !this.max) {
                this.clear();
            } else if (this.min) {
                this.start = this.min;
                this.end = this.max;
            }

            const clamp = (date) => {
                if (!date) {
                    return date;
                }

                if (this.min && date < this.min) {
                    return this.min;
                }

                return this.max && date > this.max ? this.max : date;
            };

            this.start = clamp(this.start);
            this.end = clamp(this.end);

            if (this.end && this.start && this.end < this.start) {
                this.end = '';
            }

            const focus = this.start || this.min;

            if (focus) {
                const date = parseIsoDate(focus);
                this.viewYear = date.getFullYear();
                this.viewMonth = date.getMonth();
            }
        },

        isOutOfBounds(date) {
            return date !== null && Boolean((this.min && date < this.min) || (this.max && date > this.max));
        },

        boundsLabel() {
            if (!this.min && !this.max) {
                return '';
            }

            const format = (value) => formatIndonesianDate(parseIsoDate(value));

            if (this.min && this.max) {
                return `Periode program: ${format(this.min)} – ${format(this.max)}`;
            }

            return this.min ? `Periode program: mulai ${format(this.min)}` : `Periode program: sampai ${format(this.max)}`;
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
            if (this.isOutOfBounds(date)) {
                return;
            }

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
            const format = (value) => formatIndonesianDate(parseIsoDate(value));

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
 * Zoom (tombol, scroll, cubit) dan geser (drag) gambar di dalam sebuah area.
 * Dipakai oleh komponen <x-image-upload zoomable>, baik di kotak pratinjau maupun layar penuh.
 * Butuh x-ref="stage" (area) dan x-ref="zoomImage" (gambar) di dalam komponen.
 *
 * wheelNeedsModifier: di kotak pratinjau, scroll biasa tetap menggulir halaman; zoom pakai
 * Ctrl/⌘ + scroll (cubit di trackpad juga terbaca sebagai Ctrl + scroll).
 */
function zoomPan({ wheelNeedsModifier = false, maxScale = 6 } = {}) {
    const pointers = new Map();
    let pinchDistance = 0;
    let lastPoint = null;

    return {
        src: '',
        scale: 1,
        x: 0,
        y: 0,

        transform() {
            return `translate(${this.x}px, ${this.y}px) scale(${this.scale})`;
        },

        reset() {
            this.scale = 1;
            this.x = 0;
            this.y = 0;
        },

        /**
         * Buka layar penuh (dipanggil dari event) dengan gambar dari kotak pratinjau.
         */
        show(src) {
            this.src = src;
            this.reset();
            this.$refs.dialog.showModal();
        },

        /**
         * Zoom ke arah titik (relatif terhadap tengah area) agar bagian yang dilihat tetap di tempat.
         */
        zoomBy(factor, originX = 0, originY = 0) {
            const nextScale = Math.min(maxScale, Math.max(1, this.scale * factor));
            const ratio = nextScale / this.scale;

            this.x = originX - (originX - this.x) * ratio;
            this.y = originY - (originY - this.y) * ratio;
            this.scale = nextScale;
            this.clampPan();
        },

        clampPan() {
            const image = this.$refs.zoomImage;
            const stage = this.$refs.stage;
            const maxX = Math.max(0, (image.offsetWidth * this.scale - stage.clientWidth) / 2);
            const maxY = Math.max(0, (image.offsetHeight * this.scale - stage.clientHeight) / 2);

            this.x = Math.min(maxX, Math.max(-maxX, this.x));
            this.y = Math.min(maxY, Math.max(-maxY, this.y));
        },

        pointFromCenter(clientX, clientY) {
            const rect = this.$refs.stage.getBoundingClientRect();

            return { x: clientX - rect.left - rect.width / 2, y: clientY - rect.top - rect.height / 2 };
        },

        onWheel(event) {
            if (wheelNeedsModifier && !event.ctrlKey && !event.metaKey) {
                return;
            }

            event.preventDefault();
            const point = this.pointFromCenter(event.clientX, event.clientY);
            this.zoomBy(Math.exp(-event.deltaY * 0.002), point.x, point.y);
        },

        onDoubleClick(event) {
            if (this.scale > 1) {
                this.reset();

                return;
            }

            const point = this.pointFromCenter(event.clientX, event.clientY);
            this.zoomBy(2.5, point.x, point.y);
        },

        onPointerDown(event) {
            if (event.button !== 0 || event.target.closest('button, a')) {
                return;
            }

            this.$refs.stage.setPointerCapture(event.pointerId);
            pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
            lastPoint = { x: event.clientX, y: event.clientY };

            if (pointers.size === 2) {
                const [first, second] = [...pointers.values()];
                pinchDistance = Math.hypot(first.x - second.x, first.y - second.y);
            }
        },

        onPointerMove(event) {
            if (!pointers.has(event.pointerId)) {
                return;
            }

            pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

            if (pointers.size === 2) {
                const [first, second] = [...pointers.values()];
                const distance = Math.hypot(first.x - second.x, first.y - second.y);
                const middle = this.pointFromCenter((first.x + second.x) / 2, (first.y + second.y) / 2);

                if (pinchDistance > 0) {
                    this.zoomBy(distance / pinchDistance, middle.x, middle.y);
                }

                pinchDistance = distance;

                return;
            }

            if (this.scale > 1 && lastPoint) {
                this.x += event.clientX - lastPoint.x;
                this.y += event.clientY - lastPoint.y;
                this.clampPan();
            }

            lastPoint = { x: event.clientX, y: event.clientY };
        },

        onPointerUp(event) {
            pointers.delete(event.pointerId);
            pinchDistance = 0;
            lastPoint = [...pointers.values()][0] ?? null;
        },
    };
}

/**
 * Field unggah gambar: zoom/geser pratinjau dan hapus foto (bila salah unggah).
 * Dipakai oleh komponen <x-image-upload zoomable removable>.
 * - File baru yang belum disimpan: dibatalkan, pratinjau kembali ke foto tersimpan.
 * - Foto tersimpan: ditandai untuk dihapus (remove_<name>=1) dan baru terhapus saat form disimpan.
 */
function imageField({ originalSrc = '' } = {}) {
    return {
        ...zoomPan({ wheelNeedsModifier: true }),
        originalSrc,
        hasImage: originalSrc !== '',
        hasNewFile: false,
        removed: false,

        fileChosen(event) {
            this.hasNewFile = (event.target.files ?? []).length > 0;
            this.hasImage = this.hasNewFile || this.originalSrc !== '';
            this.removed = false;
            this.reset();
        },

        removePhoto() {
            this.reset();

            if (this.hasNewFile) {
                this.$refs.input.value = '';
                this.hasNewFile = false;
                this.$refs.zoomImage.src = this.originalSrc;
                this.hasImage = this.originalSrc !== '';

                return;
            }

            this.removed = true;
        },

        undoRemove() {
            this.removed = false;
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('zoomPan', zoomPan);
    window.Alpine.data('imageField', imageField);
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
