<?php
// PayMongo API Configuration
define('PAYMONGO_SECRET_KEY', 'sk_test_XhhSeNsJTpZVbwCstLAJBzso');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_dVh6Nt6QRcN8di6tjCKmynLQ');
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
define('PAYMONGO_WEBHOOK_SIG', 'whsk_9xQNaAH2s7zvs4yge8TivmLS');

// Environment Configuration
define('APP_URL', 'https://980b-222-127-73-6.ngrok-free.app'); // Removed the space at the beginning
define('IS_PRODUCTION', false);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', IS_PRODUCTION ? 0 : 1);