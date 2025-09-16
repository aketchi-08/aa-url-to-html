@extends('layouts.app')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">新規URL追加</h1>

    <div class="card shadow">
        <div class="card-body">
            <form action="{{ route('urls.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="url">URL</label>
                    <input type="text" name="url" id="url" class="form-control @error('url') is-invalid @enderror" placeholder="https://example.com" value="{{ old('url') }}">
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 保存
                </button>
                <a href="{{ route('urls.index') }}" class="btn btn-secondary">戻る</a>
            </form>
        </div>
    </div>
@endsection
