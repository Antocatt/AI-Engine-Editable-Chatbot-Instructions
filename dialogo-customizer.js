/**
 * Dialogo.Live ChatLeg Integration - Frontend JavaScript
 * Modern vanilla JavaScript (ES6+) - No jQuery dependency
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Main elements
    const consentOverlay = document.getElementById('dialogo-consent-overlay');
    const acceptTermsBtn = document.getElementById('dialogo-accept-terms');
    const declineTermsBtn = document.getElementById('dialogo-decline-terms');
    const mainInterface = document.querySelector('.dialogo-main-interface');
    
    const legalAreaSelect = document.getElementById('dialogo-legal-area');
    const promptInput = document.getElementById('dialogo-prompt-input');
    const charCount = document.getElementById('dialogo-char-count');
    const charCounter = document.querySelector('.dialogo-char-counter');
    
    const saveBtn = document.getElementById('dialogo-save-btn');
    const resetBtn = document.getElementById('dialogo-reset-btn');
    const historyBtn = document.getElementById('dialogo-history-btn');
    
    const statusDiv = document.getElementById('dialogo-status');
    const historyModal = document.getElementById('dialogo-history-modal');
    const historyList = document.getElementById('dialogo-history-list');
    
    const presetBtns = document.querySelectorAll('.dialogo-preset-btn');
    
    // Initialize
    init();
    
    function init() {
        setupEventListeners();
        updateCharCount();
    }
    
    function setupEventListeners() {
        // Terms acceptance
        if (acceptTermsBtn) {
            acceptTermsBtn.addEventListener('click', acceptTerms);
        }
        if (declineTermsBtn) {
            declineTermsBtn.addEventListener('click', declineTerms);
        }
        
        // Character counter
        if (promptInput) {
            promptInput.addEventListener('input', updateCharCount);
        }
        
        // Legal area selection
        if (legalAreaSelect) {
            legalAreaSelect.addEventListener('change', updateLegalArea);
        }
        
        // Preset buttons
        presetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const presetType = this.dataset.preset;
                loadPreset(presetType);
            });
        });
        
        // Action buttons
        if (saveBtn) {
            saveBtn.addEventListener('click', savePrompt);
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', resetPrompt);
        }
        if (historyBtn) {
            historyBtn.addEventListener('click', showHistory);
        }
        
        // Modal close functionality
        const closeBtn = document.querySelector('.dialogo-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        // Close modal when clicking outside
        if (historyModal) {
            historyModal.addEventListener('click', function(e) {
                if (e.target === historyModal) {
                    closeModal();
                }
            });
        }
    }
    
    /**
     * Accept terms and conditions
     */
    function acceptTerms() {
        const formData = new FormData();
        formData.append('action', 'accept_dialogo_terms');
        formData.append('nonce', dialogo_ajax.terms_nonce);
        
        fetch(dialogo_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Hide overlay and show main interface
                if (consentOverlay) {
                    consentOverlay.style.display = 'none';
                }
                if (mainInterface) {
                    mainInterface.style.display = 'block';
                }
            } else {
                showStatus('Errore nel salvare il consenso', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showStatus('Errore di rete', 'error');
        });
    }
    
    /**
     * Decline terms and redirect
     */
    function declineTerms() {
        alert('Devi accettare i termini per utilizzare il servizio.');
        // Optionally redirect away
        // window.location.href = '/';
    }
    
    /**
     * Update character count and styling
     */
    function updateCharCount() {
        if (!promptInput || !charCount) return;
        
        const currentLength = promptInput.value.length;
        const maxLength = parseInt(promptInput.maxLength);
        
        charCount.textContent = currentLength;
        
        // Update styling based on character count
        charCounter.classList.remove('warning', 'error');
        if (currentLength > maxLength * 0.9) {
            charCounter.classList.add('warning');
        }
        if (currentLength >= maxLength) {
            charCounter.classList.add('error');
        }
    }
    
    /**
     * Update legal area (for future functionality)
     */
    function updateLegalArea() {
        // Could trigger additional functionality based on selected area
        console.log('Legal area changed to:', legalAreaSelect.value);
    }
    
    /**
     * Load a preset prompt
     */
    function loadPreset(presetType) {
        if (!dialogo_ajax.presets || !dialogo_ajax.presets[presetType]) {
            showStatus('Preset non trovato', 'error');
            return;
        }
        
        const presetText = dialogo_ajax.presets[presetType];
        
        if (promptInput) {
            // Ask for confirmation if current prompt is not empty
            if (promptInput.value.trim() && 
                !confirm('Sostituire il testo corrente con il preset selezionato?')) {
                return;
            }
            
            promptInput.value = presetText;
            updateCharCount();
            
            // Update legal area if applicable
            if (legalAreaSelect && presetType !== 'generale') {
                legalAreaSelect.value = presetType;
            }
            
            showStatus('Preset caricato', 'success');
        }
    }
    
    /**
     * Save the current prompt
     */
    function savePrompt() {
        if (!promptInput) return;
        
        const promptText = promptInput.value.trim();
        const legalArea = legalAreaSelect ? legalAreaSelect.value : '';
        
        // Validate input
        if (promptText.length === 0) {
            showStatus('Inserisci del testo prima di salvare', 'error');
            return;
        }
        
        // Disable button during save
        saveBtn.disabled = true;
        saveBtn.textContent = 'Salvataggio...';
        
        const formData = new FormData();
        formData.append('action', 'save_dialogo_prompt');
        formData.append('prompt_string', promptText);
        formData.append('field_of_law', legalArea);
        formData.append('nonce', dialogo_ajax.save_nonce);
        
        fetch(dialogo_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStatus(data.data, 'success');
            } else {
                showStatus('Errore: ' + data.data, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showStatus('Errore di rete durante il salvataggio', 'error');
        })
        .finally(() => {
            // Re-enable button
            saveBtn.disabled = false;
            saveBtn.textContent = 'Salva';
        });
    }
    
    /**
     * Reset the prompt to empty
     */
    function resetPrompt() {
        if (!promptInput) return;
        
        if (promptInput.value.trim() && 
            !confirm('Sei sicuro di voler cancellare tutto il testo?')) {
            return;
        }
        
        promptInput.value = '';
        if (legalAreaSelect) {
            legalAreaSelect.value = '';
        }
        updateCharCount();
        showStatus('Campo resettato', 'success');
    }
    
    /**
     * Show prompt history
     */
    function showHistory() {
        if (!historyModal || !historyList) return;
        
        const formData = new FormData();
        formData.append('action', 'get_dialogo_history');
        formData.append('nonce', dialogo_ajax.history_nonce);
        
        // Clear current history
        historyList.innerHTML = '<p>Caricamento cronologia...</p>';
        historyModal.style.display = 'flex';
        
        fetch(dialogo_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHistory(data.data);
            } else {
                historyList.innerHTML = '<p>Errore nel caricamento della cronologia</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            historyList.innerHTML = '<p>Errore di rete</p>';
        });
    }
    
    /**
     * Display history in modal
     */
    function displayHistory(history) {
        if (!historyList) return;
        
        if (!history || history.length === 0) {
            historyList.innerHTML = '<p>Nessuna cronologia disponibile</p>';
            return;
        }
        
        let html = '';
        history.reverse().forEach((item, index) => {
            html += `
                <div class="dialogo-history-item">
                    <div class="timestamp">${formatDate(item.timestamp)}</div>
                    ${item.field_of_law ? `<div class="field-of-law">Area: ${item.field_of_law}</div>` : ''}
                    <div class="prompt-text">${escapeHtml(item.prompt)}</div>
                    <button onclick="restoreFromHistory(${index})" class="dialogo-btn-secondary" style="margin-top: 10px; font-size: 12px; padding: 5px 10px;">
                        Ripristina questo prompt
                    </button>
                </div>
            `;
        });
        
        historyList.innerHTML = html;
        
        // Store history for restore function
        window.dialogoHistory = history.reverse();
    }
    
    /**
     * Restore prompt from history
     */
    window.restoreFromHistory = function(index) {
        if (!window.dialogoHistory || !window.dialogoHistory[index]) return;
        
        const item = window.dialogoHistory[index];
        
        if (confirm('Ripristinare questo prompt? Il testo corrente verrà sostituito.')) {
            if (promptInput) {
                promptInput.value = item.prompt;
                updateCharCount();
            }
            if (legalAreaSelect && item.field_of_law) {
                legalAreaSelect.value = item.field_of_law;
            }
            closeModal();
            showStatus('Prompt ripristinato dalla cronologia', 'success');
        }
    };
    
    /**
     * Close modal
     */
    function closeModal() {
        if (historyModal) {
            historyModal.style.display = 'none';
        }
    }
    
    /**
     * Show status message
     */
    function showStatus(message, type) {
        if (!statusDiv) return;
        
        statusDiv.textContent = message;
        statusDiv.className = 'dialogo-status ' + type;
        
        // Hide after 5 seconds
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    }
    
    /**
     * Format date for display
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('it-IT', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
});