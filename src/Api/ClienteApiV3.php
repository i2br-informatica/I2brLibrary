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
   * Realiza o pagamento de cobranças do Cnet usando cartão de crédito através da plataforma CIELO.
   * @param int $regional Número da região
   * @param string $cartaoNumero Número do cartão de crédito.
   * @param string $cartaoNome Nome impresso no cartão de crédito.
   * @param string $cartaoValidade Validade do cartão em formato MM/YY ou MM/YYYY.
   * @param string $cartaoCvv Código de verificação do cartão de crédito.
   * @param array $idCobrancas Array de números inteiros que representam o ID da tabela financeiro de todas as cobranças pagas pela transação.
   * @param float $valor Valor total da transação.
   * @param int $parcelas Quantidade de parcelas a ser cobrada no cartão de crédito.
   * @param string|null $descricao Nome para constar na fatura do cartão de crédito, sem acentos, somente caixa alta.
   * @param bool $resolucao O valor da transação será validado se confere com a soma do valor corrigido das cobranças citadas, a correção deve usar resolução?
   * @return RespostaHttp
   */
  public function ccPagarCobrancas(int $regional, string $cartaoNumero, string $cartaoNome, string $cartaoValidade, string $cartaoCvv, array $idCobrancas, float $valor, int $parcelas = 1, string $descricao = null, bool $resolucao = true): RespostaHttp
  {
    $json = [
      'regional' => $regional,
      'descricao' => $descricao ?: 'CRECI',
      'cobrancas' => $idCobrancas,
      'parcelas' => $parcelas,
      'cartao' => [
        'numero' => $cartaoNumero,
        'nome' => $cartaoNome,
        'validade' => $cartaoValidade,
        'cvv' => $cartaoCvv
      ],
      'valor' => $valor,
      'resolucao' => $resolucao
    ];
    return $this->send('POST', '/financeiro/cartao/pagar', json_encode($json));
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