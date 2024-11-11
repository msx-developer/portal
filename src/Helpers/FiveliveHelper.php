<?php 

namespace Msx\Portal\Helpers;

use Msx\Portal\Database\Connection;
class FiveliveHelper {

    public static function scriptTop() {
        $fivelive_url = $_SESSION['msx']['url_admin'];
        $cdPortal = $_SESSION['msx']['portal'];

        $ds_poral_url_local = Connection::getInstance()->fetchAll('SELECT * FROM poral WHERE cd_poral = ? LIMIT 1', [$cdPortal])[0]['ds_poral_url_local'];
       
        $script = "
            <script type=\"text/javascript\">
                var five_live = true;
                var five_live_portal = '{$ds_poral_url_local}';
            </script>
            <script type=\"text/javascript\" src=\"{$fivelive_url}includes/components/jquery/jquery-2.2.4.min.js\"></script>
            <link href=\"{$fivelive_url}sistemas/fiveedit6/includes/css/jq2-fivelive.css\" rel=\"stylesheet\" />
            <script type=\"text/javascript\" src=\"{$fivelive_url}includes/components/jquery/jquery-1.11.2.min.js\"></script>
            <script>jQ111 = jQuery.noConflict();</script>
            <script type=\"text/javascript\" src=\"{$fivelive_url}sistemas/fiveedit6/includes/js/ui/jquery-ui.js\"></script>
            <script type=\"text/javascript\" src=\"{$fivelive_url}sistemas/fiveedit6/includes/js/jq2-fivelive.js\"></script>
            <link href=\"{$fivelive_url}sistemas/fiveedit6/includes/js/ui/jquery-ui.css\" rel=\"stylesheet\" />
        ";

        return $script;
    }

    public static function scriptBottom($cd_site) {
        $fivelive_url = $_SESSION['msx']['url_admin'];
        $script = "
            <script type=\"text/javascript\">
                var cd_site = {$cd_site};
                window.parent.FivecomUtil.removeLoadding('.site_{$cd_site},body');
                window.parent.FivecomUtil.hideLoadding('.site_{$cd_site},body');
            </script>
        ";
        return $script;
    }

