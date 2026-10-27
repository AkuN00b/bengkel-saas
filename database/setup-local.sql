-- EN: Run once with psql as a local PostgreSQL administrator.
-- ID: Jalankan sekali melalui psql sebagai administrator PostgreSQL lokal.
-- psql -X -h 127.0.0.1 -U postgres -d postgres -f database/setup-local.sql
\set ON_ERROR_STOP on

CREATE ROLE bengkel_saas LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE;

-- EN: psql prompts securely; use the same password in the local .env file.
-- ID: psql meminta password; gunakan password yang sama di file .env lokal.
\password bengkel_saas

CREATE DATABASE bengkel_saas OWNER bengkel_saas;
CREATE DATABASE bengkel_saas_testing OWNER bengkel_saas;
