<?php
/**
 * Algoritmos do cotidiano que acessam e manipulam o financeiro do Conselho.NET, disponíveis em funções estáticas.
 * Códigos que utilizam conexão com o banco de dados ficam no objeto, do contrário ficam na classe (static).
 */

namespace I2br\Cnet\Financeiro;

use I2br\Database\DbController;
use PDO;

class FinanceiroUtilidades
{
  /** @var DbController - Guarda a conexão com o banco de dados do Conselho.NET */
  private $dbCnet;

  /**
   * @param PDO|null $conn - Conexão com a base de dados em formato de instância PDO.
   */
  public function __construct(PDO $conn)
  {
    $this->dbCnet = new DbController($conn);
  }

  /**
   * Cria um debito identico a todas as propriedades do debito de origem informado, mas com um novo ID.
   * Até mesmo os dados que estão vinculados em 'financeiro_agrupamento' é clonado com o novo registro.
   * Nota: se o débito original estiver vinculado a um boleto agrupado (tabela 'financeiro_agrupamento_boleto'), o clone
   * também fará parte do mesmo agrupamento (o mesmo boleto agrupado).
   * @param int $idFinanceiro - ID do debito de origem.
   * @param bool $preservarDataCadastro - Mantem a mesma DataCadastro e HoraCadastro do débito original, caso contrario usa a data e hora atual.
   * @param bool $clonarAgrupamentoItens - Também clona os dados de 'financeiro_agrupamento' que estão vinculados ao débito original, vinculando-os ao débito clonado.
   * @return int|null - Retorna o ID do novo debito. False em caso de erro.
   */
  public function debito_Clonar(int $idFinanceiro, bool $preservarDataCadastro = false, bool $clonarAgrupamentoItens = true): ?int
  {
    $colunas = $this->dbCnet->query('SHOW COLUMNS FROM financeiro'); //Obtem os dados das colunas da tabela financeiro
    $colunas = array_map(function ($item) { return $item['Field']; }, $colunas); //Remove dados desnecessarios, so quero o nome das colunas
    $colunas = array_filter($colunas, function ($item) { return $item !== 'id'; }); //Removo a coluna 'id' da listagem
    $colunas_str = implode(', ', $colunas); //Junto o nome das colunas em uma string separados por virgula

    //Clonando o debito
    $sql = "INSERT INTO financeiro ($colunas_str) SELECT $colunas_str FROM financeiro WHERE id = :id";
    $novo_id = $this->dbCnet->insert($sql, array(':id' => $idFinanceiro));
    if (!$novo_id) return null;
    if (!$preservarDataCadastro) {
      $sql = 'UPDATE financeiro SET DataCadastro = CURRENT_DATE AND HoraCadastro = CURRENT_TIME WHERE id = :id';
      $this->dbCnet->update($sql, array(':id' => $novo_id));
    }

    if ($clonarAgrupamentoItens) {
      //O debito foi clonado, mas agora temos que clonar os detalhes dos custos que estao sendo cobrados.
      $sql = 'SELECT id FROM financeiro_agrupamento WHERE idFinanceiro = :id';
      $custos = $this->dbCnet->query($sql, array(':id' => $idFinanceiro));
      if ($custos) {
        $colunas = $this->dbCnet->query('SHOW COLUMNS FROM financeiro_agrupamento');
        $colunas = array_map(function ($item) { return $item['Field']; }, $colunas);
        $colunas = array_filter($colunas, function ($item) { return $item !== 'id'; });
        $colunas_str = implode(', ', $colunas);
        $sql = "INSERT INTO financeiro_agrupamento ($colunas_str) SELECT $colunas_str FROM financeiro_agrupamento WHERE id = :id";

        if ($preservarDataCadastro) $sql_update = 'UPDATE financeiro_agrupamento SET idFinanceiro = :idfinanceiro WHERE id = :id';
        else $sql_update = 'UPDATE financeiro_agrupamento SET idFinanceiro = :idfinanceiro, DataCadastro = CURRENT_DATE, HoraCadastro = CURRENT_TIME WHERE id = :id';

        foreach ($custos as $custo) {
          $novocusto_id = $this->dbCnet->insert($sql, array(':id' => $custo['id'])); //Clonando o custo
          $this->dbCnet->update($sql_update, array(
            ':idfinanceiro' => $novo_id,
            ':id' => $novocusto_id
          )); //Apontando o novo custo para o boleto clonado
        }
      }
    }

    return (int)$novo_id;
  }

  /**
   * Apaga um debito da base de dados. (Nao será deletado de verdade, assumirá status 4 para se tornar oculto).
   * @param int $idFinanceiro - ID do debito na tabela 'financeiro'.
   * @param int|null $idUsuario - ID do usuario que esta realizando esta operacao.
   * @param string|null $observacao - Texto de observação, se o débito já possuir texto, será incrementado no final do texto atual.
   * @return bool - Indica o sucesso da operacao
   */
  public function debito_Apagar(int $idFinanceiro, ?int $idUsuario = null, ?string $observacao = null): bool
  {
    $sql = "UPDATE financeiro SET Status = 4, idQuemDeletou = :usuario, DataDelete = CURRENT_DATE, HoraDelete = CURRENT_TIME, Observacoes = IF(Observacoes IS NOT NULL AND LENGTH(Observacoes) > 0, CONCAT(Observacoes,' | ', IF(:observacao IS NOT NULL, :observacao, '')), IF(:observacao IS NOT NULL, :observacao, null)) WHERE id = :id";
    $sucesso = $this->dbCnet->update($sql, array(
      ':usuario' => $idUsuario,
      ':observacao' => $observacao,
      ':id' => $idFinanceiro
    ));
    return $sucesso > 0;
  }
}