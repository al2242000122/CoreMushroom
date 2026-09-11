# Herramientas locales de desarrollo

Estas herramientas sirven para trabajar y buscar en el repositorio. No forman
parte del tema de WordPress, no se suben al hosting y no se guardan binarios en
Git.

## Instalación en Windows

Desde PowerShell, situado en la raíz del repositorio:

```powershell
.\tools\instalar-herramientas.ps1
```

El script instala Context Mode con npm y tgrep en el perfil del usuario. La
versión de tgrep queda fijada a `v1.0.6` porque es la última versión publicada
con binario x86_64 para Windows al momento de preparar este proyecto. Se valida
la suma SHA-256 antes de copiar el ejecutable.

Para crear el índice local del repositorio:

```powershell
.\tools\instalar-herramientas.ps1 -Indexar
```

El índice se guarda en `.tgrep/`, está excluido por `.gitignore` y debe
regenerarse después de cambios si no se mantiene un servidor tgrep activo.

## Uso diario

```powershell
tgrep status .
tgrep -F -- "coremushroom_" .
tgrep -l -g "inc/**" -- "plugins_loaded" .
```

Context Mode conserva su base local fuera del repositorio. Se puede revisar su
estado con:

```powershell
context-mode doctor
```

Después de instalar o actualizar Context Mode, reinicia Codex una vez para
que el administrador de plugins refresque la ruta de la instalación.

No se activa la plataforma comercial ni se envía código a ningún servicio
externo. La instalación local no requiere cuenta ni token.

## Instalación en Termux, Linux o macOS

Context Mode requiere Node.js 22.5 o superior en la versión fijada de este
proyecto. tgrep se compila con Rust:

```bash
npm install --global --ignore-scripts context-mode@1.0.169
cargo install --git https://github.com/microsoft/tgrep --rev c2fdb3ee90e1fc0072b9b4d84e9c976c8b44c7fe --path tgrep-cli --locked
```

El `--rev` fija el código fuente de tgrep a un commit concreto. La versión
Windows usa el mismo release y valida su SHA-256 en el script.

Después, desde la raíz del repositorio:

```bash
tgrep index . --exclude .codex-remote-attachments --exclude .tgrep
```
