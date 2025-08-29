@extends('layouts.auth')

@section('title', 'Ingresar')

@section('content')

    <div id="login" class="bg--scroll login-section division">
        <div class="container">
            <div class="row justify-content-center">

                <div class="col-lg-11">
                    <div class="register-page-wrapper r-16 bg--fixed">
                        <div class="row">

                            <div class="col-md-6">
                                <div class="register-page-txt color--white">
                                    <h2 class="s-42 w-700">Bienvenido</h2>
                                    <h2 class="s-42 w-700">de vuelta a alvarez</h2>
                                    <p class="p-md mt-25">Tu espacio confiable para gestionar la distribución de accesorios del hogar con eficiencia y rapidez.</p>
                                    <div class="register-page-copyright">
                                        <p class="p-sm">&copy; 2025 alvarez. <strong>Reservados todos los derechos</strong></p>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="register-page-form">
                                    <form id="formLogin" class="row sign-in-form" enctype="multipart/form-data" role="form" onSubmit="return false">
                                        @csrf

                                        <div class="col-md-12 mb-4">
                                            <p class="p-sm input-header">Correo electrónico</p>
                                            <input class="form-control email" id="email" type="text" name="email"  placeholder="example@example.com" >
                                        </div>

                                        <div class="col-md-12  mb-3">
                                            <p class="p-sm input-header">Contraseña</p>
                                            <div class="wrap-input">
                                                <span class="btn-show-pass ico-20"><span class="flaticon-visibility eye-pass"></span></span>
                                                <input class="form-control password" id="password" type="password" name="password" placeholder="* * * * * * * * *">
                                            </div>
                                        </div>

                                        <div class="col-12 mb-2">
                                            <div class="errors d-none">
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn--theme hover--theme submit">Ingresar</button>
                                        </div>


                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>


@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function() {


            $(".position-relative span").on("click", function () {
                const input = $(this).siblings("input"); // Obtener el campo de entrada asociado
                const currentType = input.attr("type"); // Obtener el tipo actual del input

                // Alternar entre 'password' y 'text'
                const newType = currentType === "password" ? "text" : "password";
                input.attr("type", newType);

                // Cambiar el icono del ojo (opcional)
                const svg = $(this).find("svg");
                if (newType === "text") {
                    svg.attr("fill", "gray"); // Cambiar el color del ícono al mostrar contraseña
                } else {
                    svg.attr("fill", "currentColor"); // Restaurar el color al ocultar contraseña
                }
            });


            jQuery.validator.addMethod('emailExt', function(value, element, param) {
                return value.match(/^(([^<>()[\]\.,;:\s@"]+(\.[^<>()[\]\.,;:\s@"]+)*)|(".+"))@(([^<>()[\]\.,;:\s@"]+\.)+[^<>()[\]\.,;:\s@"]{2,})$/i);
            }, 'Por favor, ingrese un email válido.');

            $("#formLogin").validate({
                ignore: ".ignore",
                rules: {
                    email: {
                        required: true,
                        email: true,
                        emailExt: true,
                    },
                    password: {
                        required: true,
                        minlength: 6,
                        maxlength: 100,
                    },
                },
                messages: {
                    email: {
                        required: 'El campo de correo electrónico es necesario.',
                        email: 'Por favor, introduce una dirección de correo electrónico válida.',
                    },
                    password: {
                        required: 'El campo de contraseña es necesario.',
                        minlength: 'Debe contener al menos 6 caracteres.',
                        maxlength: 'Debe contener como máximo 100 caracteres.',
                    },
                },
                submitHandler: function(form) {

                    var $form = $('#formLogin');
                    var formData = new FormData($form[0]);
                    var email = $("#email").val();
                    var password = $("#password").val();
                    var remember = $("#remember").prop("checked");

                    formData.append('email', email);
                    formData.append('password', password);
                    formData.append('remember', remember);

                    var $submitButton = $('button[type="submit"]');
                    $submitButton.prop('disabled', true);

                    $.ajax({
                        url: "{{ route('auth.login') }}",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        type: "POST",
                        contentType: false,
                        processData: false,
                        data: formData,
                        success: function(response) {
                            if(response.success == true){
                                window.location.href = response.redirect;
                            }else{
                                $submitButton.prop('disabled', false);
                                error = response.message;
                                $('.errors').text(error);
                                $('.errors').removeClass('d-none');
                            }
                        }
                    });
                }
            });
        });


    </script>
@endpush