    public static function fivelive($arr, $atributo = NULL){

        $fields = array(
            "ds_matia_titlo",
            "ds_matia_chape",
            "ds_matia_assun",
            "ds_marep_titlo"
        );

        if(!is_null($atributo)  && !in_array($atributo, $fields))
            return NULL;

        $fivelive_url = $_SESSION['msx']['url_admin'];
		$materia = isset($arr['cd_matia']) ? $arr : [];
        $sesit = isset($arr['cd_sesit']) ? $arr : [];
        $putips = isset($sesit['putips']) ? $sesit['putips'] : [];
        $setips = isset($sesit['setips']) ? $sesit['setips'] : [];


        $bloco = NULL;

        if(in_array($atributo, $fields))
            if(!$materia['fivelive'] || isset($materia["cd_publi"]) == false)
                $bloco = $materia[$atributo];
            else
                $bloco = '
                <fspan class="fivelive">
                    <img class="flField" src="' . $fivelive_url . 'imagens/icons/ics_editar.gif" onclick="FiveliveUtil.editarCampo(this,\'' . $atributo . '\', \'' . $materia["cd_matia"] . '\', \'' . $materia["cd_publi"] . '\');" alt="editar" data-marep="' . ($atributo == "ds_marep_titlo" ? $materia["cd_marep"] : "") . '" />
                    <fspan class="flField">' . (in_array($materia[$atributo], ['', "&nbsp;"]) ? "!PREENCHER!": $materia[$atributo] ) . '</fspan>
                </fspan>
                ';
        else
            if(isset($materia['fivelive']) && $materia['fivelive'] == false)
                $bloco = NULL;
            else {
				$width = (count($putips)>0?'200':'172');
				if (count($materia) > 0) {
					$bloco = '
					<div class="fivelive"  style="">
						<span class="fivelive toolbar" style="width: ' . $width . 'px !important;">';
					if (count($putips)>0) {
					$bloco .= '
							<select name="putip" data-publi="' . $materia["cd_publi"] . '">
								<option value="">Op.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
						foreach($putips as $k=>$v)
							$bloco .= '
								<option value="' . $v["cd_putip"] . '">' . $v["id_putip"] . '</option>';
					$bloco .= '
							</select>
							';
					}
                    if(isset($materia["cd_publi"])) {
                        $bloco .= '
                                <a href="#" rel="new;' . $materia["cd_publi"] . '">
                                    <img src="' . $fivelive_url . 'imagens/icons/page_add.png" title="Nova Mat&eacute;ria" />
                                </a>' .
                                ($materia["cd_matia"] != "" ?
                                '
                                <a href="#" rel="edit;' . $materia["id_matia_tipo"] . ';' . $materia["cd_matia"] . '">
                                    <img src="' . $fivelive_url . 'imagens/icons/page_edit.png" title="Editar" />
                                </a>
                                <a href="#" rel="change;' . $materia["cd_publi"] . '">
                                    <img src="' . $fivelive_url . 'imagens/icons/arrow_switch.png" title="Trocar" />
                                </a>		
                                <a href="#" rel="relMatiaPubli;' . $materia["cd_publi"] . '">
                                    <img src="' . $fivelive_url . 'imagens/icons/tinymce/leia-mais.png" title="Relacionar Mat&eacute;ria">
                                </a>
                                <a href="#" rel="addLink;' . $materia["cd_publi"] . ';">
                                    <img src="' . $fivelive_url . 'imagens/icons/list_links.gif" title="Adicionar Link">
                                </a>
                                <a href="#" rel="midia;' . $materia["cd_publi"] . '">
                                    <img src="' . $fivelive_url . 'imagens/icons/tinymce/imagens.png" title="Inserir Imagem">
                                </a>
                                <a href="#" rel="crop;' . $materia["cd_publi"] . ';" class="last-btn">
                                    <img src="' . $fivelive_url . 'imagens/icons/crop.png" title="Cropar Imagem">
                                </a>' : "")	.	
                            '</span>
                            <span class="fivelive nm_sessit" style="margin: 10px 0 0 0 !important; width: ' . $width . 'px !important;">
                                <a href="javascript:;" style="" href="#">' . ($materia["nm_sesit"] != "" ? $materia["nm_sesit"] : "" ) . '</a>
                            </span>                        
                        ';
                    }
                    $bloco .= '</div>';
				} elseif(isset($sesit['fivelive']) && $sesit['fivelive'] == true) {
					$bloco = '
					<div class="fivelive"  style="top: -20px;">
						<span class="fivelive toolbar" style="width: 60px !important;">';
					if (count($setips) > 0) {
					$bloco .= '
							<select name="setip" data-sesit="' . $sesit["cd_sesit"] . '">
								<option value="">Op.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</option>';
						foreach($setips as $k=>$v)
							$bloco .= '
								<option value="' . $v["cd_setip"] . '">' . $v["id_setip"] . '</option>';
					$bloco .= '
							</select>';
					}
					$bloco .= '
							<input class="qt" data-sesit="' . $sesit["cd_sesit"] . '" name="qt_sesit_matia" value="' . $sesit["qt_sesit_matia"] . '" />
						</span>
					</div>
					';					
				}
			}

        return $bloco;
    }

    public static function getMidia($cd_midia, $midias, $cd_publi) {
        $str = "";

        if($cd_publi == null) {
            return $str;
        }

        $arr = array_filter(
            $midias, function($item) use ($cd_midia) {
                return $item['cd_midia'] == $cd_midia;
        });
        $arr = reset($arr);
        $auxCod = ($arr["cd_midia_pai"] != "" ? $arr["cd_midia_pai"] : $arr["cd_midia"]);

        if(isset($auxCod)){
          
            $pai = array_filter(
                $midias, function($item) use ($auxCod) {
                    return $item['cd_midia'] == $auxCod;
            });
            $pai = reset($pai);

            if(is_array($pai) && count($pai) > 0) {
                $str = "
                    data-midia-publi={$cd_publi}
                    data-midia-id={$arr["cd_midia"]}
                    data-default-height={$arr["cd_midia_h"]}
                    data-default-width={$arr["cd_midia_w"]}
                    data-midia={$arr["cd_midia"]};{$pai["cd_midia_w"]};{$pai["cd_midia_h"]};{$arr["cd_midia_w"]};{$arr["cd_midia_h"]};{$pai["cd_midia"]}
                ";
            }
        }
        return $str;
    }
}