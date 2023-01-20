<?php
/**
 * @author Elias Lazcano Castro Neto
 */

namespace I2br\Helpers;

use Eliaslazcano\Helpers\HttpHelper;
use Eliaslazcano\Helpers\StringHelper;

class CnetHelper
{
  static $SITUACOES_CADASTRAIS = array(
    ['id' => 1,  'color' => '#008000', 'text' => 'ATIVO'],
    ['id' => 2,  'color' => '#FF0000', 'text' => 'CANCELADO'],
    ['id' => 3,  'color' => '#FF0000', 'text' => 'CANCELADO PUNITIVAMENTE'],
    ['id' => 4,  'color' => '#FF0000', 'text' => 'CANCELADO POR DÉBITO'],
    ['id' => 5,  'color' => '#FF0000', 'text' => 'SUSPENSO'],
    ['id' => 6,  'color' => '#FF0000', 'text' => 'SUSPENSO PUNITIVAMENTE'],
    ['id' => 7,  'color' => '#0000FF', 'text' => 'TRANSFERIDO'],
    ['id' => 8,  'color' => '#FF0000', 'text' => 'FALECIDO'],
    ['id' => 9,  'color' => '#FF0000', 'text' => 'CANCELADO A PEDIDO R.T.'],
    ['id' => 10, 'color' => '#FF0000', 'text' => 'SUSPENSO POR DÉBITO'],
    ['id' => 11, 'color' => '#FF0000', 'text' => 'CANCELADO POR ATO ADMINISTRATIVO'],
    ['id' => 12, 'color' => '#FF0000', 'text' => 'ARQUIVADO'],
  );

  /**
   * A partir de um ID de Situacao Cadastral obtem o texto representativo dela.
   * @param int $situacao Identificador da situacao cadastral.
   * @return string
   */
  static function toString_SituacaoCadastral($situacao)
  {
    $x = array_filter(self::$SITUACOES_CADASTRAIS, function ($i) use ($situacao) {
      return $i['id'] == $situacao;
    });
    $x = array_values($x);
    return $x ? $x[0]['text'] : 'SITUAÇÃO CADASTRAL INDEFINIDA';
  }

  /**
   * A partir de um ID de Tipo de Telefone obtem o texto representativo dele.
   * @param int $tipo Identificador do tipo de telefone.
   * @return string
   */
  static function toString_TelefoneTipo($tipo)
  {
    if ($tipo === 1) return 'Residencial';
    elseif ($tipo === 2) return 'Comercial';
    elseif ($tipo === 3) return 'Fax Residencial';
    elseif ($tipo === 4) return 'Fax Comercial';
    elseif ($tipo === 5) return 'Celular';
    else return '';
  }

  /**
   * Converte o numero que representa a formacao do corretor (conhecida na ficha como 'idEscolaHabilitacao') para sua representacao em texto.
   * @param int $idHabilitacao Valor de 'idEscolaHabilitacao' na ficha cadastral.
   * @return string
   */
  static function toString_Formacao($idHabilitacao) {
    switch ($idHabilitacao) {
      case 1:
        return 'CIÊNCIAS IMOBILIÁRIAS';
      case 2:
        return 'CORRETOR DE IMÓVEIS';
      case 3:
        return 'GESTÃO DE NEGÓCIOS IMOBILIÁRIOS';
      case 4:
        return 'SUPERIOR DE FORMAÇÃO ESP CIÊNCIAS IMOBILIÁRIAS';
      case 5:
        return 'TÉCNICO EM TRANSAÇÕES IMOBILIÁRIAS';
      case 6:
        return 'TECNÓLOGO EM TRANSAÇÕES IMOBILIÁRIAS';
      case 7:
        return 'SUPERIOR DE FORMAÇÃO ESP GESTÃO IMOBILIÁRIA';
      case 8:
        return 'GESTÃO IMOBILIÁRIA';
      case 9:
        return 'SUPERIOR DE TECNOLOGIA EM NEGÓCIOS IMOBILIÁRIOS';
      default:
        return '';
    }
  }

