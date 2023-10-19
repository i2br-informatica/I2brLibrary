<?php

namespace I2br\Api;

use Eliaslazcano\Helpers\Http\ClienteHttp;
use Eliaslazcano\Helpers\Http\RespostaHttp;
use I2br\Helpers\CreciHelper;

class ClienteLx4 extends ClienteHttp
{

  /**
   * @param int $regional Numero do regional
   */
  public function __construct(int $regional)
  {
    $uf = CreciHelper::idParaUf($regional);
    parent::__construct("https://www.creci$uf.conselho.net.br/api");
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

  /**
   * Realiza o pagamento de cobranças do Cnet usando cartão de crédito através da plataforma CIELO.
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
  public function pagamentoCartaoCielo(string $cartaoNumero, string $cartaoNome, string $cartaoValidade, string $cartaoCvv, array $idCobrancas, float $valor, int $parcelas = 1, string $descricao = null, bool $resolucao = true): RespostaHttp
  {
    $json = [
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
    return $this->send('POST', '/pagamento_cartao_cielo.php', json_encode($json));
  }

  /**
   * Atualiza uma cobranca vencida, gerando uma nova emissao com valor corrigido.
   * @param array $cobrancasIds
   * @return RespostaHttp
   */
  public function atualizarCobrancas(array $cobrancasIds)
  {
    return $this->send('POST', '/atualizar_cobranca.php', json_encode($cobrancasIds));
  }
}