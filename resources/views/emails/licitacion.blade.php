<!DOCTYPE html>
<html>
<head>
    <title>Detalles de Licitación</title>
</head>
<body>
    <h1>Detalles de la Licitación</h1>
    <p>Estimado proveedor,</p>
    <p>Le informamos sobre los detalles de la licitación: {{ $licitacion->nombre }}.</p>
    <p><strong>Descripción:</strong> {{ $licitacion->descripcion_detalle }}</p>
    <p><strong>Fecha Limite:</strong> {{ $licitacion->fecha_limite }}</p>
</body>
</html>
