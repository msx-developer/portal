# Msx Portal

## Descrição

O projeto Msx Portal fornece uma maneira simples e eficiente de interagir com o Msx Portal. Essa biblioteca permite que você recupere e manipule facilmente dados do portal.

## Instalação

Para instalar a biblioteca do Msx Portal, execute o seguinte comando em seu terminal:

```bash
composer require msx-developer/portal --with-dependencies
```

## Configurar

Adicione no arquivo ENV com as configurações de conexão de banco de dados e do elasticsearch

```
DB_MSX_CONNECTION=mysql
DB_MSX_HOST=localhost
DB_MSX_PORT=3306
DB_MSX_DATABASE=portal_db
DB_MSX_USERNAME=user
DB_MSX_PASSWORD=pass
DB_MSX_PORTAL=1
DB_MSX_URL_ADMIN=http://admin.local/

DB_MSX_ELASTICSEARCH_HOST=localhost
DB_MSX_ELASTICSEARCH_PORT=9200
DB_MSX_ELASTICSEARCH_PASSWORD=pass
DB_MSX_ELASTICSEARCH_USER=user
DB_MSX_ELASTICSEARCH_PREFIX=client_
```

## Exemplo de uso

Este exemplo cria uma nova instância da classe PortalController e a usa para recuperar uma lista de materias, no caso a de id 1159146. Os dados resultantes são armazenados na variável $map.

```php
$portal = new \Msx\Portal\Controllers\PortalController();
$map = $portal->getMaterias([1159146]);
```

No exemplo abaixo um site é listado contendo os dados do mesmo, suas seções e as matérias das seções

```php
$cod_site = 278;
$portal = new \Msx\Portal\Controllers\PortalController();
$dados_site = $portal->getSites($cod_site);
$secoes_site = $portal->getSecoes($cod_site);
foreach ($secoes_site as $key => $value) 
    $secoes_site[$key]['noticias'] = $portal->getMateriasSesit($value['cd_sesit']);
```

Habilitando o fivelive, para tal, as funções usadas serão:

 - Barra de botões da notícias, parametro é o array com os dados de uma notícia
```php
{!! $fivelive::fivelive($noticia) !!} 
```
 - Barra edição do texto da notícia, para o parâmetro do nome do campo os valores são: ds_matia_titlo, ds_matia_assun, ds_matia_chape, ds_marep_titlo
```php
{!! $fivelive::fivelive($noticia, "ds_matia_titlo") !!} 
```

 - Atributos de imagem para edição via fivelive, os parâmetros são: código da mídia, o array das mídias da matéria e o código da publicação da notícia
```php
{{ $fivelive::getMidia($midia['cd_midia'], $midias, ($noticia['cd_publi'] ?? null) ) }} 
```

Exemplo completo com a implementação dos itens listados acima:

