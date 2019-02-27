<?php
namespace zhise;

Class HttpClient
{
    public static function exec($method, $url, $obj = [])
    {
        $curl = curl_init();
        switch($method) {
          case 'GET':
            if(strrpos($url, "?") === FALSE) {
              $url .= '?' . http_build_query($obj);
            }
            break;
          case 'POST': 
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
            break;
          case 'PUT':
          case 'DELETE':
          default:
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($obj));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json')); 
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        
        $header = trim(substr($response, 0, $info['header_size']));
        $body = substr($response, $info['header_size']);
         
        return ['status' => $info['http_code'], 'header' => $header, 'data' => json_decode($body, true)];
    }

    public static function get($url, $obj = [])
    {
        return self::exec("GET", $url, $obj);
    }

    public static function post($url, $obj = [])
    {
        return self::exec("POST", $url, $obj);
    }

    public static function put($url, $obj = [])
    {
        return self::exec("PUT", $url, $obj);
    }

    public static function delete($url, $obj = [])
    {
        return self::exec("DELETE", $url, $obj);
    }
}