#!/bin/bash
cd "$(dirname "$0")"
echo "Iniciando Ecosistema Educativo 4.2 en http://localhost:8092"
python3 -m http.server 8092 >/tmp/ecosistema42_http.log 2>&1 &
PID=$!
sleep 1
open "http://localhost:8092"
echo "Servidor activo (PID $PID). Cierra esta ventana o presiona Ctrl+C para terminar."
wait $PID
