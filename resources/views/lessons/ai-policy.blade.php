@extends('layouts.app')
@section('content')
<div class="card-dark max-w-2xl mx-auto p-6 space-y-4">
    <h1 class="text-xl font-bold">Chính sách Gia sư AI</h1>
    <p>{{ $lesson->title }}</p>
    @if(session('success')) <p role="status" class="text-teal-300">{{ session('success') }}</p> @endif
    @if($errors->any())
        <ul role="alert" class="text-red-400">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    <form action="{{ route('courses.lessons.ai-policy.update', [$lesson->course_id, $lesson->id]) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')
        @include('lessons.partials.ai-policy-fields', ['policyLesson' => $lesson])
        <button type="submit" class="btn-primary !w-auto px-5">Lưu chính sách</button>
        <a href="{{ route('courses.show', $lesson->course_id) }}" class="ml-3 text-sm">Về khóa học</a>
    </form>
</div>
@endsection
