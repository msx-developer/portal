<?php 

namespace Msx\Portal\Models;

use Msx\Portal\Database\ElasticSearchClient;
use Msx\Portal\Database\Connection;

class Sitemap { 
    private static $query_matia = [
        'query' => [
            'bool' => [
                'filter' => [
                ],
                'must_not' => [
                    [
                        'match' => [
                            'cd_matia_statu' => 3
                        ]
                    ],
                    [
                        'match' => [
                            'cd_matia' => 0
                        ]
                    ]
                ],
                'must' => [
                    [
                        'match' => [
                            'cd_matia_statu' => 2
                        ]
                    ]
                ],
                'minimum_should_match' => 1,
                'should' => [
                    'bool' => [
                        'must_not' => [
                            [
                                'exists' => [
                                    'field' => 'cd_matia_pai'
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ],
        'size' => 10,
        'sort' => [
            'dt_matia_publi_year' => ['order' => 'desc'],
            'dt_matia_publi_month' => ['order' => 'desc'],
            'dt_matia_publi_day' => ['order' => 'desc'],
        ],
        '_source' => [
            "cd_matia",
            "ds_matia_link",
            "ds_matia_titlo",
            "dt_matia_publi",
            "ds_site",
            "ds_matia_assun",
            "ds_matia_chape",
            "qt_matia_visua",
            "ds_matia_palvr",
            "dt_matia_publi",
            "cd_midia",
            "ds_midia_link",
            "nm_midia_inter_thumb1",
            "dt_matia_publi",
            "ds_matia_palvr_slug",
            "ds_midia_titlo",
            "ds_matia_palvr",
            "ds_autor_slug",
            "ds_midia_credi",
            "nm_midia_inter_thumb2",
            "dt_matia_incl",
            "cd_site",
            "cd_matia_statu",
            "nm_notia_autor",
            "dt_matia_publi_year",
            "dt_matia_publi_month",
            "dt_matia_publi_day",
            "ds_matia_palvr_slug",
            "ds_matia_assun_slug"
        ]
    ];

    private $client;
    private $indexName;
    private $counting;
    private $connection;
    private $map;
    private $request;
    private $portal;
    private $ano;

    public function getCounting() {
		return $this->counting;
	}

    public function getRequest() {
        return $this->request;  
    }

    public function getMap() {
        return $this->map;
    }

    public function getAno() {
        return $this->ano;
    }

    public function __construct($request = null) {
        $this->request = $request;
        $this->client = ElasticSearchClient::getInstance();
        $this->indexName = ElasticSearchClient::getIndices()[ElasticSearchClient::$indice_matia];
        $this->connection = Connection::getInstance();
        $this->setPortal();  
    }
    
    public function getSitemapElastic($params, $size, $page, $terms = null) {

        $result = $this->client->search($params);

        if (isset($result['error'])) {
            return [];
        }

        if (!isset($result['hits'])) {
            return [];
        }

        $data = [
            'info' => $terms,
            'q' => $terms,
            'hits' => $result['hits']['hits'],
            'total' => $result['hits']['total']['value'],   
            'qtd' => $size,
            'page' => $page
        ];

        return $data;
    }

    /**
     * Listagem de notícias para montagem de sitemap
     * @todo type index, lista por autor, lista por tags, lista de notícias com video, tratamento de ds_matia_link
     * @param mixed $request
     * @return bool|string|array
     */
    public function busca() {

        extract($this->request);

        $body = self::$query_matia;

        $body['_source'] = [
			"cd_matia",
			"dt_matia_publi"
		];

        if (isset($_SESSION['msx']['portal'])) 
            $body['query']['bool']['must'] = 
                array_merge($body['query']['bool']['must'] , [
                    ['match' => ['cd_poral' => intval($_SESSION['msx']['portal'])]]
                ]); 
  
        $body['query']['bool']['filter'] =
                array_merge($body['query']['bool']['filter'], [
                    'exists' => ['field' => 'dt_matia_publi']
                ]);
            
        // Range de datas
        if($type != "template") {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['range' => ['dt_matia_publi' => ['lte' => 'now']]]
                ]);
        }

        if (in_array($type, ["sitemap", "index"])) {
            $body['query']['bool']['should']['bool']['must_not'] =
                array_merge($body['query']['bool']['should']['bool']['must_not'], [
                    ['exists' => ['field' => 'id_matia_seo_nofol']]
                ]);
        }            

        if (isset($cd_site) && $cd_site != '') {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['cd_site' => $cd_site]]
                ]);
        }

        if (isset($site) && $site != '') {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['ds_site' => $site]]
                ]);
        }

		if (isset($arvore) && $arvore != '') {
			if (is_array($arvore))
				$arvore = implode(" OR ", $arvore);
            else 
                $arvore = mb_strtolower(trim(preg_replace('/\s/', '-', $arvore)));


			$body['query']['bool']['must'] = array_merge(
				$body['query']['bool']['must'],
				[
					[
						"query_string" => [
							"default_field" => "ds_site_arvor", 
							"query" => "\"$arvore\""
						]
					]
				]
			);
		}

		if (isset($notArvore) && $notArvore != '') {
			if (is_array($notArvore))
				$notArvore = implode(" OR ", $notArvore);

			$body['query']['bool']['should']['bool']['must_not'] =
				array_merge(
					$body['query']['bool']['should']['bool']['must_not'],
					[
						[
							"query_string" => [
								"default_field" => "ds_site_arvor",
								"query" => $notArvore
							]
						]
					]
				);
		}

        $body['size'] = 1000;
        $body['sort'] = ['dt_matia_publi' => ['order' => 'desc']];

        if (isset($qtd) && $qtd > 0) {
            $body['size'] = $qtd;
        }

        if (isset($page) && $page > 0) {
            $qtd = isset($qtd) ? $qtd : 1000;
            $body['from'] = ($page - 1) * $qtd;
            $body['sort'] = ['dt_matia_publi' => ['order' => 'asc']];
        } else {
            $page = 1;
        }
        
        $params = [
            'index' => $this->indexName,
            'body' => $body
        ];

        if($type == "index") {
            unset($body['size']);
			unset($body['from']);
			unset($body['sort']);
			unset($body['_source']);  
            return $this->client->count([
                'index' => $this->indexName,
                'body' => $body
            ]);          
        }

        if($type == "indexmap") {
            $aux = $body;

            unset($aux['size']);
            unset($aux['from']);
            unset($aux['sort']);
            unset($aux['_source']);

            $total = $this->client->count([
                'index' => $this->indexName,
                'body' => $aux
            ]);

            $body['size'] = 1000;

            $nrows = (int) ($total['count'] / $body['size']) + 1;
            $body['from'] = ($nrows - $page) * 1000;
            $body['sort'] = ['dt_matia_publi' => ['order' => 'desc']];

        } else {
            $qtd = isset($qtd) ? $qtd : 1000;
            $body['size'] = $qtd;
            $body['from'] = ($page - 1) * $qtd;
        }

        $params = [
            'index' => $this->indexName,
            'body' => $body
        ];

        return $this->getSitemapElastic($params, $body['size'], $page);
    }

    /**
     * Busca as matérias para o sitemap
     * @param array $request
     * @return Sitemap
     */
    public function getMaterias() {
        extract($this->request);

        $map = [];
        $sql = "";
        $page = isset($page) ? $page : 1;
        $qtd = isset($qtd) ? $qtd : 1000;

        $orderBy = " ORDER BY matia.dt_matia_publi DESC ";
        $limit = " LIMIT 1000 ";

        if($page > 1) {
            $orderBy = " ORDER BY matia.dt_matia_publi ";
            $limit = " LIMIT " . ($page - 1) * $qtd .  ", " . $qtd;
        }        

        switch ($type) {
            case 'indexmap':
                $sql = "SELECT
                CONCAT_WS('', 
                '<url>', 
                    '<loc>', poral.ds_poral_url, REPLACE(matia.ds_matia_path, '/index.html', '/'), '</loc>',
                    '<lastmod>', date_format(matia.dt_matia_publi, '%Y-%m-%dT%H:%i:%s-03:00'), '</lastmod>',
                    '<changefreq>daily</changefreq>',
                    '<priority>0.9</priority>',
                    if(matia.cd_midia is not null and midia.id_midia_tipo = 2, CONCAT_WS('', '<image:image><image:loc>', midia.ds_midia_link, '</image:loc></image:image>'), ''),
                '</url>'
                ) as x, ";
                
                $orderBy = " ORDER BY matia.dt_matia_publi DESC ";
                $limit = " LIMIT 1000 ";

                break;
            case 'sitemap':
                $sql = "SELECT
					CONCAT_WS('', 
					'<url>', 
						'<loc>', poral.ds_poral_url, REPLACE(matia.ds_matia_path, '/index.html', '/'), '</loc>',
						'<lastmod>', date_format(matia.dt_matia_publi, '%Y-%m-%dT%H:%i:%s-03:00'), '</lastmod>',
						if(matia.cd_midia is not null and midia.id_midia_tipo = 2, CONCAT_WS('', '<image:image><image:loc>', midia.ds_midia_link, '</image:loc></image:image>'), ''),
					'</url>'
					) as x, ";
                break;
            case 'googlenews':
                $sql = "SELECT
                    CONCAT_WS('', 
                    '<url>', 
                        '<loc>', poral.ds_poral_url, REPLACE(matia.ds_matia_path, '/index.html', '/'), '</loc>',
                        '<news:news>',
                            '<news:publication>',
                                '<news:name>', poral.ds_poral ,'</news:name>',
                                '<news:language>pt-br</news:language>',
                            '</news:publication>',
                            '<news:publication_date>', date_format(matia.dt_matia_publi, '%Y-%m-%dT%H:%i:%s-03:00'), '</news:publication_date>',
                            '<news:title><![CDATA[', matia.ds_matia_titlo ,']]></news:title>',
                            '<news:keywords><![CDATA[', matia.ds_matia_palvr, ']]></news:keywords>',
                        '</news:news>',
                        if(matia.cd_midia is not null, CONCAT_WS('', '<image:image><image:loc>',midia.ds_midia_link,'</image:loc><image:title><![CDATA[',midia.ds_midia,']]></image:title><image:caption><![CDATA[',midia.ds_midia_credi,']]></image:caption></image:image>'), ''),
                    '</url>'
                    ) as x, ";
                $orderBy = " ORDER BY matia.dt_matia_publi DESC ";
                $limit = " LIMIT 50000 ";
                break;
            case 'template':
                $sql = "SELECT 
                    matia.ds_matia_titlo AS 'title',
                    IFNULL(matia.ds_matia_link, CONCAT(poral.ds_poral_url,matia.ds_matia_path)) AS 'link',
                    DATE_FORMAT(matia.dt_matia_publi, '%Y-%m-%dT%H:%i:%s.000Z') AS 'pubDate',
                    DATE_FORMAT(IFNULL(matia.dt_matia_alter, matia.dt_matia_publi), '%Y-%m-%dT%H:%i:%s.000Z') AS 'updated',
                    matia.ds_matia_palvr AS 'keywords',
                    (SELECT GROUP_CONCAT(DISTINCT nm_autor SEPARATOR ',')
                        FROM autor
                        INNER JOIN autmt ON (autmt.cd_autor = autor.cd_autor)
                    WHERE autmt.cd_matia = matia.cd_matia) AS 'author',
                    matia.ds_matia AS 'content',
                    matia.ds_matia_assun AS 'category',
                    matia.ds_matia_chape AS 'description',
                    midia.ds_midia_link AS 'media',
                    CONCAT(poral.ds_poral_url,REPLACE(REPLACE(site.ds_site_path, '_conteudo/', ''), '/index.html', '')) AS 'siteUrl',
                    site.ds_site AS 'site', 
                    midia.cd_midia_w AS 'mediaWidth',
                    midia.cd_midia_h AS 'mediaHeight',
                    midia.nm_midia_inter_thumb1 AS 'mediaThumbnail',
                    midia.ds_midia AS 'mediaDescription',
                    midia.ds_midia_credi AS 'mediaCredit', 
                    matia.id_matia AS 'guid',
                    ";
                $orderBy = " ORDER BY matia.dt_matia_publi DESC ";
                $limit = " LIMIT 30 ";
                break;
            case 'rss':
                $sql = "SELECT	
                    CONCAT_WS('', 
                    '<entry xmlns=\"http://www.w3.org/2005/Atom\" xml:lang=\"pt\">', 
                        '<id>', poral.ds_poral_url, REPLACE(matia.ds_matia_path, '/index.html', '/'), '</id>',
                        '<published>', date_format(matia.dt_matia_publi, '%Y-%m-%dT%H:%i:%s.000Z'), '</published>',
                        '<updated>', date_format(IFNULL(matia.dt_matia_alter, matia.dt_matia_publi), '%Y-%m-%dT%H:%i:%s.000Z'), '</updated>',
                        '<title>', matia.ds_matia_titlo ,'</title>',
                        '<content type=\"text\">', IFNULL(IFNULL(matia.ds_matia_chape, matia.ds_matia_assun), matia.ds_matia_titlo) ,'</content>',
                        '<link title=\"', REPLACE(REPLACE(REPLACE(matia.ds_matia_titlo, '&amp;quot;', '\"'), '&quot;', '\"'), '\"', '&amp;quot;') ,'\" rel=\"alternate\" href=\"', poral.ds_poral_url, matia.ds_matia_path, '\" type=\"text/html\" />',
                        '<author><name>', (SELECT GROUP_CONCAT(nm_autor SEPARATOR '</name></author><author><name>') AS nm_autm FROM autor, autmt WHERE autmt.cd_autor = autor.cd_autor AND autmt.cd_matia = matia.cd_matia), '</name></author>',
                    '</entry>') as x, ";
                break;
            case 'site':
                break;
            case 'index':
                $this->map = $this->busca();   
                return $this;
            default:
                break;
        }

        $sql .= "
				matia.cd_matia,
				matia.dt_matia_publi
			";

        $sql .= $this->selectJoins();
        $sql .= $this->selectWhere();
        $sql .= $orderBy;
        $sql .= $limit;
        
        $this->map = (array) $this->connection->fetchAll($sql);

        return $this;
    }

    public function selectJoins() {
        if ($this->getCounting())
			$sql = " FROM matia USE INDEX (PRIMARY)";
		else
			$sql = " FROM matia ";

		$sql .= " 
				JOIN site ON matia.cd_site = site.cd_site
				JOIN poral ON site.cd_poral = poral.cd_poral
				LEFT JOIN midia ON matia.cd_midia = midia.cd_midia
			";
		return $sql;
    }

    public function selectWhere() {
        extract($this->request);

        $sql = "";
        $resultElastic = $this->busca();
        
        if(is_array($resultElastic) && count($resultElastic) > 0) {
            if($type == "index") {
                $sql = "";
            } else {
                $ids = '';
                if (isset($resultElastic['hits']) && count($resultElastic['hits']) > 0) {
                    $ids = array_map(function($item) {
                        return $item['_source']['cd_matia'];
                    }, $resultElastic['hits']);
                    $ids = implode(',', $ids);
                }
                $sql .= " WHERE matia.cd_matia IN ($ids) ";

                if(isset($date) && $date != "") {
                    switch ($date) {
                        case 'today':
                            $sql .= " AND DATE(matia.dt_matia_publi) = CURDATE() ";
                            break;
                        case 'yesterday':
                            $sql .= " AND DATE(matia.dt_matia_publi) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) ";
                            break;
                        case 'lastweek':
                            $sql .= " AND DATE(matia.dt_matia_publi) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
                            break;
                        case 'lastmonth':
                            $sql .= " AND DATE(matia.dt_matia_publi) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
                            break;
                    }
                }
            }
        }        

        if(empty($sql)) {
            $sql .= " WHERE 1=0 ";
        } 

        return $sql;
    }

    public function selectNrows() {
        $sql = "SELECT COUNT(*) as total FROM matia ";
        $sql .= $this->selectJoins();
        $sql .= $this->selectWhere();
        return (int) $this->connection->fetch($sql)['total'];
    }

    public function getXML() {
        
        extract($this->request);

        $poral = $this->portal;
        $page = isset($page) && is_numeric($page) ? $page : 1;

        header('Content-Type: application/xml; charset=utf-8');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        date_default_timezone_set("America/Sao_Paulo");
        header("Access-Control-Allow-Origin: " . $poral['ds_poral_url']);
        header('Access-Control-Max-Age: 86400');
        
        $content  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        if ($type == "index") {

            $nrows = $this->busca()['count'];
            $nrows = (int) ($nrows / 1000) + 1;
            $nrows = $nrows - (($page - 1) * 1000);
            
            if ($nrows > 1000)
                $nrows = 1000;

            $content .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
            for ($i = $nrows; $i >= 1; $i--) {
                $indice = $i + (($page - 1) * 1000);
                $content .= "	<sitemap>";
                if ($this->getAno() != "")
                    $content .= "		<loc>" . $poral["ds_poral_url"] . "/sitemap/" . $this->getAno() . "/{$indice}.xml</loc>";
                else
                    $content .= "		<loc>" . $poral["ds_poral_url"] . "/sitemap/map/{$indice}.xml</loc>";
                $content .= "	</sitemap>\n";
            }
            $content .= "</sitemapindex>\n";

        } else {

            
            if ($type == "rss") {
				$content .= '<feed xmlns="http://www.w3.org/2005/Atom">' . PHP_EOL;
				$content .= '<title>' . $poral["ds_poral"] . ' Sitemap</title>' . PHP_EOL;
				$content .= '<link href="' . $poral["ds_poral_url"] . '/" rel="self" type="application/atom+xml"/>' . PHP_EOL;
				$content .= '<link rel="hub" href="https://pubsubhubbub.appspot.com/" />' . PHP_EOL;
				$content .= '<updated>' . date("Y-m-d\TH:i:s-03:00") . '</updated>' . PHP_EOL;
				$content .= '<id>' . $poral["ds_poral_url"] . '/</id>' . PHP_EOL;
			} else {
                if($type != "indexmap") {
                    if(preg_match("/dev.(msx|news).local/i", $_SERVER['HTTP_HOST'])) {
                        $content .= "<?xml-stylesheet type=\"text/xsl\" href=\"http://" . $_SERVER['HTTP_HOST'] . "/sitemap.xsl\"?>\n";
                    } else {
                        $content .= "<?xml-stylesheet type=\"text/xsl\" href=\"" . $poral["ds_poral_url"] . "/sitemap.xsl\"?>\n";
                    }
                    $content .= "<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
                } else {
                    $content .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\">\n";
                }   
            }
				
            if(isset($this->map) && count($this->map) > 0) {
                foreach ($this->map as $key => $rows) {
                    $content .= str_replace("&", "&amp;", html_entity_decode($rows['x'])) . "\n";
                }
            } else {
                $content .= "<url><loc>" . $poral["ds_poral_url"] . "</loc></url>\n";
            }

            if ($type == "rss")
				$content .= "</feed>";
			else
				$content .= "</urlset>";
        }

        return $content;
    }

    public function setPortal() {
        if (isset($this->request['cd_poral']) && $this->request['cd_poral'] != '') {
            $this->request['cd_poral'] = intval($this->request['cd_poral']);
        } else {
            $this->request['cd_poral'] = $_SESSION['msx']['portal'];
        }
        $this->portal = (array) $this->connection->fetch("SELECT * FROM poral WHERE poral.cd_poral = ?", [$this->request['cd_poral']]);
    }   
}