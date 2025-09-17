@extends('layouts.app')

@section('content')
    <!-- Page Heading -->
    <h1 class="h3 mb-4 text-gray-800">URL管理</h1>

    <!-- フラッシュメッセージ -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <!-- 登録ボタン -->
    <div class="mb-3">
        <a href="{{ route('urls.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新規URL追加
        </a>
    </div>

    <!-- URL一覧テーブル -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">登録済みURL一覧</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>URL</th>
                            <th>HTMLファイル</th>
                            <th>登録ユーザー</th>
                            <th>登録日</th>
                            <th>操作</th>
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
                                <td>
                                    <a href="{{ route('urls.edit', $url->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('urls.destroy', $url->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('削除しますか？')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <!-- リロードボタン -->
                                    <form action="{{ route('urls.reload', $url->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-secondary" title="再生成">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">登録されたURLはありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