  /**
   * Obtem a imagem da foto de um cadastro.
   * @param int $regional Numero da regiao do conselho.
   * @param string $cpf CPF do cadastro da foto.
   * @return array<string,mixed>|null Array no formato ['type' => string, 'size' => int, 'content' => string]. null se nao houver.
   */
  static function getCadastroGeral_foto($regional, $cpf)
  {
    $uf = CreciHelper::idParaUf($regional);
    $cpf = StringHelper::extractNumbers($cpf);
    $endpoint = "https://creci$uf.conselho.net.br/images/cadastro/fotos/api.php?cpf=$cpf";
    $response = HttpHelper::curlGet($endpoint);
    if (!$response || $response['code'] !== 200) return null;
    return array('type' => $response['type'], 'size' => $response['size'], 'content' => $response['body']);
  }

  /**
   * Obtem a imagem da assinatura de um cadastro.
   * @param int $regional Numero da regiao do conselho.
   * @param string $cpf CPF do cadastro da assinatura.
   * @return array<string,mixed>|null Array no formato ['type' => string, 'size' => int, 'content' => string]. null se nao houver.
   */
  static function getCadastroGeral_assinatura($regional, $cpf)
  {
    $uf = CreciHelper::idParaUf($regional);
    $cpf = StringHelper::extractNumbers($cpf);
    $endpoint = "https://www.creci$uf.conselho.net.br/images/cadastro/assinaturas/api.php?cpf=$cpf";
    $response = HttpHelper::curlGet($endpoint);
    if (!$response || $response['code'] !== 200) return null;
    return array('type' => $response['type'], 'size' => $response['size'], 'content' => $response['body']);
  }

  /**
   * Obtem a assinatura do usuario em string binária.
   * @param int $regional Numero do regional.
   * @param int $id ID do usuario do Conselho.NET.
   * @param bool $generico Se nao encontrar nenhuma imagem, sera retornada uma generica.
   * @param bool $base64 True fara o retorno ser uma string base64.
   * @return array|string Array no formato ['type' => string, 'size' => int, 'content' => string]. null se nao houver.
   */
  static function getUsuario_assinatura($regional, $id, $generico = false, $base64 = false)
  {
    $uf = CreciHelper::idParaUf($regional);
    $endpoint = "https://www.creci$uf.conselho.net.br/api/assinatura_usuario.php?id=$id";
    if ($generico) $endpoint .= '&generico=1';
    $response = HttpHelper::curlGet($endpoint);
    if (!$response || $response['code'] !== 200) return null;
    if ($base64) return 'data:' . $response['type'] . ';base64,' . base64_encode($response['body']);
    return array('type' => $response['type'], 'size' => $response['size'], 'content' => $response['body']);
  }

  /**
   * Pega o valor corrigido de um debito de acordo com seu ID no ConselhoNET.
   * @param int $regional Numero da regiao.
   * @param int $idFinanceiro ID do debito.
   * @param bool $resolucao Utilizar a regra da resolucao para calcular o valor final.
   * @return float|null
   */
  static function getDebitoCorrigido($regional, $idFinanceiro, $resolucao = false)
  {
    $uf = CreciHelper::idParaUf($regional);
    $endpoint = "https://www.creci$uf.conselho.net.br/api/debito_valor_corrigido.php";
    $response = HttpHelper::curlPost($endpoint, json_encode(['id' => $idFinanceiro, 'resolucao' => $resolucao]));
    if (!$response || $response['code'] !== 200) return null;
    return $response['body']->valores;
  }

  /**
   * Pega o valor corrigido dos debitos atraves de seus IDs no ConselhoNET.
   * @param int $regional Numero da regiao.
   * @param array<int> $idsFinanceiro IDs dos debitos.
   * @param bool $resolucao Utilizar a regra da resolucao para calcular o valor final.
   * @return array|null Array de objetos, os objetos possuem propriedades id (int) e valor (float).
   */
  static function getDebitosCorrigidos($regional, $idsFinanceiro, $resolucao = false)
  {
    $uf = CreciHelper::idParaUf($regional);
    $endpoint = "https://www.creci$uf.conselho.net.br/api/debito_valor_corrigido.php";
    $response = HttpHelper::curlPost($endpoint, json_encode(['id' => $idsFinanceiro, 'resolucao' => $resolucao]));
    if (!$response || $response['code'] !== 200) return null;
    return $response['body']->valores;
  }
}