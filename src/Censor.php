<?php

namespace Addons\Censor;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Addons\Censor\Ruling\Ruler;
use Addons\Censor\Validation\ValidatorEx;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Factory as FactoryInstance;

class Censor {

    protected ?array $data;
    protected string $censorKey;
    protected ?array $attributes;
    protected ?array $replacement;
    protected ?array $validations;

    public function __construct(Ruler $ruler, string $censorKey, array $attributes, ?array $replacement = null, ?string $locale = null)
    {
        $this->censorKey = $censorKey;
        $this->replacement = $replacement;
        $this->validations = $ruler->get($censorKey, $attributes, $replacement, $locale);
        $this->attributes = array_keys($this->validations);
    }

    public function validData(): array
    {
        return Arr::only($this->parseData($this->data), $this->attributes);
    }

    public function data(?array $data = null): static|array|null
    {
        if (is_null($data))
            return $this->data;

        $this->data = $data;

        return $this;
    }

    protected function parseData(array $data): array
    {
        $newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }

            // If the data key contains a dot, we will replace it with another character
            // sequence so it doesn't interfere with dot processing when working with
            // array based validation rules and array_dot later in the validations.
            if (Str::contains($key, '.')) {
                $newData[str_replace('.', '->', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
    }

    public function attributes(): ?array
    {
        return $this->attributes;
    }

    public function censorKey(): string
    {
        return $this->censorKey;
    }

    public function replacement(): ?array
    {
        return $this->replacement;
    }

    public function messagesWithDot(): array
    {
        return Arr::dot($this->messages());
    }

    public function messages(): array
    {
        $messages = [];

        foreach($this->validations as $attribute => $line)
        {
            if (!isset($line['messages']))
                continue;

            $messages[$attribute] = $line['messages'];
        }

        return $messages;
    }

    public function messagesWithTranslate(): array
    {
        $validator = $this->validator();
        $messages = [];

        foreach($this->validations as $attribute => $line)
        {
            if (!isset($line['messages']))
                continue;

            foreach($line['messages'] as $rule => $text) {
                $messages[$attribute][$rule] = $validator->makeReplacements($text, $line['name'], $rule, $line['rules']->ruleParameters($rule) ?? []);
            }
        }

        return $messages;
    }

    public function names(): array
    {
        $names = [];

        foreach($this->validations as $attribute => $line)
        {
            if (!isset($line['name']))
                continue;
            $names[$attribute] = $line['name'];
        }

        return $names;
    }

    public function originalRules(): array
    {
        $rules = [];

        foreach($this->validations as $attribute => $line)
            $rules[$attribute] = $line['rules']->originalRules();

        return $rules;
    }

    public function rules(): array
    {
        $rules = [];

        foreach($this->validations as $attribute => $line) {
            $rules[$attribute] = $line['rules']->rules();
        }

        return $rules;
    }

    public function jsRules(): array
    {
        $rules = [];

        foreach($this->validations as $attribute => $line) {
            $rules = array_merge_recursive($rules, $line['rules']->js());
        }

        return $rules;
    }

    public function validator(): ValidatorEx
    {
        return $this->getValidationFactory()->make($this->data() ?? [], $this->originalRules(), $this->messagesWithDot(), $this->names());
    }

    public function js(): array
    {
        return [
            'rules' => $this->jsRules(),
            'messages' => $this->messagesWithTranslate(),
        ];
    }

    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Validation\Factory
     */
    protected function getValidationFactory(): FactoryInstance
    {
        return app(Factory::class);
    }

}
