
        let currentDate = new Date();

        // Inicializar mini calendario
        document.addEventListener('DOMContentLoaded', function() {
        generateMiniCalendar();
        updateCurrentMonth();
        });

        function generateMiniCalendar() {
        const grid = document.getElementById('miniCalendar');
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        // Limpiar grid
        grid.innerHTML = '';
        
        // Agregar headers de días
        const daysOfWeek = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
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
        for (let i = 0; i < 35; i++) {
                const currentDay = new Date(startDate);
                currentDay.setDate(startDate.getDate() + i);
                
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = currentDay.getDate();
                
                // Verificar si es del mes actual
                if (currentDay.getMonth() !== month) {
                dayElement.style.opacity = '0.3';
                }
                
                // Verificar si es hoy
                const today = new Date();
                if (currentDay.toDateString() === today.toDateString()) {
                dayElement.classList.add('today');
                }
                
                // Simular eventos (días 5, 12, 18, 25)
                if ([5, 12, 18, 25].includes(currentDay.getDate())) {
                dayElement.classList.add('has-event');
                }
                
                grid.appendChild(dayElement);
        }
        }

        function updateCurrentMonth() {
        const monthNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        // El título del calendario se actualiza automáticamente
        }

        function previousMonth() {
        currentDate.setMonth(currentDate.getMonth() - 1);
        generateMiniCalendar();
        }

        function nextMonth() {
        currentDate.setMonth(currentDate.getMonth() + 1);
        generateMiniCalendar();
        }

        // Animaciones para las tarjetas de estadísticas
        document.addEventListener('DOMContentLoaded', function() {
        const statCards = document.querySelectorAll('.stat-card');
        
        statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate__animated', 'animate__fadeInUp');
        });
        });

        // Actualizar hora en tiempo real
        setInterval(function() {
        const now = new Date();
        const timeElement = document.querySelector('.welcome-subtitle');
        if (timeElement) {
                timeElement.innerHTML = `
                <i class="fas fa-clock me-2"></i>
                ${now.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })} - ${now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}
                `;
        }
        }, 1000);
