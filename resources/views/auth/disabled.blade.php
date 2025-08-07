@extends('layouts.pages')

@section('title', 'Verificación')

@section('content')

<section class="contact-area pt-100 pb-100">
   <div class="container">
      <div class="section-title">
         <span class="sub-title">AUTENTICACIÓN</span>
         <h2>USUARIO DESABILITADO</h2>
         <p>Para completar el proceso de registro es necesario que verifiques tu cuenta, para esto hemos enviado un mensaje a la dirección de correo en el que encontrarás un link que te traerá de vuelta a la plataforma.</p>

         <div class="btn-content">
            <a href="{{ route('home') }}" id="addRegister" class="default-btn">REGRESAR</a>
        </div>
   </div>

</section>

@endsection
