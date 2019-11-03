@extends('layout')

@section('content')
<h1>Nieuws</h1>

<ul>
    @foreach ($items as $item)
    <li>
        <a href="{{ route('news.show', compact('item')) }}">{{ $item->title }}</a><br />
        <p>Geplaatst op {{ $item->created_at->isoFormat('D MMMM YYYY, HH:mm') }}</p>
    </li>
    @endforeach
</ul>
@endsection
