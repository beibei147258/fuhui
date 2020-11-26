<?php
class CURLDate{
    public function getDate($prifex){
        $headerArray = array("Content-type:application/json;", "Accept:application/json");
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $prifex;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArray);
        $output = curl_exec($ch);
        curl_close($ch);
    }
}
