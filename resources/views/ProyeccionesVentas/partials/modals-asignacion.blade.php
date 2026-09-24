<div class="modal fade cc-modal" id="modalAsigVer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="asig-ver-title">Ver asignación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="asig-ver-body"></div>
            <div class="cc-modal-actions">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="cc-btn" id="asig-ver-editar"><i class="fa-solid fa-pen"></i> Editar</button>
                <button type="button" class="cc-btn cc-btn-ink" id="asig-ver-permisos"><i class="fa-solid fa-user-lock"></i> Permisos</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade cc-modal" id="modalAsigEditar" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="form-asig-editar">
            <div class="modal-header">
                <h5 class="modal-title" id="asig-edit-title">Editar productos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem" id="asig-edit-sub">Elige los productos SAP con acceso en este cliente. Si es el usuario a cargo, se replican a quienes tengan acceso.</p>
                <div class="cc-panel-head" style="margin-bottom:.55rem">
                    <h3 style="font-size:.92rem"><i class="fa-solid fa-list"></i> Productos con acceso</h3>
                    <div class="cc-panel-head-tools">
                        <label class="cc-todas-toggle" title="Muestra todos los productos de la empresa, no solo los vendidos a este cliente">
                            <input type="checkbox" id="asig-edit-catalogo"> Ver todos los productos
                        </label>
                        <label class="cc-todas-toggle">
                            <input type="checkbox" id="asig-edit-todas"> Todas
                        </label>
                    </div>
                </div>
                <div class="cc-grupos-cta-tools">
                    <div class="cc-pick-search">
                        <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input id="asig-edit-q" class="cc-input" type="search" placeholder="Buscar producto…">
                    </div>
                </div>
                <div class="cc-pick-list cc-check-list" id="asig-edit-list" style="max-height:340px">
                    <div class="cc-empty">Cargando productos…</div>
                </div>
            </div>
            <div class="cc-modal-actions">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink" id="asig-edit-guardar">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar productos
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade cc-modal" id="modalAsigPermisos" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="form-asig-permisos">
            <div class="modal-header">
                <h5 class="modal-title" id="asig-perm-title">Permisos del cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" style="font-size:.85rem">El usuario a cargo es el principal. <strong>Capturar</strong> sirve mientras el ciclo esté Abierto. <strong>Editar</strong> permite seguir modificando cuando el ciclo está En revisión o Cerrado.</p>

                <div class="cc-form-kicker">Usuario a cargo</div>
                <div class="cc-user-chosen" id="asig-perm-principal-card" style="margin-bottom:.85rem">
                    <div class="cc-user-chosen-info">
                        <span class="cc-avatar" id="asig-perm-av">—</span>
                        <div>
                            <strong id="asig-perm-nombre"></strong>
                            <small id="asig-perm-email"></small>
                        </div>
                    </div>
                    <span class="cc-badge cc-badge-ink">A cargo</span>
                </div>
                <div class="cc-perm-grid is-wide" id="asig-perm-principal"></div>

                <div class="cc-form-kicker" style="margin-top:1.1rem">Otros usuarios con acceso</div>
                <p class="text-muted mb-2" style="font-size:.8rem">Por ejemplo, alguien con permiso de revisar este cliente.</p>
                <div class="cc-pick-search">
                    <span class="cc-pick-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input id="asig-acc-q" class="cc-input" type="search" placeholder="Buscar usuario por nombre o correo…">
                </div>
                <div id="asig-acc-pick" class="cc-pick-list cc-user-list" style="max-height:160px;margin-bottom:.7rem" role="listbox"></div>
                <div class="cc-acc-add" id="asig-acc-add-box" hidden>
                    <div class="cc-acc-add-user">
                        <strong id="asig-acc-add-name"></strong>
                        <small id="asig-acc-add-email"></small>
                    </div>
                    <div class="cc-acc-add-perms" id="asig-acc-add-perms"></div>
                    <button type="button" class="cc-btn cc-btn-ink" id="asig-acc-add">
                        <i class="fa-solid fa-user-plus"></i> Agregar acceso
                    </button>
                </div>
                <div class="cc-table-wrap" style="max-height:240px">
                    <table class="cc-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Permisos</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="asig-acc-tbody">
                            <tr><td colspan="3"><div class="cc-empty">Nadie más tiene acceso a este cliente.</div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="cc-modal-actions">
                <button type="button" class="cc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="cc-btn cc-btn-ink" id="asig-perm-guardar">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar permisos
                </button>
            </div>
        </form>
    </div>
</div>
