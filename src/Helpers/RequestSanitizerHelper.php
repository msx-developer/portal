<?php

namespace Msx\Portal\Helpers;

use mysqli;

class RequestSanitizerHelper
{
    /**
     * Trata os dados da superglobal $_REQUEST.
     * @param array $request Dados da superglobal $_REQUEST.
     * @param mysqli|null $dbCon Conexão com o banco de dados para usar mysqli_real_escape_string (opcional).
     * @return array Dados tratados.
     */
    public static function sanitize($request, $dbCon = null)
    {
        $sanitizedData = [];

        foreach ($request as $key => $value) {
            // Remove espaços extras ao redor da chave e valor
            $key = trim($key);

            // Se o valor for um array, aplica a sanitização recursivamente
            if (is_array($value)) {
                $sanitizedData[$key] = self::sanitize($value, $dbCon);
            } else {
                // Remove espaços ao redor do valor
                $value = trim($value);

                // Previne execução de scripts
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

                // Escapa caracteres especiais para uso em SQL, se a conexão for fornecida
                if ($dbCon instanceof mysqli) {
                    $value = mysqli_real_escape_string($dbCon, $value);
                }

                // Remove caracteres nulos e tags PHP/HTML indesejadas
                $value = strip_tags($value);

                $sanitizedData[$key] = $value;
            }
        }

        return $sanitizedData;
    }
}
?>
