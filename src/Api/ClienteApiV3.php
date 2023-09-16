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

  /**
   * Estorna a transação do cartão de crédito. Se for bem sucedida o Cnet retornará a cobrança original com o estado de pagamento pendente.
   * @param int $regional Número do regional.
   * @param string $nsu NSU da transação.
   * @param string $nrAutorizacao Número da Autorização da transação.
   * @return RespostaHttp
   */
  public function ccEstornarTransacao(int $regional, string $nsu, string $nrAutorizacao): RespostaHttp
  {
    $json = [
      "regional" => $regional,
      "nsu" => $nsu,
      "autorizacao" => $nrAutorizacao,
    ];
    return $this->send('POST', '/financeiro/cartao/estornar', json_encode($json));
  }
}