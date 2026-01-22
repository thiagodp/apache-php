<?php

// Utilities ------------------------------------------------------------------

/**
 * Detects the paths of a executable program.
 *
 * @param string $command Executable program.
 * @return string[]
 */
function where( string $command ): array {
    exec( "where $command", $output, $returnCode );
    if ( $returnCode !== 0 ) {
        return [];
    }
    $paths = [];
    foreach ( $output as $out ) {
        $isPath = str_contains( $out, '/' ) || str_contains( $out, '\\' );
        if ( $isPath ) {
            $paths []= $out;
        }
    }
    return $paths;
}

/**
 * Detects the paths of a executable program by performing a recursive search from the root directory.
 *
 * @param string $command Executable program.
 * @return string[]
 */
function longWhere( string $command ): array {
    $ok = chdir( 'C:\\' );
    if ( ! $ok ) {
        return [];
    }
    return where( "/R C: $command" );
}


function windowsToUnixPath( string $path ): string {
    return str_replace( '\\', '/', $path );
}


/**
 * Make replacements and additions to a text file.
 *
 * @param string $path
 * @param array<string,string> $replacements
 * @param array<int,string> $additions
 * @throws Exception
 * @return void
 */
function changeTextFile( string $path, array $replacements, array $additions ): void {

    // Read
    $content = file_get_contents( $path );
    if ( $content === false ) {
        throw new Exception( 'Could not read the file.' );
    }

    // Replacements
    foreach ( $replacements as $from => $to ) {
        $content = str_replace( $from, $to, $content );
    }

    // Additions
    $isWindowsEOL = str_contains( $content, "\r\n" );
    $eol = $isWindowsEOL ? "\r\n" : "\n";
    foreach ( $additions as $a ) {
        $content .= $a . $eol;
    }

    // Write
    $result = file_put_contents( $path, $content );
    if ( $result === false ) {
        throw new Exception( 'Could not write the file.' );
    }
}


/**
 * Copy a file keeping its attributes. Returns `true` if successful, `false` otherwise.
 *
 * @param string $source
 * @param string $destination
 * @return bool
 */
function copyFileWithAttributes( string $source, string $destination ): bool {

    if ( ! copy( $source, $destination ) ) {
        return false;
    }

    // Preserve permissions
    $perms = fileperms( $source );
    chmod( $destination, $perms );

    // Preserve modification time
    $mtime = filemtime( $source );
    touch( $destination, $mtime, $mtime );

    return true;
}

// User Interface -------------------------------------------------------------

/**
 * Get the user answer for one of the given options.
 * It keeps asking while the answer is not one of the options.
 *
 * @param array $options Possible options.
 * @param mixed $defaultOption Default option (optional).
 * @param bool $showOptions Show options to the user (optional)
 * @return string
 */
function answer( array $options, mixed $defaultOption = null, bool $showOptions = false ): string {
    $input = '';
    do {
        if ( $showOptions ) {
            echo '(', implode( ', ', $options ), ') ';
        }
        echo '> ';
        if ( $defaultOption !== null ) {
            echo '(Empty for ' . $defaultOption . '): ';
        }
        $input = readline();
        if ( $defaultOption !== null && $input === '' ) {
            $input = $defaultOption;
        }
    } while ( ! in_array( $input, $options ) );
    return $input;
}

// ----------------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------------

function searchApplication( $name, $exe ): string {
    echo "Searching for $name (fast search)...";
    $detectedPaths = where( $exe );
    $pathCount = count( $detectedPaths );
    $exePath = '';
    if ( $pathCount === 0 ) {
        echo ' not found', PHP_EOL;
        echo "Searching for $name (long search), please wait...", PHP_EOL;
        $detectedPaths = longWhere( $exe );
        $pathCount = count( $detectedPaths );
    }
    if ( $pathCount === 0 ) {
        echo "$name is not installed.", PHP_EOL;
        exit( 1 );
    } else if ( $pathCount > 1 ) {
        echo "Select the correct $name path by its number:", PHP_EOL;
        $options = [];
        foreach ( $detectedPaths as $i => $path ) {
            $index = $i + 1;
            $options []= (string) $index;
            echo "\t$index) $path", PHP_EOL;
        }
        $response = answer( $options, '1' );
        $exePath = $detectedPaths[ $response - 1 ];
        echo 'ok: ', $exePath, PHP_EOL;
    } else {
        $exePath = $detectedPaths[ 0 ];
        echo 'found: ', $exePath, PHP_EOL;
    }
    return $exePath;
}

$apachePath = searchApplication( 'Apache', 'httpd' );
$phpPath = searchApplication( 'PHP', 'php' );

