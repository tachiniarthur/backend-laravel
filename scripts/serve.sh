#!/bin/bash
# Inicia o servidor PHP com limites de upload aumentados
php -d post_max_size=20M -d upload_max_filesize=10M -d memory_limit=256M artisan serve --host=0.0.0.0 --port=8000
