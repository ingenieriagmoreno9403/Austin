
function bindModernFileInputs(root) {
    root = root || document;
    const fileInputs = root.querySelectorAll('.modern-file-input:not([data-file-bound])');

    fileInputs.forEach(function(fileInput) {
        fileInput.setAttribute('data-file-bound', '1');
        const wrapper = fileInput.querySelector('.file-input-wrapper');
        const input = fileInput.querySelector('.hidden-file-input');
        const preview = fileInput.querySelector('.file-preview');
        const filename = fileInput.querySelector('[id^="filename-"]');
        const filesize = fileInput.querySelector('[id^="filesize-"]');
        const progress = fileInput.querySelector('.progress-fill');

        if (!wrapper || !input) {
            return;
        }

        // Flag to prevent double processing
        let isProcessing = false;

        // Drag and drop functionality
        wrapper.addEventListener('dragover', function(e) {
            e.preventDefault();
            wrapper.classList.add('dragover');
        });

        wrapper.addEventListener('dragleave', function(e) {
            e.preventDefault();
            wrapper.classList.remove('dragover');
        });

        wrapper.addEventListener('drop', function(e) {
            e.preventDefault();
            wrapper.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0 && !isProcessing) {
                isProcessing = true;
                handleFileSelect(files[0], input, wrapper, preview, filename, filesize, progress);
                // Validate all inputs after file selection
                setTimeout(() => {
                    validateAllFileInputs();
                    isProcessing = false;
                }, 100);
            }
        });

        // File input change event - handle file selection
        input.addEventListener('change', function(e) {
            if (e.target.files.length > 0 && !isProcessing) {
                isProcessing = true;
                handleFileSelect(e.target.files[0], input, wrapper, preview, filename, filesize, progress);
                // Validate all inputs after file selection
                setTimeout(() => {
                    validateAllFileInputs();
                    isProcessing = false;
                }, 100);
            }
        });
    });

    // Initial validation (solo si ya existía el flujo global)
    setTimeout(function() {
        if (typeof validateAllFileInputs === 'function') {
            validateAllFileInputs();
        }
        if (typeof updateTotalSizeIndicator === 'function') {
            updateTotalSizeIndicator();
        }
    }, 500);
}

window.bindModernFileInputs = bindModernFileInputs;

document.addEventListener('DOMContentLoaded', function() {
    bindModernFileInputs(document);
});

function getAllowedFileTypes(input) {
    if (input && input.accept) {
        return input.accept
            .split(',')
            .map(function(type) { return type.trim().toLowerCase(); })
            .filter(Boolean);
    }

    return ['.pdf', '.jpg', '.jpeg', '.png'];
}

function handleFileSelect(file, input, wrapper, preview, filename, filesize, progress) {
    const allowedTypes = getAllowedFileTypes(input);
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    
    if (!allowedTypes.includes(fileExtension)) {
        showNotification('Tipo de archivo no permitido. Extensiones válidas: ' + allowedTypes.join(', '), 'error');
        return;
    }
    
    // Validate file size (max 100MB per file)
    const maxSize = 100 * 1024 * 1024; // 100MB in bytes
    if (file.size > maxSize) {
        showNotification('El archivo es demasiado grande. El tamaño máximo permitido es 100MB por archivo', 'error');
        return;
    }
    
    // Check total upload size (max 200MB total)
    const totalSize = calculateTotalUploadSize();
    const maxTotalSize = 200 * 1024 * 1024; // 200MB total
    if (totalSize + file.size > maxTotalSize) {
        showNotification('El tamaño total de los archivos excede el límite de 200MB. Por favor, reduce el tamaño de los archivos.', 'error');
        return;
    }
    
    // Create a new FileList-like object and assign it to the input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    input.files = dataTransfer.files;
    
    // Update UI
    wrapper.classList.add('has-file');
    wrapper.querySelector('.file-input-icon').innerHTML = '<i class="fas fa-check-circle"></i>';
    wrapper.querySelector('.file-input-text').textContent = 'Archivo seleccionado';
    wrapper.querySelector('.file-input-subtext').textContent = file.name;
    
    // Show preview
    filename.textContent = file.name;
    filesize.textContent = formatFileSize(file.size);
    preview.classList.add('show');
    
    // Simulate upload progress
    simulateUploadProgress(progress);
    
    // Mark as valid for form validation
    const fileInput = wrapper.closest('.modern-file-input');
    fileInput.classList.add('is-valid');
    fileInput.classList.remove('is-invalid');
    wrapper.classList.remove('doc-file-invalid');
    
    // Show success notification
    showNotification('Archivo cargado exitosamente', 'success');
    
    // Update total size indicator
    updateTotalSizeIndicator();
}

