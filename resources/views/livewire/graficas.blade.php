<div>
    <div class="card">
        <div class="p-2 d-flex justify-content-end">
            <select id="selectedSucursal" wire:model="selectedSucursal" class="form-control form-control-sm w-auto">
                {{-- <option value="">Seleccione una sucursal</option> --}}
                <option value="0">TODAS LAS SUCURSALES</option>
                @foreach($sucursales as $sucursal)
                <option value="{{ $sucursal->idsucursal }}">{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="card-body">
            <canvas id="charts2" height="231"></canvas>
        </div>
        <div class="card-footer">
            <div class="row justify-content-center">
                @foreach ($grafica2info as $grafica2)
                    <div class="col-6 col-md-2 ">
                        <p class="small"><strong>{{ $grafica2->mes }}</strong><br>{{ "Canjes: " . $grafica2->total_prestamos }}<br>{{ "Total: $" . number_format($grafica2->saldo, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    @section('javascript')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
         document.addEventListener('DOMContentLoaded', function () {
            var chart2;
            function renderChart(data) {
                if (chart2) {
                    chart2.destroy();
                }
                console.log(data);
                chart2 = new Chart(document.getElementById('charts2').getContext('2d'), {
                    
                    type: data.type,
                    data: data.data,
                    options: {
                        ...data.options,
                        plugins: {
                            ...data.options.plugins,
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                    if (tooltipItem.datasetIndex === 0) {
                                        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(tooltipItem.raw);
                                    } else {
                                        return tooltipItem.raw;
                                    }
                                }
                                }
                            }
                        }
                    }
                });
            }
            renderChart({!! json_encode($charts[0]) !!});
            Livewire.on('chartDataUpdated', function (data) {
                renderChart(data);
            });
        });
    </script>
     @endsection
</div>