$apacheRoot = dirname( $apachePath, 2 );
$phpRoot = dirname( $phpPath, 1 );

//
// httpd.conf
//

$httpdConfReplacements = [
    'Define SRVROOT "c:/Apache24"' => 'Define SRVROOT "' . windowsToUnixPath( $apachePath ) . '"',
];

$httpdConfOptionalReplacements = [
    '#LoadModule headers_module modules/mod_headers.so' => 'LoadModule headers_module modules/mod_headers.so',
    '#LoadModule rewrite_module modules/mod_rewrite.so' => 'LoadModule rewrite_module modules/mod_rewrite.so',
    '#LoadModule ssl_module modules/mod_ssl.so' => 'LoadModule ssl_module modules/mod_ssl.so',
];

$httpdConfAdditions = [
    '',
    '# Integration with PHP 8',
    'LoadModule php8_module "' . windowsToUnixPath( $phpRoot . '\\php8apache2_4.dll' ) . '"',
    '',
    '<IfModule php8_module>',
    'AddHandler application/x-httpd-php .php',
    'DirectoryIndex index.php index.html',
    'PHPIniDir "' . windowsToUnixPath( $phpRoot ) . '"',
    '</IfModule>',
];

$httpdConf = $apacheRoot . '\\conf\\httpd.conf';

// Backup
$backupFileName = dirname( $httpdConf ) . '\\httpd-' . date( "Y-m-d_H-i-s" ) . '.conf';
echo "Creating a backup of \"{$httpdConf}\"... ";
$ok = copyFileWithAttributes( $httpdConf, $backupFileName );
echo ( $ok ? 'successful!' : 'error.' ), PHP_EOL;


echo 'Do you want to enable some modules (mod_rewrite, mod_headers, mod_ssl) in your httpd.conf (recommended)?', PHP_EOL;
$useOptionalModules = strtolower( answer( [ 'y', 'Y', 'n', 'N' ], 'y', true ) ) == 'y';

$hasError = false;
try {
    $replacements = $useOptionalModules ? [ ...$httpdConfReplacements, ...$httpdConfOptionalReplacements ] : $httpdConfReplacements;
    changeTextFile( $httpdConf, $replacements, $httpdConfAdditions );
    echo 'httpd.conf updated successfully.', PHP_EOL;
} catch ( Exception $e ) {
    $hasError = true;
    echo $e->getMessage(), PHP_EOL;
}

//
// php.ini
//

$phpIni = $phpRoot . '\\php.ini';

$changeIni = true;
$phpIniExists = file_exists( $phpIni );
if ( $phpIniExists ) {
    echo 'Do you want to change your php.ini with the development configuration (recommended)?', PHP_EOL;
    $changeIni = strtolower( answer( [ 'y', 'Y', 'n', 'N' ], 'y', true ) ) == 'y';
    if ( ! $changeIni ) {
        echo 'Ok, no changes.', PHP_EOL;
        exit( $hasError ? 1 : 0 );
    }

    // Backup
    $backupFileName = dirname( $phpIni ) . '\\php-' . date( "Y-m-d_H-i-s" ) . '.ini';
    echo "Creating a backup of \"{$phpIni}\" to \"{$backupFileName}\"... ";
    $ok = copyFileWithAttributes( $phpIni, $backupFileName );
    echo ( $ok ? 'successful!' : 'error.' ), PHP_EOL;
}

// New ini file
$newIniFile = __DIR__ . '\\php.ini-development';

// Copy/replacing the current php.ini
$ok = copyFileWithAttributes( $newIniFile, $phpIni );
if ( ! $ok ) {
    // Trying the file from the web
    $newIniFile = 'https://raw.githubusercontent.com/thiagodp/apache-php/refs/heads/main/php.ini-development';

    $content = file_get_contents( $newIniFile );
    if ( $content === false ) {
        echo 'Could not get the php.ini from the web.', PHP_EOL;
        exit( 1 );
    }
    $result = file_put_contents( $phpIni, $content );
    if ( $result === false ) {
        echo 'Could not write php.ini at ', $phpIni, PHP_EOL;
        exit( 1 );
    }
}

$extensionDir = $phpRoot . '\\ext';

$replacements = [
    ';extension_dir = "ext"' => 'extension_dir="' . $extensionDir . '"'
];

try {
    changeTextFile( $phpIni, $replacements, [] );
    echo 'php.ini ', ( $phpIniExists ? 'updated' : 'created' ), ' successfully: ', $phpIni, PHP_EOL;
} catch ( Exception $e ) {
    echo 'php.ini ', ( $phpIniExists ? 'updated' : 'created' ), ', but the "extension_dir" property could not be updated. Please update it manually.', $phpIni, PHP_EOL;
    exit( 1 );
}

