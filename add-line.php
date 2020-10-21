<?php
/**
 * Script for adding `declare(strict_types=1);` line to PHP files
 *
 * @author Jan Vanura (https://janvanura.cz)
 * @author David Grudl (http://davidgrudl.com)
 */

// This peace of code is based on dg/php54-arrays by David Grudl (https://github.com/dg/php54-arrays)
$args = $_SERVER['argv'];
if (key_exists(1, $args)) {
	$path = $args[1];

	if (is_file($path)) {
		$iterator = array($path);
	} elseif (is_dir($path)) {
		$iterator = new CallbackFilterIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), function($file) {
			return $file->isFile() && in_array($file->getExtension(), ['php', 'phpt', 'phtml'], true);
		});
	} else {
		echo "Path $path not found.\n";
		exit(1);
	}

	foreach ($iterator as $file) {
		echo $file;
		$orig = file_get_contents($file);
		$res = addStrictTypesLine($orig);
		if ($orig !== $res) {
			echo " (changed)";
			file_put_contents($file, $res);
		}
		echo "\n";
	}
} else {
	echo "
Script for adding `declare(strict_types=1);` line to PHP files
----------------------------
Usage: {$args[0]} [<directory> | <file>]
";
	exit(1);
}

/**
 * Adds `declare(strict_types=1);` line to given code
 *
 * @param string $code
 * @return string
 */
function addStrictTypesLine($code) {

	$out = '';
	$openTagPosition = -1;
	$alreadyContainsLine = false;

	$tokens = token_get_all($code);

	for ($i = 0; $i < count($tokens); $i++) {
		$token = $tokens[$i];

		// find position of open tag `<?php`
		if (is_array($token) && $token[0] === T_OPEN_TAG) {
			$openTagPosition = $i;
		}

		elseif ($openTagPosition !== -1) {
			// skip whitespace
			if (is_array($token) && $token[0] === T_WHITESPACE) {
				continue;
			}

			// check if there already is line `declare(strict_types=1)` immediately after <?php
			if (is_array($token) && $token[0] === T_DECLARE &&
				key_exists($i+1, $tokens) && $tokens[$i+1] === '(' &&
				key_exists($i+2, $tokens) && is_array($tokens[$i+2]) && $tokens[$i+2][1] === 'strict_types' &&
				key_exists($i+3, $tokens) && $tokens[$i+3] === '=' &&
				key_exists($i+4, $tokens) && is_array($tokens[$i+4]) && $tokens[$i+4][1] === "1" &&
				key_exists($i+5, $tokens) && $tokens[$i+5] === ')'
			) {
				$alreadyContainsLine = true;
			}

			break;
		}
	}

	// if file contains `<?php` and does not contain `declare(strict_types=1)`
	if ($openTagPosition >= 0 && !$alreadyContainsLine) {
		// add the line
		array_splice($tokens, $openTagPosition+1, 0, [PHP_EOL . 'declare(strict_types=1);' . PHP_EOL]);

		// print it back to string
		for ($i = 0; $i < count($tokens); $i++) {
			$token = $tokens[$i];
			$out .= is_array($token) ? $token[1] : $token;
		}

		return $out;
	} else {
		return $code;
	}
}
