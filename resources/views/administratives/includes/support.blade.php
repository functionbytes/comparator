@if(setting('chat_user_enable') == 'true' )
    <a href="{{ route("customer.chats") }}" target="_blank" class="supports"><i class="fa-duotone fa-headset"></i><span>Chat</span></a>
@endif