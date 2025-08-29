@extends('layouts.pages')

@section('title', 'Ingresar')

@section('content')
    <section class="account pt-150 padding-bottom">
        <div class="container-fluid">
            <div class="account__wrapper aos-init aos-animate" data-aos="fade-up" data-aos-duration="800">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="verify-content">
                            <!-- account tittle -->
                            <div class="account-header">
                                <h3>Verificación cuenta</h3>
                                <p>Para completar el proceso de registro es necesario que verifiques tu cuenta, para esto hemos enviado un mensaje a la dirección de correo en el que encontrarás un link que te traerá de vuelta a la plataforma.</p>
                                <p>Puede tardar hasta 10 minutos, por favor no olvides revisar tu bandeja de correo no deseado.</p>
                                <p>¿No lo has recibido?</p>


                                {!! Form::open(['route' => 'verification.resend', 'class' => 'form', 'role' => 'form', 'method' => 'POST']) !!}

                                    @csrf

                                    <div class="form-submit-group">
                                        <button type="submit" class="trk-btn trk-btn--border trk-btn--secondary1 d-block mt-4 w-100">
                                            <span class="icon-reverse-wrapper">
                                                <span class="btn-text">Enviar nuevamente</span>
                                            </span>
                                        </button>
                                    </div>

                                {!! Form::close() !!}


                                @if (session('resent'))
                                    <div class="notification errors closeable">
                                        <p>Hemos enviado el correo nuevamente.</p>
                                        <a class="close"></a>
                                    </div>
                                @endif

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection


