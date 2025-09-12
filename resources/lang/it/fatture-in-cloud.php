<?php

return [
    'oauth2' => [
        'authorization' => [
            'access_denied' => 'L\'autorizzazione è stata annullata. Riprova se desideri connettere il tuo account.',
            'invalid_request' => 'Si è verificato un problema con la richiesta di autorizzazione. Contatta il supporto tecnico.',
            'unauthorized_client' => 'Questa applicazione non è autorizzata ad accedere al tuo account.',
            'unsupported_response_type' => 'Metodo di autorizzazione non supportato. Contatta il supporto tecnico.',
            'invalid_scope' => 'I permessi richiesti non sono disponibili.',
            'server_error' => 'Si è verificato un errore temporaneo del server. Riprova tra qualche istante.',
            'temporarily_unavailable' => 'Il servizio è temporaneamente non disponibile. Riprova più tardi.',
            'default' => 'Si è verificato un errore di autorizzazione. Riprova o contatta il supporto tecnico.',
        ],
        'token_exchange' => [
            'invalid_code' => 'Il codice di autorizzazione è scaduto o non valido. Riavvia il processo di autorizzazione.',
            'invalid_client_credentials' => 'Le credenziali dell\'applicazione non sono valide. Contatta il supporto tecnico.',
            'network_failure' => 'Connessione di rete fallita. Controlla la tua connessione e riprova.',
            'default' => 'Si è verificato un errore nello scambio del token. Riprova o contatta il supporto tecnico.',
        ],
        'token_refresh' => [
            'invalid_refresh_token' => 'La tua sessione è scaduta. Effettua nuovamente l\'accesso.',
            'client_authentication_failed' => 'Autenticazione fallita. Prova ad effettuare nuovamente l\'accesso.',
            'token_revoked' => 'Il tuo accesso è stato revocato. Effettua nuovamente l\'accesso.',
            'default' => 'Si è verificato un errore nel rinnovo del token. Effettua nuovamente l\'accesso.',
        ],
        'configuration' => [
            'missing_configuration' => 'La configurazione dell\'applicazione è incompleta. Contatta il supporto tecnico.',
            'invalid_redirect_url' => 'Configurazione URL di reindirizzamento non valida. Contatta il supporto tecnico.',
            'malformed_configuration' => 'Errore nella configurazione dell\'applicazione. Contatta il supporto tecnico.',
            'default' => 'Si è verificato un errore di configurazione. Contatta il supporto tecnico.',
        ],
    ],
];
