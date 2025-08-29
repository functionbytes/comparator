
@extends('layouts.pages')

@section('title', 'Ingresar')

@section('content')
    <section class="account pt-150 padding-bottom">
        <div class="container-fluid">
            <div class="account__wrapper aos-init aos-animate" data-aos="fade-up" data-aos-duration="800">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="account__content">
                            <!-- account tittle -->
                            <div class="account__header pb-30">
                                <h3  class="pb-5">¿Olvidaste tu contraseña?</h3>
                                <p>Ingrese la dirección de correo electrónico asociada con su cuenta y le enviaremos un enlace por correo electrónico para restablecer su contraseña.</p>
                            </div>
                            <!-- account form -->

                            {!! Form::open(['route' => 'password.reset', 'class' => 'account__form', 'method' => 'POST', 'id' => 'formPassword']) !!}

                            <input type="hidden" name="email" value="{{ $email }}">
                            @csrf
                            <div class="row g-4">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <input class="form-control"  type="text" disabled  value="{{ $email }}" placeholder="Correo electrónico o cedula" >
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-group">
                                        <input class="form-control" type="password" autocomplete="new-password" name="password" id="password" placeholder="Contraseña" >
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-group">
                                        <input class="form-control" type="password" autocomplete="new-password" name="password_confirmation" id="password_confirmation" placeholder="Repetir contraseña" >
                                    </div>
                                </div>
                            </div>

                            @if ($errors->has('password'))
                                <div class="notification errors closeable">
                                    <p>{{ $errors->first('password') }}</p>
                                    <a class="close"></a>
                                </div>
                            @endif



                            <button type="submit" class="theme-btn w-100 mt-30">Cambiar contraseña</button>

                            {!! Form::close() !!}

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection


@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {

            $("#formPassword").validate({
                submit: true,
                rules: {
                    password: {
                        required: true,
                        minlength: 3
                    },
                    password_confirmation: {
                        required: true,
                        minlength: 3,
                        equalTo: "#password"
                    },
                },
                messages: {
                    password: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                    },
                    password_confirmation: {
                        required: "El parametro es necesario.",
                        minlength: "Debe contener al menos 3 caracter",
                        maxlength: "Debe contener al menos 100 caracter",
                        equalTo: "Por favor, introduzca el mismo valor de nuevo."
                    },
                }
            });

        });
    </script>

@endpush



