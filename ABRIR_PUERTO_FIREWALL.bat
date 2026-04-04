@echo off
echo Permitiendo puerto 8081 en el Firewall de Windows...
netsh advfirewall firewall add rule name="Botanero Ventas - Puerto 8081" dir=in action=allow protocol=tcp localport=8081
if %errorlevel% equ 0 (
    echo.
    echo Listo. El puerto 8081 esta permitido.
    echo Reinicia "npm run dev" e intenta desde tu celular.
) else (
    echo.
    echo Error. Ejecuta este archivo como Administrador:
    echo Clic derecho en el archivo - Ejecutar como administrador
)
pause
