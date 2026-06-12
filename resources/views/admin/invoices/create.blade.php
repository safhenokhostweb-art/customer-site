@extends('admin.layout')
@section('title', 'ظپط§ع©طھظˆط± ط¬ط¯غŒط¯')
@section('header', 'ظپط§ع©طھظˆط± ط¬ط¯غŒط¯')
@section('content')
<div class="admin-page max-w-3xl">
    <div class="card">
        <div class="card-header">ط§ط·ظ„ط§ط¹ط§طھ ظپط§ع©طھظˆط±</div>
        <div class="card-body">
            <form action="{{ route('admin.invoices.store') }}" method="post" id="invoice-form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">ظ†ط§ظ… ظ…ط´طھط±غŒ *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">ظ…ظˆط¨ط§غŒظ„ *</label>
                        <input type="text" name="customer_mobile" value="{{ old('customer_mobile') }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">طھظ„ظپظ†</label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="غ°غ¹غ±غ²غ³غ´غµغ¶غ·غ¸غ¹">
                </div>
                <hr class="my-6 border-slate-200">
                <h3 class="font-semibold mb-4">ط¢غŒطھظ…â€Œظ‡ط§غŒ ظپط§ع©طھظˆط±</h3>
                <div id="items" class="space-y-4">
                    <div class="item-row grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="form-label">ط¹ظ†ظˆط§ظ†</label>
                            <input type="text" name="items[0][title]" placeholder="ط¹ظ†ظˆط§ظ† ط¢غŒطھظ…" required>
                        </div>
                        <div class="md:col-span-3">
                            <label class="form-label">ظ‚غŒظ…طھ (طھظˆظ…ط§ظ†)</label>
                            <input type="number" name="items[0][price]" placeholder="غ°" min="0" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="form-label">طھط¹ط¯ط§ط¯</label>
                            <input type="number" name="items[0][quantity]" min="1" value="1" required>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary mt-4" onclick="addItem()">
                    <x-core-ui::icon name="invoice" class="w- />
                    ط§ظپط²ظˆط¯ظ† ط¢غŒطھظ…
                </button>
                <hr class="my-6 border-slate-200">
                <div class="admin-page-tools">
                    <button type="submit" class="btn btn-primary">ط°ط®غŒط±ظ‡ ظپط§ع©طھظˆط±</button>
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">ط§ظ†طµط±ط§ظپ</a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
let idx = 1;
function addItem() {
    const div = document.createElement('div');
    div.className = 'item-row grid grid-cols-1 md:grid-cols-12 gap-3 items-end';
    div.innerHTML = '<div class="md:col-span-5"><label class="form-label">ط¹ظ†ظˆط§ظ†</label><input type="text" name="items['+idx+'][title]" placeholder="ط¹ظ†ظˆط§ظ† ط¢غŒطھظ…" required></div><div class="md:col-span-3"><label class="form-label">ظ‚غŒظ…طھ (طھظˆظ…ط§ظ†)</label><input type="number" name="items['+idx+'][price]" placeholder="غ°" min="0" required></div><div class="md:col-span-2"><label class="form-label">طھط¹ط¯ط§ط¯</label><input type="number" name="items['+idx+'][quantity]" min="1" value="1" required></div>';
    document.getElementById('items').appendChild(div);
    idx++;
}
</script>
@endsection

