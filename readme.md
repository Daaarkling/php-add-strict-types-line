PHP add-strict-types-line
=================================

Command-line script for adding `declare(strict_types=1);` line to PHP files

It uses native PHP tokenizer, so conversion is safe. It adds the line only to files where `declare(strict_types=1);` was not found.

Usage
-----

To convert all `*.php` and `*.phpt` files in whole directory recursively or to convert a single file use:

```
php add-line.php <directory | file>
```