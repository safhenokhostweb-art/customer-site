@extends('admin.layout')
@section('title', 'فاکتورها')
@section('header', 'فاکتورهای آنلاین')
@section('content')
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <p class="text-slate-600 text-sm">مدیریت فاکتورها و لینک پرداخت آنلاین.</p>
    <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        فاکتور جدید
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>شماره</th>
                    <th>مشتری</th>
                    <th>مبلغ</th>
                    <th>وضعیت</th>
                    <th style="width:180px">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $i)
                    <tr>
                        <td><code class="px-2 py-1 bg-slate-100 rounded text-sm font-mono">{{ $i->invoice_number }}</code></td>
                        <td>{{ $i->customer_name }}</td>
                        <td class="font-semibold">{{ number_format($i->total) }} تومان</td>
                        <td>
                            @if($i->status === 'paid')
                                <span class="badge badge-success">پرداخت شده</span>
                            @elseif($i->status === 'cancelled')
                                <span class="badge badge-danger">لغو</span>
                            @else
                                <span class="badge badge-warning">در انتظار</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.invoices.show', $i) }}" class="btn btn-primary btn-sm">مشاهده</a>
                                <form method="post" action="{{ route('admin.invoices.destroy', $i) }}" class="inline" onsubmit="return confirm('حذف این فاکتور؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-500">هنوز فاکتوری ثبت نشده است. <a href="{{ route('admin.invoices.create') }}" class="text-indigo-600 hover:underline">اولین فاکتور را ایجاد کنید</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($invoices->hasPages())
    <div class="mt-4">{{ $invoices->links() }}</div>
@endif
@endsection
