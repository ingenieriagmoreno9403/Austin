<!DOCTYPE html>
@foreach ($obtenerempleado as $emple)
    @php
        $empleado = $emple->Nombre;
        $puesto = strtoupper($emple->puesto);
        $sexo = $emple->sexo == 'F' ? 'FEMENINO' : 'MASCULINO';
        $estado_civil = $emple->estado_civil;
        $curp = strtoupper($emple->curp);
        $rfc = strtoupper($emple->rfc);
        $nss = $emple->nss;
        $telefono = $emple->telefono;
        $direccion = strtoupper($emple->direccionEmp);
        $empresa = strtoupper($emple->nombre_empresa);
    @endphp
    @break
@endforeach

<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>CONTRATO DETERMINADO {{ $empresa }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            text-align: justify;
        }

        p {
            margin: 0 0 8px;
            text-align: justify;
        }

        .titulo {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 12px;
        }

        .centrado {
            text-align: center;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 12px 0 10px;
        }

        .campo {
            font-weight: bold;
            text-decoration: underline;
        }

        .page-break {
            page-break-after: always;
        }

        .firmas {
            width: 100%;
            margin-top: 28px;
            font-size: 10px;
            text-align: center;
        }

        .firmas td {
            width: 50%;
            vertical-align: top;
            padding-top: 8px;
        }

        .linea-firma {
            display: inline-block;
            width: 80%;
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 4px;
        }

        @page {
            margin: 70px 80px;
        }
    </style>
</head>
<body>

{{-- Página 1 --}}
<div>
    <div class="titulo">CONTRATO INDIVIDUAL DE TRABAJO POR TIEMPO DETERMINADO</div>

    <p>
        CONTRATO INDIVIDUAL DE TRABAJO POR TIEMPO DETERMINADO QUE CELEBRAN POR UNA PARTE LA
        FUENTE DE TRABAJO DENOMINADA {{ $empresa }}, Y QUE EN ESTE ACTO ES REPRESENTADA POR LA
        LIC. CYNTHIA GUIJARRO MARTINEZ, QUIEN ACREDITARÁ SU PERSONALIDAD EN EL APARTADO
        CORRESPONDIENTE; Y POR LA OTRA, EL C. <span class="campo">{{ $empleado }}</span>; DE
        CONFORMIDAD CON LAS SIGUIENTES DECLARACIONES Y CLÁUSULAS:
    </p>

    <div class="centrado">D E C L A R A C I O N E S</div>

    <p>
        01.- Manifiestan ambas partes que se reconocen expresamente la personalidad con la que se ostentan, para
        todos los efectos legales a que haya lugar.
    </p>

    <p>
        02.- Manifiestan los contratantes que están conformes que en lo sucesivo y para comprensión de ambos se
        denomine a la parte patronal {{ $empresa }} como LA EMPRESA, a la parte trabajadora
        <span class="campo">{{ $empleado }}</span> como EL TRABAJADOR, a ambos como LAS PARTES y al presente
        instrumento como EL CONTRATO. Así mismo coinciden en que al Gerente General, Supervisor y Jefe de
        Cuadrilla y ya sea que actúen conjunta o separadamente, se les denomine PERSONAL DIRECTIVO.
    </p>

    <p>
        03.- EL TRABAJADOR, bajo protesta de decir verdad, declara lo siguiente: ser de nacionalidad mexicana, con
        fecha de nacimiento del <span class="campo">{{ $fechaNacimientoFmt }}</span>, sexo <span class="campo">{{ $sexo }}</span> estado civil
        <span class="campo">{{ strtoupper($estado_civil) }}</span>, con domicilio particular en
        <span class="campo">{{ $direccion }}</span>., con número telefónico personal <span class="campo">{{ $telefono }}</span>,
        CURP <span class="campo">{{ $curp }}</span>, RFC <span class="campo">{{ $rfc }}</span> y Número de Seguridad Social
        <span class="campo">{{ $nss }}</span>; y en este acto pone a la vista de los que intervienen en EL CONTRATO el
        documento que lo identifica, y que consiste en Credencial para Votar expedida por el Instituto Federal Electoral
        con número de registro _________________ y la cual le es devuelta una vez confirmados los rasgos físicos de
        la foto que aparece en el documento con los DEL TRABAJADOR y deja copia simple para que obre agregada al
        CONTRATO.
    </p>

    <p>
        04.- LA EMPRESA, bajo protesta de decir verdad, manifiesta: que es una Sociedad de tipo SOCIEDAD
        ANÓNIMA DE CAPITAL VARIABLE, cuyo único Domicilio Social es el ubicado en C. El Roble número 2137 Col.
        Brittingham en Gómez Palacio, Durango; tener la Denominación y Razón Social ya citada; y en este acto pone a
        la vista de los que intervienen en la presente, los documentos que acreditan su legal existencia y cuyos datos de
        identificación se transcriben a continuación: A) Escritura Pública de fecha 16 de Marzo del 2000 pasada ante la
        Fe del Notario Público No 05 en ejercicio en esta ciudad, C. Lic. Sergio Estrella Ochoa, bajo el número 3813 del
        libro 94 de escrituras públicas. Así mismo, la C. CYNTHIA GUIJARRO MARTINEZ, cuya legal
    </p>
