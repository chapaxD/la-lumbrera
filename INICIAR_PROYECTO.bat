@echo off
title La Lumbrera - Iniciando...
color 0A

echo ========================================
echo    LA LUMBRERA - INICIO RAPIDO
echo ========================================
echo.
:: Usar Laragon
set SERVIDOR=Laragon
set SERVIDOR_EXE=C:\laragon\laragon.exe
set SERVIDOR_PROC=laragon.exe
set PROJECT_PATH=C:\laragon\www\botanero-ventas

echo       Usando: %SERVIDOR%

tasklist /FI "IMAGENAME eq %SERVIDOR_PROC%" 2>NUL | find /I "%SERVIDOR_PROC%" >NUL
if "%ERRORLEVEL%"=="0" (
    echo       %SERVIDOR% ya esta corriendo. OK
) else (
    echo       Iniciando %SERVIDOR%...
    start "" "%SERVIDOR_EXE%"
    echo       Esperando que los servicios levanten...
    timeout /t 6 /nobreak > nul
)

:: ---- Iniciar npm run dev en nueva ventana ----
echo [2/3] Iniciando servidor Vue.js (npm run dev)...
start "La Lumbrera - NPM Dev" cmd /k "cd /d %PROJECT_PATH% && npm run dev"

:: ---- Abrir navegador (opcional, espera que el servidor levante) ----
echo [3/3] Abriendo navegador en 8 segundos...
timeout /t 8 /nobreak > nul
start "" "http://localhost:8081"

echo.
echo ========================================
echo  Todo listo! Puedes cerrar esta ventana.
echo ========================================
timeout /t 3 /nobreak > nul
exit
