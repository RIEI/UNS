echo off
TASKKILL /F /IM GoogleChromePortable.exe
TASKKILL /F /IM Chrome.exe
start %~dp0ChromePortable\GoogleChromePortable.exe