{{-- Defines window.AdminTinyMCE.baseOptions(context, height) for admin editors --}}
<script>
(function () {
    window.AdminTinyMCE = window.AdminTinyMCE || {};
    var uploadRoute = @json(route('admin.image.upload'));

    window.AdminTinyMCE.baseOptions = function (uploadContext, height) {
        return {
            height: height || 400,
            menubar: false,
            branding: false,
            promotion: false,
            resize: true,
            directionality: 'rtl',
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'table', 'help', 'wordcount', 'autoresize'
            ].join(' '),
            toolbar: [
                'undo redo | blocks | bold italic underline strikethrough',
                'forecolor backcolor | alignright aligncenter alignleft',
                'bullist numlist | outdent indent',
                'link image table | hr removeformat',
                'searchreplace | code fullscreen'
            ].join(' | '),
            block_formats: 'پاراگراف=p; عنوان 2=h2; عنوان 3=h3; عنوان 4=h4; پیش‌فرمت=pre',
            relative_urls: false,
            remove_script_host: false,
            convert_urls: true,
            paste_merge_formats: true,
            content_style: 'body { font-family: inherit; font-size: 16px; line-height: 1.75; }',
            images_upload_handler: function (blobInfo) {
                return new Promise(function (resolve, reject) {
                    var fd = new FormData();
                    fd.append('file', blobInfo.blob(), blobInfo.filename());
                    fd.append('context', uploadContext);
                    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                    fetch(uploadRoute, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    }).then(function (r) {
                        return r.json().then(function (data) { return { ok: r.ok, data: data }; });
                    }).then(function (res) {
                        if (!res.ok || !res.data.path) {
                            reject(res.data && res.data.message ? res.data.message : 'آپلود ناموفق');
                            return;
                        }
                        resolve(res.data.url || res.data.path);
                    }).catch(function () {
                        reject('خطا در آپلود');
                    });
                });
            }
        };
    };
})();
</script>
