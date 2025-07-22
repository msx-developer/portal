<?php

namespace Msx\Portal\Models;

use Msx\Portal\Database\ElasticSearchClient;
use Msx\Portal\Helpers\MateriaHelper;

class Busca {

    private static $query_matia = [
        'query' => [
            'bool' => [
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
					[
						'bool' => [
							'must_not' => [
								[
									'exists' => [
										'field' => 'cd_matia_pai'
									]
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

    public function matia($request) {

        extract($request);

        $term = isset($request['q']) ? $request['q'] : $request['term'];

        $client = ElasticSearchClient::getInstance();
        $index = ElasticSearchClient::getIndices()[ElasticSearchClient::$indice_matia];

        if (isset($term) == false)
            return [];

        $terms = explode(',', $term);

        $body = self::$query_matia;

        switch ($tipo) {

            case 'busca':
                $body['query']['bool']['should'] = [
                    'query_string' => [
                        'query' => "\"$term\"",
                        'fields' => ['ds_matia_titlo', 'ds_matia_chape'],
                        "quote_field_suffix" => ".exact",
                    ],
                ];
                $body['query']['bool']['minimum_should_match'] = count($terms);
                break;
            
            case 'related':
            case 'tags':
                $tags = explode(" ", implode(" ", $terms));
                $stopwords = ["de", "a", "o", "que", "e", "do", "da", "em", "um", "para", "é", "com", "não", "uma", "os", "no", "se", "na", "por", "mais", "as", "dos", "como", "mas", "foi", "ao", "ele", "das", "tem", "à", "seu", "sua", "ou", "ser", "quando", "muito", "há", "nos", "já", "está", "eu", "também", "só", "pelo", "pela", "até", "isso", "ela", "entre", "era", "depois", "sem", "mesmo", "aos", "ter", "seus", "quem", "nas", "me", "esse", "eles", "estão", "você", "tinha", "foram", "essa", "num", "nem", "suas", "meu", "às", "minha", "têm", "numa", "pelos", "elas", "havia", "seja", "qual", "será", "nós", "tenho", "lhe", "deles", "essas", "esses", "pelas", "este", "fosse", "dele", "tu", "te", "vocês", "vos", "lhes", "meus", "minhas", "teu", "tua", "teus", "tuas", "nosso", "nossa", "nossos", "nossas", "dela", "delas", "esta", "estes", "estas", "aquele", "aquela", "aqueles", "aquelas", "isto", "aquilo", "estou", "está", "estamos", "estão", "estive", "esteve", "estivemos", "estiveram", "estava", "estávamos", "estavam", "estivera", "estivéramos", "esteja", "estejamos", "estejam", "estivesse", "estivéssemos", "estivessem", "estiver", "estivermos", "estiverem", "hei", "há", "havemos", "hão", "houve", "houvemos", "houveram", "houvera", "houvéramos", "haja", "hajamos", "hajam", "houvesse", "houvéssemos", "houvessem", "houver", "houvermos", "houverem", "houverei", "houverá", "houveremos", "houverão", "houveria", "houveríamos", "houveriam", "sou", "somos", "são", "era", "éramos", "eram", "fui", "foi", "fomos", "foram", "fora", "fôramos", "seja", "sejamos", "sejam", "fosse", "fôssemos", "fossem", "for", "formos", "forem", "serei", "será", "seremos", "serão", "seria", "seríamos", "seriam", "tenho", "tem", "temos", "tém", "tinha", "tínhamos", "tinham", "tive", "teve", "tivemos", "tiveram", "tivera", "tivéramos", "tenha", "tenhamos", "tenham", "tivesse", "tivéssemos", "tivessem", "tiver", "tivermos", "tiverem", "terei", "terá", "teremos", "terão", "teria", "teríamos", "teriam"];
                $body['query']['bool']['should'][0]['bool']['should'] = [];
                foreach ($terms as $tag) {
                    if (in_array($tag, $stopwords)) continue;
                    $tag = trim($tag);
                    $body['query']['bool']['should'][0]['bool']['should'][] =
                        ['wildcard' => ['ds_matia_palvr' => [
                            'value' => "*$tag*"
                        ]]];
                    $body['query']['bool']['should'][0]['bool']['should'][]=
                        ['wildcard' => ['ds_matia_palvr_slug' => [
                            'value' => "*$tag*"
                        ]]];
                }

                if( $tipo == 'related' ) {
                    $cd_matia =  $cd_matia != "" ? $cd_matia : 0;
                    $body['query']['bool']['must_not'] = 
                        array_merge($body['query']['bool']['must_not'], [
                            ['match' => ['cd_matia' => $cd_matia]]
                        ]);
                
                    $body['sort'] = [
                        'dt_matia_publi_year' => ['order' => 'desc'],
                        'dt_matia_publi_month' => ['order' => 'desc'],
                        'dt_matia_publi_day' => ['order' => 'desc'],
                    ];
                }
                break;

            case 'autor':
                $body['query']['bool']['should'] = [
                    'query_string' => [
                        'query' => "\"$term\"",
                        'fields' => ['nm_notia_autor', 'ds_autor_slug'],
                        "quote_field_suffix" => ".exact",
                    ],
                ];
                $body['query']['bool']['minimum_should_match'] = count($terms);
                break;
        }

        if (isset($_SESSION['msx']['portal'])) {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['cd_poral' => intval($_SESSION['msx']['portal'])]]
                ]);
        }

        if (isset($cd_site) && $cd_site != '') {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['cd_site' => $cd_site]]
                ]);
        }

        if (isset($nm_notia_autor) && $nm_notia_autor != '') {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['nm_notia_autor' => $nm_notia_autor]]
                ]);
        }

        if (isset($ds_site) && $ds_site != '') {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => [
                        'ds_site_arvor' => [
                            'query' => "\"$ds_site\"",
                            'operator' => 'and'
                        ]
                    ]]
                ]);
        }

        $dt_ini = $dt_fim = '';
        if (isset($dt_matia_publi_inicial)  && $dt_matia_publi_inicial != '') {
            $dt_matia_publi_inicial = MateriaHelper::DataBrToEn($dt_matia_publi_inicial);
            $dt_ini = $dt_matia_publi_inicial . " 00:00:00";
        }

        if (isset($dt_matia_publi_final) && $dt_matia_publi_final != '') {
            $dt_matia_publi_final = MateriaHelper::DataBrToEn($dt_matia_publi_final);
            $dt_fim = $dt_matia_publi_final . " 23:59:59";
        }

        if ($dt_ini != "" || $dt_fim != "") {
            if ($dt_ini == "")
                $dt_ini = $dt_matia_publi_final . " 00:00:00";

            if ($dt_fim == "")
                $dt_fim = $dt_matia_publi_inicial . " 23:59:59";

            //@todo verificar posição da data no range
            //$body['query']['bool']['must'] = 
            //array_merge($body['query']['bool']['must'],[
            //    'range' => [
            //        'dt_matia_publi' => [
            //            'gte' => "'$dt_ini'",
            //            'lte' => "'$dt_fim'"
            //        ]
            //    ]
            //]);
        }

        if (isset($cd_matia) && $cd_matia != '' && $tipo != "related") {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['cd_matia' => $cd_matia]]
                ]);
        }

        if (isset($cd_templ_matia) && $cd_templ_matia != '') {
            $body['query']['bool']['must'] =
                array_merge($body['query']['bool']['must'], [
                    ['match' => ['cd_templ_matia' => $cd_templ_matia]]
                ]);
        }

        if (isset($qtd) && $qtd > 0) {
            $body['size'] = $qtd;
        }

        if (isset($page) && $page > 0) {
            $qtd = isset($qtd) ? $qtd : 10;
            $body['from'] = ($page - 1) * $qtd;
        } else {
            $page = 1;
        }

        $params = [
            'index' => $index,
            'body' => $body
        ];

        $result = $client->search($params);

        if (isset($result['error'])) {
            return [];
        }

        if (!isset($result['hits'])) {
            return [];
        }

        $data = [
            'info' => $terms,
            'q' => $term,
            'hits' => $result['hits']['hits'],
            'total' => $result['hits']['total']['value'],
            'qtd' => $body['size'],
            'page' => $page
        ];

        return $data;
    }

    public function autor($term) {

        if(is_null($term)) {
            return [];
        }

        if (is_array($term)) {
            $term = implode(",", $term);
        }

        $client = ElasticSearchClient::getInstance();
        $index = ElasticSearchClient::getIndices()[ElasticSearchClient::$indice_autor];

        $body = [
            'query' => [
                'query_string' => [
                    'query' => str_replace(",", " OR ", $term),
                    'fields' => ['nm_autor', 'ds_autor_slug']
                ]
            ],
            "size" => 50,
            "_source" => [
                "nm_autor",
                "ds_autor_email",
                "cd_midia",
                "ds_autor_tel",
                "ds_autor",
                "ds_autor_site",
                "ds_autor_faceb",
                "ds_autor_twite",
                "ds_autor_insta",
                "ds_autor_cargo",
                "ds_autor_local",
                "ds_autor_idiom",
                "ds_autor_curso",
                "ds_autor_premi",
                "ds_autor_link",
                "ds_autor_slug", 
                "ds_midia_link"
            ]

        ];

        if (isset($qtd) && $qtd > 0) {
            $body['size'] = $qtd;
        }

        if (isset($page) && $page > 0) {
            $qtd = isset($qtd) ? $qtd : 10;
            $body['from'] = ($page - 1) * $qtd;
        }

        $params = [
            'index' => $index,
            'body' => $body
        ];


        $result = $client->search($params);

        $map = $arr = [];
        if (isset($result['hits']['hits']) && count($result['hits']['hits']) > 0) {
            foreach ($result['hits']['hits'] as $key => $value) { 
                $v = $value['_source'];
                if(isset($arr[$v['nm_autor']])) {
                    $arr[$v['nm_autor']] = array_merge($arr[$v['nm_autor']], $v);
                    foreach ($arr[$v['nm_autor']] as $k_autor => &$v_autor) {
                        if (!is_null($v[$k_autor])) {
                            $v_autor = $v[$k_autor];
                        }
                    }
                } else {
                    $arr[$v['nm_autor']] = $v;
                }

                if ($v['ds_midia_link'] == "") {
                    $arr["cd_autmts"][] = (int)$value["_id"];
                }
            }
            foreach($arr as $value) {
                $map[] = $value;
            }
        }
        return $map;
    }

    public function midia($request) {
        
        extract($request);

        $client = ElasticSearchClient::getInstance();
        $index = ElasticSearchClient::getIndices()[ElasticSearchClient::$indice_midia];

        $body = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => [
                            'cd_poral' => $_SESSION['msx']['portal']
                        ]
                    ],
                    "must_not" => [
                        [
                            "exists" => [
                                "field" => "dt_midia_exclu"
                            ]
                        ],
                        [
                            "exists" => [
                                "field" => "cd_midia_pai"
                            ]
                        ]
                    ]
                ]
            ],
            "size" => 30,
            "sort" => [
                'dt_midia_incl_year' => ['order' => 'desc'],
                'dt_midia_incl_month' => ['order' => 'desc'],
                'dt_midia_incl_day' => ['order' => 'desc']
            ]
        ];



        if(isset($cd_fldmd) && $cd_fldmd != '') {
            $body['query']['bool']['must'] =
                array_merge(
                    [$body['query']['bool']['must']]
                    ,[['match' => ['cd_fldmd' => $cd_fldmd]]]
            );

            $body['sort'] = [
                "dt_midia_edcao" => ["order" => "desc"],
                'dt_midia_incl_year' => ['order' => 'desc'],
                'dt_midia_incl_month' => ['order' => 'desc'],
                'dt_midia_incl_day' => ['order' => 'desc'],
                'cd_midia' => ['order' => 'desc']
            ]; 
        } else {
            
        }
        
        if (isset($cd_midia) && $cd_midia != '') {
            $body['query']['bool']['must'] =
                array_merge(
                    [$body['query']['bool']['must']]
                    ,[["match" => ["cd_midia" => $cd_midia]]]
            );
        }

        if (isset($qtd) && $qtd > 0) {
            $body['size'] = $qtd;
        }

        if (isset($page) && $page > 0) {
            $qtd = isset($qtd) ? $qtd : 10;
            $body['from'] = ($page - 1) * $qtd;
        }

        $params = [ 
            'index' => $index,
            'body' => $body 
        ];   

        //echo json_encode($body);exit;
        $result = $client->search($params);

        return $result['hits'];
    }

    public function midiasFilhas($cd_midia) {
        $client = ElasticSearchClient::getInstance();   

        $client = ElasticSearchClient::getInstance();
        $index = ElasticSearchClient::getIndices()[ElasticSearchClient::$indice_midia];

        $body = [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => ['cd_poral' => $_SESSION['msx']['portal']]
                        ],
                        [
                            'match' => ['cd_midia_pai' => $cd_midia]
                        ]
                    ],
                    "must_not" => [
                        "exists" => [
                            "field" => "dt_midia_exclu"
                        ]
                    ]
                ]            
            ],
            "size" => 30,
            "sort" => [
                'dt_midia_incl_year' => ['order' => 'desc'],
                'dt_midia_incl_month' => ['order' => 'desc'],
                'dt_midia_incl_day' => ['order' => 'desc']
            ]       
        ];

        $params = [ 
            'index' => $index,
            'body' => $body 
        ];        
        // echo json_encode($body);exit;
        $result = $client->search($params);
        
        return $result['hits'];
    }
}