```php
<div class="rounded overflow-hidden shadow-lg flex flex-col w-80 mx-8" data-mode="load">    
{!! $fivelive::fivelive($noticia) !!}
    <div class="relative"> 
        <a href="http://dev.news.local:81/noticia/{{ $noticia['cd_matia'] }}">
        @if($noticia['cd_midia'] != "" && isset($noticia['midmas'][$noticia['cd_midia']]))
        @php 
            $midias = $noticia['midmas'][$noticia['cd_midia']]['midias']; 
            $midia = isset($midias['480x320']) ? $midias['480x320'] : end($midias);
        @endphp
           
            <img class="w-full" src="{{ $midia['ds_midia_link'] }}" alt="{{ $midia['ds_midia'] }}" {{ $fivelive::getMidia($midia['cd_midia'], $midias, ($noticia['cd_publi'] ?? null) ) }}>
        @endif
        </a>
        <a href="http://dev.news.local:81/noticia/{{ $noticia['cd_matia'] }}">
            <div class="text-xs absolute top-0 left-0 bg-indigo-600 px-4 py-2 text-white mt-3 mr-3 hover:bg-white hover:text-indigo-600 transition duration-500 ease-in-out">
            {!! $fivelive::fivelive($noticia, "ds_matia_assun") !!}
            </div>
        </a>
    </div>
    <div class="px-6 py-4 mb-auto">
        <a href="http://dev.news.local:81/noticia/{{ $noticia['cd_matia'] }}" class="font-medium text-lg inline-block hover:text-indigo-600 transition duration-500 ease-in-out inline-block mb-2">
            {!! $fivelive::fivelive($noticia, "ds_matia_titlo") !!}
        </a>
        <p class="text-gray-500"> {!! $fivelive::fivelive($noticia, "ds_matia_chape") !!} </p>
    </div>
    <div class="px-6 py-3 flex flex-row items-center justify-between bg-gray-100">
        <span href="#" class="py-1 text-xs font-regular text-gray-900 mr-1 flex flex-row items-center">
            <svg height="13px" width="13px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
                <g>
                    <g>
                        <path d="M256,0C114.837,0,0,114.837,0,256s114.837,256,256,256s256-114.837,256-256S397.163,0,256,0z M277.333,256 c0,11.797-9.536,21.333-21.333,21.333h-85.333c-11.797,0-21.333-9.536-21.333-21.333s9.536-21.333,21.333-21.333h64v-128 c0-11.797,9.536-21.333,21.333-21.333s21.333,9.536,21.333,21.333V256z">
                        </path>
                    </g>
                </g>
            </svg>
            <span class="ml-1">{{ date('d/m/Y H:i', strtotime($noticia['dt_matia_publi'])) }}</span>
        </span>
    </div>
</div>

```

 - Para habilitar os scripts que tornam a barra de botões funcionais são necessários a exibição dos dos atributos listados abaixo:
    - Na tag head do html

    ```php
    {!! $site['fivelive']['scriptTop'] !!}
    ```

    - Antes do fim da tag body

    ```php
    {!! $site['fivelive']['scriptBottom'] !!}
    ```

    - Exemplo completo
    ```php
    //site.blade.php
    @extends('layout.news')

    @section('content') 

    @if( isset($secoes) && is_array($secoes) && count($secoes) > 0)
        @foreach($secoes as $secao){{dd($secao);}}
    <section class="p-4 lg:p-8 dark:bg-gray-100 dark:text-gray-800">
        @if(isset($fivelive))
        {!! $fivelive::fivelive($secao) !!}
        @endif
        <h4 class="text-2xl font-bold text-gray-800 text-center p-3 text-uppercase border-blueGray-300">{{$secao['nm_sesit']}}</h4>        
    </section>
        @endforeach
    @endif

    @endsection

    @section('scriptTop')
    @if(isset($site) && isset($site['fivelive']))
    {!! $site['fivelive']['scriptTop'] !!}
    @endif
    @endsection

    @section('scriptBottom')
    @if(isset($site) && isset($site['fivelive']))
    {!! $site['fivelive']['scriptBottom'] !!}
    @endif
    @endsection
    ```

    ```php
    <!--layout/news.blade.php -->
    <!DOCTYPE html>
    <html>

    <head lang="{{ str_replace('_', '-', app()->getLocale()) }}">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <meta name="description" content="">
        <meta name="author" content="name">
        <meta name="keywords" content="keywords,here">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="">
        @yield('scriptTop')
    </head> 

    <body class="bg-gray-200 font-sans leading-normal tracking-normal">
        @yield('content')
        @yield('scriptBottom')
    </body>
    </html>
    ```

    Funções de busca de matérias com dados do elasticsearch:

    - Busca geral por termos

    ```php
    use Msx\Portal\Controllers\SearchController;
    $search = new SearchController();
    
    // termo buscado
    $texto = $request->input('q');
    $parametros = ['q' => $texto];
    $materias = $search->busca($parametros);

    ```
    - Busca geral por tags

    ```php
    use Msx\Portal\Controllers\SearchController;
    $search = new SearchController();
    
    //texto contendo as tags divididas por ','
    $tags = $request->input('tags');
    $parametros = ['q' => $tags];
    $materias = $search->busca($parametros);

    ```

    - Busca geral por autor

    ```php
    use Msx\Portal\Controllers\SearchController;
    $search = new SearchController();
    
    //texto contendo os autores divididos por ','
    $autores = $request->input('autores');
    $parametros = ['q' => $autores];
    $materias = $search->busca($parametros);

    ```
    
    - Busca por tags relacionadas a um matéria

    ```php
    use Msx\Portal\Controllers\SearchController;
    $search = new SearchController();
    
    //texto contendo as tags da matéria divididas por ','
    $tags = $request->input('tags');
    //identificador da matéria
    $id = $request->input('id');
    $parametros = ['q' => $autores, 'cd_matia' => $id];
    $materias = $search->busca($parametros);

    ```

    - Outros filtros relacionados as funções acima

    ```php
    //Nome do site
    $site = $request->input('site');
    //Identidicador do site
    $idSite = $request->input('id');
    //Número da página
    $page = $request->input('page');
    //Quantidade de itens por página
    $site = $request->input('size');

    $parametros = [
        'ds_site' => $site,
        'cd_site' => $cd_site,
        'qtd' => $size,
        'page' => $page
    ];
    ```

    - Exemplo completo

    ```php
    use Msx\Portal\Controllers\SearchController;
    $search = new SearchController();

    $size = 30; //padrão 10
    $requestData = $request->all();

    $q = $requestData['q'];
    $cd_site = $requestData['site'] ?? null;
    $page = $requestData['page'] ?? 1;
    $cd_matia = $requestData['id'] ?? null;

    $parametros = compact('q', 'cd_site', 'page', 'cd_matia');
    
    $materias = [];

    if ($request->is('search/*')) {
        $materias = $search->busca($parametros);
    }

    if ($request->is('tags/*')) {
        $materias = $search->busca($parametros);
    }

    if ($request->is('autor/*')) {
        $materias = $search->busca($parametros);
    }

    if ($request->is('related/*')) {
        $materias = $search->busca($parametros);
    }    
    ```

    Função de busca de mídias com dados do elasticsearch:

    - Busca por pasta

    ```php
    use Msx\Portal\Controllers\SearchController;
    $search = new SearchController();
    
    // id da pasta de mídias 
    $id = $request->input('id');
    $charges = $search->midia(['cd_fldmd' => $id]);

    ```