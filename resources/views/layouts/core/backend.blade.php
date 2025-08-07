

<!DOCTYPE html>
<html lang="en">
<head>
	@include('layouts.core._head')

	@include('layouts.core._favicon')

	@include('layouts.core._script_vars')

	@yield('head')

		<meta name="theme-color" content="#eff3f5">


    <!-- Theme -->
    <link rel="stylesheet" type="text/css" href="{{ asset('core/css/theme/dark.css') }}">
</head>

<body class="{{ isset($body_class) ? $body_class : '' }} theme-dark barmode">


	@if (config('app.saas'))
		@include('layouts.core._menu_backend')
	@else
		@include('layouts.core._menu_frontend_single')
	@endif

	@include('layouts.core._middle_bar')

	<main class="container page-container px-3">
		@include('layouts.core._headbar_backend')

		@yield('page_header')

		<!-- display flash message -->
		@include('layouts.core._errors')

		<!-- main inner content -->
		@yield('content')

		<!-- Footer -->
		@include('layouts.core._footer')
	</main>

	<!-- Notification -->
	@include('layouts.core._notify')
    @include('layouts.core._notify_backend')

	<!-- Admin area -->
	@include('layouts.core._loginas_area')

	<!-- display flash message -->
	@include('layouts.core._flash')

	{!! \App\Models\Setting::get('custom_script') !!}
</body>
</html>
