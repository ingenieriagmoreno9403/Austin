@extends('layouts.app')
<link href="{{ asset('css/planning.css') }}" rel="stylesheet">


@section('content')
<div class="planning-container">
    <!-- Header -->
    <div class="planning-header">
        <h1 class="planning-title">
            <i class="fas fa-calendar-alt me-3"></i>
            Planning & Control
        </h1>
        <p class="planning-subtitle">Gestiona tus actividades y mantén el control de tus proyectos</p>
    </div>

    <div class="row">
        <!-- Calendario Principal -->
        <div class="col-lg-9">
            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <button class="nav-btn" onclick="previousMonth()">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h2 class="current-month" id="currentMonth">Enero 2024</h2>
                        <button class="nav-btn" onclick="nextMonth()">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" onclick="addActivity()">
                            <i class="fas fa-plus me-2"></i>Nueva Actividad
                        </button>
                        <button class="btn btn-outline-secondary" onclick="exportCalendar()">
                            <i class="fas fa-download me-2"></i>Exportar
                        </button>
                    </div>
                </div>

                <div class="calendar-grid" id="calendarGrid">
                    <!-- Los días del calendario se generarán dinámicamente -->
                </div>
            </div>
        </div>

        <!-- Barra de Herramientas -->
        <div class="col-lg-3">
            <div class="tools-sidebar">
                <!-- Estadísticas Rápidas -->
                <div class="tool-section">
                    <h4><i class="fas fa-chart-bar me-2"></i>Resumen</h4>
                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-number" id="pendingCount">{{ $stats['pending'] }}</div>
                            <div class="stat-label">Pendientes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="completedCount">{{ $stats['completed'] }}</div>
                            <div class="stat-label">Completadas</div>
                        </div>
                    </div>
                </div>

                <!-- Herramientas de Control -->
                <div class="tool-section">
                    <h4><i class="fas fa-tools me-2"></i>Herramientas</h4>
                    <button class="tool-btn" onclick="showActivityModal()">
                        <i class="fas fa-plus"></i>
                        Nueva Actividad
                    </button>
                    <button class="tool-btn" onclick="showProjectModal()">
                        <i class="fas fa-project-diagram"></i>
                        Nuevo Proyecto
                    </button>
                    <button class="tool-btn" onclick="showReportModal()">
                        <i class="fas fa-chart-line"></i>
                        Generar Reporte
                    </button>
                    <button class="tool-btn" onclick="showSettingsModal()">
                        <i class="fas fa-cog"></i>
                        Configuración
                    </button>
                </div>

                <!-- Actividades Recientes -->
                <div class="tool-section">
                    <h4><i class="fas fa-clock me-2"></i>Actividades Recientes</h4>
                    <div id="recentActivities">
                        @foreach($activities as $activity)
                            <div class="activity-item {{ $activity['priority'] === 'urgent' ? 'urgent' : ($activity['status'] === 'completed' ? 'completed' : '') }}">
                                <strong>{{ $activity['title'] }}</strong><br>
                                <small>{{ $activity['date'] }} {{ $activity['time'] ? $activity['time'] : '' }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Filtros -->
                <div class="tool-section">
                    <h4><i class="fas fa-filter me-2"></i>Filtros</h4>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showPending" checked>
                        <label class="form-check-label" for="showPending">
                            Pendientes
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showCompleted" checked>
                        <label class="form-check-label" for="showCompleted">
                            Completadas
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showUrgent" checked>
                        <label class="form-check-label" for="showUrgent">
                            Urgentes
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nueva Actividad -->
<div class="modal fade" id="activityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h3 class="modal-title" style="color: #2d1b4e;">
                    <i class="fas fa-plus me-2"></i>Nueva Actividad
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body form">
                <form id="activityForm">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Título de la Actividad</label>
                                <input type="text" class="form-control" id="activityTitle" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="activityDescription" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" class="form-control" id="activityDate" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Hora</label>
                                <input type="time" class="form-control" id="activityTime">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Prioridad</label>
                                <select class="form-select" id="activityPriority">
                                    <option value="low">Baja</option>
                                    <option value="medium" selected>Media</option>
                                    <option value="high">Alta</option>
                                    <option value="urgent">Urgente</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row border-0 justify-content-center pb-2">
                <button type="button" class="col-2 m-1 btn btn-outline-secondary btn-mode" data-bs-dismiss="modal"> <i class="fas fa-xmark me-2"></i> Cancelar </button>
                <button type="button" class="col-4 m-1 btn btn-violet" onclick="saveActivity()">
                    <i class="fas fa-check me-2"></i> Guardar Actividad
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

<script src="{{ asset('js/planing.js') }}"></script>
@section('js')
<script>
let currentDate = new Date();
let activities = @json($activities);

// Inicializar calendario
document.addEventListener('DOMContentLoaded', function() {
    generateCalendar();
    updateStats();
    setCurrentDate();
});

function generateCalendar() {
    const grid = document.getElementById('calendarGrid');
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Limpiar grid
    grid.innerHTML = '';
    
    // Agregar headers de días
    const daysOfWeek = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    daysOfWeek.forEach(day => {
        const dayHeader = document.createElement('div');
        dayHeader.className = 'calendar-day-header';
        dayHeader.textContent = day;
        grid.appendChild(dayHeader);
    });
    
    // Obtener primer día del mes y último día
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    // Generar días del calendario
    for (let i = 0; i < 42; i++) {
        const currentDay = new Date(startDate);
        currentDay.setDate(startDate.getDate() + i);
        
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        
        // Verificar si es del mes actual
        if (currentDay.getMonth() !== month) {
            dayElement.classList.add('other-month');
        }
        
        // Verificar si es hoy
        const today = new Date();
        if (currentDay.toDateString() === today.toDateString()) {
            dayElement.classList.add('today');
        }
        
        const dayNumber = document.createElement('div');
        dayNumber.className = 'day-number';
        dayNumber.textContent = currentDay.getDate();
        dayElement.appendChild(dayNumber);
        
        // Agregar actividades del día
        const dayActivities = getActivitiesForDate(currentDay);
        if (dayActivities.length > 0) {
            dayElement.classList.add('has-activities');
            
            // Verificar si hay actividades urgentes
            const hasUrgent = dayActivities.some(activity => activity.priority === 'urgent');
            if (hasUrgent) {
                dayElement.classList.add('has-urgent');
            }
            
            const activityCount = document.createElement('div');
            activityCount.className = 'activity-count';
            activityCount.textContent = dayActivities.length;
            dayElement.appendChild(activityCount);
            
            dayActivities.forEach(activity => {
                const activityIndicator = document.createElement('div');
                activityIndicator.className = 'activity-indicator';
                dayElement.appendChild(activityIndicator);
            });
        }
        
        dayElement.addEventListener('click', () => showDayActivities(currentDay));
        grid.appendChild(dayElement);
    }
    
    updateCurrentMonth();
}

function getActivitiesForDate(date) {
    const dateString = date.toISOString().split('T')[0];
    return activities.filter(activity => activity.date === dateString);
}

function updateCurrentMonth() {
    const monthNames = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];
    document.getElementById('currentMonth').textContent = 
        `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
}

function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    generateCalendar();
}

function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    generateCalendar();
}

function updateStats() {
    const pending = activities.filter(a => a.status === 'pending').length;
    const completed = activities.filter(a => a.status === 'completed').length;
    
    document.getElementById('pendingCount').textContent = pending;
    document.getElementById('completedCount').textContent = completed;
}

function setCurrentDate() {
    const today = new Date();
    document.getElementById('activityDate').value = today.toISOString().split('T')[0];
}

function showActivityModal() {
    const modal = new bootstrap.Modal(document.getElementById('activityModal'));
    modal.show();
}

function saveActivity() {
    const title = document.getElementById('activityTitle').value;
    const description = document.getElementById('activityDescription').value;
    const date = document.getElementById('activityDate').value;
    const time = document.getElementById('activityTime').value;
    const priority = document.getElementById('activityPriority').value;
    
    if (!title || !date) {
        Swal.fire('Error', 'Por favor completa los campos requeridos', 'error');
        return;
    }
    
    const newActivity = {
        id: activities.length + 1,
        title: title,
        description: description,
        date: date,
        time: time,
        priority: priority,
        status: 'pending'
    };
    
    activities.push(newActivity);
    generateCalendar();
    updateStats();
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('activityModal'));
    modal.hide();
    
    // Limpiar formulario
    document.getElementById('activityForm').reset();
    setCurrentDate();
    
    Swal.fire('Éxito', 'Actividad guardada correctamente', 'success');
}

function showDayActivities(date) {
    const dayActivities = getActivitiesForDate(date);
    const dateString = date.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    let activitiesHtml = '';
    if (dayActivities.length > 0) {
        dayActivities.forEach(activity => {
            const priorityClass = activity.priority === 'urgent' ? 'urgent' : 
                                activity.priority === 'high' ? 'high' : 
                                activity.status === 'completed' ? 'completed' : 'normal';
            const priorityBadge = `<span class="priority-badge ${activity.priority}">${activity.priority}</span>`;
            activitiesHtml += `
                <div class="activity-item ${priorityClass}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong>${activity.title}</strong>
                        <div class="d-flex gap-2">
                            ${priorityBadge}
                            <button class="btn btn-sm btn-outline-success" onclick="toggleActivityStatus(${activity.id})" title="${activity.status === 'pending' ? 'Marcar como completada' : 'Marcar como pendiente'}">
                                <i class="fas fa-${activity.status === 'pending' ? 'check' : 'undo'}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteActivity(${activity.id})" title="Eliminar actividad">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <small>${activity.time || 'Sin hora'} - ${activity.description}</small>
                </div>
            `;
        });
    } else {
        activitiesHtml = '<p class="text-muted">No hay actividades programadas para este día.</p>';
    }
    
    Swal.fire({
        title: `Actividades del ${dateString}`,
        html: activitiesHtml,
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#3b82f6'
    });
}

function addActivity() {
    showActivityModal();
}

function exportCalendar() {
    Swal.fire('Información', 'Función de exportación en desarrollo', 'info');
}

function showProjectModal() {
    Swal.fire('Información', 'Función de proyectos en desarrollo', 'info');
}

function showReportModal() {
    Swal.fire('Información', 'Función de reportes en desarrollo', 'info');
}

function showSettingsModal() {
    Swal.fire('Información', 'Función de configuración en desarrollo', 'info');
}

function toggleActivityStatus(activityId) {
    const activity = activities.find(a => a.id === activityId);
    if (activity) {
        activity.status = activity.status === 'pending' ? 'completed' : 'pending';
        generateCalendar();
        updateStats();
        
        Swal.fire({
            title: 'Estado Actualizado',
            text: `Actividad marcada como ${activity.status === 'completed' ? 'completada' : 'pendiente'}`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }
}

function deleteActivity(activityId) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            activities = activities.filter(a => a.id !== activityId);
            generateCalendar();
            updateStats();
            
            Swal.fire('Eliminado', 'La actividad ha sido eliminada', 'success');
        }
    });
}
</script>
@endsection 