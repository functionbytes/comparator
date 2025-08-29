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
                                <h3 class="pb-5">¿Olvidaste tu contraseña?</h3>
                                <p>Ingrese la dirección de correo electrónico asociada con su cuenta y le enviaremos un enlace por correo electrónico para restablecer su contraseña.</p>
                            </div>
                            <!-- account form -->

                            {!! Form::open(['route' => 'password.email', 'class' => 'account__form', 'method' => 'POST', 'id' => 'formPassword']) !!}

                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <div class="input-group">
                                            <input class="form-control" id="email" type="text" name="email" placeholder="Correo electrónico o cedula" >
                                        </div>
                                    </div>
                                </div>

                                @if ($errors->has('email'))
                                    <div class="notification errors closeable">
                                        <p>{{ $errors->first('email') }}</p>
                                        <a class="close"></a>
                                    </div>
                                @endif


                                <button type="submit" class="theme-btn w-100 mt-30">Has olvidado tu contraseñas</button>

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

            jQuery.validator.addMethod("emailExt", function(value, element, param) {
                return value.match(/^[a-zA-Z0-9_\.%\+\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,3}$/);
            }, 'Porfavor ingrese email valido');

            $("#formPassword").validate({
                submit: true,
                rules: {
                    email: {
                        required: true,
                        email: true,
                        emailExt: true
                    },
                },
                messages: {
                    email: {
                        required: "El email es necesario",
                        email: "Por favor ingrese email valido"
                    },
                }
            });

        });
    </script>

@endpush
