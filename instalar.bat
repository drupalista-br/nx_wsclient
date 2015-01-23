if "%~1"=="" (
  set phplocation=c:\php5
) else (
  set phplocation=%1
)
cd %phplocation%
ren php.ini-production php.ini

SETX /M PATH "%PATH%;%phplocation%"
SETX /M PATHEXT "%PATHEXT%;.php"
assoc .php=phpfile
ftype phpfile="%phplocation%\php.exe" -f "%1" -- %~2
