<?php

namespace I2br\Cielo;

class Api
{
  private $merchantId;
  private $merchantKey;
  private $baseUrlTransacoes;
  private $baseUrlConsultas;

  private $cartaoNumero;
  private $cartaoNomeImpresso;
  private $cartaoValidade;
  private $cartaoCvv;

  /**
   * @param string $merchantId Identifica o recebedor. Tamanho 36.
   * @param string $merchantKey Chave de uso da API. Tamanho 40.
   * @param bool $sandbox Opera no modo homologação para realizar simulações.
   */
  public function __construct(string $merchantId, string $merchantKey, bool $sandbox = false)
  {
    $this->merchantId = $merchantId;
    $this->merchantKey = $merchantKey;
    $this->baseUrlTransacoes = $sandbox ? 'https://apisandbox.cieloecommerce.cielo.com.br' : 'https://api.cieloecommerce.cielo.com.br';
    $this->baseUrlConsultas = $sandbox ? 'https://apiquerysandbox.cieloecommerce.cielo.com.br' : 'https://apiquery.cieloecommerce.cielo.com.br';
  }

  /**
   * Envia requisições HTTP para o sistema da API.
   * @param string $method - Método da requisição (GET, POST, PUT, PATCH).
   * @param string $endpoint - URL da requisição, omitindo a baseUrl, exemplo: '/v2/cob/'.
   * @param string|null $body - Texto JSON para o corpo da requisição.
   * @param bool $baseUrlConsultas - Ao inves de usar o prefixo de URL para as APIs de transacoes, usa a URL da API de consultas.
   * @return array - 'error'(string),'code'(int),'response'(any),'type'(string).
   */
  private function send(string $method, string $endpoint, string $body = null, bool $baseUrlConsultas = false): array
  {
    $endpoint = $baseUrlConsultas ? ($this->baseUrlConsultas.$endpoint) : ($this->baseUrlTransacoes.$endpoint);
    $headers = [
      'Cache-Control: no-cache',
      'Content-Type: application/json',
      'MerchantId: '.$this->merchantId,
      'MerchantKey: '.$this->merchantKey,
    ];

    //CONFIGURAÇÃO DO CURL
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $endpoint,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => $headers
    ]);

    switch ($method) {
      case 'POST':
      case 'PUT':
      case 'PATCH':
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        break;
    }

    //EXECUTA O CURL
    $return = [];
    $return['response'] = curl_exec($curl);
    $return['error'] = curl_error($curl) ?: null;
    $return['type'] = !$return['error'] ? curl_getinfo($curl, CURLINFO_CONTENT_TYPE) : null;
    $return['code'] = !$return['error'] ? curl_getinfo($curl, CURLINFO_HTTP_CODE) : null;
    curl_close($curl);

    $isJson = $return['type'] && substr($return['type'], 0, 16) === 'application/json';
    if ($isJson && $return['response']) $return['response'] = json_decode($return['response'], true);
    return $return;
  }

  /**
   * Verifica se uma string corresponde a uma expressão regular.
   * @param string $expressao - A expressão regular.
   * @param string $string - A string a ser verificada.
   * @return bool
   */
  private function testarExpressaoRegular(string $expressao, string $string): bool
  {
    return preg_match($expressao, $string) > 0;
  }

  /**
   * Descobre a bandeira do cartão através do número dele.
   * @param string $numberoCartao - Número do cartão.
   * @return string|null
   */
  private function obterBandeira(string $numberoCartao): ?string
  {
    if ($this->testarExpressaoRegular('/^3[47]\d{13}$/', $numberoCartao)) {
      return 'Amex';
    } elseif ($this->testarExpressaoRegular('/^3(?:0[0-5]|[68]\d)\d{11}$/', $numberoCartao)) {
      return 'Diners';
    } elseif ($this->testarExpressaoRegular('/^4011(78|79)|^43(1274|8935)|^45(1416|7393|763(1|2))|^50(4175|6699|67[0-6][0-9]|677[0-8]|9[0-8][0-9]{2}|99[0-8][0-9]|999[0-9])|^627780|^63(6297|6368|6369)|^65(0(0(3([1-3]|[5-9])|4([0-9])|5[0-1])|4(0[5-9]|[1-3][0-9]|8[5-9]|9[0-9])|5([0-2][0-9]|3[0-8]|4[1-9]|[5-8][0-9]|9[0-8])|7(0[0-9]|1[0-8]|2[0-7])|9(0[1-9]|[1-6][0-9]|7[0-8]))|16(5[2-9]|[6-7][0-9])|50(0[0-9]|1[0-9]|2[1-9]|[3-4][0-9]|5[0-8]))/', $numberoCartao)) {
      return 'Elo';
    } elseif ($this->testarExpressaoRegular('/^(606282\d{10}(\d{3})?)|(3841\d{15})$/', $numberoCartao)) {
      return 'Hipercard';
    } elseif ($this->testarExpressaoRegular('/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/', $numberoCartao)) {
      return 'Master';
    } elseif ($this->testarExpressaoRegular('/^4\d{12}(?:\d{3})?$/', $numberoCartao)) {
      return 'Visa';
    } else {
      return null;
    }
  }

  /**
   * Aplica os dados do cartão, procedimento obrigatório para usar funções que criam transações com cartão de crédito/débito.
   * @param $numero - Número do cartão sem espaços.
   * @param $nomeImpresso - Nome impresso no cartão. Não aceita caracteres especiais ou acentuação.
   * @param $validade - Data de validade impressa no cartão. Ex. MM/AAAA.
   * @param $cvv - Código de segurança impresso no verso do cartão.
   * @return void
   */
  public function definirCartao($numero, $nomeImpresso, $validade, $cvv)
  {
    $this->cartaoNumero = $numero;
    $this->cartaoNomeImpresso = $nomeImpresso;
    $this->cartaoValidade = $validade;
    $this->cartaoCvv = $cvv;
  }

  /**
   * Cria uma transação no cartão de crédito.
   * @param string $merchantOrderId - É um identificador que você pode atribuir a transação. Até 50 digitos.
   * @param string $descricao - O complemento do nome da loja que aparecerá na fatura do cartão. Não permite caracteres especiais. Limite 13.
   * @param int $valor - Quantia monetaria da cobrança em centavos. Ex. 17 Reais = 1700 Centavos.
   * @param int $parcelas - Número de Parcelas.
   * @param bool $capturaAutomatica - A captura da transação pode ser automática ou posterior. Para captura automática, envie true.
   * @return array O retorno da requisição HTTP em array com as chaves: 'error'(string),'code'(int),'response'(any),'type'(string).
   */
  public function criarTransacaoCredito(string $merchantOrderId, string $descricao, int $valor, int $parcelas = 1, bool $capturaAutomatica = true): array
  {
    if (strlen($merchantOrderId) > 50) return ['error' => 'O identificador atribuído a transação está muito longo. O limite é de 50 digitos.'];
    if (strlen($descricao) > 13) return ['error' => 'A descrição está acima do comprimento permitido de 13 caracteres.'];
    if (!in_array(strlen($this->cartaoValidade), [5,7])) return ['error' => 'A validade do cartão precisa ser informada no formato MM/AAAA.'];
    if (strlen($this->cartaoValidade) === 5) $this->cartaoValidade = substr($this->cartaoValidade,0,3).'20'.substr($this->cartaoValidade,3);
    if ($valor <= 0) return ['error' => 'Não é possível realizar uma transação com valor zero.'];

    $bandeira = $this->obterBandeira($this->cartaoNumero);
    if (!$bandeira) return ['error' => 'A bandeira do cartão informado não é aceita no momento.'];
    $bandeirasCompativeis = ['Visa','Master','Amex','Elo','Aura','JCB','Diners','Discover','Hipercard','Hiper'];
    if (!in_array($bandeira, $bandeirasCompativeis)) return ['error' => "A bandeira do cartão informado não é aceita no momento. ($bandeira)"];

    return $this->send('POST', '/1/sales/', json_encode([
      'MerchantOrderId' => $merchantOrderId,
      'Customer' => [
        'Name' => $this->cartaoNomeImpresso
      ],
      'Payment' => [
        'Type' => 'CreditCard',
        'Capture' => $capturaAutomatica,
        'Amount' => $valor,
        'Installments' => $parcelas,
        'SoftDescriptor' => $descricao,
        'CreditCard' => [
          'CardNumber' => $this->cartaoNumero,
          'Holder' => $this->cartaoNomeImpresso,
          'ExpirationDate' => $this->cartaoValidade,
          'SecurityCode' => $this->cartaoCvv,
          'Brand' => $bandeira,
        ]
      ]
    ]));
  }

  /**
   * Consultar dados de uma venda de cartão de crédito através do PaymentId.
   * @param string $paymentId - O PaymentId foi fornecido pela API da Cielo quando criou a transação.
   * @return array
   */
  public function consulta_porPaymentId(string $paymentId): array
  {
    return $this->send('GET', "/1/sales/$paymentId", null, true);
  }

  /**
   * Consultar dados de uma venda de cartão de crédito através do número de referência único da transação na adquirente (TId).
   * @param string $TId - O TID foi fornecido pela API da Cielo quando criou a transação.
   * @return array
   */
  public function consulta_porTId(string $TId): array
  {
    return $this->send('GET', "/1/sales/acquirerTid/$TId", null, true);
  }

  /**
   * Obtem o PaymentId da transação através do MerchantOrderId que é o identificador que atribuiu quando criou a transação.
   * @param string $merchantOrderId - O MerchantOrderId é um identificador atribuído por você quando criou a transação.
   * @return array
   */
  public function consulta_porMerchantOrderId(string $merchantOrderId): array
  {
    return $this->send('GET', "/1/sales?merchantOrderId=$merchantOrderId", null, true);
  }

  /**
   * Cancela uma transação de crédito ou débito permitindo estornar o valor da compra.
   * Para as solicitações de cancelamento da mesma transação, é necessário aguardar um período de 5 segundos entre uma solicitação e outra.
   * É necessário que o estabelecimento possua saldo suficiente na transação e na agenda.
   * @param string $merchantOrderId - O MerchantOrderId é um identificador atribuído por você quando criou a transação.
   * @param int $valor - Valor do pedido em centavos. Ex. 17 Reais = 1700 Centavos.
   * @return array
   */
  public function cancelamentoTotal_porMerchantOrderId(string $merchantOrderId, int $valor): array
  {
    return $this->send('PUT', "/1/sales/OrderId/$merchantOrderId/void?amount=$valor");
  }

  /**
   * Cancela uma transação de crédito ou débito permitindo estornar o valor da compra.
   * Para as solicitações de cancelamento da mesma transação, é necessário aguardar um período de 5 segundos entre uma solicitação e outra.
   * É necessário que o estabelecimento possua saldo suficiente na transação e na agenda.
   * @param string $paymentId - O PaymentId foi fornecido pela API da Cielo quando criou a transação.
   * @param int $valor - Valor do pedido em centavos. Ex. 17 Reais = 1700 Centavos.
   * @return array
   */
  public function cancelamentoTotal_porPaymentId(string $paymentId, int $valor): array
  {
    return $this->send('PUT', "/1/sales/$paymentId/void?amount=$valor");
  }

  /**
   * O cancelamento parcial é o ato de cancelar um valor menor do que o valor total que foi autorizado e capturado.
   * Esse modelo de cancelamento pode ocorrer inúmeras vezes, até que o valor total da transação seja cancelado.
   * @param string $paymentId - O PaymentId foi fornecido pela API da Cielo quando criou a transação.
   * @param int $valor - Valor do pedido em centavos. Ex. 17 Reais = 1700 Centavos.
   * @return array
   */
  public function cancelamentoParcial(string $paymentId, int $valor): array
  {
    return $this->send('PUT', "/1/sales/$paymentId/void?amount=$valor");
  }

  /**
   * Realiza a 'Captura posterior' de uma transação.
   * A captura é procedimento exclusivo para transações de cartões de crédito, ela executa a cobrança do cartão.
   * Há dois tipos de captura:
   * - Captura automática: é feita na mesma requisição que cria a transação.
   * - Captura posterior: é feita depois de ter criado a transação. O cartão foi validado mas a cobrança ainda não realizada.
   * O prazo para realizar a captura da transação é de até 15 dias.
   * @param $paymentId - O PaymentId foi fornecido pela API da Cielo quando criou a transação.
   * @return array
   */
  public function capturaTransacao($paymentId): array
  {
    return $this->send('PUT', "/1/sales/$paymentId/capture");
  }

  /**
   * Descubra o valor que será cobrado em cada parcela para pagamento parcelado. Retorna um array com o valor de cada parcela.
   * @param int $valorTotal - Valor total do pagamento em centavos. Ex. 17 Reais = 1700 Centavos.
   * @param int $numParcelas - Quantidade de parcelas.
   * @return array - Inicia no indice 0, contem o valor da primeira ate a ultima parcela respectivamente. (Em centavos, ex. 17 Reais = 1700 Centavos)
   */
  public static function estimarValorDasParcelas(int $valorTotal, int $numParcelas): array
  {
    // Calcula o valor da parcela normal (sem considerar o resto)
    $valorParcelaNormal = floor($valorTotal / $numParcelas);

    // Calcula o valor do resto (diferença entre o valor total e a soma das parcelas normais)
    $resto = $valorTotal % $numParcelas;

    // Inicializa um array para armazenar as parcelas
    $parcelas = array_fill(0, $numParcelas, $valorParcelaNormal);

    // Distribui o valor do resto entre as parcelas, centavo por centavo.
    if ($resto > 0) {
      for ($i = 0; $i < $resto; $i++) {
        $parcelas[$i] += 1;
      }
    }

    return $parcelas;
  }
}