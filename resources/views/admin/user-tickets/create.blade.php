@extends('admin.layout')
@section('title', 'تیکت جدید برای کاربر')
@section('header', 'ثبت تیکت برای کاربر')

@section('content')
<div style="max-width:680px;">

    <a href="{{ route('admin.user-tickets.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 transition-colors mb-5">
        <x-core-ui::icon name="chevron-left" class="w-4 h-4" />
        بازگشت به لیست تیکت‌ها
    </a>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
         x-data="{
             users: {{ json_encode($users->map(fn($u) => ['id'=>$u->id,'name'=>$u->name,'mobile'=>$u->mobile??'','email'=>$u->email??''])->values()) }},
             selected: null,
             search: '',
             open: false,
             get filtered() {
                 if (!this.search) return this.users.slice(0, 20);
                 const q = this.search.toLowerCase();
                 return this.users.filter(u =>
                     (u.name && u.name.toLowerCase().includes(q)) ||
                     (u.mobile && u.mobile.includes(q)) ||
                     (u.email && u.email.toLowerCase().includes(q))
                 ).slice(0, 20);
             },
             pick(u) { this.selected = u; this.open = false; this.search = u.name || u.mobile || ''; },
             clear() { this.selected = null; this.search = ''; }
         }">

        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#4338ca,#6366f1);padding:1.25rem 1.5rem;">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(255,255,255,0.2);">
                    <x-core-ui::icon name="edit" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <div class="text-white font-bold text-base">تیکت جدید از طرف ادمین</div>
                    <div class="text-indigo-200 text-sm">ثبت تیکت برای یک کاربر خاص</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.user-tickets.store') }}" class="p-6 space-y-5">
            @csrf

            {{-- User selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    کاربر
                    <span class="text-gray-400 font-normal text-xs">(اختیاری)</span>
                </label>

                <div class="relative" @click.outside="open = false">
                    {{-- Input --}}
                    <div class="flex items-center gap-2 px-3 py-2.5 border rounded-lg transition-colors cursor-text"
                         :class="open ? 'border-indigo-400 ring-2 ring-indigo-200' : 'border-gray-200 hover:border-gray-300'"
                         @click="open = true; $nextTick(() => $refs.searchInput.focus())">
                        <x-core-ui::icon name="user" class="w-4 h-4 text-gray-400 shrink-0" />
                        <input x-ref="searchInput" type="text" x-model="search"
                               @focus="open = true" @input="if(selected && search !== (selected.name || selected.mobile)) { selected = null; }"
                               class="flex-1 text-sm outline-none bg-transparent placeholder-gray-400"
                               placeholder="جستجو بر اساس نام، موبایل یا ایمیل...">
                        <button x-show="selected" type="button" @click.stop="clear()"
                                class="text-gray-400 hover:text-gray-600">
                            <x-core-ui::icon name="close" class="w-4 h-4" />
                        </button>
                    </div>

                    {{-- Dropdown --}}
                    <div x-show="open && filtered.length > 0"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         class="absolute z-50 w-full mt-1.5 bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden"
                         style="display:none; max-height:260px; overflow-y:auto;">
                        <template x-for="u in filtered" :key="u.id">
                            <div @click="pick(u)"
                                 class="flex items-center gap-3 px-4 py-2.5 hover:bg-indigo-50 cursor-pointer transition-colors border-b border-gray-50 last:border-0">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-bold shrink-0"
                                     x-text="(u.name || u.mobile || '?').charAt(0)"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-800 truncate" x-text="u.name || '—'"></div>
                                    <div class="text-xs text-gray-400 flex items-center gap-2 flex-wrap">
                                        <span x-show="u.mobile" x-text="u.mobile" dir="ltr"></span>
                                        <span x-show="u.mobile && u.email">·</span>
                                        <span x-show="u.email" x-text="u.email" class="truncate"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- No results --}}
                    <div x-show="open && search.length > 1 && filtered.length === 0"
                         class="absolute z-50 w-full mt-1.5 bg-white rounded-xl border border-gray-200 shadow-xl px-4 py-4 text-sm text-center text-gray-400"
                         style="display:none;">
                        کاربری یافت نشد
                    </div>
                </div>

                {{-- Selected user info bar --}}
                <div x-show="selected" x-transition
                     class="mt-2 flex items-center gap-3 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2.5"
                     style="display:none;">
                    <x-core-ui::icon name="check-circle" class="w-4 h-4 text-indigo-500 shrink-0" />
                    <div class="text-sm text-indigo-700">
                        <span x-text="selected?.name || '—'"></span>
                        <span x-show="selected?.mobile" class="text-indigo-500 text-xs mr-1.5" x-text="'· ' + selected?.mobile" dir="ltr"></span>
                    </div>
                </div>

                <input type="hidden" name="for_user_id" :value="selected?.id ?? ''">
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">موضوع <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="255"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 transition-colors"
                       placeholder="موضوع تیکت را بنویسید...">
            </div>

            {{-- Priority + Category --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">اولویت</label>
                    <select name="priority"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300">
                        <option value="normal" @selected(old('priority','normal')==='normal')>عادی</option>
                        <option value="high"   @selected(old('priority')==='high')>بالا (فوری)</option>
                        <option value="low"    @selected(old('priority')==='low')>کم</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">دسته‌بندی</label>
                    <select name="category"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300">
                        <option value="">—</option>
                        <option value="technical" @selected(old('category')==='technical')>فنی</option>
                        <option value="billing"   @selected(old('category')==='billing')>مالی</option>
                        <option value="product"   @selected(old('category')==='product')>محصول</option>
                        <option value="other"     @selected(old('category')==='other')>سایر</option>
                    </select>
                </div>
            </div>

            {{-- Message --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">متن تیکت <span class="text-red-500">*</span></label>
                <textarea name="description" rows="6" required
                          class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm resize-y focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 transition-colors"
                          style="min-height:140px;"
                          placeholder="متن پیام را بنویسید...">{{ old('description') }}</textarea>
            </div>

            {{-- SMS option (shown when user with mobile is selected) --}}
            <div x-show="selected?.mobile" x-transition style="display:none;">
                <div class="flex items-center gap-2.5 bg-amber-50 border border-amber-100 rounded-lg px-4 py-3">
                    <label class="flex items-center gap-2.5 cursor-pointer select-none text-sm text-amber-800">
                        <input type="checkbox" name="send_sms" value="1"
                               class="w-4 h-4 rounded border-amber-300 text-amber-600 focus:ring-amber-400 cursor-pointer">
                        <span>ارسال پیامک اطلاع‌رسانی به</span>
                        <span class="font-semibold" x-text="selected?.mobile" dir="ltr"></span>
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <x-core-ui::icon name="arrow-right" class="w-4 h-4" />
                    ثبت تیکت
                </button>
                <a href="{{ route('admin.user-tickets.index') }}"
                   class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                    انصراف
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
