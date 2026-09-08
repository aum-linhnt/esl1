@echo off
title Stop Laravel ESL Server
echo Stopping all server services...
taskkill /F /IM php.exe /T 2>nul
taskkill /F /IM mysqld.exe /T 2>nul
taskkill /F /IM node.exe /T 2>nul
echo All services stopped successfully.
pause
