@extends('layouts.app')

@section('content')
<h1 class="h3 mb-4 text-gray-800">ドメイン管理</h1>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="mb-3">
    <a href="{{ route('domains.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> 新規ドメイン追加</a>
</div>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ドメイン名</th>
                        <th>URL数</th>
                        <th>登録者</th>
                        <th>登録日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($domains as $domain)
                        <tr>
                            <td>{{ $domain->id }}</td>
                            <td><a href="{{ route('domains.show', $domain->id) }}">{{ $domain->name }}</a></td>
                            <td>{{ $domain->urls_count }}</td>
                            <td>{{ $domain->user->name ?? '-' }}</td>
                            <td>{{ $domain->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('domains.edit', $domain->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('domains.destroy', $domain->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('削除しますか？')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">登録されたドメインはありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $domains->links() }}
        </div>
    </div>
</div>
@endsection
