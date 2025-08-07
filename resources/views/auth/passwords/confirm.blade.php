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
                            <div class="account__header">
                                <h3>Contraseña establecida correctamente</h3>
                                <p>Tu contraseña del correo se a restablecido correctamente <u>{{ $email }}</u></p>
                            </div>
                            <a href="{{ route('login') }}" class="theme-btn w-100 mt-30">Ingresar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection



