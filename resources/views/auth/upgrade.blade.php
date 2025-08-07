@extends('layouts.auth')

@section('title', 'Ingresar')

@section('content')

        <div class="position-relative overflow-hidden radial-gradient min-vh-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center justify-content-center w-100">
                <div class="row justify-content-center w-100">
                    <div class="col-md-6 col-lg-6  col-xxl-4 ">
                        <div class="card mb-0">
                            <div class="card-body">
                                <a href="{{ route('index') }}"  class="text-nowrap logo-img text-center d-block mt-3 mb-5 w-100">
                                    @if(count($setting->getMedia('logo'))>0)
                                        <img src="{{ $setting->getfirstMedia('logo')->getfullUrl() }}" width="180" alt="" />
                                    @endif
                                </a>


                                <p>
                                    Actualización datos
                                </p>
                                <p>
                                    Por movimos de políticas de privacidad es necesario cambiar actualizar los datos de  nuestros clientes
                                </p>
                               <form class="max-width-auto" id="formUpgrades" enctype="multipart/form-data" role="form" onSubmit="return false">
                                                    
                                     @csrf
                                    <div class="mb-3">
                                        <label  class="form-label">Nombres</label>
                                        <input type="text" name="firstname" id="firstname" placeholder="Nombres *" class="form-control" >
                                    </div>
                                    <div class="mb-4">
                                        <label  class="form-label">Apellidos</label>
                                        <input type="text" name="lastname" id="lastname" placeholder="Apellidos *" class="form-control" >
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Celular</label>
                                        <input type="text" name="cellphone" id="cellphone" placeholder="Celular *" class="form-control">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Ciudades</label>
                                        {!! Form::select('citie', $cities, $citie, ['id' => 'cities', 'class' => 'select2-container']) !!}
                                        <label id="citie-error" class="error d-none" for="citie"></label>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Dirección</label>
                                        <input type="text" name="address" id="address" placeholder="Dirección *"  class="form-control">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Documento</label>
                                        <input type="text" name="identification" id="identification"  placeholder="Documento identificación *" class="form-control">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Correo Electronico</label>
                                        <input type="email" name="email" id="email" placeholder="Correo Electronico *" class="form-control">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Correo Electronico</label>
                                        <input type="password" name="password" id="password" placeholder="Contraseña *" autocomplete="new-password" class="form-control">
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Correo Electronico</label>
                                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Repetir Contraseña  *" class="form-control">
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-4">
                                        <div class="form-check">
                                            <input class="form-check-input primary" type="checkbox" value="" name="terms" id="terms" checked="">
                                            <label class="form-check-label text-dark" for="terms">
                                                Acepto los
                                                <a class="text-heading hover-primary" href="{{ route('terms') }}">
                                                    <u>Términos y Condiciones</u>
                                                </a> y la
                                                <a class="text-heading hover-primary" href="{{ route('politics') }}">
                                                    <u>Política de Tratamiento</u>
                                                </a>
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" id="addUpgrades" class="btn btn-primary w-100 py-8 mb-4 rounded-2">Actualizar</button>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>

@endsection





@push('scripts')

<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>

<script>
    $(document).ready(function() {
        $("#terms").on("change", function() {

            value = $(this).is(":checked");

            if (value == true) {
                $('#addUpgrades').removeClass("register-disabled");
            } else {
                $('#addUpgrades').addClass("register-disabled");
            }
        });


        jQuery.validator.addMethod("emailExt", function(value, element, param) {
            return value.match(/^[a-zA-Z0-9_\.%\+\-]+@[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,3}$/);
        }, 'Porfavor ingrese email valido');

        $("#formUpgrades").validate({
            submit: false,
            ignore: ":hidden:not(#note),.note-editable.panel-body",
            rules: {
                firstname: {
                    required: true,
                    minlength: 2,
                    maxlength: 200,
                },
                lastname: {
                    required: true,
                    minlength: 2,
                    maxlength: 200,
                },
                address: {
                    required: true,
                    minlength: 10,
                    maxlength: 500,
                },
                company: {
                    required: false,
                    minlength: 4,
                    maxlength: 100,
                },
                cellphone: {
                    required: true,
                    number: true,
                    minlength: 8,
                    maxlength: 500,
                },
                email: {
                    required: true,
                    email: true,
                    emailExt: true,
                },
                citie: {
                    required: true,
                }
            },
            messages: {
                firstname: {
                    required: "El Nombre es necesario",
                    minlength: "El Nombre debe contener al menos 5 caracteres",
                    maxlength: "El Nombre debe contener no mas de 50 caracteres"
                },
                lastname: {
                    required: "El Apellido es necesario.",
                    minlength: "El Apellido debe contener al menos 5 caracteres",
                    maxlength: "El Apellido debe contener no mas de 50 caracteres"
                },
                company: {
                    required: "La empresa es necesario",
                    minlength: "La empresa debe contener al menos 4 caracteres",
                    maxlength: "La empresa debe contener no mas de 100 caracteres",
                    number: "Solo se puede ingresar numeros"
                },
                address: {
                    required: "La dirección línea  es necesaria",
                    minlength: "La dirección  línea 10 debe contener al menos 10 caracteres",
                    maxlength: "Eldirección  línea 500  debe contener no mas de 500 caracteres"
                },
                cellphone: {
                    required: "La celular es necesario",
                    minlength: "La celular debe contener al menos 6 caracteres",
                    maxlength: "La celular debe contener no mas de 20 caracteres",
                    number: "Sólo se pueden ingresar números"
                },
                email: {
                    required: "El email es necesario",
                    email: "Por favor ingrese email valido"
                },
                citie: {
                    required: "El campo ciudad es necesario",
                }
            },

            submitHandler: function(form) {

                var $form = $('#formUpgrades');
                var formData = new FormData($form[0]);

                $.ajax({
                    url: "/upgrade/store",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content')
                    },
                    type: "POST",
                    contentType: false,
                    processData: false,
                    data: formData,
                    success: function(data) {

                        if (data.status == "success") {

                            location.href = "/home";

                        }else if(data == "success" && option == "password") {

                           $('#validationEmail').removeClass("none");
                           $('#validationPassword').addClass("none");

                        }else if(data == "success" && option == "email") {

                           $('#validationEmail').addClass("none");
                            $('#validationPassword').removeClass("none");

                        }


                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        if (jqXHR.status == 500) {
                            alert('Internal error: ' + jqXHR.responseText);
                        } else {
                            alert('Unexpected error.');
                        }
                    }
                });



            }

        });

        $("#addUpgrades").click(function() {
            //$('#addUpgrades').addClass("Upgrades-disabled");
            $("#formUpgrades").submit();
        });


        $('#cities').select2({
            placeholder: "Seleccionar una ciudad",
            ajax: {
                dataType: 'json',
                url: '/cities',
                delay: 250,
                data: function(params) {
                    return {
                        term: $.trim(params.term)
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                cache: true

            }
        });

    });
</script>

@endpush