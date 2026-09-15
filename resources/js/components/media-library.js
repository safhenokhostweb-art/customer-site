/**
 * Media Library 2.0 — Alpine.js component for the canonical customer media
 * admin surface (کتابخانه رسانه). Every mutation goes through the existing
 * canonical HTTP endpoints (admin.media.*), which delegate 100% to
 * CustomerMediaService — this component never talks to storage/DB directly.
 */
export default function mediaLibrary(config) {
    return {
        // Library state
        items: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 24 },
        loading: false,
        view: 'grid',

        // Filters
        q: '',
        category: '',
        usage: '',
        sort: 'newest',
        page: 1,

        // Selection (for batch delete)
        selected: [],

        // Upload queue
        uploads: [],
        dragOver: false,

        // Details modal
        detail: null,
        detailLoading: false,
        editingMetadata: false,
        metadataForm: { title: '', alt_text: '', caption: '', description: '' },
        replacing: false,
        imageEditing: false,
        cropBox: null,
        cropDragStart: null,

        endpoints: {},
        labels: {},
        _fetchSeq: 0,

        init() {
            this.endpoints = config.endpoints;
            this.labels = config.labels || {};
            this.view = localStorage.getItem('media-library-view') || 'grid';
            this.fetchItems();

            this.$watch('q', () => this.debouncedFetch());
            this.$watch('category', () => this.fetchItems(1));
            this.$watch('usage', () => this.fetchItems(1));
            this.$watch('sort', () => this.fetchItems(1));
        },

        setView(view) {
            this.view = view;
            localStorage.setItem('media-library-view', view);
        },

        debouncedFetch() {
            clearTimeout(this._debounce);
            this._debounce = setTimeout(() => this.fetchItems(1), 350);
        },

        csrfHeader() {
            const token = document.querySelector('meta[name="csrf-token"]');
            return token ? { 'X-CSRF-TOKEN': token.content } : {};
        },

        /**
         * `init()` fires an unfiltered fetch immediately, and a user can
         * type into the search box before that first request even returns.
         * Network/server timing is not guaranteed to preserve request
         * order, so without a sequence guard a slow, STALE (e.g. the
         * original unfiltered) response arriving after a newer, filtered
         * one would silently clobber the correct, already-rendered result.
         * Only the response to the MOST RECENTLY issued fetchItems() call
         * is ever applied.
         */
        async fetchItems(page = null) {
            if (page) this.page = page;
            this.loading = true;
            const requestId = ++this._fetchSeq;
            try {
                const params = new URLSearchParams({
                    page: String(this.page),
                    sort: this.sort,
                });
                if (this.q) params.set('q', this.q);
                if (this.category) params.set('category', this.category);
                if (this.usage) params.set('usage', this.usage);

                const response = await fetch(`${this.endpoints.index}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) throw new Error('fetch-failed');
                const data = await response.json();
                if (requestId !== this._fetchSeq) return; // superseded by a newer request
                this.items = data.items;
                this.pagination = data.pagination;
                // Selection deliberately survives a search/filter/sort/page
                // change — a user narrowing the list while building up a
                // batch-delete set (across searches or pages) should not
                // silently lose earlier picks. It's cleared explicitly after
                // a successful batchDelete() instead.
            } catch (e) {
                if (requestId === this._fetchSeq) this.items = [];
            } finally {
                if (requestId === this._fetchSeq) this.loading = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchItems(page);
        },

        // ---------- Upload ----------

        onFileInput(event) {
            this.queueFiles(Array.from(event.target.files || []));
            event.target.value = '';
        },

        onDrop(event) {
            event.preventDefault();
            this.dragOver = false;
            this.queueFiles(Array.from(event.dataTransfer.files || []));
        },

        queueFiles(files) {
            files.forEach((file) => {
                const id = `${Date.now()}-${Math.random().toString(36).slice(2)}`;
                this.uploads.unshift({ id, name: file.name, status: 'QUEUED', progress: 0, message: '' });
                this.uploadOne(file, id);
            });
        },

        /**
         * Alpine/Vue-reactivity gotcha: `entry` is a plain object captured
         * BEFORE it's inserted into the reactive `uploads` array. Mutating
         * that raw reference later (from XHR callbacks) changes the
         * underlying data but does not go through the reactive array's own
         * proxy, so no re-render is scheduled — the DOM would silently
         * freeze at whatever status last rendered "for free" (e.g. via an
         * unrelated re-render). Always look the row up by id and replace it
         * THROUGH `this.uploads`, so every status/progress change is a real
         * reactive write.
         */
        updateUpload(id, patch) {
            const idx = this.uploads.findIndex((u) => u.id === id);
            if (idx !== -1) {
                this.uploads[idx] = { ...this.uploads[idx], ...patch };
            }
        },

        uploadOne(file, id) {
            this.updateUpload(id, { status: 'UPLOADING' });
            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.endpoints.store);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            const csrf = this.csrfHeader()['X-CSRF-TOKEN'];
            if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf);

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.updateUpload(id, { progress: Math.round((e.loaded / e.total) * 100) });
                }
            });
            xhr.addEventListener('load', () => {
                try {
                    const data = JSON.parse(xhr.responseText);
                    const result = (data.results || [])[0];
                    if (xhr.status >= 200 && xhr.status < 300 && result && result.status === 'success') {
                        this.updateUpload(id, { progress: 100, status: 'SUCCESS' });
                        this.fetchItems(1);
                    } else {
                        this.updateUpload(id, { progress: 100, status: 'FAILED', message: (result && result.message) || this.labels.uploadFailed || 'Upload failed' });
                    }
                } catch (e) {
                    this.updateUpload(id, { progress: 100, status: 'FAILED', message: this.labels.uploadFailed || 'Upload failed' });
                }
            });
            xhr.addEventListener('error', () => {
                this.updateUpload(id, { status: 'FAILED', message: this.labels.uploadFailed || 'Upload failed' });
            });

            const formData = new FormData();
            formData.append('files[]', file);
            xhr.send(formData);
        },

        dismissUpload(id) {
            this.uploads = this.uploads.filter((u) => u.id !== id);
        },

        // ---------- Selection / batch delete ----------

        toggleSelect(uuid) {
            const idx = this.selected.indexOf(uuid);
            if (idx === -1) this.selected.push(uuid);
            else this.selected.splice(idx, 1);
        },

        isSelected(uuid) {
            return this.selected.includes(uuid);
        },

        async batchDelete() {
            if (this.selected.length === 0) return;
            const response = await fetch(this.endpoints.batchDestroy, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...this.csrfHeader() },
                body: JSON.stringify({ uuids: this.selected }),
            });
            const data = await response.json();
            const blocked = (data.results || []).filter((r) => r.status === 'blocked').length;
            this.selected = [];
            this.fetchItems();
            if (blocked > 0) {
                window.alert(this.labels.someBlocked || `${blocked} item(s) are in use and were not deleted.`);
            }
        },

        // ---------- Details modal ----------

        async openDetails(uuid) {
            this.detailLoading = true;
            this.detail = null;
            this.editingMetadata = false;
            this.imageEditing = false;
            this.cropBox = null;
            try {
                const response = await fetch(this.endpoints.show.replace('__UUID__', uuid), {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();
                this.detail = data.item;
                this.detail.usages = data.usages;
                this.metadataForm = {
                    title: this.detail.title || '',
                    alt_text: this.detail.alt_text || '',
                    caption: this.detail.caption || '',
                    description: this.detail.description || '',
                };
            } finally {
                this.detailLoading = false;
            }
        },

        closeDetails() {
            this.detail = null;
        },

        async saveMetadata() {
            const response = await fetch(this.endpoints.update.replace('__UUID__', this.detail.uuid), {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...this.csrfHeader() },
                body: JSON.stringify(this.metadataForm),
            });
            if (response.ok) {
                const data = await response.json();
                this.detail = { ...this.detail, ...data.item };
                this.editingMetadata = false;
                this.fetchItems();
            }
        },

        async deleteCurrent() {
            const response = await fetch(this.endpoints.destroy.replace('__UUID__', this.detail.uuid), {
                method: 'DELETE',
                headers: { Accept: 'application/json', ...this.csrfHeader() },
            });
            if (response.ok) {
                this.closeDetails();
                this.fetchItems();
            } else {
                const data = await response.json().catch(() => ({}));
                window.alert(data.message || this.labels.deleteBlocked || 'This item is in use and cannot be deleted.');
            }
        },

        // ---------- Replace file ----------

        onReplaceFile(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file) return;
            this.replacing = true;
            const formData = new FormData();
            formData.append('file', file);
            fetch(this.endpoints.replace.replace('__UUID__', this.detail.uuid), {
                method: 'POST',
                headers: { Accept: 'application/json', ...this.csrfHeader() },
                body: formData,
            })
                .then(async (response) => {
                    const data = await response.json();
                    if (!response.ok) {
                        window.alert(data.message || this.labels.replaceFailed || 'Replacement failed.');
                        return;
                    }
                    this.detail = { ...this.detail, ...data.item };
                    this.fetchItems();
                })
                .finally(() => {
                    this.replacing = false;
                });
        },

        // ---------- Image editing ----------

        async rotateImage(degrees) {
            await this.applyImageEdit('rotate', { degrees });
        },

        async applyImageEdit(operation, params) {
            this.imageEditing = true;
            try {
                const response = await fetch(this.endpoints.editImage.replace('__UUID__', this.detail.uuid), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...this.csrfHeader() },
                    body: JSON.stringify({ operation, ...params }),
                });
                const data = await response.json();
                if (!response.ok) {
                    window.alert(data.message || this.labels.editFailed || 'Image edit failed.');
                    return;
                }
                this.detail = { ...this.detail, ...data.item };
                this.cropBox = null;
                this.fetchItems();
            } finally {
                this.imageEditing = false;
            }
        },

        startCrop(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            this.cropDragStart = { x: event.clientX - rect.left, y: event.clientY - rect.top, rect };
            this.cropBox = { left: this.cropDragStart.x, top: this.cropDragStart.y, width: 0, height: 0 };
        },

        dragCrop(event) {
            if (!this.cropDragStart) return;
            const rect = this.cropDragStart.rect;
            const x = Math.min(Math.max(event.clientX - rect.left, 0), rect.width);
            const y = Math.min(Math.max(event.clientY - rect.top, 0), rect.height);
            this.cropBox = {
                left: Math.min(x, this.cropDragStart.x),
                top: Math.min(y, this.cropDragStart.y),
                width: Math.abs(x - this.cropDragStart.x),
                height: Math.abs(y - this.cropDragStart.y),
            };
        },

        endCrop() {
            this.cropDragStart = null;
        },

        async applyCrop(imgEl) {
            if (!this.cropBox || this.cropBox.width < 4 || this.cropBox.height < 4) return;
            const scaleX = this.detail.width / imgEl.clientWidth;
            const scaleY = this.detail.height / imgEl.clientHeight;
            await this.applyImageEdit('crop', {
                x: Math.round(this.cropBox.left * scaleX),
                y: Math.round(this.cropBox.top * scaleY),
                width: Math.round(this.cropBox.width * scaleX),
                height: Math.round(this.cropBox.height * scaleY),
            });
        },

        // ---------- Formatting helpers ----------

        formatSize(bytes) {
            if (!bytes) return '';
            const units = ['B', 'KB', 'MB', 'GB'];
            let value = bytes;
            let unitIndex = 0;
            while (value >= 1024 && unitIndex < units.length - 1) {
                value /= 1024;
                unitIndex += 1;
            }
            return `${value.toFixed(unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
        },
    };
}
