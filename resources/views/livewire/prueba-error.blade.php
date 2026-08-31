@livewireStyles

<div>
    <h2>Prueba de Error en Livewire</h2>

    <button wire:click="lanzarError" class="btn btn-danger">
        Lanzar Error
    </button>
</div>

<script>
    Livewire.on('errorEvent', message => {
        alert('¡Error recibido!: ' + message);
    });
</script>

@livewireScripts
