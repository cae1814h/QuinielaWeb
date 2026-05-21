#!/bin/bash
set -e
PORT=${PORT:-22286}
echo "Iniciando servidor PHP en el puerto $PORT..."
exec php -S 0.0.0.0:$PORT /home/runner/workspace/artifacts/quiniela-php/router.php
