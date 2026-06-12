@extends('admin.layout')
@section('title', 'ظپط§ع©طھظˆط±ظ‡ط§')
@section('header', 'ظپط§ع©طھظˆط±ظ‡ط§غŒ ط¢ظ†ظ„ط§غŒظ†')
@section('content')
<div class="admin-page">
    <div class="admin-page-head">
        <p class="text-slate-600 text-sm">ظ…ط¯غŒط±غŒطھ ظپط§ع©طھظˆط±ظ‡ط§ ظˆ ظ„غŒظ†ع© ظ¾ط±ط¯ط§ط®طھ ط¢ظ†ظ„ط§غŒظ†.</p>
        <div class="admin-page-tools">
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
                <x-core-ui::icon name="invoice" class="w- />
                ظپط§ع©طھظˆط± ط¬ط¯غŒط¯
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
            <thead>
                <tr>
                    <th>ط´ظ…ط§ط±ظ‡</th>
                    <th>ظ…ط´طھط±غŒ</th>
                    <th>ظ…ط¨ظ„ط؛</th>
                    <th>ظˆط¶ط¹غŒطھ</th>
                    <th style="width:180px">ط¹ظ…ظ„غŒط§طھ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $i)
                    <tr>
                        <td><code class="px-2 py-1 bg-slate-100 rounded text-sm font-mono">{{ $i->invoice_number }}</code></td>
                        <td>{{ $i->customer_name }}</td>
                        <td class="font-semibold">{{ number_format($i->total) }} طھظˆظ…ط§ظ†</td>
                        <td>
                            @if($i->status === 'paid')
                                <span class="badge badge-success">ظ¾ط±ط¯ط§ط®طھ ط´ط¯ظ‡</span>
                            @elseif($i->status === 'cancelled')
                                <span class="badge badge-danger">ظ„ط؛ظˆ</span>
                            @else
                                <span class="badge badge-warning">ط¯ط± ط§ظ†طھط¸ط§ط±</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.invoices.show', $i) }}" class="btn btn-primary btn-sm">ظ…ط´ط§ظ‡ط¯ظ‡</a>
                                <form method="post" action="{{ route('admin.invoices.destroy', $i) }}" class="inline" onsubmit="return confirm('ط­ط°ظپ ط§غŒظ† ظپط§ع©طھظˆط±طں');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">ط­ط°ظپ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="admin-empty">ظ‡ظ†ظˆط² ظپط§ع©طھظˆط±غŒ ط«ط¨طھ ظ†ط´ط¯ظ‡ ط§ط³طھ. <a href="{{ route('admin.invoices.create') }}" class="text-indigo-600 hover:underline">ط§ظˆظ„غŒظ† ظپط§ع©طھظˆط± ط±ط§ ط§غŒط¬ط§ط¯ ع©ظ†غŒط¯</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @if($invoices->hasPages())
        <div class="admin-pagination">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection

