<?php
/**
 * @author Elias Lazcano Castro Neto
 */

namespace I2br\Helpers;

class CreciHelper
{
  static $regionais = [
    0 => ['uf' => 'XX', 'extenso' => 'Homologacao'],
    1 => ['uf' => 'RJ', 'extenso' => 'Rio de Janeiro'],
    2 => ['uf' => 'SP', 'extenso' => 'São Paulo'],
    3 => ['uf' => 'RS', 'extenso' => 'Rio Grande do Sul'],
    4 => ['uf' => 'MG', 'extenso' => 'Minas Gerais'],
    5 => ['uf' => 'GO', 'extenso' => 'Goiás'],
    6 => ['uf' => 'PR', 'extenso' => 'Paraná'],
    7 => ['uf' => 'PE', 'extenso' => 'Pernambuco'],
    8 => ['uf' => 'DF', 'extenso' => 'Distrito Federal'],
    9 => ['uf' => 'BA', 'extenso' => 'Bahia'],
    11 => ['uf' => 'SC', 'extenso' => 'Santa Catarina'],
    12 => ['uf' => 'PA', 'extenso' => 'Pará & Amapá'],
    13 => ['uf' => 'ES', 'extenso' => 'Espírito Santo'],
    14 => ['uf' => 'MS', 'extenso' => 'Mato Grosso do Sul'],
    15 => ['uf' => 'CE', 'extenso' => 'Ceará'],
    16 => ['uf' => 'SE', 'extenso' => 'Sergipe'],
    17 => ['uf' => 'RN', 'extenso' => 'Rio Grande do Norte'],
    18 => ['uf' => 'AM', 'extenso' => 'Amazonas & Roraima'],
    19 => ['uf' => 'MT', 'extenso' => 'Mato Grosso'],
    20 => ['uf' => 'MA', 'extenso' => 'Maranhão'],
    21 => ['uf' => 'PB', 'extenso' => 'Paraíba'],
    22 => ['uf' => 'AL', 'extenso' => 'Alagoas'],
    23 => ['uf' => 'PI', 'extenso' => 'Piauí'],
    24 => ['uf' => 'RO', 'extenso' => 'Rondônia'],
    25 => ['uf' => 'TO', 'extenso' => 'Tocantins'],
    26 => ['uf' => 'AC', 'extenso' => 'Acre'],
    27 => ['uf' => 'RR', 'extenso' => 'Roraima']
  ];

  /**
   * Obtem o UF do regional.
   * @param int $regional Numero da regiao.
   * @param bool $upper O retorno será em caixa alta.
   * @param bool $extenso O retorno será o nome do estado por extenso.
   * @return string|null false caso não encontre o regional.
   */
  static function idParaUf($regional, $upper = false, $extenso = false)
  {
    if (!isset(self::$regionais[$regional])) return null;
    $uf = $extenso ? self::$regionais[$regional]['extenso'] : self::$regionais[$regional]['uf'];
    return $upper ? mb_strtoupper($uf, 'UTF-8') : mb_strtolower($uf, 'UTF-8');
  }

  /**
   * Obtem o numero da regiao.
   * @param string $uf Sigla do UF do regional (dois caracteres).
   * @return int|null false caso não encontre o regional.
   */
  static function ufParaId($uf)
  {
    if (strlen($uf) !== 2) return null;
    $uf = mb_strtoupper($uf, 'UTF-8');
    foreach (self::$regionais as $id => $regional) {
      if ($regional['uf'] === $uf) return $id;
    }
    return null;
  }
}