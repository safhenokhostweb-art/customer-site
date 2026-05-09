@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof tinymce === 'undefined') return;
    var el = document.getElementById('post-body-editor');
    if (!el) return;
    tinymce.init({
        selector: '#post-body-editor',
        height: 480,
        menubar: false,
        directionality: 'rtl',
        plugins: 'link lists image autoresize code',
        toolbar: 'undo redo | blocks | bold italic underline | alignright aligncenter alignleft | bullist numlist | link image | code',
        relative_urls: false,
        convert_urls: true,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var fd = new FormData();
                fd.append('file', blobInfo.blob(), blobInfo.filename());
                fd.append('context', 'featured');
                fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fetch('{{ route('admin.image.upload') }}', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (res) {
                    if (!res.ok || !res.data.path) {
                        reject(res.data && res.data.message ? res.data.message : 'آپلود ناموفق');
                        return;
                    }
                    var url = res.data.url || res.data.path;
                    resolve(url);
                }).catch(function () { reject('خطا در آپلود'); });
            });
        }
    });
});
</script>
@endpush