</div>

<div class="page-break"></div>

{{-- Página 2 --}}
<div>
    <p>
        representación se desprende de la lectura de la Escritura Pública de fecha 19 de Abril del 2022 pasada
        ante la Fe del Notario Público No 03 en ejercicio de esta ciudad, C. Lic. Octaviano Rendón Arce, bajo el
        número 47804 del volumen 1945, y se identifica en este acto ante EL TRABAJADOR y los que
        intervienen en la presente, mediante Credencial para Votar expedida por el Instituto Nacional Electoral
        con número de registro 1183082896084, y la cual le es devuelta una vez confirmados los rasgos físicos
        de la foto que aparece en el documento, con los del Representante Legal de LA EMPRESA.
    </p>

    <p>
        05.- EL TRABAJADOR manifiesta que tiene la capacidad, aptitudes y facultades necesarias para desempeñar las
        actividades que le encomienda LA EMPRESA, así como la habilidad para asimilar las capacitaciones que brinda
        la empresa.
    </p>

    <p>
        06.- LA EMPRESA declara que requiere los servicios de personal apto para el desarrollo de su objeto social, y
        que son: obra de ingeniería y mantenimiento social.
    </p>

    <p>
        07.- LAS PARTES están conformes en celebrar EL CONTRATO y en plasmar las condiciones generales de
        trabajo sobre las cuales se desarrollará el mismo, sujetándose a lo establecido en las siguientes:
    </p>

    <div class="centrado">C L Á U S U L A S</div>

    <p>
        PRIMERA.- EL CONTRATO, de conformidad con lo establecido en el artículo 37 de LA LEY, se celebra por
        TIEMPO DETERMINADO siendo dicha temporalidad la relativa al servicio de planeación, administración,
        ejecución y control de las actividades relacionadas con los servicios de Instalación y Proyectos. Dicho Contrato
        tendrá una duración contada a partir del día <span class="campo">{{ $fechaInicioFmt }}</span> al
        <span class="campo">{{ $fechaFinFmt }}</span>, permaneciendo mientras la materia de trabajo exista; pudiendo ampliarse,
        modificarse, rescindirse y terminarse en los casos y condiciones especificados en EL CONTRATO o en LA LEY.
    </p>

    <p>
        SEGUNDA.- EL TRABAJADOR se obliga a prestar sus servicios personales bajo la dirección, dependencia,
        subordinación, coordinación y supervisión de LA EMPRESA a través del PERSONAL DIRECTIVO que la misma
        designe con los cargos de Gerente General, Supervisor y Jefe de Cuadrilla. Dichos servicios los desempeñará en
        el puesto de <span class="campo">{{ $puesto }}</span> y consistirán en:
    </p>

    <p>A.- Asistencia y ayuda en general al Jefe de Departamento.</p>
    <p>B.- Sujetarse a los horarios que le sean asignados, mismos que podrán ser modificados, de acuerdo a las
        necesidades de LA EMPRESA.</p>
    <p>C.- Cumplir y velar por el cumplimiento del Reglamento Interno de Trabajo, mismo que se adjunta al CONTRATO
        como Anexo I.</p>
    <p>D.- Tomar las capacitaciones que LA EMPRESA le indique.</p>

    <p>
        TERCERA.- Los servicios se estipulan en forma enunciativa y no limitativa, por tanto, EL TRABAJADOR se
        obliga a desempeñar todas las labores anexas o conexas con su obligación principal y las demás que le ordene
        LA EMPRESA por conducto de su PERSONAL DIRECTIVO, siempre que sean compatibles con sus fuerzas y/o
        aptitudes y no impliquen menoscabo del salario pactado.
    </p>

    <p>
        CUARTA.- LAS PARTES convienen en que los servicios objeto del CONTRATO se prestarán en el lugar o en los
        lugares señalados por LA EMPRESA, y que la última podrá cambiar el lugar o los lugares de la prestación del
        servicio cuando las circunstancias así lo requieran o lo estime pertinente, salvando en todo momento que tales
        cambios no impliquen erogaciones o un menoscabo al salario e ingresos del TRABAJADOR.
    </p>
