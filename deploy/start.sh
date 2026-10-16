#!/bin/sh

# Cria diretórios necessários
mkdir -p /var/www/html/storage /var/www/html/api/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/api/logs
chmod -R 775 /var/www/html/storage /var/www/html/api/logs

# Roda migrations a cada start.
# IMPORTANTE: NÃO esconder o erro. Em deploys anteriores o `|| echo ...` mascarava
# falhas de conexão com o banco e ainda criava /storage/.migrated, deixando o
# sistema travado sem tabelas (ex.: "Table 'workspaces' doesn't exist").
# O migrator é idempotente (só aplica o que falta), então rodar a cada start é seguro.
echo "Running migrations..."
if php /var/www/html/scripts/migrate.php; then
    touch /var/www/html/storage/.migrated
    echo "Migrations completed!"

    # Admin global só faz sentido se as tabelas existem.
    if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
        echo "Garantindo administrador global..."
        php /var/www/html/scripts/create_admin.php || echo "Aviso: falha ao configurar admin (seguindo)."
    fi
else
    echo "ERRO: migrations falharam. Verifique DB_HOST/DB_DATABASE/DB_USERNAME/DB_PASSWORD no Ambiente."
    echo "O container vai subir mesmo assim, mas o site não funcionará ate as migrations rodarem."
fi

# O banco SQLite e a pasta storage/ precisam ser gravaveis pelo PHP-FPM (www-data).
# As migrations rodam como root e criam o arquivo com dono root; sem este chown as leituras
# funcionam mas criar/publicar/salvar falha com "attempt to write a readonly database".
chown -R www-data:www-data /var/www/html/storage /var/www/html/api/logs
chmod -R 775 /var/www/html/storage /var/www/html/api/logs
find /var/www/html/storage -type f -exec chmod 664 {} \;

# Inicia PHP-FPM em background
echo "Starting PHP-FPM..."
php-fpm -D

# Inicia Nginx em foreground
echo "Starting Nginx..."
exec nginx -g "daemon off;"

