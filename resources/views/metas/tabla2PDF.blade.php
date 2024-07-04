<!DOCTYPE html> 
<html>
<head>
    <title>Produccion</title>
<style>
@page {
            margin: 0cm 0cm;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            margin: 1cm;

        }
        table {
            color: black;
            width: 100%; /* Asegura que la tabla use todo el ancho disponible */
            font-size: 10px; /* Reduce el tamaño de la fuente */
            border-collapse: collapse; /* Elimina el espacio entre las celdas */
        }

        /* Asegúrate de que los colores de fondo se impriman */
        th, td {
            padding: 2px; /* Reduce el padding para hacer más compacta la celda */
            text-align: center; /* Centra el contenido de las celdas */
            border: 1px solid black; /* Define bordes para mejorar la legibilidad */
        }
        /* Estilos específicos para las celdas con bordes */
        .borde {
            border: 1px solid black;
        }

        /* Estilos específicos para las celdas sin bordes */
        .sin-borde {
            border: none;
        }
        /* Colores de las cabeceras de colores */
        .green { background-color: #00B0F0; }
        .light-green { background-color: #00B050; }
        .yellow { background-color: #FFFF00; }
        .SaddleBrown { background-color: #C65911; }
        .red { background-color: #FF0000; }
        .peach { background-color: #A6A6A6; }
        .grey { background-color: #F9F9EB; }


</style>
</head>
<body>
    <div id="encabezado">
        <table style="width:100%; align:center">
        <tr class="sin-borde">
            <td class="sin-borde" width=30% style="text-align:center"><img src="../public/material/img/logo.png" width="100px" heigth="82px" ></td>
            <td class="sin-borde" width=30%>&nbsp;</td>
            <td class="sin-borde" style="text-align:center; font-size: 10px" >.</td>
         </tr>
         <tr>
            <td class="sin-borde" style="text-align:center; font-size: 10px " colspan =3><h3>SEGUIMIENTO A CUMPLIMIENTO DE METAS <br>Planta San Bartolo </h3></td>
         </tr>
        </table>
        <!-- Este POST manda a llamar la funcion del controller llamdo Reportes -->

        <div class="table-responsive">
            <table BORDER>
                <tr>
                    <th rowspan="2"></th>
                    <th rowspan="2"></th>
                    @foreach ($mesesAMostrar as $mes => $semanas)
                        <th colspan="{{ count($semanas) * 2 }}" style="text-align: center;">{{ strtoupper($mes) }}</th>
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
                            <th class="semana semana{{ $semana }}">&nbsp;{{ $contadorTSPlanta2[$semana] }}&nbsp;</th>
                            <th class="semana semana{{ $semana }}"><strong>{{ $Tporcentajes3Planta2[$semana] ?? 0 }}%</strong></th>
                        @endforeach
                    @endforeach
                </tr>
                @for ($i = 1; $i <= 7; $i++)
                    <tr>
                        <th>{{ $i }}</th>
                        <th id="dato{{ $i }}" style="background-color: {{ $colores[$i-1] }}; text-align: left;">&nbsp;{{ $titulos[$i-1] }}&nbsp;</th>
                        @foreach ($mesesAMostrar as $mes => $semanas)
                            @foreach ($semanas as $semana)
                                @php
                                    $valor = $contadoresSemanaPlanta2[$semana][$i];
                                    $total = $contadorTSPlanta2[$semana];
                                    $porcentaje = ($total != 0) ? number_format(($valor / $total) * 100, 2) : 0;
                                @endphp
                                <td class="semana semana{{ $semana }}">&nbsp;&nbsp;{{ $valor }}&nbsp;</td>
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
        <div class="table-responsive">
            <table BORDER id="myTable2">
                <thead>
                    <tr>
                        <th rowspan="2">Supervisor</th>
                        <th rowspan="2">Módulo</th>
                        @foreach ($mesesAMostrar as $mes => $semanas)
                            <th colspan="{{ count($semanas) }}" style="text-align: center;">{{ strtoupper($mes) }}</th>
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
                    @foreach($supervisoresPlanta2 as $supervisor)
                        <tr>
                            <td style="text-align: left">{{ $supervisor->nombre }}</td>
                            <td>{{ $supervisor->modulo }}</td>
                            @foreach ($mesesAMostrar as $mes => $semanas)
                                @foreach ($semanas as $semana)
                                    @php
                                        $produccion = $produccionPlanta2->where('supervisor_id', $supervisor->id)->where('semana', $semana)->first();
                                        $valorSemanal = $produccion ? $produccion->valor : '';
                                        $colorClass = ($valorSemanal != '') ? $colores[$valorSemanal - 1] : '';
                                        $extraValue = $produccion ? $produccion->te : 0;
                                    @endphp
                                    <td style="background-color: {{ $colorClass }};">
                                        @if($extraValue)
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

</body>

</html>