</div>

<div class="page-break"></div>

{{-- Página 3 --}}
<div>
    <p>
        QUINTA.- La desobediencia del TRABAJADOR a las órdenes o indicaciones del PERSONAL DIRECTIVO de LA
        EMPRESA, para el cumplimiento del trabajo contratado, será causal de rescisión sin responsabilidad para LA
        EMPRESA.
    </p>

    <p>
        SEXTA.- EL TRABAJADOR deberá atender las normas de seguridad en todo momento en que preste sus
        servicios para LA EMPRESA, haciendo el uso adecuado de los medios que la empresa le proporciona.
    </p>

    <p>
        SÉPTIMA.- LAS PARTES convienen, en apego al artículo 59 de la LEY, que la duración de la jornada de trabajo
        dentro de las instalaciones de la empresa será de 48 (cuarenta y ocho) horas a la semana, distribuidas de lunes a
        Viernes en un horario de 08:00 a 18:00 hrs, con un tiempo de hora y media para tomar sus alimentos, y los días
        sábados de 08:00 a 13:00 hrs. Especificando que en trabajo de campo los horarios se verían afectados y
        modificados de acuerdo a las necesidades de un tercero.
    </p>

    <p>
        OCTAVA.- Cuando la Jornada de Trabajo pactada deba prolongarse por circunstancias especiales, EL
        TRABAJADOR se obliga a laborar el tiempo extra necesario, en los términos de los artículos 65, 66, 67 y 68 de
        LA LEY, en la inteligencia de que sólo se considerarán y pagarán como horas extraordinarias las que excedan de
        48 horas a la semana a menos que se tratare de jornadas distintas a la diurna, y las mismas hayan sido
        autorizadas por escrito por LA EMPRESA y dicha autorización esté firmada por el PERSONAL DIRECTIVO.
        Cuando la jornada se prolongue por siniestro o riesgo inminente, EL TRABAJADOR tendrá derecho a un salario
        igual al que le corresponde por las horas de labor ordinarias.
    </p>

    <p>
        NOVENA.- EL TRABAJADOR se obliga a utilizar los registros de asistencia que LA EMPRESA establezca para
        debida constancia de las horas de entrada y de salida de sus labores, el incumplimiento de este requisito indicará
        falta injustificada para todos los efectos legales a que haya lugar.
    </p>

    <p>
        DÉCIMA.- EL TRABAJADOR se obliga a desempeñar sus labores con la intensidad, cuidado y esmero
        apropiados, en la forma, tiempo y lugar a que se refiere este CONTRATO. El incumplimiento de esta disposición
        se considerará falta de probidad del TRABAJADOR y se sancionará con la rescisión del contrato sin
        responsabilidad para LA EMPRESA, en los términos de la LEY.
    </p>

    <p>
        DÉCIMA PRIMERA.- LA EMPRESA ofrece pagar al TRABAJADOR un salario de
        <span class="campo">${{ number_format($salario_semanal, 2) }} ({{ $salario_semanal_letras }} PESOS)</span>, pagaderos en una sola emisión los días sábado. El citado pago incluye
        séptimo día y días festivos, por lo que se deduce que el salario del trabajador para todos los efectos legales es
        de <span class="campo">${{ number_format($salario_diario, 2) }}</span> pesos diarios y de
        <span class="campo">${{ number_format($salario_hora, 2) }}</span> por hora laborada, menos las deducciones a que se refiere el
        artículo 110 de LA LEY. EL TRABAJADOR solicita y LA EMPRESA acepta, que el salario y demás prestaciones
        sean pagados por conducto de una EMPRESA bancaria, a efecto de proteger sus remuneraciones contra robo.
    </p>
</div>

<div class="page-break"></div>

