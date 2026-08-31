@extends('layouts.app')
@section('content')
<link href="{{ asset('css/vistas.css') }}" rel="stylesheet">

@if($mensaje = Session::get('warningSaldo'))
    @php
            echo '<script language="JavaScript">';
            echo 'const Toast = Swal.mixin({';
            echo 'toast: true,position: "top-end",showConfirmButton: false,timer: 3000,timerProgressBar: true,';
            echo 'didOpen: (toast) => {';
            echo '  toast.onmouseenter = Swal.stopTimer;';
            echo '  toast.onmouseleave = Swal.resumeTimer;}};';
            echo 'Toast.fire({ icon: "warning",title: "¡Saldo Insuficiente!", text: "No se a efectuado la acción, necesita un mayor saldo para hacer el cargo revise el saldo o consulte con tesoreria"});';
            echo '</script>'; 
    @endphp
@endif

@foreach($obtenerInfo as $data)
@php($pertenencia = $data->nombre_sucursal)
@php($nombre = $data->nombre)
@php($empresa = $data->nombre_empresa)
@endforeach

@foreach($obtenerInfo as $info)
@php($id = $info->id)
@php($status = $info->status)
@php($saldo_inicial = $info->saldo_inicial)
@endforeach

<!-- Estilos personalizados para la nueva vista -->
<style>
    :root {
        --primary-orange: #ff6b35;
        --secondary-orange: #ff8c42;
        --light-orange: #ffb366;
        --dark-orange: #e55a2b;
        --accent-orange: #ffa726;
        --gradient-orange: linear-gradient(135deg, #ff6b35 0%, #ff8c42 50%, #ffb366 100%);
        --shadow-orange: 0 8px 32px rgba(255, 107, 53, 0.15);
        --border-orange: 1px solid rgba(255, 107, 53, 0.2);
    }

    .caja-chica-container {
        min-height: 100vh;
    }

    .modern-header {
        background: var(--gradient-orange);
        color: white;
        padding: 2rem;
        border-radius: 20px;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-orange);
        position: relative;
        overflow: hidden;
    }

    .modern-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }

    .modern-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        color: #ff612c;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .modern-header p {
        font-size: 1.1rem;
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
    }

    .info-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    /* Dashboard Grid - Diseño moderno y elegante */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    /* Tarjetas del dashboard */
    .dashboard-card {
        background: linear-gradient(145deg, #ffffff, #fafbfc);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(255, 107, 53, 0.1);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        backdrop-filter: blur(10px);
    }

    .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-orange);
        transform: scaleX(0);
        transition: transform 0.4s ease;
    }

    .dashboard-card:hover::before {
        transform: scaleX(1);
    }

    .dashboard-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 20px 60px rgba(255, 107, 53, 0.15);
        border-color: rgba(255, 107, 53, 0.3);
    }

    /* Header de la tarjeta */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
    }

    .card-icon {
        width: 48px;
        height: 48px;
        background: var(--gradient-orange);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        box-shadow: 0 4px 16px rgba(255, 107, 53, 0.3);
    }

    .card-badge {
        background: rgba(255, 107, 53, 0.1);
        color: var(--primary-orange);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid rgba(255, 107, 53, 0.2);
    }

    /* Contenido de la tarjeta */
    .card-content {
        margin-bottom: 1.25rem;
    }

    .card-amount {
        font-size: 2.1rem;
        font-weight: 800;
        color: var(--dark-orange);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        line-height: 1;
    }

    .card-label {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dark-orange);
        margin-bottom: 0.5rem;
    }

    .card-description {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Footer de la tarjeta */
    .card-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding-top: 1rem;
    }

    /* Indicadores de tendencia */
    .trend-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .trend-indicator.positive {
        color: #10b981;
    }

    .trend-indicator.negative {
        color: #ef4444;
    }

    .trend-indicator i {
        font-size: 0.7rem;
    }

    /* Botón de acción moderno */
    .modern-action-btn {
        width: 100%;
        background: var(--gradient-orange);
        color: white;
        border: none;
        padding: 0.875rem 1.25rem;
        border-radius: 14px;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 20px rgba(255, 107, 53, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modern-action-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(255, 107, 53, 0.4);
    }

    .modern-action-btn:active {
        transform: translateY(0);
    }

    .modern-action-btn.disabled {
        background: #6c757d;
        cursor: not-allowed;
        box-shadow: none;
        opacity: 0.7;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .dashboard-card {
            padding: 1.5rem;
        }
        
        .card-amount {
            font-size: 2rem;
        }
    }



    .status-badge {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 25px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-active {
        background: linear-gradient(135deg, #4caf50, #66bb6a);
        color: white;
    }

    .status-cancelled {
        background: linear-gradient(135deg, #f44336, #ef5350);
        color: white;
    }

    .form-section {
        background: linear-gradient(145deg, #ffffff, #fafbfc);
        border-radius: 24px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 40px rgba(0,0,0,0.08);
        border: var(--border-orange);
        position: relative;
        overflow: hidden;
    }

    .form-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-orange);
    }

    .form-section:hover {
        box-shadow: 0 12px 50px rgba(255, 107, 53, 0.15);
        transform: translateY(-4px);
    }

    .form-section h2 {
        color: var(--dark-orange);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-section h2::before {
        content: '';
        width: 4px;
        height: 24px;
        background: var(--gradient-orange);
        border-radius: 2px;
    }

    .modern-form-group {
        margin-bottom: 1.25rem;
    }

    .modern-form-group label {
        display: block;
        color: var(--dark-orange);
        font-weight: 600;
        margin-bottom: 0.4rem;
        font-size: 0.85rem;
    }

    .modern-form-control {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid #e1e5e9;
        border-radius: 16px;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        color: #2c3e50;
    }

    .modern-form-control:focus {
        outline: none;
        border-color: var(--primary-orange);
        background: white;
        box-shadow: 0 0 0 6px rgba(255, 107, 53, 0.15), 0 8px 25px rgba(255, 107, 53, 0.2);
        transform: translateY(-2px);
    }

    .modern-form-control:hover {
        border-color: var(--light-orange);
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .modern-form-control::placeholder {
        color: #a0a0a0;
        font-weight: 400;
    }

    /* Estilos base para todos los inputs */
    .modern-form-control {
        width: 100%;
        padding: 0.6rem 1rem;
        border: 2px solid #e1e5e9;
        border-radius: 14px;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: linear-gradient(145deg, #ffffff, #f8f9fa);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        color: #2c3e50;
        display: block;
        box-sizing: border-box;
        height: 2.8rem;
        min-height: 2.8rem;
        line-height: 1.5;
    }

    /* Estilos específicos para select */
    select.modern-form-control {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.75rem center;
        background-repeat: no-repeat;
        background-size: 1.2em 1.2em;
        padding-right: 2.5rem;
        appearance: none;
    }

    /* Estilos específicos para textarea */
    textarea.modern-form-control {
        resize: vertical;
        min-height: 100px;
        line-height: 1.6;
        height: auto;
        min-height: 100px;
    }

    /* Estilos específicos para inputs numéricos */
    input[type="number"].modern-form-control {
        font-family: inherit;
        font-weight: 500;
        text-align: left;
        -webkit-appearance: none;
        -moz-appearance: textfield;
        appearance: textfield;
    }

    /* Eliminar flechas del input number en Chrome/Safari/Edge */
    input[type="number"].modern-form-control::-webkit-outer-spin-button,
    input[type="number"].modern-form-control::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Asegurar que el input sea visible */
    .modern-form-control:focus {
        outline: none;
        border-color: var(--primary-orange);
        background: white;
        box-shadow: 0 0 0 4px rgba(255, 107, 53, 0.15), 0 8px 25px rgba(255, 107, 53, 0.2);
        transform: translateY(-2px);
        z-index: 1;
        position: relative;
    }

    /* Estilos específicos para el input de importe */
    #saldo.modern-form-control {
        opacity: 1 !important;
        visibility: visible !important;
        display: block !important;
        position: relative !important;
        z-index: 10 !important;
    }

    /* Asegurar que el input de importe no se oculte */
    .modern-form-group:has(#saldo) {
        position: relative;
        z-index: 5;
    }

    /* Estilos para inputs con validación */
    .modern-form-control.is-valid {
        border-color: #10b981;
        background: linear-gradient(145deg, #f0fdf4, #ffffff);
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    .modern-form-control.is-invalid {
        border-color: #ef4444;
        background: linear-gradient(145deg, #fef2f2, #ffffff);
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    /* Estilos para los mensajes de validación */
    .valid-feedback,
    .invalid-feedback {
        display: none;
        margin-top: 0.5rem;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        animation: feedbackSlideIn 0.4s ease forwards;
    }

    .valid-feedback.show,
    .invalid-feedback.show {
        display: block;
    }

    .valid-feedback {
        color: #10b981;
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .invalid-feedback {
        color: #ef4444;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    @keyframes feedbackSlideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Animación de entrada para los inputs */
    .modern-form-control {
        animation: inputSlideIn 0.6s ease forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    @keyframes inputSlideIn {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modern-btn {
        background: var(--gradient-orange);
        color: white;
        border: none;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        position: relative;
        overflow: hidden;
    }

    .modern-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
    }

    .modern-btn:active {
        transform: translateY(0);
    }

    .modern-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .modern-btn:hover::before {
        left: 100%;
    }

    .file-upload-area {
        border: 2px dashed var(--primary-orange);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        background: rgba(255, 107, 53, 0.05);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .file-upload-area:hover {
        background: rgba(255, 107, 53, 0.1);
        border-color: var(--dark-orange);
    }

    .file-upload-area input[type="file"] {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 10;
    }

    .file-upload-area.dragover {
        background: rgba(255, 107, 53, 0.15);
        border-color: var(--dark-orange);
        transform: scale(1.02);
    }

    .file-upload-area.file-selected {
        background: rgba(76, 175, 80, 0.1);
        border-color: #4caf50;
    }

    .file-upload-area.file-selected .file-upload-icon {
        color: #4caf50;
    }

    .file-upload-area.file-selected h6 {
        color: #4caf50;
    }

    .file-upload-icon {
        font-size: 3rem;
        color: var(--primary-orange);
        margin-bottom: 1rem;
    }

    .table-container {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: var(--border-orange);
        overflow: hidden;
    }

    .table-container h2 {
        color: var(--dark-orange);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Estilos para el header de la tabla con botón de exportar */
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    /* Botón de exportar más compacto */
    .export-btn {
        background: var(--gradient-orange);
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        white-space: nowrap;
    }

    .export-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    }

    .export-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        box-shadow: none;
    }

    .table-container h2::before {
        content: '';
        width: 4px;
        height: 24px;
        background: var(--gradient-orange);
        border-radius: 2px;
    }

    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .modern-table thead th {
        background: linear-gradient(145deg, #f8f9fa, #ffffff);
        color: #494949;
        padding: 1.25rem 1rem;
        font-weight: 300;
        font-size: 0.7rem;
        text-align: center;
        position: relative;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .modern-table thead th:first-child {
        border-top-left-radius: 12px;
    }

    .modern-table thead th:last-child {
        border-top-right-radius: 12px;
    }

    .modern-table tbody tr {
        transition: all 0.3s ease;
        animation: fadeInUp 0.6s ease forwards;
        opacity: 0;
        transform: translateY(20px);
    }

    .modern-table tbody tr:nth-child(even) {
        background: rgba(255, 107, 53, 0.02);
    }

    .modern-table tbody tr:hover {
        background: rgba(255, 107, 53, 0.05);
        transform: scale(1.01);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .modern-table tbody td {
        padding: 1rem;
        border-bottom: 1px solid rgba(255, 107, 53, 0.1);
        font-size: 0.75rem;
        text-align: start;
        vertical-align: middle;
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modern-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
    .modern-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
    .modern-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
    .modern-table tbody tr:nth-child(4) { animation-delay: 0.4s; }
    .modern-table tbody tr:nth-child(5) { animation-delay: 0.5s; }

    .movement-badge {
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        min-width: 80px;
    }

    .movement-apertura { background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; }
    .movement-cancelacion { background: linear-gradient(135deg, #f44336, #ef5350); color: white; }
    .movement-transferencia { background: linear-gradient(135deg, #ff9800, #ffb74d); color: white; }
    .movement-ingreso { background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; }
    .movement-pago { background: linear-gradient(135deg, #4caf50, #66bb6a); color: white; }
    .movement-cargo { background: linear-gradient(135deg, #f44336, #ef5350); color: white; }
    .movement-gasto { background: linear-gradient(135deg, #f44336, #ef5350); color: white; }
    .movement-entrega { background: linear-gradient(135deg, #f44336, #ef5350); color: white; }
    .movement-desembolso { background: linear-gradient(135deg, #f44336, #ef5350); color: white; }
    .movement-retiro { background: linear-gradient(135deg, #f44336, #ef5350); color: white; }
    .movement-cancelacion { background: linear-gradient(135deg, #ff8785, #ffadab); color: white; }

    .amount-positive {
        color: #4caf50;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .amount-negative {
        color: #f44336;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .amount-neutral {
        color: #333;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .action-btn {
        padding: 0.4rem 0.8rem;
        border: none;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .action-btn-primary {
        background: linear-gradient(135deg, #2196f3, #42a5f5);
        color: white;
    }

    .action-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
    }

    .action-btn-success {
        background: linear-gradient(135deg, #4caf50, #66bb6a);
        color: white;
    }

    .action-btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
    }

    .export-section {
        background: linear-gradient(135deg, #4caf50, #66bb6a);
        color: white;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(76, 175, 80, 0.3);
    }

    .export-section h3 {
        margin: 0 0 1rem 0;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .export-btn {
        background: white;
        color: #4caf50;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .export-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .export-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 768px) {
        .info-cards {
            grid-template-columns: 1fr;
        }
        
        .modern-header h1 {
            font-size: 2rem;
        }
        
        .form-section, .table-container {
            padding: 1.5rem;
        }
    }

    /* Animaciones adicionales */
    .pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .slide-in-left {
        animation: slideInLeft 0.8s ease forwards;
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .slide-in-right {
        animation: slideInRight 0.8s ease forwards;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .bounce-in {
        animation: bounceIn 0.8s ease forwards;
    }

    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: scale(0.3);
        }
        50% {
            opacity: 1;
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
</style>

<div class="caja-chica-container">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center header">
                    <div class="d-flex align-items-center">
                        <div class="header-icon me-3">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <h2 class="mb-0 text-marino fw-bold">Control de {{ $tipo }}</h2>
                            <p class="text-muted mb-0">{{ $empresa }} • {{ $pertenencia }} • {{ $nombre }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard de información financiera -->
        <div class="dashboard-grid">
            @foreach($obtenerInfo as $info)
            <!-- Tarjeta de Saldo Inicial -->
            <div class="dashboard-card balance-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <div class="card-badge">Inicial</div>
                </div>
                <div class="card-content">
                    <div class="card-amount">${{number_format($info->saldo_inicial, 2)}}</div>
                    <div class="card-label">Saldo de Apertura</div>
                </div>
                <div class="card-footer">
                    <div class="trend-indicator positive">
                        <i class="fa-solid fa-arrow-up"></i>
                        <span>Capital Base</span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Saldo Actual -->
            <div class="dashboard-card balance-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                    <div class="card-badge">Actual</div>
                </div>
                <div class="card-content">
                    <div class="card-amount">${{number_format($info->saldo_actual, 2)}}</div>
                    <div class="card-label">Disponible</div>
                </div>
                <div class="card-footer">
                    <div class="trend-indicator {{ $info->saldo_actual >= $info->saldo_inicial ? 'positive' : 'negative' }}">
                        <i class="fa-solid fa-{{ $info->saldo_actual >= $info->saldo_inicial ? 'arrow-up' : 'arrow-down' }}"></i>
                        <span>{{ $info->saldo_actual >= $info->saldo_inicial ? 'Crecimiento' : 'Disminución' }}</span>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Exportar -->
            <div class="dashboard-card action-card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fa-solid fa-file-export"></i>
                    </div>
                    <div class="card-badge">Acción</div>
                </div>
                <div class="card-content">
                    <div class="card-title">Exportar Movimientos</div>
                    <div class="card-description">Descarga el historial completo en formato Excel</div>
                </div>
                <div class="card-footer">
                    <form action="/Tesoreria/Movimientos/ExportarMovimientos/{{$tipo}}/{{$id}}" class="export-form">
                        <input type="text" name="nombre" value="{{$nombre}}" hidden required />
                        <input type="text" name="fecha_inicio" value="{{$fecha_inicio}}" hidden required />
                        <input type="text" name="fecha_fin" value="{{$fecha_fin}}" hidden required />
                        <input type="text" name="empresa" value="{{$empresa}}" hidden required />

                        @if($permisos1 == "movimientos_exportar")
                            @if($obtenerHistorial->isEmpty())
                                <button disabled class="modern-action-btn disabled">
                                    <i class="fa-solid fa-download"></i>
                                    <span>Sin Datos</span>
                                </button>
                            @else
                                <button type="submit" class="modern-action-btn">
                                    <i class="fa-solid fa-download"></i>
                                    <span>Descargar</span>
                                </button>
                            @endif
                        @else
                            <button disabled class="modern-action-btn disabled">
                                <i class="fa-solid fa-lock"></i>
                                <span>Sin Permisos</span>
                            </button>
                        @endif 
                    </form>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Formulario de gastos -->
        <div class="form-section slide-in-left">
            <h2><i class="fas fa-plus-circle me-2"></i>Alta de Gastos</h2>
            <form action="/Tesoreria/Movimientos/Responsable/AplicarGasto/{{$id}}" method="POST" enctype="multipart/form-data" class="g-3 form needs-validation formulario1" novalidate>
                @csrf
              <div class="row">
                <input type="text" value="{{$pertenencia}}" name="sucursal" hidden>
                <input type="text" value="{{$nombre}}" name="nombre" hidden>
                <input type="text" value="{{$mesNombre}}" name="mes" hidden>
                <input type="text" value="Cajas Chicas" name="tipo" hidden>
                    
                <div class="col-md-8 col-12">
                  <div class="row">
                      <div class="col-md-8 col-12">
                            <div class="modern-form-group">
                                <label for="gasto">Gastos *</label>
                                <select name="gasto" id="gasto" class="modern-form-control" required>
                                    <option value="" selected>Selecciona un tipo de gasto...</option>
                                        @foreach($varGastos as $gastos)
                                                    <option value="{{$gastos->id}}">{{$gastos->id}} - {{$gastos->nombre}}</option>
                                        @endforeach 
                                    </select>
                                    <div class="valid-feedback" style="display: none;">¡Tipo de gasto seleccionado correctamente!</div>
                                    <div class="invalid-feedback" style="display: none;">Debe seleccionar un tipo de gasto.</div>
                            </div>
                      </div>

                      <div class="col-md-4 col-12">
                        <div class="modern-form-group">
                            <label for="saldo">Importe *</label>
                            <input type="number" step="any" class="modern-form-control" name="saldo" id="saldo" maxlength="10" placeholder="0.00" min="0.01" required />
                            <div class="valid-feedback" style="display: none;">¡Importe válido!</div>
                            <div class="invalid-feedback" style="display: none;">El importe debe ser mayor a $0.00.</div>
                        </div>
                      </div>
                  </div>

                  <div class="row mt-3">
                    <div class="col col-12">
                        <div class="modern-form-group">
                            <label for="floatingTextarea2">Descripción *</label>
                            <textarea class="modern-form-control" placeholder="Describe detalladamente el gasto realizado..." name="descripcion" id="floatingTextarea2" style="height: 80px" minlength="10" required></textarea>
                            <div class="valid-feedback" style="display: none;">¡Descripción válida!</div>
                            <div class="invalid-feedback" style="display: none;">La descripción debe tener al menos 10 caracteres.</div>
                        </div>
                    </div>
                  </div>
                </div>

                <div class="col-md-4 col-12">
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="file-upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h6 id="uploadTitle">Subir Evidencia *</h6>
                        <small id="uploadSubtitle">Archivos permitidos: .PDF (máximo 10MB)</small>
                        <input type="file" name="evidencia" id="evidencia" class="validaPDF" accept=".pdf" required>
                        <div class="valid-feedback" style="display: none;">¡Archivo PDF válido!</div>
                        <div class="invalid-feedback" style="display: none;">Debe subir un archivo PDF como evidencia.</div>
                    </div>
                </div>

                <div class="row justify-content-center mt-4">
                    <div class="text-center">
                        <button type="submit" class="modern-btn">
                            <i class="fa-solid fa-check me-2"></i>Aplicar Gasto
                        </button>
                    </div>
                  </div>
              </div>
            </form>
        </div>

        <div  class="row form-section slide-in-left me-1 ms-1">
            <div class="col-md-10">
                <!-- Filtros de Fecha -->
                <form action="/Tesoreria/Movimientos/ResponsableCajaChica/{{$tipo}}/{{$empresaid}}/{{$id}}" method="" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row mb-0">
                        <div class="col-md-4 col-6 mb-2">
                            <label class="form-label fw-bold fs-9">
                                <i class="fas fa-calendar me-1 text-orange"></i>Fecha Inicio
                            </label>

                            <input type="date" class="form-control fs-9" name="fecha_inicio" value="{{ $fecha_inicio }}" required>
                            
                            <div class="valid-feedback">
                                <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                            </div>

                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                            </div>
                        </div>

                        <div class="col-md-4 col-6 mb-2">
                            <label class="form-label fw-bold fs-9">
                                <i class="fas fa-calendar me-1 text-orange"></i>Fecha Final
                            </label>

                            <input type="date" class="form-control fs-9" name="fecha_fin" value="{{ $fecha_fin }}" required>
                            <div class="valid-feedback">
                                <i class="fas fa-check-circle text-success"></i> ¡Se ve bien!
                            </div>

                            <div class="invalid-feedback">
                                <i class="fas fa-exclamation-circle text-danger"></i> Por favor, completa la información requerida.
                            </div>
                        </div>

                        <div class="col-md-4 col-12 mb-2">
                            <br>
                            <button class="btn btn-baseColor w-100 mt-2 fs-9 fw-bold rounded-1" type="submit">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <form class="col-md-2" action="/Tesoreria/Movimientos/ExportarMovimientos/{{$tipo}}/{{$id}}">
                <input type="text" name="nombre" value="{{$nombre}}" hidden required />
                <input type="text" name="fecha_inicio" value="{{$fecha_inicio}}" hidden required />
                <input type="text" name="fecha_fin" value="{{$fecha_fin}}" hidden required />
                <input type="text" name="empresa" value="{{$empresaid}}" hidden required />
                <br>

                @if($permisos1 == "movimientos_exportar")
                    @if($obtenerHistorial->isEmpty())
                        <button class="btn btn-success w-100 mt-2 fs-9 fw-bold rounded-1" disabled>
                            <i class="fas fa-file-excel me-1"></i>Exportar
                        </button> 
                    @else
                        <button class="btn btn-success w-100 mt-2 fs-9 fw-bold rounded-1" type="submit">
                            <i class="fas fa-file-excel me-1"></i>Exportar
                        </button> 
                    @endif
                @else
                    <button class="btn btn-success w-100 mt-2 fs-9 fw-bold rounded-1" disabled>
                        <i class="fas fa-file-excel me-1"></i>Exportar
                    </button> 
                @endif 
            </form>
        </div>

        <!-- Tabla de movimientos -->
        <div class="table-container slide-in-right">
            <div class="table-header">
                <h2><i class="fas fa-table me-2"></i>Historial de Movimientos</h2>
            </div>

            <div class="">
            <div class="">
                    <table class="modern-table" id="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Tipo</th>
                                <th>Ingreso</th>
                                <th>Egreso</th>
                                <th>Saldo</th>
                                <th>Importe</th>
                                <th>IVA</th>
                                <th>RET IVA</th>
                                <th>RET ISR</th>
                                <th>RET ISR RESICO</th>
                                <th>Información</th>
                                <th>Estado</th>
                                @if($tipo == 'Caja Chica')
                                <th>Comprobante</th>
                                @endif
                                <th>Titular</th>
                                <th>No. Referencia</th>
                                <th>Pertenece</th>
                                <th>Realizado Por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($obtenerHistorial as $datos)
                            @php($signo = " ")
                                <tr>
                                    <td class="text-secondary text-truncate">{{$datos->created_at}}</td>
                                    <td class="amount-neutral text-truncate">{{$datos->concepto}}</td>
                                    <td class="text-truncate">
                                        @if($datos->tipo_movimiento == "APERTURA")
                                            <span class="movement-badge movement-apertura text-truncate"><i class="fa-solid fa-check me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "CANCELACION")
                                            <span class="movement-badge movement-cancelacion text-truncate"><i class="fa-solid fa-ban me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "TRANSFERENCIA")
                                            <span class="movement-badge movement-transferencia text-truncate"><i class="fa-solid fa-right-left me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "INGRESO")
                                            <span class="movement-badge movement-ingreso text-truncate"><i class="fa-solid fa-plus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "PAGO")
                                            <span class="movement-badge movement-pago"><i class="fa-solid fa-plus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "CARGO" || $datos->tipo_movimiento == "ENTREGA")
                                            <span class="movement-badge movement-cargo"><i class="fa-solid fa-minus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "GASTO")
                                            @if($datos->estado == "C")
                                                <span class="movement-badge movement-cancelacion fst-italic"><i class="fa-solid fa-minus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                            @else
                                                <span class="movement-badge movement-cargo"><i class="fa-solid fa-minus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                            @endif
                                        @elseif($datos->tipo_movimiento == "DESEMBOLSO")
                                            <span class="movement-badge movement-desembolso text-truncate"><i class="fa-solid fa-minus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @elseif($datos->tipo_movimiento == "RETIRO")
                                            <span class="movement-badge movement-retiro text-truncate"><i class="fa-solid fa-minus me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @else
                                            <span class="movement-badge movement-ingreso text-truncate"><i class="fa-solid fa-money-bill me-1"></i>{{$datos->tipo_movimiento}}</span>
                                        @endif
                                    </td>

                                    <td class="text-truncate">
                                        @if($datos->ingreso == 0)
                                            <span class="amount-neutral">-</span>
                                        @else
                                            <span class="amount-positive">+ ${{number_format($datos->ingreso, 2)}}</span>
                                        @endif
                                    </td>

                                    <td class="text-truncate">
                                        @if($datos->egreso == 0)
                                            <span class="amount-neutral">-</span>
                                        @else
                                            <span class="amount-negative">- ${{number_format($datos->egreso, 2)}}</span>
                                        @endif
                                    </td>

                                    <td class="amount-neutral">${{number_format($datos->saldo, 2)}}</td>

                                    
                                    @php($total_neto = $datos->egreso)
                                    @php($suma = $datos->total_iva)
                                    @php($resta = $datos->total_ret_iva + $datos->total_ret_isr + $datos->total_ret_isr_resico)
                                    @php($importe = ($total_neto + $resta) - $suma)

                                    <td class="amount-neutral text-truncate">${{number_format($importe, 2)}}</td>
                                    <td class="amount-neutral text-truncate">{{$datos->total_iva}}</td>
                                    <td class="amount-neutral text-truncate">{{$datos->total_ret_iva}}</td>
                                    <td class="amount-neutral text-truncate">{{$datos->total_ret_isr}}</td>
                                    <td class="amount-neutral text-truncate">{{$datos->total_ret_isr_resico}}</td>
                                    
                                    
                                    <td>
                                        <button class="action-btn action-btn-primary" data-bs-toggle="modal" data-bs-target="#Modalcomentario{{$datos->id}}">
                                            <i class="fa-solid fa-info-circle me-1"></i>Ver
                                        </button>
                                    </td>
                                    
                                    <td>
                                        @if($datos->estado == "A")
                                            <span class="movement-badge movement-apertura"><i class="fa-solid fa-check me-1"></i>AUTORIZADO</span>
                                        @elseif($datos->estado == "D")
                                            <span class="movement-badge movement-cancelacion"><i class="fa-solid fa-ban me-1"></i>DECLINADO</span>
                                        @elseif($datos->estado == "E")
                                            <span class="movement-badge movement-transferencia"><i class="fa-solid fa-pause me-1"></i>EN ESPERA</span>  
                                        @elseif($datos->estado == "C")
                                            <span class="movement-badge movement-cancelacion"><i class="fa-solid fa-ban me-1"></i>CANCELADO</span>  
                                        @else
                                            <span class="movement-badge movement-ingreso"><i class="fa-solid fa-exclamation me-1"></i>INCONCLUSO</span> 
                                        @endif
                                    </td>
                                                
                                    @if($tipo == 'Caja Chica')
                                    <td>
                                        @if(!is_null($datos->ruta_evidencia))
                                        <a target="_blank" class="action-btn action-btn-success" href="{{asset('Tesoreria/Gastos/Cajas Chicas/'.$datos->ruta_evidencia)}}">
                                            <i class="fa-solid fa-eye me-1"></i>Ver
                                        </a>
                                        @endif
                                    </td>
                                    @endif
                                                
                                    <td class="text-secondary">{{$datos->numero_referencia}}</td>
                                    <td class="fw-bold text-secondary text-truncate">{{$datos->responsable}}</td>
                                    <td class="text-secondary">{{$datos->pertenencia}}</td>
                                    <td class="text-secondary">{{$datos->created_by}} - {{$datos->created_at}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div> 
        </div>
   </div>  
</div>

@foreach($obtenerHistorial as $datos)
  <!-- Modal -->
    <div class="modal fade" id="Modalcomentario{{$datos->id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-orange); color: white;">
                    <h5 class="modal-title"><i class="fa-regular fa-comment me-2"></i>Información de Movimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <h6 class="text-dark mb-2"><strong>CONCEPTO:</strong></h6>
                        <p class="text-dark">{{$datos->concepto}}</p>
                    </div>
                    <hr>
                    <div>
                        <h6 class="text-dark mb-2"><strong>DESCRIPCIÓN:</strong></h6>
                        <p class="text-dark">{{$datos->descripcion}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
<script src="{{ asset('js/table.js') }}"></script>
<script src="{{ asset('js/validation.js') }}"></script>
<script src="{{ asset('js/validaPDF.js') }}"></script>

<!-- Script para mejorar la experiencia de usuario -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de entrada para las tarjetas del dashboard
        const dashboardCards = document.querySelectorAll('.dashboard-card');
        dashboardCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.2}s`;
        });

        // Efecto hover mejorado para la tabla
        const tableRows = document.querySelectorAll('.modern-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.02)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Animación para el botón de exportar
        const exportBtn = document.querySelector('.export-btn:not([disabled])');
        if (exportBtn) {
            exportBtn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.05)';
            });
            
            exportBtn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        }

        // Efecto de carga para la tabla
        const table = document.getElementById('table');
        if (table) {
            table.style.opacity = '0';
            setTimeout(() => {
                table.style.transition = 'opacity 0.8s ease';
                table.style.opacity = '1';
            }, 300);
        }

        // Funcionalidad del input de archivo
        const fileInput = document.getElementById('evidencia');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const uploadTitle = document.getElementById('uploadTitle');
        const uploadSubtitle = document.getElementById('uploadSubtitle');

        if (fileInput && fileUploadArea) {
            // Click en el área de subida
            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });

            // Cambio de archivo seleccionado
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validar tipo de archivo
                    if (file.type !== 'application/pdf') {
                        alert('Solo se permiten archivos PDF');
                        this.value = '';
                        return;
                    }

                    // Validar tamaño (máximo 10MB)
                    if (file.size > 10 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 10MB');
                        this.value = '';
                        return;
                    }

                    // Mostrar archivo seleccionado
                    fileUploadArea.classList.add('file-selected');
                    uploadTitle.textContent = 'Archivo Seleccionado';
                    uploadSubtitle.textContent = file.name;
                    
                    // Cambiar icono
                    const icon = fileUploadArea.querySelector('.file-upload-icon i');
                    icon.className = 'fas fa-check-circle';
                    
                    // Agregar clase para validación
                    fileInput.classList.add('is-valid');
                    fileInput.classList.remove('is-invalid');
                    
                    // Mostrar mensaje de validación exitosa
                    const validFeedback = fileInput.parentElement.querySelector('.valid-feedback');
                    const invalidFeedback = fileInput.parentElement.querySelector('.invalid-feedback');
                    if (validFeedback) validFeedback.classList.add('show');
                    if (invalidFeedback) invalidFeedback.classList.remove('show');
                }
            });

            // Drag and drop
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const file = files[0];
                    
                    // Validar tipo de archivo
                    if (file.type !== 'application/pdf') {
                        alert('Solo se permiten archivos PDF');
                        return;
                    }

                    // Validar tamaño
                    if (file.size > 10 * 1024 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 10MB');
                        return;
                    }

                    // Asignar archivo al input
                    fileInput.files = files;
                    
                    // Disparar evento change
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            });

            // Validación del formulario completo al enviar
            const form = document.querySelector('.formulario1');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault(); // Prevenir envío por defecto
                    
                    // Validar todos los campos
                    const isValid = validateForm();
                    
                    if (isValid) {
                        // Mostrar confirmación antes de enviar
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '¿Confirmar gasto?',
                                text: '¿Estás seguro de que deseas aplicar este gasto?',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#ff6b35',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: 'Sí, aplicar',
                                cancelButtonText: 'Cancelar'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Si confirma, enviar el formulario
                                    this.submit();
                                }
                            });
                        } else {
                            // Si no hay SweetAlert, usar confirm nativo
                            if (confirm('¿Estás seguro de que deseas aplicar este gasto?')) {
                                this.submit();
                            }
                        }
                    } else {
                        // Mostrar mensaje de error
                        showFormError();
                    }
                });
            }

            // Validación en tiempo real para todos los campos
            const formInputs = document.querySelectorAll('.modern-form-control');
            formInputs.forEach(input => {
                // Validar al perder el foco
                input.addEventListener('blur', function() {
                    validateField(this);
                });

                // Validar al escribir (para campos de texto)
                if (input.type !== 'file') {
                    input.addEventListener('input', function() {
                        if (this.value.trim() !== '') {
                            validateField(this);
                        }
                    });
                }
            });

            // Validación adicional al presionar el botón
            const submitButton = document.querySelector('.modern-btn');
            if (submitButton) {
                submitButton.addEventListener('click', function(e) {
                    // Validar antes de enviar
                    if (!validateForm()) {
                        e.preventDefault();
                        showFormError();
                        return false;
                    }
                });
            }
            
            // Validación en tiempo real mejorada
            const gastoSelect = document.getElementById('gasto');
            const saldoInput = document.getElementById('saldo');
            const descripcionTextarea = document.getElementById('floatingTextarea2');
            
            // Validación específica para el select de gastos
            if (gastoSelect) {
                gastoSelect.addEventListener('change', function() {
                    validateField(this);
                });
            }
            
            // Validación específica para el input de importe
            if (saldoInput) {
                saldoInput.addEventListener('input', function() {
                    const value = parseFloat(this.value);
                    if (this.value && value <= 0) {
                        this.setCustomValidity('El importe debe ser mayor a 0');
                    } else {
                        this.setCustomValidity('');
                    }
                    validateField(this);
                });
                
                saldoInput.addEventListener('blur', function() {
                    validateField(this);
                });
            }
            
            // Validación específica para la descripción
            if (descripcionTextarea) {
                descripcionTextarea.addEventListener('input', function() {
                    if (this.value.trim().length < 10) {
                        this.setCustomValidity('La descripción debe tener al menos 10 caracteres');
                    } else {
                        this.setCustomValidity('');
                    }
                    validateField(this);
                });
                
                descripcionTextarea.addEventListener('blur', function() {
                    validateField(this);
                });
            }

            // Función para validar un campo
            function validateField(field) {
                const validFeedback = field.parentElement.querySelector('.valid-feedback');
                const invalidFeedback = field.parentElement.querySelector('.invalid-feedback');
                
                // Ocultar ambos mensajes primero
                if (validFeedback) validFeedback.classList.remove('show');
                if (invalidFeedback) invalidFeedback.classList.remove('show');
                
                // Validar el campo
                if (field.checkValidity()) {
                    field.classList.add('is-valid');
                    field.classList.remove('is-invalid');
                    if (validFeedback) validFeedback.classList.add('show');
                } else {
                    field.classList.add('is-invalid');
                    field.classList.remove('is-valid');
                    if (invalidFeedback) invalidFeedback.classList.add('show');
                }
            }

            // Función para validar todo el formulario
            function validateForm() {
                const form = document.querySelector('.formulario1');
                const inputs = form.querySelectorAll('.modern-form-control');
                let isValid = true;
                let errorMessages = [];
                
                // Validar cada campo
                inputs.forEach(input => {
                    const fieldName = getFieldName(input);
                    
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                        input.classList.remove('is-valid');
                        isValid = false;
                        
                        // Mostrar mensaje de error específico
                        const invalidFeedback = input.parentElement.querySelector('.invalid-feedback');
                        if (invalidFeedback) {
                            invalidFeedback.classList.add('show');
                        }
                        
                        // Agregar mensaje de error específico
                        if (input.hasAttribute('required') && !input.value.trim()) {
                            errorMessages.push(`${fieldName} es obligatorio`);
                        } else if (input.type === 'number' && input.value && parseFloat(input.value) <= 0) {
                            errorMessages.push(`${fieldName} debe ser mayor a 0`);
                        } else if (input.type === 'file' && !input.files.length) {
                            errorMessages.push(`${fieldName} es obligatorio`);
                        }
                    } else {
                        input.classList.add('is-valid');
                        input.classList.remove('is-invalid');
                        
                        // Mostrar mensaje de éxito
                        const validFeedback = input.parentElement.querySelector('.valid-feedback');
                        if (validFeedback) {
                            validFeedback.classList.add('show');
                        }
                    }
                });
                
                // Validaciones adicionales específicas
                const gastoSelect = document.getElementById('gasto');
                const saldoInput = document.getElementById('saldo');
                const descripcionTextarea = document.getElementById('floatingTextarea2');
                const evidenciaFile = document.getElementById('evidencia');
                
                // Validar selección de gasto
                if (!gastoSelect.value) {
                    isValid = false;
                    errorMessages.push('Debe seleccionar un tipo de gasto');
                }
                
                // Validar importe
                if (!saldoInput.value || parseFloat(saldoInput.value) <= 0) {
                    isValid = false;
                    errorMessages.push('El importe debe ser mayor a 0');
                }
                
                // Validar descripción
                if (!descripcionTextarea.value.trim()) {
                    isValid = false;
                    errorMessages.push('La descripción es obligatoria');
                }
                
                // Validar archivo PDF
                if (!evidenciaFile.files.length) {
                    isValid = false;
                    errorMessages.push('Debe subir un archivo PDF como evidencia');
                } else {
                    const file = evidenciaFile.files[0];
                    if (file.type !== 'application/pdf') {
                        isValid = false;
                        errorMessages.push('Solo se permiten archivos PDF');
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        isValid = false;
                        errorMessages.push('El archivo PDF no debe exceder 10MB');
                    }
                }
                
                // Guardar mensajes de error para mostrar
                window.formErrorMessages = errorMessages;
                
                return isValid;
            }
            
            // Función auxiliar para obtener el nombre del campo
            function getFieldName(input) {
                const label = input.parentElement.querySelector('label');
                if (label) {
                    return label.textContent.replace(':', '').trim();
                }
                
                // Nombres por defecto según el ID
                const fieldNames = {
                    'gasto': 'Tipo de gasto',
                    'saldo': 'Importe',
                    'floatingTextarea2': 'Descripción',
                    'evidencia': 'Archivo de evidencia'
                };
                
                return fieldNames[input.id] || 'Campo';
            }

            // Función para mostrar error del formulario
            function showFormError() {
                // Hacer scroll al primer campo con error
                const firstInvalidInput = document.querySelector('.modern-form-control.is-invalid');
                if (firstInvalidInput) {
                    firstInvalidInput.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    firstInvalidInput.focus();
                }
                
                // Mostrar alerta de error con mensajes específicos
                if (typeof Swal !== 'undefined') {
                    const errorMessages = window.formErrorMessages || [];
                    const errorList = errorMessages.length > 0 ? 
                        '<ul style="text-align: left; margin: 10px 0;">' + 
                        errorMessages.map(msg => `<li>${msg}</li>`).join('') + 
                        '</ul>' : 
                        'Por favor, completa todos los campos requeridos correctamente.';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Formulario Incompleto',
                        html: errorList,
                        confirmButtonColor: '#ff6b35',
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    const errorMessages = window.formErrorMessages || [];
                    const message = errorMessages.length > 0 ? 
                        errorMessages.join('\n') : 
                        'Por favor, completa todos los campos requeridos correctamente.';
                    alert(message);
                }
            }

            // Funcionalidad del modal personalizado
            function initModal() {
                const modalButtons = document.querySelectorAll('[data-bs-toggle="modal"]');
                
                modalButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const targetModal = this.getAttribute('data-bs-target');
                        const modal = document.querySelector(targetModal);
                        
                        if (modal) {
                            // Mostrar modal
                            modal.style.display = 'block';
                            modal.classList.add('show');
                            
                            // Agregar clase al body para evitar scroll
                            document.body.style.overflow = 'hidden';
                        }
                    });
                });

                // Cerrar modal con botón close
                const closeButtons = document.querySelectorAll('.btn-close, [data-bs-dismiss="modal"]');
                closeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const modal = this.closest('.modal');
                        if (modal) {
                            closeModal(modal);
                        }
                    });
                });

                // Cerrar modal haciendo click fuera
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            closeModal(this);
                        }
                    });
                });

                // Cerrar modal con ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        const openModal = document.querySelector('.modal.show');
                        if (openModal) {
                            closeModal(openModal);
                        }
                    }
                });
            }

            function closeModal(modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }

            // Inicializar modal
            initModal();
        }
    });
</script>
@endsection