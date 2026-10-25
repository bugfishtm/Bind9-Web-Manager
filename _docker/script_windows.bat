@echo off
cls
:menu
echo.
echo Suitefish Docker Image Script
echo.
echo Choose an option by entering a number (1-4):
echo 1. Rebuild the Image without Cache
echo 2. Docker Compose Up
echo 3. Docker Compose Restart
echo 4. Docker Compose Down
echo 5. Docker Compose Purge Content (CAUTION)
echo 6. Exit
echo.
set /p choice="Enter your choice: "

if "%choice%"=="1" goto option1
if "%choice%"=="2" goto option2
if "%choice%"=="3" goto option3
if "%choice%"=="4" goto option4
if "%choice%"=="5" goto option5
if "%choice%"=="6" goto end

echo Invalid choice! Please try again.
cls
goto menu

:option1
echo.
docker compose build --no-cache
goto end

:option2
echo.
docker compose -f ./docker-compose.yml up -d
goto end

:option3
echo.
docker compose -f ./docker-compose.yml restart
goto end

:option4
echo.
docker compose -f ./docker-compose.yml down
goto end

:option5
echo.
docker compose -f ./docker-compose.yml down -v --rmi all --remove-orphans
goto end

:end
echo.
echo Suitefish Docker Script reached its End of File
echo Goodbye!
echo.
pause
