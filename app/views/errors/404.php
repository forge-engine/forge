@layout('layouts/main')

@section('content')
<div class="error-container">
    <h1>404 - Page Not Found</h1>
    <p>The requested resource could not be located</p>
    @component('alert', [
    'type' => 'danger',
    'message' => "Path: {$_SERVER['REQUEST_URI']}"
    ])
</div>
@endsection