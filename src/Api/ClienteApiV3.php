<?php

namespace I2br\Api;

class ClienteApiV3 extends ClienteHttp
{
  protected $baseUrl = 'https://api.conselho.net.br/v3';

  /**
   * Consulta a lista de cobranças contidas em uma ficha cadastral através do CPF/CNPJ dela.
   * @param int $regional Número da região.
   * @param string $cpfCnpj CPF ou CNPJ do cadastro.
   * @param bool $corrigir As cobranças também vão informar seu valor corrigido. Torna o carregamento mais lento!
   * @param bool $resolucao Usa o calculo corrigido da resolução (só é utilizado se o parametro anterior for true).
   * @return RespostaHttp
   */
  public function consultarCobrancas(int $regional, string $cpfCnpj, bool $corrigir = false, bool $resolucao = false): RespostaHttp
  {
    $corrigir = $corrigir ? 1 : 0;
    $resolucao = $resolucao ? 1 : 0;
    return $this->send('GET', "/financeiro/consultar-cobrancas?regional=$regional&cpf=$cpfCnpj&corrigido=$corrigir&resolucao=$resolucao");
  }
}