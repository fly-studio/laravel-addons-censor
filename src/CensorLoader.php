<?php

namespace Addons\Censor;

use Illuminate\Support\Arr;
use Addons\Censor\File\Localer;
use Addons\Censor\Validation\Validation;
use Addons\Censor\Exceptions\RuleNotFoundException;

class CensorLoader extends Localer {

    /**
     * 读取censors目录，获得$censorFile中的所有validation
     */
    public function get(string $censorFile, array $ruleKeys, string $locale = null, bool $fallback = true): array
    {
        //get all
        $validations = $this->getLine($censorFile, $locale, $fallback);

        if (empty($validations))
            throw new RuleNotFoundException('[Censor] Censor KEY is not exists: ['. $censorFile. ']. You may create it.', $this, $censorFile);

        in_array('*', $ruleKeys) && $ruleKeys = array_keys($validations);

        $validations = Arr::only($validations, $ruleKeys);

        if (!empty($diff = array_diff($ruleKeys, array_keys($validations))))
            throw new RuleNotFoundException('[Censor] Rule keys are not exists: ['.implode(', ', $diff). '].', $this, $censorFile);

        $result = [];
        foreach($validations as $attribute => $v) {
            $result[$attribute] = new Validation($attribute, $v['name'], $v['rules'] ?? [], $v['messages'] ?? []);
        }

        return $result;
    }

}
