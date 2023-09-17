<?php

namespace I2br\Api;

abstract class ClienteHttp
{
  /** @var string Prefixo da URL da API */
  protected $baseUrl = 'http://exemplo.com.br';

  /**
   * Envia requisições HTTP para o sistema da API.
   * @param string $method Método da requisição (GET, POST, PUT, PATCH).
   * @param string $endpoint URL da requisição, omitindo a baseUrl, exemplo: '/v2/cob/'.
   * @param string|null $body Texto JSON para o corpo da requisição.
   * @return RespostaHttp Um objeto contendo informações da resposta HTTP.
   */
  protected function send(string $method, string $endpoint, string $body = null): RespostaHttp
  {
    if (substr($endpoint, 0, 1) !== "/") $endpoint = "/$endpoint";
    $url = $this->baseUrl . $endpoint;

    $headers = ['Cache-Control: no-cache'];
    if (in_array($method, ['POST','PUT','PATCH'])) $headers[] = 'Content-Type: application/json';

    //CONFIGURAÇÃO DO CURL
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 12,
      CURLOPT_TIMEOUT => 60,
      CURLOPT_CONNECTTIMEOUT => 8,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
    ]);

    if (in_array($method, ['POST','PUT','PATCH'])) {
      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    //EXECUTA O CURL
    $return = [];
    $return['response'] = curl_exec($curl);
    $return['error'] = curl_error($curl) ?: null;
    $return['code'] = !$return['error'] ? curl_getinfo($curl, CURLINFO_HTTP_CODE) : null;
    $return['type'] = !$return['error'] ? curl_getinfo($curl, CURLINFO_CONTENT_TYPE) : null;
    curl_close($curl);

    //Adapta erros disparados pelo servidor de API para ocupar o lugar do erro no retorno desta função
    if (!$return['error'] && $return['response'] && $return['code'] >= 400 && $return['type'] && substr($return['type'], 0, 16) === 'application/json') {
      $erroApi = json_decode($return['response'], true);
      if (!empty($erroApi['mensagem'])) $return['error'] = $erroApi['mensagem'];
    }

    return new RespostaHttp($url, $return['error'], $return['code'], $return['type'], $return['response']);
  }
}