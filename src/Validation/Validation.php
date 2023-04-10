<?php

namespace Addons\Censor\Validation;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationRuleParser;

class Validation {

    protected array|string|null $rawRules;
    protected string $attribute;
    protected ?string $name;
    protected ?array $computedRules;
    protected ?array $messages;
    protected ?array $originalRules;

    public function __construct(string $attribute, ?string $name, array|string|null $rawRules, ?array $messages = null)
    {
        $this->attribute = $attribute;
        $this->name = $name;
        $this->rawRules = $rawRules;
        $this->messages = $messages;
    }

    public function rawRules(): array|string|null {
        return $this->rawRules;
    }

    public function attribute(): string {
        return $this->attribute;
    }

    public function name(): string {
        return $this->name;
    }

    public function messages(): ?array {
        return $this->messages;
    }

    public function originalRules(): ?array
    {
        return $this->originalRules;
    }

    public function computedRules(): ?array
    {
        return $this->computedRules;
    }

    public function ruleParameters(string $ruleName): null|array|object
    {
        $ruleName = Str::studly($ruleName);
        return $this->computedRules[$ruleName] ?? null;
    }

    public function jsRules()
    {
        $rules = [];
        // <input name="rule[]" />
        $attribute = $this->isArray() ? $this->attribute. '[]' : $this->attribute;

        $rules[$attribute] = [];
        foreach($this->computedRules() as $ruleName => $parameters)
        {
            if (empty($ruleName))
                continue;
            $parameters = empty($parameters) || is_object($parameters)
                ? true
                : (count($parameters) == 1 ? $parameters[0] : $parameters);

            $ruleName = strtolower($ruleName);

            switch ($ruleName) { // 1
                case 'alpha':
                    $ruleName = 'regex';
                    $parameters = '/^[\w]+$/i';
                    break;
                case 'alphadash':
                    $ruleName = 'regex';
                    $parameters = '/^[\w_-]+$/i';
                    break;
                case 'alpha_num':
                    $ruleName = 'regex';
                    $parameters = '/^[\w\d]+$/i';
                    break;
                case 'ansi':
                    $parameters = $parameters === true ? 2 : floatval($parameters);
                    break;
                case 'notin':
                    $ruleName = 'regex';
                    $parameters = '(?!('.implode('|', array_map('preg_quote', $parameters)).'))';
                    break;
                case 'in':
                    $ruleName = 'regex';
                    $parameters = '('.implode('|', array_map('preg_quote', $parameters)).')';
                    break;
                case 'digits':
                    if (!empty($parameters))
                    {
                        $rules[$attribute] += ['rangelength' => [floatval($parameters), floatval($parameters)]];
                        $parameters = true;
                    }
                    break;
                case 'digitsbetween':
                    $rules[$attribute] += ['rangelength' => [floatval($parameters[0]), floatval($parameters[1])]];
                    $ruleName = 'digits';
                    $parameters = true;
                    break;
                case 'ip':
                    $ruleName = 'regex';
                    $parameters = '(((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)|([\da-fA-F]{1,4}:){6}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^::([\da-fA-F]{1,4}:){0,4}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:):([\da-fA-F]{1,4}:){0,3}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){2}:([\da-fA-F]{1,4}:){0,2}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){3}:([\da-fA-F]{1,4}:){0,1}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){4}:((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){7}[\da-fA-F]{1,4}$|^:((:[\da-fA-F]{1,4}){1,6}|:)$|^[\da-fA-F]{1,4}:((:[\da-fA-F]{1,4}){1,5}|:)$|^([\da-fA-F]{1,4}:){2}((:[\da-fA-F]{1,4}){1,4}|:)$|^([\da-fA-F]{1,4}:){3}((:[\da-fA-F]{1,4}){1,3}|:)$|^([\da-fA-F]{1,4}:){4}((:[\da-fA-F]{1,4}){1,2}|:)$|^([\da-fA-F]{1,4}:){5}:([\da-fA-F]{1,4})?$|^([\da-fA-F]{1,4}:){6}:)';
                    break;
                case 'ipv4':
                    $ruleName = 'regex';
                    $parameters = '((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)';
                    break;
                case 'ipv6':
                    $ruleName = 'regex';
                    $parameters = '([\da-fA-F]{1,4}:){6}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^::([\da-fA-F]{1,4}:){0,4}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:):([\da-fA-F]{1,4}:){0,3}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){2}:([\da-fA-F]{1,4}:){0,2}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){3}:([\da-fA-F]{1,4}:){0,1}((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){4}:((25[0-5]|2[0-4]\d|[01]?\d\d?)\\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){7}[\da-fA-F]{1,4}$|^:((:[\da-fA-F]{1,4}){1,6}|:)$|^[\da-fA-F]{1,4}:((:[\da-fA-F]{1,4}){1,5}|:)$|^([\da-fA-F]{1,4}:){2}((:[\da-fA-F]{1,4}){1,4}|:)$|^([\da-fA-F]{1,4}:){3}((:[\da-fA-F]{1,4}){1,3}|:)$|^([\da-fA-F]{1,4}:){4}((:[\da-fA-F]{1,4}){1,2}|:)$|^([\da-fA-F]{1,4}:){5}:([\da-fA-F]{1,4})?$|^([\da-fA-F]{1,4}:){6}:';
                    break;
                case 'boolean':
                    $ruleName = 'regex';
                    $parameters = '(true|false|1|0)';
                    break;
                case 'size':
                    $ruleName = $this->isNumeric() ? 'range' : 'rangelength';
                    $parameters = [floatval($parameters), floatval($parameters)];
                    break;
                /*case 'requiredwithoutall': //任意一个有值
                    $ruleName = 'require_from_group';
                    !is_array($parameters) && $parameters = [$parameters];
                    $parameters =  [1, implode(',', array_map(function($v) {return '[name="'.$v.'"]';}, $parameters))];
                    break;
                case 'requiredwithout': //任意一个有值
                    $ruleName = 'require_from_group';
                    !is_array($parameters) && $parameters = [$parameters];
                    $parameters =  [count($parameters) > 1 ? count($parameters) - 1 : 1, implode(',', array_map(function($v) {return '[name="'.$v.'"]';}, $parameters))];
                    break;*/
                case 'max':
                    $ruleName = $this->isNumeric() ? 'max' : 'maxlength';
                    $parameters = floatval($parameters);
                    break;
                case 'min':
                    $ruleName = $this->isNumeric() ? 'min' : 'minlength';
                    $parameters = floatval($parameters);
                    break;
                case 'between':
                    $ruleName = 'range';
                    $parameters = [floatval($parameters[0]), floatval($parameters[1])] ;
                    break;
                case 'confirmed': //改变attribute
                    $parameters = '[name="'.$attribute.'"]';
                    $attribute = $attribute.'_confirmation';
                    !isset($rules[$attribute]) && $rules[$attribute] = [];
                case 'same':
                    $ruleName = 'equalTo';
                    break;
                case 'mimes':
                    $ruleName = 'extension';
                    $parameters = implode('|', $parameters);
                    break;
                case 'accepted':
                    $ruleName = 'required';
                    break;
                case 'dateformat':
                    $ruleName = 'date';
                    break;
                case 'integer':
                    $ruleName = 'digits';
                    break;
                case 'numeric':
                    $ruleName = 'number';
                    break;
                //case 'date':
                //	$ruleName = 'regex';
                //	$parameters = '(1[1-9]\d{2}|20\d{2}|2100)-([0-1]?[1-9]|1[0-2])-([0-2]?[1-9]|3[0-1]|[1-2]0)(\s([0-1]?\d|2[0-3]):([0-5]?\d)(:([0-5]?\d))?)?';
                //	break;
                case 'distinct':
                case 'nullable':
                case 'json':
                case 'before':
                case 'different':
                case 'exists':
                case 'image':
                case 'array':
                case 'requiredif':
                case 'requiredunless':
                case 'requiredwith':
                case 'requiredwithall':
                case 'string':
                case 'timezone':
                case 'unique':
                case 'requiredwithout':
                case 'requiredwithoutall':
                    continue 2;
                case 'email':
                case 'activeurl':
                case 'url':
                case 'regex':
                case 'required':
                case 'phone':
                case 'idcard':
                case 'notzero':
                case 'timestamp':
                case 'timetick':
                    break;
                default:
                    continue 2;
            }
            $rules[$attribute] +=  [$ruleName => $parameters];
        }
        foreach($rules as $key => $value)
            if (empty($value))
                unset($rules[$key]);

        return $rules;
    }

    public function parse(?array $input = null, ?array $extraData = null): void
    {
        $this->originalRules = [];
        $this->computedRules = [];
        $rawRules = $this->rawRules;

        if (empty($rawRules))
            return;

        if (!is_array($rawRules))
            $rawRules = explode('|', $rawRules);

        foreach($rawRules as $rule)
        {
            if ($rule instanceof \Closure)
                $rule = $this->callRule($rule, $input, $extraData);

            [$ruleName, $parameters] = ValidationRuleParser::parse($rule);

            $this->originalRules[] = $rule;
            if (is_object($ruleName)) {
                $this->computedRules[get_class($ruleName)] = $ruleName;
            } else {
                $this->computedRules[$ruleName] = $parameters;
            }
        }
    }

    protected function callRule(\Closure $callback, ?array $input = null, ?array $extraData = null) {
        return call_user_func_array($callback, [$this, $input, $extraData]);
    }

    public function isNumeric(): bool
    {
        foreach(['Digits', 'DigitsBetween', 'Numeric', 'Integer'] as $pattern)
        {
            if (array_key_exists($pattern, $this->computedRules()))
                return true;
        }

        return false;
    }

    public function isArray(): bool
    {
        return array_key_exists('Array', $this->computedRules());
    }

}
