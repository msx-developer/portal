# Msx Portal
======================

## Description

The Msx Portal project provides a simple and efficient way to interact with the Msx Portal. This library allows you to easily retrieve and manipulate data from the portal.

## Installation

To install the Msx Portal library, run the following command in your terminal:

```bash
composer require msx/portal --with-dependencies
```

## Configure

Add in ENV file the connection configures

```
DB_MSX_CONNECTION=mysql
DB_MSX_HOST=localhost
DB_MSX_PORT=3306
DB_MSX_DATABASE=portal_db
DB_MSX_USERNAME=user
DB_MSX_PASSWORD=pass
DB_MSX_PORTAL=1
```

## Example Usage

Here is an example of how to use the Msx Portal library:

```php
$portal = new \Msx\Portal\Controllers\PortalController();
$map = $portal->getMaterias([1159146]);
```

This example creates a new instance of the PortalController class and uses it to retrieve a list of materias with the ID 1159146. The resulting data is stored in the $map variable.

Note: Make sure to replace the ID 1159146 with the actual ID of the materia you want to retrieve.