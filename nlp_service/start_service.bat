@echo off
REM ============================================================
REM  Menjalankan service NLP E-DOC
REM  Klik dua kali file ini, lalu BIARKAN jendelanya tetap terbuka
REM  selama aplikasi E-DOC dipakai.
REM  Untuk berhenti: tekan CTRL+C atau tutup jendelanya.
REM ============================================================
cd /d "%~dp0"

if not exist "venv\Scripts\python.exe" (
  echo.
  echo [!] Folder venv belum ada.
  echo     Jalankan dulu setup.bat
  echo.
  pause
  exit /b 1
)

echo Menjalankan service NLP di http://127.0.0.1:5000 ...
echo.
venv\Scripts\python.exe app.py

echo.
echo Service berhenti.
pause
