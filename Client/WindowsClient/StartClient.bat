echo off
TASKKILL /F /IM AutoAuthenticate.exe
TASKKILL /F /IM Explorer.exe
start %~dp0AutoAuthenticate\AutoAuthenticate.exe
start %~dp0FirefoxPortable\FirefoxPortable.exe