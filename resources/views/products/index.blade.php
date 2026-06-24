@extends('layout')

@section('content')
    <header>
        <h1>Products</h1>
    </header>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors">{{ $errors->first() }}</div>
    @endif

    <form class="filters" method="GET" action="{{ route('products.index') }}">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, SKU, brand, category">
        <label>
            <input type="checkbox" name="without_video" value="1" @checked($filters['without_video'] ?? false)>
            Without video
        </label>
        <button type="submit">Filter</button>
        <a class="button secondary" href="{{ route('products.index') }}">Reset</a>
    </form>

    <table>
        <thead>
        <tr>
            <th>Product</th>
            <th>Brand</th>
            <th>Category</th>
            <th>Video</th>
            <th>AI Verdict</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($products as $product)
            <tr>
                <td>
                    <strong>{{ $product->name }}</strong><br>
                    <span class="muted">{{ $product->sku ?? 'No SKU' }}</span>
                    <details>
                        <summary>Candidates ({{ $product->videoCandidates->count() }})</summary>
                        @forelse ($product->videoCandidates as $candidate)
                            <div class="candidate">
                                <strong>{{ $candidate->title }}</strong><br>
                                <span class="muted">{{ $candidate->youtube_video_id }} · {{ $candidate->channel_title }}</span><br>
                                @if ($candidate->is_ai_selected)
                                    AI selected: {{ $candidate->is_match ? 'match' : 'not match' }}
                                    ({{ $candidate->accuracy }}%)<br>
                                    {{ $candidate->ai_reason }}
                                @endif
                            </div>
                        @empty
                            <p class="muted">No candidates searched yet.</p>
                        @endforelse
                    </details>
                </td>
                <td>{{ $product->brand ?? '-' }}</td>
                <td>{{ $product->category ?? '-' }}</td>
                <td>
                    @if ($product->video_url)
                        <a href="{{ $product->video_url }}" target="_blank" rel="noreferrer">{{ $product->selected_youtube_video_id }}</a>
                    @else
                        <span class="muted">Missing</span>
                    @endif
                </td>
                <td>
                    @if ($product->video_verified_at)
                        {{ $product->ai_accuracy }}%<br>
                        <span class="muted">{{ $product->ai_reason }}</span>
                    @else
                        <span class="muted">Pending</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('products.search-youtube', $product) }}">
                        @csrf
                        <button type="submit">Search YouTube</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="muted">No products found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top: 16px;">
        {{ $products->links() }}
    </div>
@endsection
