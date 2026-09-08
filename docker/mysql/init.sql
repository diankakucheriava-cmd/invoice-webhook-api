CREATE DATABASE IF NOT EXISTS webhook_service_test;

GRANT ALL PRIVILEGES
ON webhook_service_test.*
TO 'app'@'%';

FLUSH PRIVILEGES;