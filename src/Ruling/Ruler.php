<?php

namespace Addons\Censor\Ruling;

use Illuminate\Support\Arr;
use Addons\Censor\File\Localer;
use Addons\Censor\Exceptions\RuleNotFoundException;

class Ruler extends Localer {

    /**
     * 读取censors目录，获得$censorFile中的所有validation
     */
    public function get(string $censorFile, array $ruleKeys, array $replacement = null, string $locale = null): array
    {
        //get all
        $validations = $this->getLine($censorFile, $locale);

        if (empty($validations))
            throw new RuleNotFoundException('[Censor] Censor KEY is not exists: ['. $censorFile. ']. You may create it.', $this, $censorFile);

        in_array('*', $ruleKeys) && $ruleKeys = array_keys($validations);

        $validations = Arr::only($validations, $ruleKeys);

        if (!empty($diff = array_diff($ruleKeys, array_keys($validations))))
            throw new RuleNotFoundException('[Censor] Rule keys are not exists: ['.implode(', ', $diff). '].', $this, $censorFile);

        foreach($validations as $attribute => &$v)
            $v['rules'] = $this->parseRules($attribute, $v['rules'], $replacement);

        return $validations;
    }

    private function parseRules(string $attribute, $rules, array $replacement = null): Rules
    {
        return new Rules($attribute, $rules, $replacement);
    }

}