function removeFile(inputId) {
    const fileInput = document.querySelector(`[data-input-id="${inputId}"]`);
    const wrapper = fileInput.querySelector('.file-input-wrapper');
    const input = fileInput.querySelector('.hidden-file-input');
    const preview = fileInput.querySelector('.file-preview');
    
    // Reset UI
    wrapper.classList.remove('has-file');
    wrapper.querySelector('.file-input-icon').innerHTML = '<i class="fas fa-cloud-upload-alt"></i>';
    wrapper.querySelector('.file-input-text').textContent = 'Arrastra tu archivo aquí';
    wrapper.querySelector('.file-input-subtext').textContent = 'o haz clic para seleccionar';
    
    // Hide preview
    preview.classList.remove('show');
    
    // Clear input
    input.value = '';
    
    // Reset validation state
    fileInput.classList.remove('is-valid', 'is-invalid');
    wrapper.classList.remove('doc-file-invalid');
    
    // Validate all inputs after file removal
    setTimeout(validateAllFileInputs, 100);
    
    // Show notification
    showNotification('Archivo removido', 'info');
    
    // Update total size indicator
    updateTotalSizeIndicator();
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function calculateTotalUploadSize() {
    let totalSize = 0;
    const allFileInputs = document.querySelectorAll('.hidden-file-input');
    
    allFileInputs.forEach(function(input) {
        if (input.files && input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                totalSize += input.files[i].size;
            }
        }
    });
    
    return totalSize;
}

function updateTotalSizeIndicator() {
    const totalSize = calculateTotalUploadSize();
    const maxTotalSize = 200 * 1024 * 1024; // 200MB
    const percentage = (totalSize / maxTotalSize) * 100;
    
    const totalSizeElement = document.getElementById('total-size');
    const progressElement = document.getElementById('total-progress');
    
    if (totalSizeElement && progressElement) {
        totalSizeElement.textContent = formatFileSize(totalSize);
        progressElement.style.width = Math.min(percentage, 100) + '%';
        
        // Change color based on usage
        if (percentage > 90) {
            progressElement.className = 'progress-bar bg-danger';
        } else if (percentage > 70) {
            progressElement.className = 'progress-bar bg-warning';
        } else {
            progressElement.className = 'progress-bar bg-success';
        }
    }
}

function simulateUploadProgress(progressElement) {
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
        }
        progressElement.style.width = progress + '%';
    }, 100);
}

// Tab functionality
function Distribuidor() {
    var blockD = document.getElementById("blockDistribuidor");
    var blockA = document.getElementById("blockAval");
    if (blockD) blockD.style.display = "block";
    if (blockA) blockA.style.display = "none";

    var tabD = document.getElementById("Distribuidor");
    var tabA = document.getElementById("Aval");
    if (tabD) tabD.classList.add("active");
    if (tabA) tabA.classList.remove("active");

    if (typeof validateAllFileInputs === "function") {
        setTimeout(validateAllFileInputs, 0);
    }
}

function Aval() {
    var blockD = document.getElementById("blockDistribuidor");
    var blockA = document.getElementById("blockAval");
    if (blockD) blockD.style.display = "none";
    if (blockA) blockA.style.display = "block";

    var tabD = document.getElementById("Distribuidor");
    var tabA = document.getElementById("Aval");
    if (tabA) tabA.classList.add("active");
    if (tabD) tabD.classList.remove("active");

    if (typeof validateAllFileInputs === "function") {
        setTimeout(validateAllFileInputs, 0);
    }
}

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Add keyboard accessibility
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        const focusedElement = document.activeElement;
        if (focusedElement.classList.contains('file-input-wrapper')) {
            e.preventDefault();
            focusedElement.click();
        }
    }
});

// Function to reset button state
function resetButtonState() {
    const form = document.getElementById('documentForm') || document.querySelector('.msform');
    const submitButton = form ? form.querySelector('button[type="submit"]') : document.querySelector('.msform button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = false;
        // Only reset to "Guardar Documentación" if it's not the validation form
        if (!submitButton.closest('#form1')) {
            submitButton.innerHTML = '<i class="fas fa-check me-2"></i>Guardar Documentación';
        }
    }
}

