@php
    use Illuminate\Support\Facades\Session;
@endphp

<script>
    @if (null !== Session::get('orig_admin_id') && Auth::user()->admin)
        notify({
            type: 'warning',
            message: `{!! trans('messages.current_login_as', ["name" => Auth::user()->firsname]) !!}<br>{!! trans('messages.click_to_return_to_origin_user', ["link" => route("Admin\AdminController@loginBack")]) !!}`,
            timeout: false,
        });
    @endif

    @if (
        \Auth::user()->customer &&
        config('app.saas') &&
        \Auth::user()->customer->getCurrentActiveGeneralSubscription() &&
        \Auth::user()->customer->getCurrentActiveGeneralSubscription()->planGeneral->useOwnSendingServer() &&
        !\Auth::user()->customer->activeSendingServers()->count()
    )
        notify({
            type: 'warning',
            message: `{!! trans('messages.not_have_any_customer_sending_server', [
                'link' => route('SendingServerController@select'),
            ]) !!}`,
            timeout: false,
        });
    @endif

    @if (\Auth::user()->customer &&
        config('app.saas') &&
        !\Auth::user()->customer->getCurrentActiveGeneralSubscription() &&
        !isset($subscriptionPage)
    )
        notify({
            type: 'warning',
            message: `{!! trans('messages.not_have_any_plan_notification', [
                'link' => route('SubscriptionController@index'),
            ]) !!}`,
            timeout: false,
        });
    @endif
</script>
