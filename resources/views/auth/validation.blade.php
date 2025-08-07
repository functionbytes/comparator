@extends('layouts.pages')

@section('title', 'Verificación')

@section('content')


<div class="content-error-area pt-120 pb-120">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="content-error-item text-center">
                    <div class="error-thumb">
                        <img src="/pages/images/privileges.svg" alt="image not found">
                    </div>
                    <div class="section-title">
                        <h2 class="mb-20">No tienes privilegios</h2>
                        <p>Para completar el proceso de registro es necesario que verifiques tu cuenta, para esto hemos
                            enviado un mensaje a la
                            dirección de correo en el que encontrarás un link que te traerá de vuelta a la plataforma.
                        </p>
                    </div>
                    <div class="error-btn">
                        <a class="edu-btn" href="{{ route('home') }}">Regresar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

