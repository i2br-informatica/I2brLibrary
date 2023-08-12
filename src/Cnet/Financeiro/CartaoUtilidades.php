<?php

namespace I2br\Cnet\Financeiro;

class CartaoUtilidades
{
  /**
   * Retorna o limite de parcelas que pode ser utilizado para pagamento de uma cobrança no cartão de crédito.
   * Esse limite não se aplica a pagamento combinado com outras cobranças, ele considera como se fosse pagamento individual desta cobrança.
   * @param int $idTipoReceita - Identifica o tipo de receita utilizado na cobrança.
   * @return int - Limite de parcelas.
   */
  public static function calcularLimiteParcelas(int $idTipoReceita): int
  {
    if (in_array($idTipoReceita, [22,26,30])) return 5;
    else return 3;
  }

  /**
   * Retorna o limite de parcelas que pode ser utilizado para pagamento da cobrança no cartão de crédito caso ela seja
   * paga combinada com outras cobranças.
   * @param int $idTipoReceita - Identifica o tipo de receita utilizado na cobrança.
   * @return int - Limite de parcelas.
   */
  public static function calcularLimiteParcelas_noCombo(int $idTipoReceita): int
  {
    if (in_array($idTipoReceita, [22,26,30])) return 12;
    else return 3;
  }

  /**
   * Esta função descobre o limite de parcelas que pode ser utilizado para pagamento respeitando duas restrições:
   * Valor mínimo por parcela, limite máximo de parcelas.
   * @param float $valorTotal - Valor total da cobrança atual.
   * @param float $valorMinParcela - Valor mínimo da parcela.
   * @param int $maxParcelas - Máximo de parcelas.
   * @return int
   */
  public static function calcularLimiteParcelas_comValorMinimo(float $valorTotal, float $valorMinParcela, int $maxParcelas): int
  {
    return min($maxParcelas, floor($valorTotal / $valorMinParcela));
  }
}