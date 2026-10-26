@echo off
cd /d %~dp0
start http://localhost:8092
python -m http.server 8092
