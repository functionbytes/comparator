@extends('layouts.auth')

@section('title', 'Registro')

@section('content')

<section class="register-area">
    <div class="row m-0">
       <div class="col-lg-6 col-md-12 p-0">
          <div class="register-image">
             <img src="/pages/img/register-bg.jpg" alt="image">
          </div>
       </div>
       <div class="col-lg-6 col-md-12 p-0">
          <div class="register-content">
             <div class="d-table">
                <div class="d-table-cell">
                   <div class="register-form">
                      <div class="logo">
                        <a href="{{ route('index') }}"><img src="/pages/images/logo/logo.svg" alt="image"></a>
                      </div>
                      <h3>Abre tu cuenta ahora</h3>
                      <p>¿Ya estas registrado? <a href="{{ route('auth') }}">Iniciar sesión</a></p>

                    <form  method="POST" action="{{ route('register') }}">
                        @csrf

                         <div class="form-group">
                            <input type="email" name="email" id="email" placeholder="Correo Electronico" class="form-control" required>
                         </div>
                         <div class="form-group">
                            <input type="password" name="password" id="password" autocomplete="new-password" placeholder="Contraseña" class="form-control" required>
                         </div>

                         <div class="form-group">
                            <input type="password" id="password_confirmation" autocomplete="new-password" name="password_confirmation" placeholder="Repetir Contraseña" class="form-control" required>
                         </div>


                        @if ($errors->has('email'))
                              <div class="notification error closeable">
                                 <p>{{ $errors->first('email') }}</p>
                                 <a class="close"></a>
                              </div>
                        @endif

                        @if ($errors->has('password'))
                              <div class="notification error closeable">
                                 <p>{{ $errors->first('password') }}</p>
                                 <a class="close"></a>
                              </div>
                        @endif

                        @if ($errors->has('password_confirmation'))
                              <div class="notification error closeable">
                                 <p>{{ $errors->first('password-confirmation') }}</p>
                                 <a class="close"></a>
                              </div>
                        @endif

                        @if ($errors->has('recaptcha'))
                              <div class="notification error closeable">
                                 <p>{{ $errors->first('recaptcha') }}</p>
                                 <a class="close"></a>
                              </div>
                        @endif
                        <div class="checkbox terms">
                           <input type="checkbox" name="terms" id="terms" class="terms">
                           <label for="terms"><span class="checkbox-icon"></span>
                                   Acepto los
                               <a  class="text-heading hover-primary" href="{{ route('terms') }}">
                                   <u>Términos y Condiciones</u>
                               </a> y la
                               <a  class="text-heading hover-primary" href="{{ route('politics') }}">
                                   <u>Política de Tratamiento</u>
                               </a>
                           </label>
                       </div>

                         <button type="submit" class="register-disabled" id="addRegister">Registrarme</button>

                       
                      </form>
                   </div>
                </div>
             </div>
          </div>
       </div>
    </div>
 </section>


<script src="{{ url('pages/js/jquery.min.js') }}" type="text/javascript"></script>

<script>

     $(document).ready(function() {
            $("#terms").on("change",function(){

                    value = $(this).is(":checked");

                    if(value == true){
                        $('#addRegister').removeClass("register-disabled");
                    }else{
                        $('#addRegister').addClass("register-disabled");
                    }


            });
     });

</script>

@endsection




