# apache-php

> 🔌Automates the integration between Apache 2.4 and PHP 8.x on Windows

## Introduction

What it does:
1. Detects where Apache and PHP are installed;
2. Configures Apache (`httpd.conf`) to use PHP;
3. Optionally (you decide): Enables useful Apache modules for development: `mod_rewrite`, `mod_ssl`, and `mod_headers`;
3. Optionally (you decide): Changes `php.ini` with a [custom development configuration](php.ini-development) that also adjusts `extension_dir`;
4. Always creates backups of your `httpd.conf` and `php.ini` before changing them.

What it does **not** do:
- Installs Apache or PHP for you (use `winget` for that)
- Changes your PATH variable (`winget` already does that)

When to use it:
- After installing Apache and PHP with [WinGet](https://github.com/microsoft/winget-cli), [Chocolatey](https://github.com/chocolatey/choco) or another Windows package manager
- After installing Apache and PHP separately

## How to use it

```bash
git clone https://github.com/thiagodp/apache-php
cd apache-php
php integrate.php
```

Or download [integrate.php](integrate.php) and then execute it with PHP: `php integrate.php`

## Bonus

The following bash script installs Apache and PHP in `C:\dev` using WinGet and then integrates them. Execute it using `cmd`.

```bash
cd C:\dev || (mkdir C:\dev && cd C:\dev)
winget install -e --id PHP.PHP.8.5 -l C:\dev\php
winget install -e --id ApacheLounge.httpd -l C:\dev\apache
git clone https://github.com/thiagodp/apache-php
cd apache-php && php integrate.php
cd .. && rmdir /Q /S apache-php
```
_Note: Windows 10 and 11 already include Winget._

## License

[MIT](LICENSE) ©️ [Thiago Delgado Pinto](https://github.com/thiagodp)
