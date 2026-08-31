@if(isset($contratosPorVencer) && $contratosPorVencer->isNotEmpty())
    <button type="button"
        class="btn btn-baseColor-light position-relative"
        data-bs-toggle="offcanvas"
        data-bs-target="#offcanvasContratosVencer"
        aria-controls="offcanvasContratosVencer"
        title="Contratos por vencer">
        <i class="fa-solid fa-bell"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger fs-9">
            {{ $contratosPorVencer->count() }}
            <span class="visually-hidden">alertas de contrato</span>
        </span>
    </button>
@endif
