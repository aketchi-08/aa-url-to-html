@extends('layouts.app')

@section('content')
<h1 class="h3 mb-4 text-gray-800">ドメイン: {{ $domain->name }}</h1>

<a href="{{ route('urls.create') }}" class="btn btn-primary mb-3">
    <i class="fas fa-plus"></i> 新規URL追加
</a>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>URL</th>
                        <th>HTMLファイル</th>
                        <th>登録者</th>
                        <th>登録日</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($urls as $url)
                        <tr>
                            <td>{{ $url->id }}</td>
                            <td><a href="{{ $url->url }}" target="_blank">{{ $url->url }}</a></td>
                            <td>
                                @if($url->html_path)
                                    <a href="{{ route('urls.show', $url->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-file-alt"></i> 表示
                                    </a>
                                @else
                                    <span class="text-muted">未保存</span>
                                @endif
                            </td>
                            <td>{{ $url->user->name ?? '-' }}</td>
                            <td>{{ $url->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">登録されたURLはありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $urls->links() }}
        </div>
    </div>
</div>
@endsection
