<?php
// PayMongo API Configuration
define('PAYMONGO_SECRET_KEY', 'sk_test_AZb3Ve2hSMuBTzWb4gbjtSP6');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_dVh6Nt6QRcN8di6tjCKmynLQ');
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
define('PAYMONGO_WEBHOOK_SIG', 'your_webhook_signing_secret');

// Environment Configuration
define('APP_URL', 'https://your-domain.com');
define('IS_PRODUCTION', false);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', IS_PRODUCTION ? 0 : 1);

?>