@echo off
set phplocation=%cd:~0,2%\php5
set search=%PATH%

if NOT "%search%"=="%search%:%phplocation%=%" (
    SETX /M PATH "%PATH%;%phplocation%"
    SETX /M PATHEXT "%PATHEXT%;.PHP"
    assoc .php=phpfile
    ftype phpfile="%phplocation%\php.exe" -f "%1" -- %~2

    cd %phplocation%
    ren php.ini-production php.ini
    php -r "readfile('https://getcomposer.org/installer');" | php
    echo @php "%~dp0composer.phar" %*>composer.bat
)
php -v
composer -v
echo Instalacao concluida.
