@extends('layouts.administratives')

@section('content')

  @include('managers.includes.card', ['title' => 'Documentos'])

  <div class="widget-content searchable-container list">
    <div class="card card-body">
      <div class="row">
        <div class="col-md-12 col-xl-12">
          <form class="position-relative form-search" action="{{ request()->fullUrl() }}" method="GET">
            <div class="row justify-content-between g-2 ">
              <div class="col-auto flex-grow-1">
                <div class="tt-search-box">
                  <div class="input-group">
                    <span class="position-absolute top-50 start-0 translate-middle-y ms-2"> <i data-feather="search"></i></span>
                    <input class="form-control rounded-start w-100" type="text" id="search" name="search" placeholder="Buscar" @isset($searchKey) value="{{ $searchKey }}" @endisset>
                  </div>
                </div>
              </div>
              <div class="col-auto">
                <div class="input-group">
                  <select class="form-select select2" name="available" data-minimum-results-for-search="Infinity">
                    <option value="">Seleccionar estado</option>
                    <option value="1" @isset($available) @if ($available==1) selected @endif @endisset>  Publico</option>
                    <option value="0" @isset($available) @if ($available==0) selected  @endif @endisset>  Oculto</option>
                  </select>
                </div>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Buscar">
                  <i class="fa-duotone fa-magnifying-glass"></i>
                </button>
              </div>
              <div class="col-auto">

              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="card card-body">
      <div class="table-responsive">
        <table class="table search-table align-middle text-nowrap">
          <thead class="header-item">
              <tr>
                <th>Orden</th>
                <th>Cliente</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Acciones</th>
              </tr>
          </thead>
          <tbody>
              @foreach ($documents as $key => $document)
                <tr class="search-items">
                  <td>
                      ({{ $document->order_id }}) ({{ $document->order->reference }})
                  </td>
                  <td>
                        {{ $document->customer->firstname }}  {{ $document->customer->lastname }}
                  </td>
                  <td>
                      <span class="badge {{ $document->proccess == 1 ? 'bg-light-primary' : 'bg-light-secondary' }} rounded-3 py-2 text-primary fw-semibold fs-2 d-inline-flex align-items-center gap-1">
                           {{ $document->proccess == 1 ? 'Gestionado' : 'Pendiente' }}
                      </span>
                  </td>
                  <td>
                    <span class="usr-ph-no" data-phone="{{ date('Y-m-d', strtotime($document->updated_at)) }}">{{ date('Y-m-d', strtotime($document->updated_at)) }}</span>
                  </td>
                  <td class="text-left">
                    <div class="dropdown dropstart">
                      <a href="#" class="text-muted" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ti ti-dots fs-5"></i>
                      </a>
                      <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <li>
                          <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('administrative.documents.edit', $document->uid) }}">Editar</a>
                        </li>
                          <li>
                              <a class="dropdown-item d-flex align-items-center gap-3" href="{{ route('administrative.documents.view', $document->uid) }}">Visualizar</a>
                          </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              @endforeach
          </tbody>
        </table>
      </div>
      <div class="result-body ">
        <span>Mostrar {{ $documents->firstItem() }}-{{ $documents->lastItem() }} de {{ $documents->total() }} resultados</span>
        <nav>
          {{ $documents->appends(request()->input())->links() }}
        </nav>
      </div>
    </div>
  </div>
@endsection


