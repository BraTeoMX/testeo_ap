@extends('layouts.app', ['activePage' => 'reporteGeneral', 'titlePage' => __('Reporte General')])

@section('content')
    <div class="card" style="height: auto; width: auto;">
        <div class="card-header">
            <h2>Reporte Cumplimiento de Metas</h2>
        </div>

        <div style="margin: 20px;">
            <form method="GET" action="{{ route('metas.reporteGeneralMetas') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="start_week">Semana de inicial:</label>
                        <input type="week" id="start_week" name="start_week" class="form-control"
                            value="{{ request('start_week', date('o-\WW', strtotime('-1 week'))) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_week">Semana de final:</label>
                        <input type="week" id="end_week" name="end_week" class="form-control"
                            value="{{ request('end_week', date('o-\WW')) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>

        <div id="accordion">
            <!-- Tarjeta para Planta 1 -->
            <div class="card">
                <div class="card-header" id="headingOne" style="background: #4F3C20;">
                    <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true"
                        aria-controls="collapseOne" style="color: white; font-size: 16px; font-weight: bold;">
                        Planta 1 - Ixtlahuaca
                    </button>
                </div>

                <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-container form-filter">
                            <button class="btn btn-primary" type="button" id="generatePdf1">Generar Reporte PDF - Tabla
                                General Planta 1</button>
                            {{-- Formularios ocultos para cada acción de PDF --}}
                            <form method="POST" action="{{ route('metas.tablaPDF1') }}" class="d-none" id="formPDF1">
                                @csrf
                                <input type="hidden" name="start_week" id="pdfStartWeek1">
                                <input type="hidden" name="end_week" id="pdfEndWeek1">
                            </form>
                        </div>
                        <!-- Inicio Sección de la primera tabla -->
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th rowspan="2"></th>
                                    <th rowspan="2"></th>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        <th colspan="{{ count($semanas) * 2 }}" style="text-align: center;">
                                            {{ strtoupper($mes) }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <th colspan="2" style="text-align: center;">SEMANA {{ $semana }}</th>
                                        @endforeach
                                    @endforeach
                                </tr>
                                <tr>
                                    <th>#</th>
                                    <th>Total de Módulos</th>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <th class="semana semana{{ $semana }}">
                                                &nbsp;{{ $contadorTS[$semana] }}&nbsp;</th>
                                            <th class="semana semana{{ $semana }}">
                                                <strong>{{ $Tporcentajes3[$semana] ?? 0 }}%</strong></th>
                                        @endforeach
                                    @endforeach
                                </tr>
                                @for ($i = 1; $i <= 7; $i++)
                                    <tr>
                                        <th>{{ $i }}</th>
                                        <th id="dato{{ $i }}"
                                            style="background-color: {{ $colores[$i - 1] }}; text-align: left;">
                                            &nbsp;{{ $titulos[$i - 1] }}&nbsp;</th>
                                        @foreach ($mesesAMostrar as $mes => $semanas)
                                            @foreach ($semanas as $semana)
                                                @php
                                                    $valor = $contadoresSemana[$semana][$i];
                                                    $total = $contadorTS[$semana];
                                                    $porcentaje =
                                                        $total != 0 ? number_format(($valor / $total) * 100, 2) : 0;
                                                @endphp
                                                <td class="semana semana{{ $semana }}">
                                                    &nbsp;&nbsp;{{ $valor }}&nbsp;</td>
                                                <td class="semana semana{{ $semana }}"> {{ $porcentaje }}% </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endfor
                                <!-- Fila Cumplimiento en Tiempo -->
                                <tr>
                                    <td class="propiedadNuevaN">%</td>
                                    <td class="propiedadNuevaN">Cumplimiento en Tiempo</td>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <td class="propiedadNueva"> {{ $TcontadorSuma3[$semana] }} </td>
                                            <td class="propiedadNuevaN"> {{ $Tporcentajes3[$semana] }}% </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                                <!-- Fila Cumplimiento con TE (Viernes) -->
                                <tr>
                                    <td class="propiedadNuevaN">%</td>
                                    <td class="propiedadNuevaN">Cumplimiento con TE (Viernes)</td>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <td class="propiedadNueva"> {{ $TcontadorSuma[$semana] }} </td>
                                            <td class="propiedadNuevaN"> {{ $Tporcentajes[$semana] }}% </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </table>
                        </div>
                        <br>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <input type="text" id="searchInput" onkeyup="filterTable()"
                                    placeholder="Buscar por nombre o módulo...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="myTable">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Supervisor</th>
                                        <th rowspan="2">Módulo</th>
                                        @foreach ($mesesAMostrar as $mes => $semanas)
                                            <th colspan="{{ count($semanas) }}" style="text-align: center;">
                                                {{ strtoupper($mes) }}</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($mesesAMostrar as $mes => $semanas)
                                            @foreach ($semanas as $semana)
                                                <th style="text-align: center;">SEMANA {{ $semana }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supervisoresPlanta1 as $supervisor) 
                                        <tr>
                                            <td style="text-align: left">{{ $supervisor->nombre }}</td>
                                            <td>{{ $supervisor->modulo }}</td>
                                            @foreach ($mesesAMostrar as $mes => $semanas)
                                                @foreach ($semanas as $semana)
                                                    @php
                                                        $produccion = $produccionPlanta1
                                                            ->where('supervisor_id', $supervisor->id)
                                                            ->where('semana', $semana)
                                                            ->first();
                                                        $valorSemanal = $produccion ? $produccion->valor : '';
                                                        $colorClass =
                                                            $valorSemanal != '' ? $colores[$valorSemanal - 1] : '';
                                                        $extraValue = $produccion ? $produccion->te : 0;
                                                    @endphp
                                                    <td style="background-color: {{ $colorClass }};">
                                                        @if ($extraValue)
                                                            <strong>* * * </strong>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    @endforeach 
                                </tbody>
                            </table>
                        </div>
                        <br>
                    </div>
                </div>
            </div>

            <!-- Tarjeta para Planta 2 -->
            <div class="card">
                <div class="card-header" id="headingTwo" style="background: #4F3C20;">
                    <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo"
                        aria-expanded="false" aria-controls="collapseTwo"
                        style="color: white; font-size: 16px; font-weight: bold;">
                        Planta 2 - San Bartolo
                    </button>
                </div>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
                    <div class="card-body">
                        <div class="form-container form-filter">
                            <button class="btn btn-primary" type="button" id="generatePdf2">Generar Reporte PDF - Tabla
                                General Planta 2</button>

                            {{-- Formularios ocultos para cada acción de PDF --}}
                            <form method="POST" action="{{ route('metas.tabla2PDF1') }}" class="d-none"
                                id="formPDF2">
                                @csrf
                                <input type="hidden" name="start_week" id="pdfStartWeek2">
                                <input type="hidden" name="end_week" id="pdfEndWeek2">
                            </form>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th rowspan="2"></th>
                                    <th rowspan="2"></th>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        <th colspan="{{ count($semanas) * 2 }}" style="text-align: center;">
                                            {{ strtoupper($mes) }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <th colspan="2" style="text-align: center;">SEMANA {{ $semana }}
                                            </th>
                                        @endforeach
                                    @endforeach
                                </tr>
                                <tr>
                                    <th>#</th>
                                    <th>Total de Módulos</th>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <th class="semana semana{{ $semana }}">
                                                &nbsp;{{ $contadorTSPlanta2[$semana] }}&nbsp;</th>
                                            <th class="semana semana{{ $semana }}">
                                                <strong>{{ $Tporcentajes3Planta2[$semana] ?? 0 }}%</strong></th>
                                        @endforeach
                                    @endforeach
                                </tr>
                                @for ($i = 1; $i <= 7; $i++)
                                    <tr>
                                        <th>{{ $i }}</th>
                                        <th id="dato{{ $i }}"
                                            style="background-color: {{ $colores[$i - 1] }}; text-align: left;">
                                            &nbsp;{{ $titulos[$i - 1] }}&nbsp;</th>
                                        @foreach ($mesesAMostrar as $mes => $semanas)
                                            @foreach ($semanas as $semana)
                                                @php
                                                    $valor = $contadoresSemanaPlanta2[$semana][$i];
                                                    $total = $contadorTSPlanta2[$semana];
                                                    $porcentaje =
                                                        $total != 0 ? number_format(($valor / $total) * 100, 2) : 0;
                                                @endphp
                                                <td class="semana semana{{ $semana }}">
                                                    &nbsp;&nbsp;{{ $valor }}&nbsp;</td>
                                                <td class="semana semana{{ $semana }}"> {{ $porcentaje }}% </td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endfor
                                <!-- Fila Cumplimiento en Tiempo -->
                                <tr>
                                    <td class="propiedadNuevaN">%</td>
                                    <td class="propiedadNuevaN">Cumplimiento en Tiempo</td>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <td class="propiedadNueva"> {{ $TcontadorSuma3Planta2[$semana] }} </td>
                                            <td class="propiedadNuevaN"> {{ $Tporcentajes3Planta2[$semana] }}% </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                                <!-- Fila Cumplimiento con TE (Viernes) -->
                                <tr>
                                    <td class="propiedadNuevaN">%</td>
                                    <td class="propiedadNuevaN">Cumplimiento con TE (Viernes)</td>
                                    @foreach ($mesesAMostrar as $mes => $semanas)
                                        @foreach ($semanas as $semana)
                                            <td class="propiedadNueva"> {{ $TcontadorSumaPlanta2[$semana] }} </td>
                                            <td class="propiedadNuevaN"> {{ $TporcentajesPlanta2[$semana] }}% </td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </table>
                        </div>
                        <br>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <input type="text" id="searchInput2" onkeyup="filterTable2()"
                                    placeholder="Buscar por nombre o módulo...">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="myTable2">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Supervisor</th>
                                        <th rowspan="2">Módulo</th>
                                        @foreach ($mesesAMostrar as $mes => $semanas)
                                            <th colspan="{{ count($semanas) }}" style="text-align: center;">
                                                {{ strtoupper($mes) }}</th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($mesesAMostrar as $mes => $semanas)
                                            @foreach ($semanas as $semana)
                                                <th style="text-align: center;">SEMANA {{ $semana }}</th>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supervisoresPlanta2 as $supervisor)
                                        <tr>
                                            <td style="text-align: left">{{ $supervisor->nombre }}</td>
                                            <td>{{ $supervisor->modulo }}</td>
                                            @foreach ($mesesAMostrar as $mes => $semanas)
                                                @foreach ($semanas as $semana)
                                                    @php
                                                        $produccion = $produccionPlanta2
                                                            ->where('supervisor_id', $supervisor->id)
                                                            ->where('semana', $semana)
                                                            ->first();
                                                        $valorSemanal = $produccion ? $produccion->valor : '';
                                                        $colorClass =
                                                            $valorSemanal != '' ? $colores[$valorSemanal - 1] : '';
                                                        $extraValue = $produccion ? $produccion->te : 0;
                                                    @endphp
                                                    <td style="background-color: {{ $colorClass }};">
                                                        @if ($extraValue)
                                                            <strong>* * * </strong>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .table-responsive {
            overflow-x: auto;
        }
    
        .table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
            min-width: 100%;
        }
    </style>
    <style>
        .propiedadNueva {
            background-color: #bbcdce;
        }

        .propiedadNuevaN {
            background-color: #bbcdce;
            font-weight: bold;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: solid 1px #ddd;
            color: black;
        }

        th {
            background-color: #bbcdce;
            color: #333;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .green {
            background-color: #00B0F0;
        }

        .light-green {
            background-color: #00B050;
        }

        .yellow {
            background-color: #FFFF00;
        }

        .SaddleBrown {
            background-color: #C65911;
        }

        .red {
            background-color: #FF0000;
        }

        .peach {
            background-color: #A6A6A6;
        }

        .grey {
            background-color: #F9F9EB;
        }

        .centered-content {
            text-align: center;
            vertical-align: middle;
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 16px;
            border-bottom: solid 1px #ddd;
        }

        #searchInput,
        #searchInput2 {
            width: 100%;
            padding: 10px 50px 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            outline: none;
            transition: all 0.3s ease-in-out;
        }

        #searchInput:focus,
        #searchInput2:focus {
            border-color: #0056b3;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        #searchInput::placeholder,
        #searchInput2::placeholder {
            color: #999;
        }

        .form-filter {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .form-filter select,
        .form-filter button {
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: #fff;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-filter button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .form-filter-verde button {
            background-color: #00C9A7;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        .form-filter button:hover {
            background-color: #0056b3;
        }

        .form-filter label {
            font-weight: bold;
        }

        .form-container {
            margin-bottom: 20px;
        }
    </style>

    <script>
        function filterTable() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("myTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                var tdLeader = tr[i].getElementsByTagName("td")[0];
                var tdModule = tr[i].getElementsByTagName("td")[1];
                if (tdLeader || tdModule) {
                    if (tdLeader.textContent.toUpperCase().indexOf(filter) > -1 || tdModule.textContent.toUpperCase()
                        .indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }

        function filterTable2() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput2");
            filter = input.value.toUpperCase();
            table = document.getElementById("myTable2");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                var tdLeader = tr[i].getElementsByTagName("td")[0];
                var tdModule = tr[i].getElementsByTagName("td")[1];
                if (tdLeader || tdModule) {
                    if (tdLeader.textContent.toUpperCase().indexOf(filter) > -1 || tdModule.textContent.toUpperCase()
                        .indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
    <script>
        document.getElementById('generatePdf1').addEventListener('click', function() {
            document.getElementById('pdfStartWeek1').value = document.getElementById('start_week').value;
            document.getElementById('pdfEndWeek1').value = document.getElementById('end_week').value;
            document.getElementById('formPDF1').submit();
        });

        document.getElementById('generatePdf2').addEventListener('click', function() {
            document.getElementById('pdfStartWeek2').value = document.getElementById('start_week').value;
            document.getElementById('pdfEndWeek2').value = document.getElementById('end_week').value;
            document.getElementById('formPDF2').submit();
        });
    </script>
@endsection
