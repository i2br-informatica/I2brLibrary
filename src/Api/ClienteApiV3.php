<?php

namespace I2br\Api;

class ClienteApiV3 extends ClienteHttp
{
  protected $baseUrl = 'https://api.conselho.net.br/v3';

  /**
   * Consulta a lista de cobranças contidas em uma ficha cadastral através do CPF/CNPJ dela.
   * @param int $regional Número da região.
   * @param string $cpfCnpj CPF ou CNPJ do cadastro.
   * @return RespostaHttp
   */
  public function consultarCobrancas(int $regional, string $cpfCnpj): RespostaHttp
  {
    return $this->send('GET', "/financeiro/consultar-cobrancas?regional=$regional&cpf=$cpfCnpj");
  }
}