{{-- Página 4 --}}
<div>
    <p>
        DÉCIMA SEGUNDA.- Convienen LAS PARTES, de conformidad con la fracción I del artículo 110 de LA LEY, que
        los descuentos al salario del TRABAJADOR ante eventuales deudas contraídas con LA EMPRESA, pagos en
        exceso, errores, pérdidas, averías o servicios proporcionados por la misma, sean del 20% del salario diario del
        trabajador.
    </p>

    <p>
        DÉCIMA TERCERA.- En apego a los artículos 69 y 71 de LA LEY, EL TRABAJADOR gozará de un día de
        descanso por cada seis días de trabajo, conviniendo LAS PARTES en que dicho día será el domingo de cada
        semana. Y convienen igualmente en que si por circunstancias especiales LA EMPRESA tuviera la necesidad de
        cambiar el día de descanso semanal, EL TRABAJADOR disfrutará del pago de la prima dominical a que se
        refiere LA LEY, siempre mediante orden por escrito firmada por el PERSONAL DIRECTIVO.
    </p>

    <p>
        DÉCIMA CUARTA.- LAS PARTES acuerdan y EL TRABAJADOR otorga su consentimiento expreso para que la
        EMPRESA pueda cambiar el horario, la jornada, el día de descanso semanal, la ubicación del lugar o lugares de
        trabajo, cuando las necesidades de LA EMPRESA lo ameriten, previo aviso por escrito con diez días de
        anticipación.
    </p>

    <p>
        DÉCIMA QUINTA. En apego a lo dispuesto por el artículo 74 de LA LEY, serán días de descanso obligatorio los
        siguientes: 1º de enero, el primer lunes de Febrero en conmemoración del día 5 de febrero, el tercer lunes de
        Marzo en conmemoración del día 21 de marzo, 1º de mayo, 16 de septiembre, el tercer lunes de Noviembre en
        conmemoración del 20 de noviembre y 25 de diciembre, así como el 1º de diciembre de cada seis años, cuando
        corresponda a la transmisión del Poder Ejecutivo Federal.
    </p>

    <p>
        DÉCIMA SÉXTA.- En lo relativo a Vacaciones y Prima Vacacional, LAS PARTES se sujetan a lo estipulado por
        LA LEY en el Capítulo IV del Título III y en específico lo señalado en: ARTÍCULO 76.- Los trabajadores que
        tengan más de un año de servicios disfrutarán de un período anual de vacaciones pagadas, que en ningún caso
        podrá ser inferior a doce días laborables, y que aumentará en dos días laborables, hasta llegar a veinte, por cada
        año subsecuente de servicios. Después del sexto año, el período de vacaciones aumentará en dos días por cada
        cinco de servicios. ARTÍCULO 77.- Los trabajadores que presten servicios discontinuos y los de temporada
        tendrán derecho a un período anual de vacaciones, en proporción al número de días de trabajos en el año.
    </p>

    <p>
        DÉCIMA SEPTIMA.- LA EMPRESA ofrece pagar AL TRABAJADOR el monto que por aguinaldo establece el
        artículo 87 de LA LEY o la parte proporcional al tiempo laborado, que para tal efecto se calculará en 1.25 días de
        salario por cada mes o fracción laborado.
    </p>
</div>

<div class="page-break"></div>

