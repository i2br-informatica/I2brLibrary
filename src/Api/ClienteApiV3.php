<?php

namespace I2br\Api;

use Eliaslazcano\Helpers\ArrayHelper;

class ClienteApiV3 extends ClienteHttp
{
  protected $baseUrl = 'https://api.conselho.net.br/v3';

  /**
   * Consulta a lista de cobranças contidas em uma ficha cadastral através do CPF/CNPJ dela.
   * @param int $regional Número da região.
   * @param string $cpfCnpj CPF ou CNPJ do cadastro.
   * @param bool $corrigir As cobranças também vão informar seu valor corrigido. Torna o processo mais lento.
   * @param bool $resolucao Usa o calculo corrigido da resolução (só é utilizado se o parametro anterior for true).
   * @return RespostaHttp
   */
  public function consultarCobrancas(int $regional, string $cpfCnpj, bool $corrigir = false, bool $resolucao = false): RespostaHttp
  {
    $resposta = $this->send('GET', "/financeiro/consultar-cobrancas?regional=$regional&cpf=$cpfCnpj");
    if (!$corrigir || $resposta->error || $resposta->code !== 200 || !$resposta->isJson()) return $resposta;

    //Corrige o valor das cobrancas atrasadas usando outra API para calcular os valores novos
    $dados = $resposta->getJson();
    if (empty($dados->cobrancas)) return $resposta;
    $cobrancasVencidas = array_filter($dados->cobrancas, function ($i) { return $i->vencido && !$i->pago; });
    $itensVencidos = [];
    foreach ($cobrancasVencidas as $i) $itensVencidos = array_merge($itensVencidos, $i->itens);
    $idsVencidos = array_column($itensVencidos, 'id');

    $clienteLx4 = new ClienteLx4($regional);
    $respostaCorrecao = $clienteLx4->financeiroValorCorrigido($idsVencidos, $resolucao);
    if ($respostaCorrecao->error || $respostaCorrecao->code !== 200 || !$respostaCorrecao->isJson()) return $respostaCorrecao;

    $correcoes = $respostaCorrecao->getJson();
    $dados->cobrancas = array_map(function ($i) use ($correcoes) {
      $totalCorrigido = 0;
      foreach ($i->itens as $index => $cobrancaItem) {
        $itemCorrigido = ArrayHelper::find($correcoes->valores, function ($x) use ($cobrancaItem) { return $x->id === $cobrancaItem->id; });
        $i->itens[$index]->corrigido = $itemCorrigido ? $itemCorrigido->valor : $i->itens[$index]->valor;
        $totalCorrigido += $i->itens[$index]->corrigido;
      }
      $i->corrigido = $totalCorrigido;
      return $i;
    }, $dados->cobrancas);
    $resposta->response = json_encode($dados);

    return $resposta;
  }
}