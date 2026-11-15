@php($translation = $translation ?? 'performance.demo')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __($translation.'.title') }}</title>
</head>
<body>
    <main>
        <h1>{{ __($translation.'.title') }}</h1>

        @if ($errors->any())
            <ul role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @if ($companies)
            <p role="status">{{ __($translation.'.ready') }}</p>
            <ul>
                @foreach ($companies as $company)
                    <li><a href="{{ url()->query($dashboardUrl, ['companyId' => $company['id']]) }}">{{ $company['label'] }}</a></li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route($submitRoute ?? 'system.seed-performance-demo') }}">
            @csrf
            <button type="submit">{{ __($translation.'.run') }}</button>
        </form>

        <p><a href="{{ $dashboardUrl }}">{{ __('performance.title') }}</a></p>

        @if ($companies && isset($escalationsUrl))
            <p>{{ __($translation.'.check') }}</p>
            <p><a href="{{ $escalationsUrl }}">{{ __('performance_escalations.navigation') }}</a></p>
        @endif
    </main>
</body>
</html>
