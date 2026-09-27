@echo off
REM ============================================================
REM  Pemasangan service NLP E-DOC (cukup dijalankan SEKALI)
REM  1. membuat virtual environment (folder venv)
REM  2. memasang library dari requirements.txt
REM  3. mengunduh data NLTK (punkt, punkt_tab, stopwords)
REM  Syarat: Python 3.10+ sudah terpasang dan terdaftar di PATH.
REM ============================================================
cd /d "%~dp0"

echo ============================================================
echo  [1/3] Membuat virtual environment...
echo ============================================================
if exist "venv\Scripts\python.exe" (
  echo Folder venv sudah ada, dilewati.
) else (
  python -m venv venv
  if errorlevel 1 (
    echo.
    echo [!] Gagal membuat venv. Pastikan Python sudah terpasang.
    echo     Cek dengan perintah: python --version
    pause
    exit /b 1
  )
)

echo.
echo ============================================================
echo  [2/3] Memasang library Python...
echo ============================================================
venv\Scripts\python.exe -m pip install --upgrade pip
venv\Scripts\python.exe -m pip install -r requirements.txt
if errorlevel 1 (
  echo.
  echo [!] Gagal memasang library. Cek koneksi internet.
  pause
  exit /b 1
)

echo.
echo ============================================================
echo  [3/3] Mengunduh data NLTK...
echo ============================================================
venv\Scripts\python.exe setup_nltk.py
if errorlevel 1 (
  echo.
  echo [!] Gagal mengunduh data NLTK. Cek koneksi internet.
  pause
  exit /b 1
)

echo.
echo ============================================================
echo  SELESAI
echo ------------------------------------------------------------
echo  Langkah berikutnya:
echo    1. Import schema_nlp.sql di phpMyAdmin (database e-doc_db)
echo    2. Jalankan start_service.bat
echo    3. Index dokumen lama: venv\Scripts\python reindex.py
echo ============================================================
pause
