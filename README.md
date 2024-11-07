# Msx Portal

## Descrição

O projeto Msx Portal fornece uma maneira simples e eficiente de interagir com o Msx Portal. Essa biblioteca permite que você recupere e manipule facilmente dados do portal.

## Instalação

Para instalar a biblioteca do Msx Portal, execute o seguinte comando em seu terminal:

```bash
composer require msx-developer/portal --with-dependencies
```

## Configurar

Adicione no arquivo ENV com as configurações de conexão de banco de dados

```
DB_MSX_CONNECTION=mysql
DB_MSX_HOST=localhost
DB_MSX_PORT=3306
DB_MSX_DATABASE=portal_db
DB_MSX_USERNAME=user
DB_MSX_PASSWORD=pass
DB_MSX_PORTAL=1
```

## Exemplo de uso

Este exemplo cria uma nova instância da classe PortalController e a usa para recuperar uma lista de materias, no caso a de id 1159146. Os dados resultantes são armazenados na variável $map.

```php
$portal = new \Msx\Portal\Controllers\PortalController();
$map = $portal->getMaterias([1159146]);
```


