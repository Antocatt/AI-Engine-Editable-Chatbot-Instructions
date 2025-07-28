# Dialogo.Live - ChatLeg Integration

Un plugin WordPress che permette a ogni utente registrato di personalizzare i prompt del proprio chatbot AI con controlli admin e conformità GDPR.

## Caratteristiche Principali

### Dettagli Plugin
- **Nome**: Dialogo.Live - ChatLeg Integration  
- **Descrizione**: Permette agli utenti di personalizzare i prompt del chatbot AI con controlli admin e conformità GDPR
- **Branding**: Include "Powered by Dialogo.Live" con link a https://chatleg.pro/dialogo

### Funzionalità Frontend
- **Route**: `/personalizza-assistente` o shortcode `[dialogo_prompt_customizer]`
- **Saluto utente**: "Ciao {username}, queste sono le tue istruzioni custom per il modello Omnia."
- **Overlay GDPR/ToS** alla prima visita
- **Dropdown area legale** (Penale, Civile, Tributario, Lavoro, Altro)
- **Pulsanti preset** per template legali comuni
- **Contatore caratteri** per l'input utente
- **Funzionalità** Salva/Reset/Cronologia
- **Rendering chatbot live** con prompt salvati

### Backend Admin
- **Pagina impostazioni**: "Dialogo.Live Settings"
- Configurazione prompt hardcoded (visibili/invisibili agli utenti)
- Impostazione limiti caratteri per input utente
- Abilitazione/disabilitazione filtro moderazione
- Attivazione/disattivazione varie funzionalità

### Requisiti Tecnici
- Compatibile con Elementor e AI Engine Pro
- Utilizza vanilla JavaScript moderno (ES6+), senza jQuery
- Codice semplice e pulito con commenti chiari
- Design responsive
- Sicurezza con WordPress nonces
- Salvataggio dati in user_meta e opzioni WordPress

### Archiviazione Dati
- **User meta**: prompt_string, prompt_history, field_of_law, consent_accepted
- **Opzioni**: hardcoded_prompt, max_chars, show_hardcoded_prompt, ecc.

### Integrazione
- Funziona con shortcode `[mwai_chatbot]` di AI Engine Pro
- Iniezione dinamica prompt: hardcoded_prompt + user_prompt
- Rendering context-aware basato sulle selezioni utente

## Utilizzo

### Shortcode Base
Per visualizzare l'interfaccia di personalizzazione:
```
[dialogo_prompt_customizer]
```

### Route Personalizzata
Gli utenti possono accedere direttamente tramite:
```
https://tuosito.com/personalizza-assistente
```

### Impostazioni Admin
1. Vai su **Settings > Dialogo.Live Settings**
2. Configura il prompt hardcoded base
3. Imposta il limite massimo di caratteri
4. Personalizza i preset per le diverse aree legali
5. Abilita/disabilita le funzionalità desiderate

## Funzionalità Dettagliate

### GDPR e Privacy
- Overlay di consenso obbligatorio alla prima visita
- Salvataggio timestamp del consenso
- Gestione completa dei dati utente secondo GDPR

### Preset Legali
- **Penale**: Template per diritto penale
- **Civile**: Template per diritto civile  
- **Tributario**: Template per diritto tributario
- **Lavoro**: Template per diritto del lavoro
- **Generale**: Template assistente legale generale

### Cronologia
- Salvataggio automatico delle modifiche
- Visualizzazione cronologia con timestamp
- Ripristino prompt precedenti
- Massimo 10 voci nella cronologia

### Sicurezza
- Nonces WordPress per tutte le operazioni AJAX
- Sanitizzazione completa degli input
- Controllo permessi utente
- Validazione lato client e server

## Sviluppo

Il codice è progettato per essere semplice da comprendere e modificare anche da sviluppatori con conoscenze base di HTML/WordPress.

### Struttura File
- `dialogo-live-chatleg-integration.php` - File principale del plugin
- `dialogo-customizer.js` - JavaScript frontend (vanilla ES6+)

### Commenti Estesi
Ogni sezione del codice include commenti dettagliati che spiegano:
- Funzionalità della sezione
- Parametri utilizzati
- Integrazione con WordPress
- Note di sicurezza

---

**Powered by [Dialogo.Live](https://chatleg.pro/dialogo)**
