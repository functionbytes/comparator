<!DOCTYPE html>

<html>

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="DSAThemes">
    <meta name="description" content="">

    <!-- FAVICON AND TOUCH ICONS -->
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="152x152" href="images/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="120x120" href="images/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="76x76" href="images/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">


    <!-- GOOGLE FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:site" content="@publisher_handle">


    @yield('head')

    <!-- BOOTSTRAP pages/css/ -->
    <link href="/pages/css//bootstrap.min.css" rel="stylesheet">

    <!-- FONT ICONS -->
    <link href="/pages/css//flaticon.css" rel="stylesheet">

    <!-- PLUGINS STYLESHEET -->
    <link href="/pages/css//menu.css" rel="stylesheet">
    <link id="effect" href="/pages/css//dropdown-effects/fade-down.css" media="all" rel="stylesheet">
    <link href="/pages/css//magnific-popup.css" rel="stylesheet">
    <link href="/pages/css//owl.carousel.min.css" rel="stylesheet">
    <link href="/pages/css//owl.theme.default.min.css" rel="stylesheet">
    <link href="/pages/css//lunar.css" rel="stylesheet">

    <!-- ON SCROLL ANIMATION -->
    <link href="/pages/css//animate.css" rel="stylesheet">

    <!-- TEMPLATE pages/css/ -->
    <link href="/pages/css//blue-theme.css" rel="stylesheet">

    <!-- Style Switcher pages/css/ -->
    <link href="/pages/css//crocus-theme.css" rel="alternate stylesheet" title="crocus-theme">
    <link href="/pages/css//green-theme.css" rel="alternate stylesheet" title="green-theme">
    <link href="/pages/css//magenta-theme.css" rel="alternate stylesheet" title="magenta-theme">
    <link href="/pages/css//pink-theme.css" rel="alternate stylesheet" title="pink-theme">
    <link href="/pages/css//purple-theme.css" rel="alternate stylesheet" title="purple-theme">
    <link href="/pages/css//skyblue-theme.css" rel="alternate stylesheet" title="skyblue-theme">
    <link href="/pages/css//red-theme.css" rel="alternate stylesheet" title="red-theme">
    <link href="/pages/css//violet-theme.css" rel="alternate stylesheet" title="violet-theme">

    <!-- RESPONSIVE pages/css/ -->
    <link href="/pages/css//responsive.css" rel="stylesheet">

    @stack('css')


</head>

<body>


<div id="page" class="page font--jakarta">

    @yield('content')

</div>

<!-- EXTERNAL SCRIPTS
============================================= -->
<script src="/pages/js/jquery-3.7.0.min.js"></script>
<script src="/pages/js/bootstrap.min.js"></script>
<script src="/pages/js/modernizr.custom.js"></script>
<script src="/pages/js/jquery.easing.js"></script>
<script src="/pages/js/jquery.appear.js"></script>
<script src="/pages/js/menu.js"></script>
<script src="/pages/js/owl.carousel.min.js"></script>
<script src="/pages/js/pricing-toggle.js"></script>
<script src="/pages/js/jquery.magnific-popup.min.js"></script>
<script src="/pages/js/request-form.js"></script>
<script src="/pages/js/jquery.validate.min.js"></script>
<script src="/pages/js/jquery.ajaxchimp.min.js"></script>
<script src="/pages/js/popper.min.js"></script>
<script src="/pages/js/lunar.js"></script>
<script src="/pages/js/wow.js"></script>

<!-- Custom Script -->
<script src="/pages/js/custom.js"></script>

<script src="/pages/js/changer.js"></script>

@stack('scripts')

</body>

</html>
