@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-gray-800">Domain編集: {{ $domain->name }}</h1>

    <!-- Domain情報 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">Domain情報</div>
        <div class="card-body">
            <form method="POST" action="{{ route('domains.update', $domain->id) }}">
                @csrf
                @method('PUT')
                <input type="text" id="domain-name" class="form-control mb-2" value="{{ $domain->name }}">
                <button type="submit" class="btn btn-primary" id="update-domain">更新</button>
            </form>
        </div>
    </div>

    <!-- 抽出セレクタ -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">抽出セレクタ一覧</div>
        <div class="card-body">
            <table class="table table-bordered" id="extract-table">
                <thead>
                    <tr>
                        <th>セレクタ</th>
                        <th>マーク</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domain->extractSelectors as $sel)
                    <tr data-id="{{ $sel->id }}">
                        <td id="extract-selector-{{ $sel->id }}" contenteditable="true" class="selector">{{ $sel->selector }}</td>
                        <td id="extract-mark-{{ $sel->id }}" contenteditable="true" class="mark">{{ $sel->mark }}</td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm btn-update" data-target="{{ route('extract-selectors.update', $sel->id) }}" data-selector="extract-selector-{{ $sel->id }}" data-mark="extract-mark-{{ $sel->id }}">更新</button>
                            <button type="button" class="btn btn-danger btn-sm btn-delete" data-target="{{ route('extract-selectors.destroy', $sel->id) }}">削除</button>
                        </td>
                    </tr>
                    @endforeach
                    <tr>
                        <td id="extract-selector" contenteditable="true" class="selector"></td>
                        <td id="extract-mark" contenteditable="true" class="selector">content</td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm btn-store" data-target="{{ route('extract-selectors.store') }}" data-selector="extract-selector" data-mark="extract-mark">追加</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 削除セレクタ -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">削除セレクタ一覧</div>
        <div class="card-body">
            <table class="table table-bordered" id="remove-table">
                <thead><tr><th>セレクタ</th><th>操作</th></tr></thead>
                <tbody>
                    @foreach($domain->removeSelectors as $sel)
                    <tr data-id="{{ $sel->id }}">
                        <td id="remove-selector-{{ $sel->id }}" contenteditable="true" class="selector">{{ $sel->selector }}</td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm btn-update" data-target="{{ route('remove-selectors.update', $sel->id) }}" data-selector="remove-selector-{{ $sel->id }}">更新</button>
                            <button type="button" class="btn btn-danger btn-sm btn-delete" data-target="{{ route('remove-selectors.destroy', $sel->id) }}">削除</button>
                        </td>
                    </tr>
                    @endforeach
                    <tr>
                        <td id="remove-selector" contenteditable="true" class="selector"></td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm btn-store" data-target="{{ route('remove-selectors.store') }}" data-selector="remove-selector">追加</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="form-update-selector" method="POST">
    @csrf
    @method('PUT')
    <input id="input-update-selector" type="hidden" name="selector" />
    <input id="input-update-mark" type="hidden" name="mark" />
</form>

<form id="form-delete-selector" method="POST">
    @csrf
    @method('DELETE')
    <input id="input-delete-selector" type="hidden" name="selector" />
</form>

<form id="form-store-selector" method="POST">
    @csrf
    <input type="hidden" name="domain_id" value="{{ $domain->id }}">
    <input id="input-store-selector" type="hidden" name="selector" />
    <input id="input-store-mark" type="hidden" name="mark" />
</form>
@endsection

@section('scripts')
<script>
$(function() {
    // 更新ボタン
    $('.btn-update').click(function(){
        $target = $(this).data('target');
        $('#form-update-selector').attr('action', $target);
        $selector = $('#' + $(this).data('selector')).text();
        $('#input-update-selector').val($selector);
        $mark = $('#' + $(this).data('mark')).text();
        $('#input-update-mark').val($mark);
        $('#form-update-selector').submit();
    });

    // 削除ボタン
    $('.btn-delete').click(function(){
        if (!confirm('本当に削除しますか？')) {
            return;
        }
        $target = $(this).data('target');
        $('#form-delete-selector').attr('action', $target);
        $('#form-delete-selector').submit();
    });

    // 追加ボタン
    $('.btn-store').click(function(){
        $target = $(this).data('target');
        $('#form-store-selector').attr('action', $target);
        $selector = $('#' + $(this).data('selector')).text();
        $('#input-store-selector').val($selector);
        $mark = $('#' + $(this).data('mark')).text();
        $('#input-store-mark').val($mark);
        $('#form-store-selector').submit();
    });
});
</script>
@endsection
