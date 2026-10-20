@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

set "JDK_BIN="
if defined JAVA_HOME if exist "%JAVA_HOME%\bin\javac.exe" set "JDK_BIN=%JAVA_HOME%\bin"
if not defined JDK_BIN (
    for /f "tokens=3,*" %%I in ('java -XshowSettings:properties -version 2^>^&1 ^| findstr /C:"java.home ="') do (
        set "JDK_HOME=%%I %%J"
    )
    if defined JDK_HOME set "JDK_BIN=!JDK_HOME!\bin"
)
if not defined JDK_BIN (
    echo No se encontro un JDK instalado. Instala JDK 17 o superior y vuelve a intentar.
    pause
    exit /b 1
)
if not exist "%JDK_BIN%\javac.exe" (
    echo No se encontro javac.exe en "%JDK_BIN%". Instala el JDK completo.
    pause
    exit /b 1
)
if not exist "%JDK_BIN%\jar.exe" (
    echo No se encontro jar.exe en "%JDK_BIN%". Instala el JDK completo.
    pause
    exit /b 1
)

if not exist "build" mkdir "build"
set "BUILD_DIR=%TEMP%\MetalGest-build-%RANDOM%-%RANDOM%"
set "CLASSES_DIR=%BUILD_DIR%\classes"
set "SOURCE_LIST=%BUILD_DIR%\sources.txt"
mkdir "%CLASSES_DIR%"
if errorlevel 1 goto :build_failed

for /r "src\main\java" %%F in (*.java) do (
    set "SOURCE=%%~fF"
    set "SOURCE=!SOURCE:\=/!"
    echo "!SOURCE!">>"%SOURCE_LIST%"
)
"%JDK_BIN%\javac.exe" --release 17 -encoding UTF-8 -d "%CLASSES_DIR%" @"%SOURCE_LIST%"
if errorlevel 1 goto :build_failed

"%JDK_BIN%\jar.exe" --create --file "build\MetalGestApp-latest.jar" --main-class modulos.MetalGestApp -C "%CLASSES_DIR%" .
if errorlevel 1 goto :build_failed

rmdir /s /q "%BUILD_DIR%"
"%JDK_BIN%\java.exe" -jar "build\MetalGestApp-latest.jar"
exit /b %errorlevel%

:build_failed
echo Error al compilar o empaquetar la aplicacion.
if exist "%BUILD_DIR%" rmdir /s /q "%BUILD_DIR%"
pause
exit /b 1
