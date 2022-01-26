<?php

namespace AmoIntegrations\Enums;

class ERequestTypes
{
    public const GET = 'GET';
    public const POST = 'POST';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';

    public static function get($value){
		$reflectionClass = new \ReflectionClass(get_called_class());
		$constants = $reflectionClass->getConstants();
		if($key = array_search($value, $constants)){
			return $key;
		}
		return '';
	}
}