{{-- Página 5 --}}
<div>
    <p>
        DÉCIMA OCTAVA. LA EMPRESA proporcionará AL TRABAJADOR las herramientas de trabajo necesarias para
        el desempeño de sus labores y quedarán a su más estricta guarda y cuidado durante el periodo de duración del
        CONTRATO. En ningún momento EL TRABAJADOR estará autorizado para emplear máquinas o herramientas
        propias y/o ajenas a LA EMPRESA en el desempeño de sus labores, por lo que la misma quedará relevada de
        pagos por pérdidas, daños o destrucción de máquinas y herramientas que indebidamente sean introducidas por
        EL TRABAJADOR.
    </p>

    <p>
        DECIMA NOVENA.- LA EMPRESA se obliga a reintegrar AL TRABAJADOR los gastos que el mismo erogue al
        laborar en localidades y regiones que se encuentren a 100 o más kilómetros del domicilio de LA EMPRESA por
        los siguientes conceptos: 1) hospedaje, 2) comidas, 3) lavandería y 4) pasajes foráneos. EL TRABAJADOR se
        obliga a realizar las comprobaciones de gastos a más tardar a los 7 días naturales posteriores a haber retornado
        de la zona o zonas foráneas, exhibiendo los comprobantes fiscales o documentos que respalden las erogaciones
        por los conceptos numerados. Cuando los gastos sean depositados de manera anticipada por LA EMPRESA,
        deberá EL TRABAJADOR reintegrar las cantidades que excedan a las erogaciones realizadas en el mismo plazo
        de 7 días señalados para la comprobación de gastos.
    </p>

    <p>
        VIGÉSIMA.- EL TRABAJADOR reconoce a LA EMPRESA como su único patrón y como la única responsable de
        las obligaciones materia del CONTRATO, por lo que se compromete a abstenerse de realizar reclamaciones a
        título personal relacionadas con EL CONTRATO a PERSONAL DIRECTIVO como a clientes y contratantes del
        servicio que brinda LA EMPRESA.
    </p>

    <p>
        VIGÉSIMA PRIMERA. El CONTRATO podrá prorrogarse cuando la subsistencia de la materia de trabajo así lo
        exija y EL TRABAJADOR sea conforme en continuar la prestación del servicio.
    </p>

    <p>
        VIGÉSIMA SEGUNDA. Serán consideradas faltas de probidad y darán lugar a la rescisión del CONTRATO, sin
        responsabilidad para LA EMPRESA, además de las establecidas en el artículo 47 de LA LEY, cualquiera de las
        siguientes acciones cometidas por EL TRABAJADOR:
    </p>

    <p>A.- Utilizar las herramientas de trabajo para fines distintos de la labor asignada.</p>

    <p>
        B.- Revelar información confidencial de LA EMPRESA o PERSONAL DIRECTIVO, como lo son directorio de
        clientes, números telefónicos o domicilios de PERSONAL DIRECTIVO o empleados, así como cualquier
        documentación que tuviere el carácter referido.
    </p>

    <p>
        C.- Fotocopiar planos de obras realizadas o por realizar sin el consentimiento por escrito del PERSONAL
        DIRECTIVO.
    </p>
</div>

<div class="page-break"></div>

{{-- Página 6 --}}
<div>
    <p>
        D.- No rendir el informe de comprobación de gastos en el plazo de 7 días como se indica en la cláusula
        VIGÉSIMA del CONTRATO.
    </p>

    <p>
        VIGÉSIMA TERCERA.- Las situaciones que no se prevean de manera expresa en EL CONTRATO quedarán
        sujetas a las disposiciones contenidas en LA LEY y normas de aplicación supletoria.
    </p>

    <p>
        LEÍDO QUE FUE POR AMBAS PARTES EL CONTRATO Y SABEDORES DE LAS OBLIGACIONES QUE
        CONTRAEN, LO RATIFICAN EN SU CONTENIDO, LO EMITEN POR DUPLICADO EN SUS 6 FOJAS ÚTILES
        CONSERVANDO CADA PARTE UN TANTO, FIRMANDO EN CADA FOJA PREVIA AL MARGEN Y EN ESTA
        ÚLTIMA FOJA AL CALCE; EN GÓMEZ PALACIO, DURANGO; A LOS <span class="campo">{{ $diaFirma }}</span> DÍAS DEL MES DE
        <span class="campo">{{ $mesFirma }}</span> DEL AÑO <span class="campo">{{ $anioFirma }}</span>
    </p>

    <table class="firmas">
        <tr>
            <td>LA EMPRESA</td>
            <td>EL TRABAJADOR</td>
        </tr>
        <tr>
            <td><span class="linea-firma">&nbsp;</span></td>
            <td><span class="linea-firma">&nbsp;</span></td>
        </tr>
        <tr>
            <td>LIC. CYNTHIA GUIJARRO MARTINEZ.</td>
            <td>C. {{ strtoupper($empleado) }}</td>
        </tr>
        <tr>
            <td>Representante Legal</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:18px;">TESTIGO</td>
        </tr>
        <tr>
            <td colspan="2"><span class="linea-firma" style="width:45%;">&nbsp;</span></td>
        </tr>
        <tr>
            <td colspan="2">C.P. TANIA GUADALUPE MARTINEZ SANCHEZ.</td>
        </tr>
    </table>
</div>

<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_script('
            $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
            $pdf->text(270, 750, "Pág $PAGE_NUM de $PAGE_COUNT", $font, 10);
        ');
    }
</script>
</body>
</html>
