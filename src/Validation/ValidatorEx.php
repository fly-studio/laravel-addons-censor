<?php

namespace Addons\Censor\Validation;

use BadMethodCallException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator as BaseValidator;

/**
 * 本Class主要是处理宽字符的长度、Fields检索等
 *
 */
class ValidatorEx extends BaseValidator {

    protected function validatePhone($attribute, $value, $parameters) {
        $pattern = '/[0-9\-\s]*/i';
        empty($parameters) && $parameters = ['zh-CN'];
        switch (strtolower($parameters[0])) {
            case 'us':
                break;
            case 'zh-cn': //cn
                //如：010-12345678、0912-1234567、(010)-12345678、(0912)1234567、(010)12345678、(0912)-1234567、01012345678、09121234567
                // /^(((\+86|086)[\-\s])?1[0-9]{2}[\-\s]?[0-9]{4}[\-\s]?[0-9]{4}|(^0\d{2}-?\d{8}$)|(^0\d{3}-?\d{7}$)|(^\(0\d{2}\)-?\d{8}$)|(^\(0\d{3}\)-?\d{7}$))$/
                $pattern = '/^((\+86|086)[\-\s])?1[0-9]{2}[0-9]{4}[0-9]{4}$/'; //只验证手机
                break;
        }
        return preg_match($pattern, $value);
    }

    protected function validateNotZero($attribute, $value, $parameters) {
        if (!is_numeric($value))
            return true;

        $value += 0;
        return !empty($value);
    }

    protected function validateIdCard($attribute, $value, $parameters) {
        $pattern = '/[0-9\-\s]*/i';
        empty($parameters) && $parameters = ['zh-CN'];

        switch (strtolower($parameters[0])) {
            case 'us':
                $pattern = '/^\d{6}-\d{2}-\d{4}$/';
                break;
            case 'zh-cn': //cn
                $pattern = '/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/';
                if(strlen($value) == 18) {
                    $idCardWi = [ 7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2 ]; //将前17位加权因子保存在数组里
                    $idCardY = [ 1, 0, 10, 9, 8, 7, 6, 5, 4, 3, 2 ]; //这是除以11后，可能产生的11位余数、验证码，也保存成数组
                    $idCardWiSum = 0; //用来保存前17位各自乖以加权因子后的总和
                    for($i = 0; $i < 17; $i++)
                        $idCardWiSum += $value[$i] * $idCardWi[$i];
                    $idCardMod = $idCardWiSum % 11;//计算出校验码所在数组的位置
                    $idCardLast = $value[17];//得到最后一位身份证号码

                    //如果等于2，则说明校验码是10，身份证号码最后一位应该是X
                    if($idCardMod == 2){
                        if(strtolower($idCardLast) != 'x')
                            return false;
                    } else {
                        //用计算出的验证码与最后一位身份证号码匹配，如果一致，说明通过，否则是无效的身份证号码
                        if($idCardLast != $idCardY[$idCardMod])
                            return false;
                    }
                }
                break;
        }

        return preg_match($pattern, $value);
    }

    /**
     * Handle dynamic calls to class methods.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters) {
        $rule = Str::snake(substr($method, 8));

        if (method_exists($this, $method)) { // call the private, protected
            return $this->$method(...$parameters);
        } else if (isset($this->extensions[$rule])) {
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

}
