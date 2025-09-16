@extends('layouts.app')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">HTMLプレビュー</h1>

    <div class="card shadow">
        <div class="card-body">
            <iframe src="{{ route('urls.download', $url->id) }}" style="width:100%; height:80vh; border:0;"></iframe>
        </div>
    </div>
@endsection
