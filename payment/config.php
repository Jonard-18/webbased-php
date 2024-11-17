<?php
// PayMongo API Configuration
define('PAYMONGO_SECRET_KEY', 'sk_test_XhhSeNsJTpZVbwCstLAJBzso');
define('PAYMONGO_PUBLIC_KEY', 'pk_test_byhHBAjzP1fU4e1itsT4cXQS');
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');
define('PAYMONGO_WEBHOOK_SIG', 'whsk_kPm9ZUysvdJg6Wkk82y1UcwT');

// Environment Configuration
define('APP_URL', 'https://88aa-2405-8d40-48cc-9336-cdd7-50d6-dd06-c50a.ngrok-free.app');
define('IS_PRODUCTION', false);

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', IS_PRODUCTION ? 0 : 1);

?>