<?php

namespace I2br\Api;

use I2br\Helpers\CreciHelper;

class ClienteLx4 extends ClienteHttp
{
  protected $baseUrl;

  /**
   * @param int $regional Numero do regional
   */
  public function __construct(int $regional)
  {
    $uf = CreciHelper::idParaUf($regional);
    $this->baseUrl = "https://www.creci$uf.conselho.net.br/api";
  }

  /**
   * Obtem o valor corrigido das cobranças informadas.
   * @param array $idsFinanceiro ID das cobranças em array<int>, de acordo com a tabela 'financeiro'.
   * @param bool $resolucao Usa o calculo corrigido da resolução.
   * @return RespostaHttp
   */
  public function financeiroValorCorrigido(array $idsFinanceiro, bool $resolucao = false): RespostaHttp
  {
    $json = ['id' => $idsFinanceiro, 'resolucao' => $resolucao];
    return $this->send('POST', '/debito_valor_corrigido.php', json_encode($json));
  }
}