<?php
//Esta es la funcion principal, de inicio lo que mostrara  cuando se accede ini
namespace App\Http\Controllers;
use App\Produccion1;
use App\Supervisor;
use App\ColoresMetas;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProduccionesMultiExport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use DateTime; // Asegúrate de importar esta clase
use Illuminate\Support\Facades\DB; // Importa la clase DB



class ProduccionMetasController extends Controller
{

    public function supervisorModulo()
    {
        $supervisoresPlanta1 = Supervisor::where('planta', 'Intimark1')->get();
        $supervisoresPlanta2 = Supervisor::where('planta', 'Intimark2')->get();
        return view('metas.supervisorModulo', compact('supervisoresPlanta1', 'supervisoresPlanta2'));
    }

    public function storeSupervisor(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'modulo' => 'required|string|max:255',
            'planta' => 'required|string',
        ]);

        // Verificar si ya existe un registro con el mismo nombre y módulo
        $existingSupervisor = Supervisor::where('nombre', $request->nombre)
                                        ->where('modulo', $request->modulo)
                                        ->first();

        if ($existingSupervisor) {
            return redirect()->route('metas.supervisorModulo')->with('error', 'Ya existe un supervisor con ese nombre y módulo.');
        }

        // Asignar el valor por defecto para 'estatus'
        $data = $request->all();
        $data['estatus'] = 'A';

        Supervisor::create($data);

        return redirect()->route('metas.supervisorModulo')->with('success', 'Supervisor agregado exitosamente.');
    }

    public function updateStatusSupervisor(Request $request, $id)
    {
        $supervisor = Supervisor::findOrFail($id);
        $supervisor->estatus = $request->estatus;
        $supervisor->save();

        return redirect()->route('metas.supervisorModulo')->with('success', 'Estatus del supervisor actualizado.');
    }


    public function registroSemanal()
    {
        $supervisoresPlanta1 = Supervisor::where('planta', 'Intimark1')->where('estatus', 'A')->get();
        $supervisoresPlanta2 = Supervisor::where('planta', 'Intimark2')->where('estatus', 'A')->get();
        $current_week = date('W'); // Obtener la semana actual
        //$current_week = 26;
        $current_month = date('F');
        //$currentYear = 2023;
        $currentYear = date('Y'); // Obtener el año actual

        $produccionPlanta1 = Produccion1::where('semana', $current_week)
            ->where('año', $currentYear) // Filtrar también por el año actual
            ->whereIn('supervisor_id', $supervisoresPlanta1->pluck('id'))
            ->get()->keyBy('supervisor_id');

        $produccionPlanta2 = Produccion1::where('semana', $current_week)
            ->where('año', $currentYear) // Filtrar también por el año actual
            ->whereIn('supervisor_id', $supervisoresPlanta2->pluck('id'))
            ->get()->keyBy('supervisor_id');

        return view('metas.registroSemanal', compact('supervisoresPlanta1', 'supervisoresPlanta2', 'produccionPlanta1', 'produccionPlanta2', 'current_week', 'current_month', 'currentYear'));
    }

    public function storeProduccion1(Request $request)
    {
        //$current_week = 26;
        $current_week = date('W'); // Obtener la semana actual
        //$currentYear = 2023;
        $currentYear = date('Y'); // Obtener el año actual

        foreach ($request->semanas as $supervisor_id => $data) {
            $te_value = isset($data['te']) ? 1 : 0; // Obtener el valor de 'te' o usar 0 si no está presente

            // Inicializamos el valor como nulo para comprobar si se seleccionó algún checkbox de semana
            $valor = null;

            // Iterar a través de los datos para encontrar el valor de la semana
            foreach ($data as $key => $value) {
                if ($key !== 'te' && is_numeric($value)) {
                    $valor = $value;
                }
            }

            // Si se ha seleccionado un valor para la semana, actualizamos/creamos el registro
            if ($valor !== null) {
                Produccion1::updateOrCreate(
                    ['supervisor_id' => $supervisor_id, 'semana' => $current_week, 'año' => $currentYear], // Incluir el año en la condición
                    ['te' => $te_value, 'valor' => $valor]
                );
            }
        }

        return redirect()->route('metas.registroSemanal')->with('success', 'Datos de producción actualizados correctamente.');
    }


    public function reporteGeneralMetas(Request $request) 
    {
        // Obtener la semana y el año actual
        $currentDate = new DateTime();
        $currentWeek = $currentDate->format("W");
        $currentYear = $currentDate->format("o");

        // Obtener la semana anterior
        $previousDate = clone $currentDate;
        $previousDate->modify('-1 week');
        $previousWeek = $previousDate->format("W");
        $previousYear = $previousDate->format("o");

        // Obtener los parámetros de la solicitud o establecer valores por defecto
        $startWeek = $request->input('start_week', "$previousYear-W$previousWeek");
        $endWeek = $request->input('end_week', "$currentYear-W$currentWeek");

        // Extraer el año y la semana de los parámetros
        $startYear = substr($startWeek, 0, 4);
        $startWeek = substr($startWeek, 6);

        $endYear = substr($endWeek, 0, 4);
        $endWeek = substr($endWeek, 6);


        // Filtrar producción por rango de semanas y años 
        $produccionPlanta1 = Produccion1::with('supervisor')
            ->whereHas('supervisor', function ($query) {
                $query->where('planta', 'Intimark1');
            })
            ->whereBetween(DB::raw("CONCAT(año, '-', LPAD(semana, 2, '0'))"), ["$startYear-$startWeek", "$endYear-$endWeek"])
            ->get();

        $produccionPlanta2 = Produccion1::with('supervisor')
            ->whereHas('supervisor', function ($query) {
                $query->where('planta', 'Intimark2');
            })
            ->whereBetween(DB::raw("CONCAT(año, '-', LPAD(semana, 2, '0'))"), ["$startYear-$startWeek", "$endYear-$endWeek"])
            ->get();

        // Filtrar supervisores que tienen registros en el rango de semanas seleccionadas
        $supervisoresPlanta1 = $produccionPlanta1->pluck('supervisor')->unique('id');
        $supervisoresPlanta2 = $produccionPlanta2->pluck('supervisor')->unique('id');

        $mesesAMostrar = $this->obtenerMeses($produccionPlanta1, $produccionPlanta2);

        $contadorTS = [];
        $contadorSuma = [];
        $contadoresSemana = [];
        $TcontadorSuma3 = [];
        $Tporcentajes3 = [];
        $TcontadorSuma = [];
        $Tporcentajes = [];

        $contadorTSPlanta2 = [];
        $contadorSumaPlanta2 = [];
        $contadoresSemanaPlanta2 = [];
        $TcontadorSuma3Planta2 = [];
        $Tporcentajes3Planta2 = [];
        $TcontadorSumaPlanta2 = [];
        $TporcentajesPlanta2 = [];

        $colores = ['#00B0F0', '#00B050', '#FFFF00', '#C65911', '#FF0000', '#A6A6A6', '#F9F9EB'];
        $titulos = [
            'CUMPLIMIENTO DE META JUEVES 7:00 P.M.',
            'CUMPLIMIENTO META VIERNES ANTES DE LAS 2:00 P.M ',
            'CUMPLIMIENTO META VIERNES 2:00 P.M.',
            'CUMPLIMIENTO META VIERNES DESPUES DE LAS 2:00 P.M. ',
            'NO CUPLIO META VIERNES 2:00 P.M. ,SIN APOYO TE',
            'NO CUMPLE META VIERNES 2:00 P.M., CON TE VIERNES Y SIN APOYO SABADO TE ',
            'SIN CUMPLIR META MOD ENTTO NO PARTICIPA EN PROGRAMA '
        ];

        foreach ($mesesAMostrar as $mes => $semanas) {
            foreach ($semanas as $semana) {
                $contadorTS[$semana] = 0;
                $contadorSuma[$semana] = 0;
                $TcontadorSuma3[$semana] = 0;
                $Tporcentajes3[$semana] = 0;
                $TcontadorSuma[$semana] = 0;
                $Tporcentajes[$semana] = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $contadoresSemana[$semana][$i] = 0;
                }

                $contadorTSPlanta2[$semana] = 0;
                $contadorSumaPlanta2[$semana] = 0;
                $TcontadorSuma3Planta2[$semana] = 0;
                $Tporcentajes3Planta2[$semana] = 0;
                $TcontadorSumaPlanta2[$semana] = 0;
                $TporcentajesPlanta2[$semana] = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $contadoresSemanaPlanta2[$semana][$i] = 0;
                }
            }
        }

        foreach ($produccionPlanta1 as $produccion) {
            $semana = $produccion->semana;
            $valor = $produccion->valor;
            $te = $produccion->te;

            $contadorTS[$semana] += 1;
            $contadorSuma[$semana] += $valor;
            $contadoresSemana[$semana][$valor] += 1;

            if ($te == 1) {
                $TcontadorSuma3[$semana] += 1;
            }
        }

        foreach ($produccionPlanta2 as $produccion) {
            $semana = $produccion->semana;
            $valor = $produccion->valor;
            $te = $produccion->te;

            $contadorTSPlanta2[$semana] += 1;
            $contadorSumaPlanta2[$semana] += $valor;
            $contadoresSemanaPlanta2[$semana][$valor] += 1;

            if ($te == 1) {
                $TcontadorSuma3Planta2[$semana] += 1;
            }
        }

        foreach ($mesesAMostrar as $mes => $semanas) {
            foreach ($semanas as $semana) {
                $total = $contadorTS[$semana];
                if ($total != 0) {
                    // Sumar filas 1, 2 y 3 para "Cumplimiento en Tiempo"
                    $TcontadorSuma3[$semana] = $contadoresSemana[$semana][1] + $contadoresSemana[$semana][2] + $contadoresSemana[$semana][3];
                    $Tporcentajes3[$semana] = number_format(($TcontadorSuma3[$semana] / $total) * 100, 2);

                    // Sumar filas 1, 2, 3 y 4 para "Cumplimiento con TE (Viernes)"
                    $TcontadorSuma[$semana] = $contadoresSemana[$semana][1] + $contadoresSemana[$semana][2] + $contadoresSemana[$semana][3] + $contadoresSemana[$semana][4];
                    $Tporcentajes[$semana] = number_format(($TcontadorSuma[$semana] / $total) * 100, 2);
                }

                $totalPlanta2 = $contadorTSPlanta2[$semana];
                if ($totalPlanta2 != 0) {
                    // Sumar filas 1, 2 y 3 para "Cumplimiento en Tiempo" en Planta 2
                    $TcontadorSuma3Planta2[$semana] = $contadoresSemanaPlanta2[$semana][1] + $contadoresSemanaPlanta2[$semana][2] + $contadoresSemanaPlanta2[$semana][3];
                    $Tporcentajes3Planta2[$semana] = number_format(($TcontadorSuma3Planta2[$semana] / $totalPlanta2) * 100, 2);

                    // Sumar filas 1, 2, 3 y 4 para "Cumplimiento con TE (Viernes)" en Planta 2
                    $TcontadorSumaPlanta2[$semana] = $contadoresSemanaPlanta2[$semana][1] + $contadoresSemanaPlanta2[$semana][2] + $contadoresSemanaPlanta2[$semana][3] + $contadoresSemanaPlanta2[$semana][4];
                    $TporcentajesPlanta2[$semana] = number_format(($TcontadorSumaPlanta2[$semana] / $totalPlanta2) * 100, 2);
                }
            }
        }

        return view('metas.ReporteGeneralMetas', compact(
            'mesesAMostrar',
            'contadorTS',
            'contadorSuma',
            'contadoresSemana',
            'TcontadorSuma3',
            'Tporcentajes3',
            'TcontadorSuma',
            'Tporcentajes',
            'contadorTSPlanta2',
            'contadorSumaPlanta2',
            'contadoresSemanaPlanta2',
            'TcontadorSuma3Planta2',
            'Tporcentajes3Planta2',
            'TcontadorSumaPlanta2',
            'TporcentajesPlanta2',
            'produccionPlanta1',
            'produccionPlanta2',
            'supervisoresPlanta1',
            'supervisoresPlanta2',
            'colores',
            'titulos'
        ));
    }

    private function obtenerMeses($produccionPlanta1, $produccionPlanta2)
    {
        // Combina los resultados de las dos plantas
        $produccion1 = $produccionPlanta1->concat($produccionPlanta2);

        // Crear un array para almacenar los meses y las semanas
        $mesesAMostrar = [];

        // Recorrer todos los registros
        foreach ($produccion1 as $produccion) {
            // Obtener la semana y el año del registro
            $semana = $produccion->semana;
            $año = $produccion->año;

            // Convertir la semana y el año a una fecha
            $fecha = new DateTime();
            $fecha->setISODate($año, $semana);

            // Obtener el mes de la fecha
            $mes = $fecha->format('F') . ' - ' . $año;

            // Añadir la semana al mes correspondiente en el array
            if (!isset($mesesAMostrar[$mes])) {
                $mesesAMostrar[$mes] = [];
            }
            if (!in_array($semana, $mesesAMostrar[$mes])) {
                $mesesAMostrar[$mes][] = $semana;
            }
        }

        // Ordenar las semanas dentro de cada mes de menor a mayor
        foreach ($mesesAMostrar as $mes => $semanas) {
            sort($semanas, SORT_NUMERIC);
            $mesesAMostrar[$mes] = $semanas; // Asegurar que se reasignan las semanas ordenadas
        }

        // Ordenar los meses cronológicamente
        uksort($mesesAMostrar, function($a, $b) {
            $dateA = DateTime::createFromFormat('F - Y', $a);
            $dateB = DateTime::createFromFormat('F - Y', $b);
            return $dateA <=> $dateB;
        });

        // Devolver el array de meses y semanas
        return $mesesAMostrar;
    }

    public function tablaPDF(Request $request)
    {
        // Obtener la semana y el año actual
        $currentDate = new DateTime();
        $currentWeek = $currentDate->format("W");
        $currentYear = $currentDate->format("o");

        // Obtener la semana anterior
        $previousDate = clone $currentDate;
        $previousDate->modify('-1 week');
        $previousWeek = $previousDate->format("W");
        $previousYear = $previousDate->format("o");

        // Obtener los parámetros de la solicitud o establecer valores por defecto
        $startWeek = $request->input('start_week', "$previousYear-W$previousWeek");
        $endWeek = $request->input('end_week', "$currentYear-W$currentWeek");

        // Extraer el año y la semana de los parámetros
        $startYear = substr($startWeek, 0, 4);
        $startWeek = substr($startWeek, 6);

        $endYear = substr($endWeek, 0, 4);
        $endWeek = substr($endWeek, 6);

        // Filtrar producción por rango de semanas y años
        $produccionPlanta1 = Produccion1::with('supervisor')
            ->whereHas('supervisor', function ($query) {
                $query->where('planta', 'Intimark1');
            })
            ->whereBetween(DB::raw("CONCAT(año, '-', LPAD(semana, 2, '0'))"), ["$startYear-$startWeek", "$endYear-$endWeek"])
            ->get();

        $produccionPlanta2 = Produccion1::with('supervisor')
            ->whereHas('supervisor', function ($query) {
                $query->where('planta', 'Intimark2');
            })
            ->whereBetween(DB::raw("CONCAT(año, '-', LPAD(semana, 2, '0'))"), ["$startYear-$startWeek", "$endYear-$endWeek"])
            ->get();

        //
        // Filtrar supervisores que tienen registros en el rango de semanas seleccionadas
        $supervisoresPlanta1 = $produccionPlanta1->pluck('supervisor')->unique('id');
        $supervisoresPlanta2 = $produccionPlanta2->pluck('supervisor')->unique('id');
        $mesesAMostrar = $this->obtenerMeses($produccionPlanta1, $produccionPlanta2);

        $contadorTS = [];
        $contadorSuma = [];
        $contadoresSemana = [];
        $TcontadorSuma3 = [];
        $Tporcentajes3 = [];
        $TcontadorSuma = [];
        $Tporcentajes = [];

        $contadorTSPlanta2 = [];
        $contadorSumaPlanta2 = [];
        $contadoresSemanaPlanta2 = [];
        $TcontadorSuma3Planta2 = [];
        $Tporcentajes3Planta2 = [];
        $TcontadorSumaPlanta2 = [];
        $TporcentajesPlanta2 = [];

        $colores = ['#00B0F0', '#00B050', '#FFFF00', '#C65911', '#FF0000', '#A6A6A6', '#F9F9EB'];
        $titulos = [
            'CUMPLIMIENTO DE META JUEVES 7:00 P.M.',
            'CUMPLIMIENTO META VIERNES ANTES DE LAS 2:00 P.M ',
            'CUMPLIMIENTO META VIERNES 2:00 P.M.',
            'CUMPLIMIENTO META VIERNES DESPUES DE LAS 2:00 P.M. ',
            'NO CUPLIO META VIERNES 2:00 P.M. ,SIN APOYO TE',
            'NO CUMPLE META VIERNES 2:00 P.M., CON TE VIERNES Y SIN APOYO SABADO TE ',
            'SIN CUMPLIR META MOD ENTTO NO PARTICIPA EN PROGRAMA '
        ];

        foreach ($mesesAMostrar as $mes => $semanas) {
            foreach ($semanas as $semana) {
                $contadorTS[$semana] = 0;
                $contadorSuma[$semana] = 0;
                $TcontadorSuma3[$semana] = 0;
                $Tporcentajes3[$semana] = 0;
                $TcontadorSuma[$semana] = 0;
                $Tporcentajes[$semana] = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $contadoresSemana[$semana][$i] = 0;
                }

                $contadorTSPlanta2[$semana] = 0;
                $contadorSumaPlanta2[$semana] = 0;
                $TcontadorSuma3Planta2[$semana] = 0;
                $Tporcentajes3Planta2[$semana] = 0;
                $TcontadorSumaPlanta2[$semana] = 0;
                $TporcentajesPlanta2[$semana] = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $contadoresSemanaPlanta2[$semana][$i] = 0;
                }
            }
        }

        foreach ($produccionPlanta1 as $produccion) {
            $semana = $produccion->semana;
            $valor = $produccion->valor;
            $te = $produccion->te;

            $contadorTS[$semana] += 1;
            $contadorSuma[$semana] += $valor;
            $contadoresSemana[$semana][$valor] += 1;

            if ($te == 1) {
                $TcontadorSuma3[$semana] += 1;
            }
        }

        foreach ($produccionPlanta2 as $produccion) {
            $semana = $produccion->semana;
            $valor = $produccion->valor;
            $te = $produccion->te;

            $contadorTSPlanta2[$semana] += 1;
            $contadorSumaPlanta2[$semana] += $valor;
            $contadoresSemanaPlanta2[$semana][$valor] += 1;

            if ($te == 1) {
                $TcontadorSuma3Planta2[$semana] += 1;
            }
        }

        foreach ($mesesAMostrar as $mes => $semanas) {
            foreach ($semanas as $semana) {
                $total = $contadorTS[$semana];
                if ($total != 0) {
                    // Sumar filas 1, 2 y 3 para "Cumplimiento en Tiempo"
                    $TcontadorSuma3[$semana] = $contadoresSemana[$semana][1] + $contadoresSemana[$semana][2] + $contadoresSemana[$semana][3];
                    $Tporcentajes3[$semana] = number_format(($TcontadorSuma3[$semana] / $total) * 100, 2);

                    // Sumar filas 1, 2, 3 y 4 para "Cumplimiento con TE (Viernes)"
                    $TcontadorSuma[$semana] = $contadoresSemana[$semana][1] + $contadoresSemana[$semana][2] + $contadoresSemana[$semana][3] + $contadoresSemana[$semana][4];
                    $Tporcentajes[$semana] = number_format(($TcontadorSuma[$semana] / $total) * 100, 2);
                }

                $totalPlanta2 = $contadorTSPlanta2[$semana];
                if ($totalPlanta2 != 0) {
                    // Sumar filas 1, 2 y 3 para "Cumplimiento en Tiempo" en Planta 2
                    $TcontadorSuma3Planta2[$semana] = $contadoresSemanaPlanta2[$semana][1] + $contadoresSemanaPlanta2[$semana][2] + $contadoresSemanaPlanta2[$semana][3];
                    $Tporcentajes3Planta2[$semana] = number_format(($TcontadorSuma3Planta2[$semana] / $totalPlanta2) * 100, 2);

                    // Sumar filas 1, 2, 3 y 4 para "Cumplimiento con TE (Viernes)" en Planta 2
                    $TcontadorSumaPlanta2[$semana] = $contadoresSemanaPlanta2[$semana][1] + $contadoresSemanaPlanta2[$semana][2] + $contadoresSemanaPlanta2[$semana][3] + $contadoresSemanaPlanta2[$semana][4];
                    $TporcentajesPlanta2[$semana] = number_format(($TcontadorSumaPlanta2[$semana] / $totalPlanta2) * 100, 2);
                }
            }
        }

        // Generar el PDF utilizando la vista Blade
        $pdf = PDF::loadView('metas.tablaPDF', compact('mesesAMostrar',
            'contadorTS', 'contadorSuma','contadoresSemana','TcontadorSuma3',
            'Tporcentajes3', 'TcontadorSuma', 'Tporcentajes', 'contadorTSPlanta2',
            'contadorSumaPlanta2', 'contadoresSemanaPlanta2', 'TcontadorSuma3Planta2',
            'Tporcentajes3Planta2', 'TcontadorSumaPlanta2', 'TporcentajesPlanta2',
            'produccionPlanta1', 'produccionPlanta2', 'supervisoresPlanta1',
            'supervisoresPlanta2', 'colores', 'titulos'
        ))->setPaper('letter', 'landscape');

        return $pdf->download('Tabla-General-Planta1.pdf');
    }

    public function tabla2PDF(Request $request)
    {
        // Obtener la semana y el año actual
        $currentDate = new DateTime();
        $currentWeek = $currentDate->format("W");
        $currentYear = $currentDate->format("o");

        // Obtener la semana anterior
        $previousDate = clone $currentDate;
        $previousDate->modify('-1 week');
        $previousWeek = $previousDate->format("W");
        $previousYear = $previousDate->format("o");

        // Obtener los parámetros de la solicitud o establecer valores por defecto
        $startWeek = $request->input('start_week', "$previousYear-W$previousWeek");
        $endWeek = $request->input('end_week', "$currentYear-W$currentWeek");

        // Extraer el año y la semana de los parámetros
        $startYear = substr($startWeek, 0, 4);
        $startWeek = substr($startWeek, 6);

        $endYear = substr($endWeek, 0, 4);
        $endWeek = substr($endWeek, 6);

        // Filtrar producción por rango de semanas y años
        $produccionPlanta1 = Produccion1::with('supervisor')
            ->whereHas('supervisor', function ($query) {
                $query->where('planta', 'Intimark1');
            })
            ->whereBetween(DB::raw("CONCAT(año, '-', LPAD(semana, 2, '0'))"), ["$startYear-$startWeek", "$endYear-$endWeek"])
            ->get();

        $produccionPlanta2 = Produccion1::with('supervisor')
            ->whereHas('supervisor', function ($query) {
                $query->where('planta', 'Intimark2');
            })
            ->whereBetween(DB::raw("CONCAT(año, '-', LPAD(semana, 2, '0'))"), ["$startYear-$startWeek", "$endYear-$endWeek"])
            ->get();

        // Filtrar supervisores que tienen registros en el rango de semanas seleccionadas
        $supervisoresPlanta1 = $produccionPlanta1->pluck('supervisor')->unique('id');
        $supervisoresPlanta2 = $produccionPlanta2->pluck('supervisor')->unique('id');

        $mesesAMostrar = $this->obtenerMeses($produccionPlanta1, $produccionPlanta2);

        $contadorTS = [];
        $contadorSuma = [];
        $contadoresSemana = [];
        $TcontadorSuma3 = [];
        $Tporcentajes3 = [];
        $TcontadorSuma = [];
        $Tporcentajes = [];

        $contadorTSPlanta2 = [];
        $contadorSumaPlanta2 = [];
        $contadoresSemanaPlanta2 = [];
        $TcontadorSuma3Planta2 = [];
        $Tporcentajes3Planta2 = [];
        $TcontadorSumaPlanta2 = [];
        $TporcentajesPlanta2 = [];

        $colores = ['#00B0F0', '#00B050', '#FFFF00', '#C65911', '#FF0000', '#A6A6A6', '#F9F9EB'];
        $titulos = [
            'CUMPLIMIENTO DE META JUEVES 7:00 P.M.',
            'CUMPLIMIENTO META VIERNES ANTES DE LAS 2:00 P.M ',
            'CUMPLIMIENTO META VIERNES 2:00 P.M.',
            'CUMPLIMIENTO META VIERNES DESPUES DE LAS 2:00 P.M. ',
            'NO CUPLIO META VIERNES 2:00 P.M. ,SIN APOYO TE',
            'NO CUMPLE META VIERNES 2:00 P.M., CON TE VIERNES Y SIN APOYO SABADO TE ',
            'SIN CUMPLIR META MOD ENTTO NO PARTICIPA EN PROGRAMA '
        ];

        foreach ($mesesAMostrar as $mes => $semanas) {
            foreach ($semanas as $semana) {
                $contadorTS[$semana] = 0;
                $contadorSuma[$semana] = 0;
                $TcontadorSuma3[$semana] = 0;
                $Tporcentajes3[$semana] = 0;
                $TcontadorSuma[$semana] = 0;
                $Tporcentajes[$semana] = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $contadoresSemana[$semana][$i] = 0;
                }

                $contadorTSPlanta2[$semana] = 0;
                $contadorSumaPlanta2[$semana] = 0;
                $TcontadorSuma3Planta2[$semana] = 0;
                $Tporcentajes3Planta2[$semana] = 0;
                $TcontadorSumaPlanta2[$semana] = 0;
                $TporcentajesPlanta2[$semana] = 0;
                for ($i = 1; $i <= 7; $i++) {
                    $contadoresSemanaPlanta2[$semana][$i] = 0;
                }
            }
        }

        foreach ($produccionPlanta1 as $produccion) {
            $semana = $produccion->semana;
            $valor = $produccion->valor;
            $te = $produccion->te;

            $contadorTS[$semana] += 1;
            $contadorSuma[$semana] += $valor;
            $contadoresSemana[$semana][$valor] += 1;

            if ($te == 1) {
                $TcontadorSuma3[$semana] += 1;
            }
        }

        foreach ($produccionPlanta2 as $produccion) {
            $semana = $produccion->semana;
            $valor = $produccion->valor;
            $te = $produccion->te;

            $contadorTSPlanta2[$semana] += 1;
            $contadorSumaPlanta2[$semana] += $valor;
            $contadoresSemanaPlanta2[$semana][$valor] += 1;

            if ($te == 1) {
                $TcontadorSuma3Planta2[$semana] += 1;
            }
        }

        foreach ($mesesAMostrar as $mes => $semanas) {
            foreach ($semanas as $semana) {
                $total = $contadorTS[$semana];
                if ($total != 0) {
                    // Sumar filas 1, 2 y 3 para "Cumplimiento en Tiempo"
                    $TcontadorSuma3[$semana] = $contadoresSemana[$semana][1] + $contadoresSemana[$semana][2] + $contadoresSemana[$semana][3];
                    $Tporcentajes3[$semana] = number_format(($TcontadorSuma3[$semana] / $total) * 100, 2);

                    // Sumar filas 1, 2, 3 y 4 para "Cumplimiento con TE (Viernes)"
                    $TcontadorSuma[$semana] = $contadoresSemana[$semana][1] + $contadoresSemana[$semana][2] + $contadoresSemana[$semana][3] + $contadoresSemana[$semana][4];
                    $Tporcentajes[$semana] = number_format(($TcontadorSuma[$semana] / $total) * 100, 2);
                }

                $totalPlanta2 = $contadorTSPlanta2[$semana];
                if ($totalPlanta2 != 0) {
                    // Sumar filas 1, 2 y 3 para "Cumplimiento en Tiempo" en Planta 2
                    $TcontadorSuma3Planta2[$semana] = $contadoresSemanaPlanta2[$semana][1] + $contadoresSemanaPlanta2[$semana][2] + $contadoresSemanaPlanta2[$semana][3];
                    $Tporcentajes3Planta2[$semana] = number_format(($TcontadorSuma3Planta2[$semana] / $totalPlanta2) * 100, 2);

                    // Sumar filas 1, 2, 3 y 4 para "Cumplimiento con TE (Viernes)" en Planta 2
                    $TcontadorSumaPlanta2[$semana] = $contadoresSemanaPlanta2[$semana][1] + $contadoresSemanaPlanta2[$semana][2] + $contadoresSemanaPlanta2[$semana][3] + $contadoresSemanaPlanta2[$semana][4];
                    $TporcentajesPlanta2[$semana] = number_format(($TcontadorSumaPlanta2[$semana] / $totalPlanta2) * 100, 2);
                }
            }
        }

        // Generar el PDF utilizando la vista Blade
        $pdf = PDF::loadView('metas.tabla2PDF', compact(
            'startYear', 'startWeek', 'endYear', 'endWeek',
            'mesesAMostrar', 'contadorTSPlanta2', 'contadorSumaPlanta2', 'contadoresSemanaPlanta2',
            'TcontadorSuma3Planta2', 'Tporcentajes3Planta2', 'TcontadorSumaPlanta2', 'TporcentajesPlanta2',
            'colores', 'titulos', 'supervisoresPlanta2', 'produccionPlanta2'
        ))->setPaper('letter', 'landscape');

        return $pdf->download('Tabla-General-Planta2.pdf');
    }

}
