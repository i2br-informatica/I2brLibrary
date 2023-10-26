<?php

namespace I2br\Api;

use Eliaslazcano\Helpers\Http\ClienteHttp;
use Eliaslazcano\Helpers\Http\RespostaHttp;

class ClienteApiV3 extends ClienteHttp
{
  public $baseUrl = 'https://api.conselho.net.br/v3';

  /**
   * Consulta a lista de cobranças contidas em uma ficha cadastral através do CPF/CNPJ dela.
   * @deprecated Função movida para a classe ClienteLx4.
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
   * Envia a segunda-via do boleto por email.
   * @param int $regional Número da região.
   * @param string $email Endereço de email do destinatário.
   * @param int $idFinanceiro ID da cobrança na tabela financeiro.
   * @param string $baseUrl Url para o arquivo PHP que gera a tela do boleto. Ex: "https://www.crecirj.conselho.net.br/ver_boleto_aberto.php".
   * @return RespostaHttp
   */
  public function enviarBoletoEmail(int $regional, string $email, int $idFinanceiro, string $baseUrl): RespostaHttp
  {
    $json = json_encode(["regional" => $regional, "idFinanceiro" => $idFinanceiro, "url" => $baseUrl, "email" => $email]);
    return $this->send('POST', '/financeiro/enviar-boleto', $json);
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