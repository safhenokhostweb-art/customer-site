{{-- ─── Canvas Theme Import Panel ──────────────────────────────────────────── --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
     x-data="{ dragging: false, fileName: null }">

  <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3">
    <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0">
      <x-core-ui::icon name="upload" class="w-5 h-5 text-emerald-600" />
    </div>
    <div>
      <h3 class="text-sm font-bold text-gray-800">وارد کردن قالب از فایل JSON</h3>
      <p class="text-xs text-gray-500 mt-0.5">فایل JSON صادرشده از همین سیستم را آپلود کنید</p>
    </div>
  </div>

  <div class="p-6">
    <form method="POST"
          action="{{ route('admin.theme-design.canvas-import') }}"
          enctype="multipart/form-data"
          x-on:submit="if(!fileName){ $event.preventDefault(); alert('لطفاً یک فایل JSON انتخاب کنید.'); }">
      @csrf

      {{-- Drop zone --}}
      <label
        class="relative flex flex-col items-center justify-center w-full h-36 border-2 rounded-xl cursor-pointer transition"
        x-bind:class="dragging
          ? 'border-emerald-500 bg-emerald-50'
          : (fileName ? 'border-emerald-400 bg-emerald-50/50' : 'border-dashed border-gray-300 bg-gray-50 hover:bg-gray-100')"
        x-on:dragover.prevent="dragging = true"
        x-on:dragleave.prevent="dragging = false"
        x-on:drop.prevent="dragging = false; const f = $event.dataTransfer.files[0]; if(f){ fileName = f.name; $refs.fileInput.files = $event.dataTransfer.files; }">

        <input type="file"
               name="theme_file"
               accept=".json,application/json"
               class="sr-only"
               x-ref="fileInput"
               x-on:change="fileName = $event.target.files[0]?.name || null">

        <template x-if="!fileName">
          <div class="text-center pointer-events-none">
            <x-core-ui::icon name="upload" class="w-8 h-8 text-gray-400 mx-auto mb-2" />
            <p class="text-sm font-medium text-gray-600">فایل JSON را اینجا بکشید یا کلیک کنید</p>
            <p class="text-xs text-gray-400 mt-1">حداکثر ۲ مگابایت</p>
          </div>
        </template>

        <template x-if="fileName">
          <div class="text-center pointer-events-none">
            <x-core-ui::icon name="document-text" class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
            <p class="text-sm font-semibold text-emerald-700" x-text="fileName"></p>
            <p class="text-xs text-gray-500 mt-1">برای تغییر، کلیک کنید</p>
          </div>
        </template>
      </label>

      @error('theme_file')
        <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
          <x-core-ui::icon name="x-circle" class="w-4 h-4 shrink-0" />
          {{ $message }}
        </p>
      @enderror

      <div class="mt-4 flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition">
          <x-core-ui::icon name="upload" class="w-4 h-4" />
          نصب قالب از فایل
        </button>
        <p class="text-xs text-gray-400">قالب فعلی جایگزین می‌شود</p>
      </div>
    </form>
  </div>
</div>
