@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Space+Grotesk:wght@500;600;700&display=swap');

    :root {
        --home-ink: #0a0a0a;
        --home-black: #141414;
        --home-white: #ffffff;
        --home-page: #f4f4f5;
        --home-muted: #525252;
        --home-line: #e4e4e7;
        --home-soft: #fafafa;
        --home-radius: 18px;
        --home-radius-sm: 12px;
        --home-shadow: 0 1px 2px rgba(0, 0, 0, 0.04), 0 8px 24px rgba(0, 0, 0, 0.06);
        --home-shadow-hover: 0 4px 8px rgba(0, 0, 0, 0.06), 0 16px 40px rgba(0, 0, 0, 0.1);
        --home-ease: cubic-bezier(0.22, 1, 0.36, 1);
    }

    .mining-dashboard {
        min-height: 100vh;
        color: var(--home-ink);
        background: var(--home-page);
        font-family: 'DM Sans', system-ui, sans-serif;
        padding: 1.25rem 0 2.5rem;
    }

    .mining-dashboard .container-fluid {
        max-width: none;
        width: 100%;
        margin: 0;
        padding-left: 1.25rem;
        padding-right: 1.25rem;
    }

    .hero-section {
        margin-bottom: 0.5rem;
    }

    .hero-section::before {
        display: none;
    }

    .greeting-card {
        background: var(--home-ink);
        color: var(--home-white);
        border: none;
        border-radius: var(--home-radius);
        padding: 2rem 2.25rem;
        height: 220px;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        box-shadow: var(--home-shadow);
        transition: transform 0.35s var(--home-ease), box-shadow 0.35s var(--home-ease);
        position: relative;
        overflow: hidden;
    }

    .greeting-card::after {
        content: '';
        position: absolute;
        right: -40px;
        bottom: -40px;
        width: 160px;
        height: 160px;
        border: 28px solid rgba(255, 255, 255, 0.06);
        border-radius: 50%;
        pointer-events: none;
    }

    .greeting-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--home-shadow-hover);
    }

    .greeting-eyebrow {
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.55);
        margin-bottom: 0.65rem;
    }

    .greeting-text {
        font-family: 'Space Grotesk', 'DM Sans', sans-serif;
        font-size: clamp(2rem, 4vw, 2.75rem);
        font-weight: 700;
        line-height: 1.1;
        letter-spacing: -0.03em;
        margin: 0 0 1.1rem;
        color: #ffffff;
    }

    .time-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.45rem 0.9rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #ffffff !important;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 500;
        width: fit-content;
        backdrop-filter: blur(8px);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card,
    .info-card,
    .charts-section,
    .suggestion-box,
    .mission-statement,
    .mining-visualization,
    .weather-widget {
        background: var(--home-white);
        border: 1px solid var(--home-line);
        border-radius: var(--home-radius);
        box-shadow: var(--home-shadow);
        transition: transform 0.35s var(--home-ease), box-shadow 0.35s var(--home-ease), border-color 0.25s ease;
    }

    .stat-card {
        padding: 1.5rem 1.25rem;
        text-align: left;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: var(--home-ink);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.35s var(--home-ease);
    }

    .stat-card:hover::before {
        transform: scaleX(1);
    }

    .stat-card:hover,
    .info-card:hover,
    .weather-widget:hover {
        transform: translateY(-4px);
        box-shadow: var(--home-shadow-hover);
        border-color: #d4d4d8;
    }

    .info-card {
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .mantenimiento-layout > [class*="col-"] {
        display: flex;
    }

    .mantenimiento-layout .info-card {
        width: 100%;
    }

    .mantenimiento-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        width: 100%;
        min-height: 100%;
    }

    .mantenimiento-sidebar .info-card {
        height: auto;
        flex: 0 0 auto;
    }

    .mantenimiento-sidebar .info-card-detalle {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 220px;
    }

    .mantenimiento-sidebar .info-card-detalle .notification-list {
        flex: 1 1 auto;
        max-height: none;
        min-height: 120px;
    }

    .info-card::after {
        display: none;
    }

    .info-title {
        font-family: 'Space Grotesk', 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: 1.05rem;
        letter-spacing: -0.02em;
        color: var(--home-ink);
        margin-bottom: 1.15rem;
        display: flex;
        align-items: center;
        gap: 0.65rem;
    }

    .info-title i {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: var(--home-ink);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
    }

    .stat-icon {
        width: 44px;
        height: 44px;
        margin: 0 0 1rem;
        background: var(--home-ink);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: white;
    }

    .stat-number {
        font-family: 'Space Grotesk', 'DM Sans', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        letter-spacing: -0.04em;
        margin-bottom: 0.25rem;
        color: var(--home-ink);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.78rem;
        color: var(--home-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 600;
    }

    .quick-actions {
        padding: 0;
        margin-bottom: 1.75rem;
    }

    .quick-actions .row {
        margin-left: -0.4rem;
        margin-right: -0.4rem;
    }

    .quick-actions [class*="col-"] {
        padding: 0.4rem;
    }

    .action-btn {
        background: var(--home-white);
        border: 1px solid var(--home-line);
        border-radius: var(--home-radius-sm);
        padding: 1.35rem 0.85rem;
        color: var(--home-ink);
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.65rem;
        transition: all 0.3s var(--home-ease);
        font-weight: 600;
        font-size: 0.88rem;
        text-align: center;
        box-shadow: var(--home-shadow);
        height: 100%;
        min-height: 110px;
    }

    .action-btn i {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: var(--home-ink);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        transition: transform 0.3s var(--home-ease);
    }

    .action-btn:hover {
        transform: translateY(-4px);
        box-shadow: var(--home-shadow-hover);
        color: var(--home-ink);
        text-decoration: none;
        background: var(--home-ink);
        border-color: var(--home-ink);
        color: #ffffff;
    }

    .action-btn:hover i {
        background: #ffffff;
        color: var(--home-ink);
        transform: scale(1.05);
    }

    .charts-section {
        padding: 1.75rem;
        margin-bottom: 1.75rem;
    }

    .chart-container {
        height: 300px;
        margin: 1rem 0 0;
        position: relative;
    }

    .chart-title {
        font-family: 'Space Grotesk', 'DM Sans', sans-serif;
        color: var(--home-ink);
        font-weight: 600;
        letter-spacing: -0.02em;
        margin-bottom: 0.5rem;
        text-align: left;
        font-size: 1.2rem;
    }

    .weather-widget {
        padding: 1.5rem;
        height: 220px;
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--home-white);
    }

    .weather-widget img {
        max-width: 160px;
        width: 70%;
        height: auto;
        filter: contrast(1.05);
    }

    .suggestion-box {
        padding: 1.5rem;
    }

    .suggestion-textarea {
        background: var(--home-soft);
        border: 1px solid var(--home-line);
        border-radius: var(--home-radius-sm);
        color: var(--home-ink);
        padding: 1rem 1.1rem;
        width: 100%;
        min-height: 96px;
        resize: vertical;
        font-family: inherit;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .suggestion-textarea::placeholder {
        color: #a1a1aa;
    }

    .suggestion-textarea:focus {
        outline: none;
        border-color: var(--home-ink);
        box-shadow: 0 0 0 3px rgba(10, 10, 10, 0.08);
        background: #ffffff;
    }

    .send-btn {
        background: var(--home-ink);
        border: 1px solid var(--home-ink);
        border-radius: 999px;
        padding: 0.7rem 1.6rem;
        color: white;
        font-weight: 600;
        transition: all 0.3s var(--home-ease);
        font-size: 0.9rem;
        letter-spacing: 0.01em;
    }

    .send-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--home-shadow-hover);
        background: #262626;
        border-color: #262626;
        color: #fff;
    }

    .mission-statement {
        padding: 1.75rem 1.5rem;
        text-align: left;
        font-style: normal;
        position: relative;
        font-size: 1rem;
        color: var(--home-ink);
        line-height: 1.55;
        background: var(--home-ink) !important;
        color: #ffffff !important;
        border: none !important;
    }

    .mission-statement p {
        position: relative;
        z-index: 1;
        margin: 0;
        font-weight: 500;
        letter-spacing: -0.01em;
    }

    .mission-statement::before,
    .mission-statement::after {
        display: none;
    }

    .progress-bar {
        background: #ececef;
        border: none;
        border-radius: 999px;
        height: 6px;
        margin: 0.35rem 0 0.85rem;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--home-ink);
        border-radius: 999px;
        transition: width 2s var(--home-ease);
    }

    .metric-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.15rem;
        padding: 0.15rem 0;
    }

    .metric-label {
        font-weight: 500;
        color: var(--home-muted);
        font-size: 0.9rem;
    }

    .metric-value {
        font-family: 'Space Grotesk', 'DM Sans', sans-serif;
        font-weight: 700;
        color: var(--home-ink);
        letter-spacing: -0.02em;
    }

    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.4rem;
        margin-top: 0.85rem;
    }

    .calendar-day {
        background: var(--home-soft);
        border: 1px solid transparent;
        border-radius: 10px;
        padding: 0.7rem 0.35rem;
        text-align: center;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--home-ink);
        position: relative;
        transition: all 0.25s var(--home-ease);
        cursor: pointer;
        aspect-ratio: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .calendar-day-empty {
        visibility: hidden;
        pointer-events: none;
    }

    .calendar-day:hover {
        background: #ffffff;
        border-color: var(--home-ink);
        transform: translateY(-2px);
    }

    .calendar-day.event {
        background: #ffffff;
        border: 1px solid var(--home-ink);
    }

    .calendar-day.selected {
        background: var(--home-ink);
        color: #fff;
        border-color: var(--home-ink);
        box-shadow: var(--home-shadow);
    }

    .calendar-day .event-dot {
        width: 5px;
        height: 5px;
        background: var(--home-ink);
        border-radius: 999px;
        display: inline-block;
        margin-top: 4px;
    }

    .calendar-day.selected .event-dot {
        background: #ffffff;
    }

    .status-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.7rem;
        font-weight: 600;
        border: 1px solid transparent;
        white-space: nowrap;
    }

    .status-chip-aceptado {
        background: #0a0a0a;
        color: #ffffff;
        border-color: #0a0a0a;
    }

    .status-chip-rechazado {
        background: #ffffff;
        color: #0a0a0a;
        border-color: #0a0a0a;
        text-decoration: line-through;
        text-decoration-thickness: 1px;
    }

    .status-chip-pendiente {
        background: #f4f4f5;
        color: #0a0a0a;
        border-color: #a1a1aa;
    }

    .status-chip-realizado {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }

    .status-chip-vencido {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fca5a5;
    }

    .status-chip-cancelado {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .calendar-day.event-realizado {
        background: rgba(34, 197, 94, 0.14) !important;
        border-color: rgba(34, 197, 94, 0.35) !important;
    }

    .calendar-day.event-pendiente {
        background: rgba(245, 158, 11, 0.14) !important;
        border-color: rgba(245, 158, 11, 0.35) !important;
    }

    .calendar-day.event-vencido {
        background: rgba(239, 68, 68, 0.12) !important;
        border-color: rgba(239, 68, 68, 0.35) !important;
    }

    .mantenimiento-chart-container {
        height: 180px;
        margin-top: 1rem;
    }

    .cumplimiento-resumen {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .cumplimiento-resumen .porcentaje {
        font-size: 1.75rem;
        font-weight: 700;
        color: #334155;
        line-height: 1;
    }

    .mantenimiento-mini-stats {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
    }

    .mantenimiento-mini-stat {
        flex: 1;
        min-width: 90px;
        padding: 0.55rem 0.65rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        text-align: center;
    }

    .mantenimiento-mini-stat strong {
        display: block;
        font-size: 1.1rem;
        color: #334155;
    }

    .mantenimiento-mini-stat span {
        font-size: 0.72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .calendar-month-title {
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .calendar-labels {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 0.4rem;
        color: var(--home-muted);
        text-transform: uppercase;
        font-size: 0.68rem;
        letter-spacing: 0.1em;
        text-align: center;
        font-weight: 600;
    }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        margin-top: 0.75rem;
        max-height: 360px;
        overflow-y: auto;
    }

    .notification-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem;
        border-radius: var(--home-radius-sm);
        background: var(--home-soft);
        border: 1px solid var(--home-line);
        transition: all 0.25s var(--home-ease);
    }

    .notification-item:hover {
        transform: translateX(3px);
        border-color: #d4d4d8;
        background: #ffffff;
        box-shadow: var(--home-shadow);
    }

    .notification-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: var(--home-ink);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .notification-time {
        font-size: 0.75rem;
        color: var(--home-muted);
        font-weight: 500;
    }

    .notification-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.6rem;
        border-radius: 999px;
        background: var(--home-soft);
        color: var(--home-ink);
        border: 1px solid var(--home-line);
        font-size: 0.7rem;
        font-weight: 700;
    }

    .mining-dashboard .greeting-card {
        background: #0a0a0a !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: var(--home-shadow) !important;
    }

    .mining-dashboard .weather-widget,
    .mining-dashboard .stat-card,
    .mining-dashboard .info-card,
    .mining-dashboard .charts-section,
    .mining-dashboard .suggestion-box,
    .mining-dashboard .mining-visualization {
        background: #ffffff !important;
        border: 1px solid #e4e4e7 !important;
        color: #0a0a0a !important;
        box-shadow: var(--home-shadow) !important;
    }

    .mining-dashboard .mission-statement {
        background: #0a0a0a !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: var(--home-shadow) !important;
    }

    .mining-dashboard .action-btn {
        background: #ffffff !important;
        border: 1px solid #e4e4e7 !important;
        color: #0a0a0a !important;
        box-shadow: var(--home-shadow) !important;
    }

    .mining-dashboard .action-btn:hover {
        background: #0a0a0a !important;
        color: #ffffff !important;
        border-color: #0a0a0a !important;
        box-shadow: var(--home-shadow-hover) !important;
    }

    .mining-dashboard .send-btn,
    .mining-dashboard .btn-baseColor {
        background: #0a0a0a !important;
        border: 1px solid #0a0a0a !important;
        color: #ffffff !important;
    }

    .mining-dashboard .send-btn:hover,
    .mining-dashboard .btn-baseColor:hover {
        background: #262626 !important;
        color: #ffffff !important;
        border-color: #262626 !important;
        box-shadow: var(--home-shadow-hover) !important;
    }

    .mining-dashboard .stat-icon,
    .mining-dashboard .notification-icon,
    .mining-dashboard .info-title i {
        background: #0a0a0a !important;
        color: #ffffff !important;
    }

    .mining-dashboard .stat-number,
    .mining-dashboard .metric-value,
    .mining-dashboard .info-title,
    .mining-dashboard .text-orange,
    .mining-dashboard .chart-title,
    .mining-dashboard .weather-temp,
    .mining-dashboard h6,
    .mining-dashboard .fw-bold {
        color: #0a0a0a !important;
    }

    .mining-dashboard .mission-statement,
    .mining-dashboard .mission-statement p,
    .mining-dashboard .greeting-text {
        color: #ffffff !important;
    }

    .mining-dashboard .stat-label,
    .mining-dashboard .weather-desc,
    .mining-dashboard .text-muted,
    .mining-dashboard .notification-time,
    .mining-dashboard .calendar-labels,
    .mining-dashboard .metric-label {
        color: #525252 !important;
    }

    .mining-dashboard .suggestion-textarea {
        background: #fafafa !important;
        border: 1px solid #e4e4e7 !important;
        color: #0a0a0a !important;
    }

    .mining-dashboard .suggestion-textarea:focus {
        background: #ffffff !important;
        border-color: #0a0a0a !important;
        box-shadow: 0 0 0 3px rgba(10, 10, 10, 0.08) !important;
    }

    .mining-dashboard .progress-bar {
        background: #ececef !important;
        border: none !important;
    }

    .mining-dashboard .calendar-day,
    .mining-dashboard .notification-item,
    .mining-dashboard .card {
        background: #fafafa !important;
        border: 1px solid transparent !important;
        color: #0a0a0a !important;
        box-shadow: none !important;
    }

    .mining-dashboard .notification-item {
        border: 1px solid #e4e4e7 !important;
    }

    .mining-dashboard .progress-fill,
    .mining-dashboard .calendar-day.selected {
        background: #0a0a0a !important;
        color: #ffffff !important;
    }

    .mining-dashboard .calendar-day.event {
        background: #ffffff !important;
        border: 1px solid #0a0a0a !important;
    }

    .mining-dashboard .calendar-day:hover {
        background: #ffffff !important;
        border-color: #0a0a0a !important;
    }

    @media (max-width: 768px) {
        .greeting-text {
            font-size: 1.85rem;
        }

        .greeting-card,
        .weather-widget {
            height: 200px;
            min-height: 200px;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .calendar-day {
            padding: 0.45rem 0.15rem;
            font-size: 0.78rem;
        }
    }

    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="mining-dashboard">
    <div class="container-fluid">
        <!-- hero section -->
        <div class="hero-section">
            <div class="row align-items-stretch">
                <div class="col-lg-8 mb-4">
                    <div class="greeting-card">
                        @php
                        $date = intval($date);
                        if($formato == "am"){
                            if($date >= 5 && $date <= 11){
                                $saludo = "Buenos días";
                            } elseif($date == 12){ 
                                $saludo = "Buenas Noches";
                            }elseif($date >= 1 && $date <= 4) {
                                $saludo = "Buenas Noches";
                            }
                        }else{
                            if($date == 12){ 
                                $saludo = "Buenas Tardes";
                            }elseif($date >= 1 && $date <= 8){ 
                                $saludo = "Buenas Tardes";
                            }elseif($date >= 9 && $date <= 11){
                                $saludo = "Buenas Noches";
                            }
                        }
                        @endphp
                        
                        <div class="greeting-eyebrow">Panel principal</div>
                        <h3 class="greeting-text">Hola,<br>{{$saludo}}</h3>
                        <div class="time-indicator">
                            @if($formato == "am")
                                @if($date >= 5 && $date <= 11) 
                                   <i class="fas fa-sun"></i>
                                @elseif($date == 12) 
                                    <i class="fas fa-moon"></i>
                                @elseif($date >= 1 && $date <= 4) 
                                    <i class="fas fa-moon"></i>
                                @endif
                            @else
                                @if($date == 12) 
                                    <i class="fas fa-sun"></i>
                                @elseif($date >= 1 && $date <= 8) 
                                    <i class="fas fa-sun"></i>
                                @elseif($date >= 9 && $date <= 11) 
                                    <i class="fas fa-moon"></i>
                                @endif
                            @endif
                            <span>{{ now()->format('h:i') }} {{$formato == "am" ? "AM" : "PM"}} · {{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="weather-widget">
                        <img src= "{{ asset('Images/AUSTIN_POWDER.png') }}" width="400px"/>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions: solo módulos/vistas con permiso del usuario -->
        @if(auth()->user()->tipo != 'alumno' && auth()->user()->tipo != 'empresa')
        @php
            $modulosPermitidos = collect($varpantallas ?? [])
                ->pluck('nombre')
                ->map(fn ($n) => mb_strtolower(trim($n)))
                ->filter()
                ->values();
            $rutasPermitidas = collect($varsubmenus ?? [])
                ->pluck('descripcion')
                ->map(fn ($d) => mb_strtolower(trim($d, '/')))
                ->filter()
                ->values();

            $tieneModulo = function (...$nombres) use ($modulosPermitidos) {
                foreach ($nombres as $nombre) {
                    if ($modulosPermitidos->contains(mb_strtolower(trim($nombre)))) {
                        return true;
                    }
                }
                return false;
            };

            $tieneRuta = function (...$rutas) use ($rutasPermitidas) {
                foreach ($rutas as $ruta) {
                    $ruta = mb_strtolower(trim($ruta, '/'));
                    if ($rutasPermitidas->contains($ruta)) {
                        return true;
                    }
                    if ($rutasPermitidas->contains(fn ($r) => str_starts_with($r, $ruta . '/') || str_starts_with($ruta, $r . '/'))) {
                        return true;
                    }
                }
                return false;
            };

            $accesosRapidos = collect([
                [
                    'label' => 'Recursos Humanos',
                    'route' => 'verempleados',
                    'icon' => 'fa-solid fa-user-tie',
                    'visible' => $tieneModulo('Recursos Humanos') || $tieneRuta('Empleados'),
                ],
                [
                    'label' => 'Ventas',
                    'route' => 'ventas.pedidos',
                    'icon' => 'fa-solid fa-chart-simple',
                    'visible' => $tieneModulo('Gestion de Pedidos') || $tieneRuta('pedidos'),
                ],
                [
                    'label' => 'Compras',
                    'route' => 'ordcompras.index',
                    'icon' => 'fa-solid fa-file-invoice-dollar',
                    'visible' => $tieneModulo('Ordenes de Compra') || $tieneRuta('OrdenCompras'),
                ],
                [
                    'label' => 'Maquinaria',
                    'route' => 'produccion.maquinas',
                    'icon' => 'fa-solid fa-gears',
                    'visible' => $tieneModulo('Gestion Maquinaria') || $tieneRuta('maquinas'),
                ],
                [
                    'label' => 'Reportes',
                    'route' => 'reportePosicionFinanciera',
                    'icon' => 'fa-solid fa-chart-line',
                    'visible' => $tieneModulo('Reportes')
                        || $tieneRuta('Reportes/PosicionFinanciera', 'ReportesTesoreria/Ingresos', 'ReportesTesoreria/Gastos'),
                ],
                [
                    'label' => 'Producción',
                    'route' => 'produccion.ordenes',
                    'icon' => 'fa-solid fa-industry',
                    'visible' => $tieneModulo('Gestion de Produccion') || $tieneRuta('produccion'),
                ],
            ])->filter(fn ($acceso) => $acceso['visible'])->values();
        @endphp
        @if($accesosRapidos->isNotEmpty())
        <div class="quick-actions">
            <div class="row text-center">
                @foreach($accesosRapidos as $acceso)
                <div class="col-lg-2 col-6 p-1">
                    <a href="{{ route($acceso['route']) }}" class="action-btn">
                        <i class="{{ $acceso['icon'] }}"></i>
                        <span>{{ $acceso['label'] }}</span>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

        <!-- Calendario de mantenimiento -->
        @php
            $mm = $datosMantenimientoMaquinas ?? [];
            $mmRealizados = $mm['mantenimientoSemanalRealizados'] ?? [];
            $mmPendientes = $mm['mantenimientoSemanalPendientes'] ?? [];
            $mmCumplimiento = $mm['cumplimientoSemanal'] ?? 0;
            $mmTotal = $mm['totalSemanal'] ?? 0;
            $mmHechos = $mm['realizadosSemana'] ?? 0;
            $mmFaltan = $mm['pendientesSemana'] ?? 0;
        @endphp
        <div class="row mb-4 align-items-stretch mantenimiento-layout">
            <div class="col-lg-8 mb-4">
                <div class="info-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                        <div class="info-title mb-0">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                            Calendario de mantenimiento de máquinas
                        </div>
                        <div class="calendar-month-title" id="calendarMantenimientoMesTitulo"></div>
                    </div>
                    <p class="text-muted fs-8 mb-2">Mantenimientos preventivos semanales programados y ejecutados en el mes.</p>
                    <div class="calendar-labels">
                        <span>Lu</span>
                        <span>Ma</span>
                        <span>Mi</span>
                        <span>Ju</span>
                        <span>Vi</span>
                        <span>Sa</span>
                        <span>Do</span>
                    </div>
                    <div class="calendar-grid" id="calendarGridMantenimiento"></div>

                    <div class="cumplimiento-resumen">
                        <div class="porcentaje">{{ number_format($mmCumplimiento, 1) }}%</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold text-secondary">Cumplimiento semanal actual</div>
                            <div class="text-muted fs-8">{{ $mmHechos }} de {{ $mmTotal }} actividades semanales realizadas</div>
                            <div class="progress-bar mt-2">
                                <div class="progress-fill" style="width: {{ min(100, $mmCumplimiento) }}%"></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-secondary mt-3 mb-2 fs-8 fw-semibold">Cumplimiento de las últimas 6 semanas</h6>
                    <div class="mantenimiento-chart-container">
                        <canvas id="mantenimientoCumplimientoChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <div class="mantenimiento-sidebar">
                    <div class="info-card">
                        <div class="info-title">
                            <i class="fa-solid fa-list-check"></i>
                            Preventivos semanales
                        </div>
                        <div class="mantenimiento-mini-stats">
                            <div class="mantenimiento-mini-stat">
                                <strong>{{ $mmHechos }}</strong>
                                <span>Hechos</span>
                            </div>
                            <div class="mantenimiento-mini-stat">
                                <strong>{{ $mmFaltan }}</strong>
                                <span>Faltan</span>
                            </div>
                            <div class="mantenimiento-mini-stat">
                                <strong>{{ $mmTotal }}</strong>
                                <span>Total</span>
                            </div>
                        </div>
                        @if ($mmTotal > 0)
                            <div class="mb-2">
                                <small class="text-success fw-semibold d-block mb-1"><i class="fa-solid fa-circle-check me-1"></i>Realizados esta semana</small>
                                @forelse ($mmRealizados as $item)
                                    <div class="notification-item py-2">
                                        <div class="notification-icon" style="width:30px;height:30px;font-size:.75rem"><i class="fa-solid fa-check"></i></div>
                                        <div class="fs-8">
                                            <strong>{{ $item['maquina'] }}</strong>
                                            <div class="text-muted">{{ $item['actividad'] }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted fs-8 ps-1">Ninguno registrado aún.</div>
                                @endforelse
                            </div>
                            <div>
                                <small class="text-warning fw-semibold d-block mb-1"><i class="fa-solid fa-clock me-1"></i>Pendientes o vencidos</small>
                                @forelse ($mmPendientes as $item)
                                    <div class="notification-item py-2">
                                        <div class="notification-icon" style="width:30px;height:30px;font-size:.75rem;background:{{ $item['estatus'] === 'VENCIDO' ? 'linear-gradient(135deg,#b91c1c,#ef4444)' : 'linear-gradient(135deg,#b45309,#f59e0b)' }}"><i class="fa-solid fa-wrench"></i></div>
                                        <div class="fs-8 w-100">
                                            <div class="d-flex justify-content-between gap-1">
                                                <strong>{{ $item['maquina'] }}</strong>
                                                <span class="status-chip {{ $item['estatus'] === 'VENCIDO' ? 'status-chip-vencido' : 'status-chip-pendiente' }}">{{ $item['estatus'] }}</span>
                                            </div>
                                            <div class="text-muted">{{ $item['actividad'] }}</div>
                                            @if (!empty($item['proxima_ejecucion']))
                                                <div class="text-muted">Próxima: {{ \Carbon\Carbon::parse($item['proxima_ejecucion'])->format('d/m/Y') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-muted fs-8 ps-1">¡Todo al día esta semana!</div>
                                @endforelse
                            </div>
                        @else
                            <div class="text-muted fs-8 mt-2">
                                No hay actividades preventivas semanales programadas.
                                Configure la programación desde el módulo de máquinas.
                            </div>
                        @endif
                    </div>

                    <div class="info-card info-card-detalle">
                        <div class="info-title">
                            <i class="fa-solid fa-calendar-day"></i>
                            Detalle del día seleccionado
                        </div>
                        <div class="mt-3 mb-2">
                            <small class="text-muted">Fecha:</small>
                            <div class="fw-bold" id="calendarMantenimientoFechaLabel">—</div>
                        </div>
                        <div class="notification-list" id="calendarMantenimientoDiaLista">
                            <div class="notification-item">
                                <div class="notification-icon">
                                    <i class="fa-solid fa-calendar-days"></i>
                                </div>
                                <div class="text-muted fs-8">Selecciona un día en el calendario para ver los mantenimientos.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="row">
            <!-- Statistics Dashboard -->
            <div class="col">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Operación Continua</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-number">98%</div>
                        <div class="stat-label">Cumplimiento</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number">200</div>
                        <div class="stat-label">Procesos Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-number">150+</div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                </div>
            </div>

             
      
        </div>

        @if(auth()->user()->tipo != 'alumno' && auth()->user()->tipo != 'empresa')
        <!-- Charts Section -->
        <div class="charts-section">
            <h4 class="chart-title">Indicadores Generales del ERP</h4>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-center mb-3">Operaciones Mensuales</h6>
                    <div class="chart-container">
                        <canvas id="productionChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-center mb-3">Distribución por Área</h6>
                    <div class="chart-container">
                        <canvas id="efficiencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endif
        

        <!-- Performance Metrics -->
        <div class="row mb-3">
            <div class="col-md-4 mb-4">
                <div class="stat-card">
                    <h6 class="text-center mb-3">Rendimiento Operativo</h6>
                    <div class="metric-item">
                        <span class="metric-label">Eficiencia</span>
                        <span class="metric-value">93%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 93%"></div>
                    </div>
                    
                    <div class="metric-item">
                        <span class="metric-label">Seguimiento</span>
                        <span class="metric-value">89%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 89%"></div>
                    </div>
                    
                    <div class="metric-item">
                        <span class="metric-label">Cumplimiento</span>
                        <span class="metric-value">91%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 91%"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stat-card">
                    <h6 class="text-center mb-3">Indicadores Administrativos</h6>
                    <div class="metric-item">
                        <span class="metric-label">Solicitudes pendientes</span>
                        <span class="metric-value">12</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Procesos automatizados</span>
                        <span class="metric-value">24</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Tareas programadas</span>
                        <span class="metric-value">8</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Módulos activos</span>
                        <span class="metric-value">100%</span>
                    </div>
                </div>
            </div>
       
            <div class="col-lg-4">
                <div class="mission-statement mb-2">
                    <p class="mb-0">
                        "Centraliza tu operación, consulta indicadores clave y da seguimiento a los procesos de tu organización."
                    </p>
                </div>

                <!-- Suggestion Box -->
                <div class="suggestion-box mb-4">
                    <h6 class="mb-3 text-center">Buzón de Sugerencias</h6>
                    <div class="row">
                        <textarea class="suggestion-textarea" placeholder="Comparte ideas para mejorar la operación del sistema..."></textarea>

                        <div class="align-items-end mt-2">
                            <button class="send-btn text-truncate">
                                <i class="fas fa-paper-plane me-2"></i>
                                Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Add some interactive animations
document.addEventListener('DOMContentLoaded', function() {
    const mantenimientosCalendario = @json($mm['mantenimientosCalendario'] ?? []);
    const graficaCumplimiento = @json($mm['graficaCumplimiento'] ?? ['labels' => [], 'valores' => []]);
    const calendarGridMantenimiento = document.getElementById('calendarGridMantenimiento');
    const calendarMantenimientoFechaLabel = document.getElementById('calendarMantenimientoFechaLabel');
    const calendarMantenimientoDiaLista = document.getElementById('calendarMantenimientoDiaLista');
    const calendarMantenimientoMesTitulo = document.getElementById('calendarMantenimientoMesTitulo');
    const escHtml = function (value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };
    const estadoClaseMantenimiento = function (estatus) {
        const e = String(estatus || '').trim().toUpperCase();
        if (e === 'REALIZADO') {
            return 'status-chip status-chip-realizado';
        }
        if (e === 'VENCIDO') {
            return 'status-chip status-chip-vencido';
        }
        if (e === 'CANCELADO') {
            return 'status-chip status-chip-cancelado';
        }
        return 'status-chip status-chip-pendiente';
    };
    const estadoClase = function (estatus) {
        const e = String(estatus || '').trim().toLowerCase();
        if (e === 'aceptado') {
            return 'status-chip status-chip-aceptado';
        }
        if (e === 'rechazado') {
            return 'status-chip status-chip-rechazado';
        }
        return 'status-chip status-chip-pendiente';
    };
    const estadoTexto = function (estatus) {
        const e = String(estatus || '').trim();
        return e !== '' ? e : 'Pendiente';
    };
    const pad2 = function (n) { return String(n).padStart(2, '0'); };
    const hoy = new Date();
    const currentYear = hoy.getFullYear();
    const currentMonth = hoy.getMonth();
    const mantenimientosPorFecha = mantenimientosCalendario.reduce(function (acc, item) {
        const key = item && item.fecha ? String(item.fecha) : '';
        if (!key) {
            return acc;
        }
        if (!acc[key]) {
            acc[key] = [];
        }
        acc[key].push(item);
        return acc;
    }, {});

    const claseCeldaPorEventos = function (lista) {
        if (!lista || !lista.length) {
            return '';
        }
        if (lista.some(function (i) { return String(i.estatus).toUpperCase() === 'VENCIDO'; })) {
            return ' event-vencido';
        }
        if (lista.some(function (i) { return String(i.estatus).toUpperCase() === 'PENDIENTE'; })) {
            return ' event-pendiente';
        }
        if (lista.some(function (i) { return String(i.estatus).toUpperCase() === 'REALIZADO'; })) {
            return ' event-realizado';
        }
        return ' event';
    };

    const renderMantenimientosDia = function (dateIso) {
        if (!calendarMantenimientoFechaLabel || !calendarMantenimientoDiaLista) {
            return;
        }
        const fecha = new Date(dateIso + 'T00:00:00');
        calendarMantenimientoFechaLabel.textContent = fecha.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });

        const lista = mantenimientosPorFecha[dateIso] || [];
        calendarMantenimientoDiaLista.innerHTML = '';
        if (!lista.length) {
            const empty = document.createElement('div');
            empty.className = 'notification-item';
            empty.innerHTML =
                '<div class="notification-icon"><i class="fa-solid fa-calendar-check"></i></div>' +
                '<div class="text-muted fs-8">Sin mantenimientos registrados para este día.</div>';
            calendarMantenimientoDiaLista.appendChild(empty);
            return;
        }

        lista.forEach(function (item) {
            const row = document.createElement('div');
            row.className = 'notification-item';
            row.innerHTML =
                '<div class="notification-icon"><i class="fa-solid fa-gears"></i></div>' +
                '<div class="w-100">' +
                '  <div class="d-flex justify-content-between align-items-center gap-2">' +
                '    <strong class="fs-8">' + escHtml(item.maquina || 'Máquina') + '</strong>' +
                '    <span class="' + estadoClaseMantenimiento(item.estatus) + '">' + escHtml(item.estatus || 'PENDIENTE') + '</span>' +
                '  </div>' +
                '  <div class="text-muted fs-8">' + escHtml(item.actividad || '—') + '</div>' +
                '</div>';
            calendarMantenimientoDiaLista.appendChild(row);
        });
    };

    const renderCalendarMantenimiento = function () {
        if (!calendarGridMantenimiento) {
            return;
        }
        calendarGridMantenimiento.innerHTML = '';
        const firstDay = new Date(currentYear, currentMonth, 1);
        const firstWeekIndex = (firstDay.getDay() + 6) % 7;
        const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();

        if (calendarMantenimientoMesTitulo) {
            calendarMantenimientoMesTitulo.textContent = firstDay.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
        }

        for (let i = 0; i < firstWeekIndex; i++) {
            const empty = document.createElement('div');
            empty.className = 'calendar-day calendar-day-empty';
            empty.textContent = '·';
            calendarGridMantenimiento.appendChild(empty);
        }

        for (let day = 1; day <= totalDays; day++) {
            const iso = currentYear + '-' + pad2(currentMonth + 1) + '-' + pad2(day);
            const eventos = mantenimientosPorFecha[iso] || [];
            const cell = document.createElement('div');
            cell.className = 'calendar-day' + (eventos.length > 0 ? claseCeldaPorEventos(eventos) : '');
            cell.dataset.dateIso = iso;
            cell.innerHTML = String(day) + (eventos.length > 0 ? ' <span class="event-dot"></span>' : '');
            cell.addEventListener('click', function () {
                calendarGridMantenimiento.querySelectorAll('.calendar-day.selected').forEach(function (d) {
                    d.classList.remove('selected');
                });
                cell.classList.add('selected');
                renderMantenimientosDia(iso);
            });
            calendarGridMantenimiento.appendChild(cell);
        }
    };

    renderCalendarMantenimiento();
    if (calendarGridMantenimiento) {
        const initialIso = currentYear + '-' + pad2(currentMonth + 1) + '-' + pad2(hoy.getDate());
        const initialCell = calendarGridMantenimiento.querySelector('[data-date-iso="' + initialIso + '"]');
        if (initialCell) {
            initialCell.classList.add('selected');
            renderMantenimientosDia(initialIso);
        }
    }

    const mantenimientoChartCanvas = document.getElementById('mantenimientoCumplimientoChart');
    if (mantenimientoChartCanvas && typeof Chart !== 'undefined') {
        new Chart(mantenimientoChartCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: graficaCumplimiento.labels || [],
                datasets: [{
                    label: 'Cumplimiento %',
                    data: graficaCumplimiento.valores || [],
                    backgroundColor: 'rgba(71, 85, 105, 0.75)',
                    borderColor: '#475569',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) { return value + '%'; },
                            color: '#6b7280'
                        },
                        grid: { color: 'rgba(51, 65, 85, 0.08)' }
                    },
                    x: {
                        ticks: { color: '#6b7280', maxRotation: 45, minRotation: 0, font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    const visitasPorFecha = {};
    const renderVisitasDia = function () {};
    const renderCalendarVisitas = function () {};

    // Animate stat cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.stat-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
    
    // Add click effects to action buttons
   // document.querySelectorAll('.action-btn').forEach(btn => {
      //  btn.addEventListener('click', function(e) {
    //        e.preventDefault();
    //        this.style.transform = 'scale(0.95)';
     //       setTimeout(() => {
     //           this.style.transform = 'translateY(-3px)';
      //      }, 150);
       // });
    //
    //});

    // Operations Chart
    const productionCtx = document.getElementById('productionChart').getContext('2d');
    const productionChart = new Chart(productionCtx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Operaciones',
                data: [320, 410, 380, 520, 480, 560],
                borderColor: '#000000',
                backgroundColor: 'rgba(0, 0, 0, 0.08)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Meta',
                data: [300, 400, 400, 500, 500, 550],
                borderColor: '#666666',
                backgroundColor: 'transparent',
                borderDash: [5, 5],
                tension: 0.3,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '#000000'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.12)'
                    },
                    ticks: {
                        color: '#444444'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.12)'
                    },
                    ticks: {
                        color: '#444444'
                    }
                }
            }
        }
    });

    // Area Distribution Chart
    const efficiencyCtx = document.getElementById('efficiencyChart').getContext('2d');
    const efficiencyChart = new Chart(efficiencyCtx, {
        type: 'doughnut',
        data: {
            labels: ['Ventas', 'Compras', 'Inventario', 'Administración'],
            datasets: [{
                data: [40, 30, 20, 10],
                backgroundColor: [
                    '#000000',
                    '#333333',
                    '#666666',
                    '#bbbbbb'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#000000'
                    }
                }
            }
        }
    });

    // Animate progress bars
    setTimeout(() => {
        document.querySelectorAll('.progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 500);
        });
    }, 1000);
});

</script>

@endsection