// Function to validate all file inputs in real time
function validateAllFileInputs() {
    const form = document.getElementById('documentForm') || document.querySelector('.msform');
    if (!form) return;
    
    const requiredInputs = form.querySelectorAll('input[type="file"][required]');
    let allValid = true;

    requiredInputs.forEach(function(input) {
        const fileInput = input.closest('.modern-file-input');
        if (!fileInput) return;

        const wrapper = fileInput.querySelector('.file-input-wrapper');
        if (!wrapper) return;

        // Check if the input has files (incluye pestañas ocultas: deben marcarse igual)
        const hasFiles = (input.files && input.files.length > 0) ||
                       wrapper.classList.contains('has-file');

        if (!hasFiles) {
            fileInput.classList.add('is-invalid');
            fileInput.classList.remove('is-valid');
            wrapper.classList.add('doc-file-invalid');
            allValid = false;
        } else {
            fileInput.classList.add('is-valid');
            fileInput.classList.remove('is-invalid');
            wrapper.classList.remove('doc-file-invalid');
        }
    });
    
    // Botón deshabilitado si faltan requeridos; páginas específicas pueden forzar otra regla
    const submitButton = form.querySelector('button[type="submit"]');
    let disableSubmit = !allValid;
    if (typeof window.onAfterValidateAllFileInputs === 'function') {
        try {
            const forced = window.onAfterValidateAllFileInputs(form, allValid);
            if (typeof forced === 'boolean') {
                disableSubmit = forced;
            }
        } catch (err) {
            console.error(err);
        }
    }
    if (submitButton) {
        submitButton.disabled = disableSubmit;
    }

    try {
        form.dispatchEvent(new CustomEvent('univale:fileinputsvalidated', { bubbles: true, detail: { allValid: allValid } }));
    } catch (err) { /* ignore */ }
    
    return allValid;
}

// Function to check if a specific file input has a file
function hasFileAttached(inputId) {
    const fileInput = document.querySelector(`[data-input-id="${inputId}"]`);
    if (!fileInput) return false;
    
    const input = fileInput.querySelector('.hidden-file-input');
    const wrapper = fileInput.querySelector('.file-input-wrapper');
    
    return (input.files && input.files.length > 0) || 
           wrapper.classList.contains('has-file');
}

// Form validation on submit
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('documentForm') || document.querySelector('.msform');

    if (form) {
        form.addEventListener('submit', function(e) {
            // First, let Bootstrap validation run
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                form.classList.add('was-validated');
                // Los file inputs personalizados no muestran :invalid en el dropzone; aplicar clases aquí
                if (typeof validateAllFileInputs === 'function') {
                    validateAllFileInputs();
                }
                return false;
            }
            
            let isValid = true;
            let missingFiles = [];
            
            // Check all required file inputs
            const requiredInputs = form.querySelectorAll('input[type="file"][required]');
            
            // Only validate if there are required file inputs
            if (requiredInputs.length > 0) {
                requiredInputs.forEach(function(input) {
                    const fileInput = input.closest('.modern-file-input');
                    if (!fileInput) return; // Skip if not inside modern-file-input

                    const wrapper = fileInput.querySelector('.file-input-wrapper');
                    if (!wrapper) return;
                    
                    // More robust file detection
                    const hasFiles = (input.files && input.files.length > 0) || 
                                   wrapper.classList.contains('has-file');
                    
                    if (!hasFiles) {
                        // Show validation error
                        fileInput.classList.add('is-invalid');
                        fileInput.classList.remove('is-valid');
                        wrapper.classList.add('doc-file-invalid');
                        isValid = false;
                        
                        // Get the document name for better error message
                        const documentName = fileInput.querySelector('.sub-tittle')?.textContent?.trim() || 'documento';
                        missingFiles.push(documentName);
                    } else {
                        // Show validation success
                        fileInput.classList.add('is-valid');
                        fileInput.classList.remove('is-invalid');
                        wrapper.classList.remove('doc-file-invalid');
                    }
                });
                
                // If form is not valid, prevent submission
                if (!isValid) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    let errorMessage = 'Por favor, completa todos los campos requeridos';
                    if (missingFiles.length > 0) {
                        errorMessage = `Faltan los siguientes documentos: ${missingFiles.join(', ')}`;
                    }
                    
                    showNotification(errorMessage, 'error');
                    return false;
                }
            }
            
            // Form is valid, show loading state
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton && !submitButton.disabled) {
                const originalHTML = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
                
                // Set a timeout to reset button if form doesn't submit
                setTimeout(() => {
                    if (submitButton.disabled) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalHTML;
                        showNotification('El envío está tardando más de lo esperado. Por favor, verifica tu conexión e intenta nuevamente.', 'warning');
                    }
                }, 30000); // 30 seconds timeout
            }
            
            // Allow the form to submit naturally
            return true;
        });
        
        // Reset button state if form submission fails
        window.addEventListener('beforeunload', function() {
            resetButtonState();
        });
        
        // Also reset button state on page load in case of back navigation
        window.addEventListener('load', function() {
            resetButtonState();
        });
        
        // Handle form submission errors
        window.addEventListener('unhandledrejection', function(event) {
            resetButtonState();
            showNotification('Error al enviar el formulario. Por favor, intenta nuevamente.', 'error');
        });
        
        // Handle network errors
        window.addEventListener('online', function() {
            showNotification('Conexión restaurada', 'success');
        });
        
        window.addEventListener('offline', function() {
            showNotification('Sin conexión a internet. Verifica tu conexión.', 'warning');
        });
    }
});