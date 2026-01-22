# apache-php

> 🔌Automates the integration between Apache 2.4 and PHP 8.x on Windows

## Introduction

What it does:
1. Detects where Apache and PHP are installed;
2. Configures Apache (`httpd.conf`) to use PHP;
3. Enables useful Apache modules for development: `mod_rewrite`, `mod_ssl`, and `mod_headers`;
3. Optionally (you decide): Changes `php.ini` with a [custom development configuration](php.ini-development);
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

## License

[MIT](LICENSE) ©️ [Thiago Delgado Pinto](https://github.com/thiagodp)
