<?php

namespace Addons\Censor;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Addons\Censor\Validation\ValidationLoader;
use Addons\Censor\Validation\ValidatorEx;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Factory as FactoryInstance;

class Censor {

    protected CensorLoader $loader;
    protected ?array $input;
    protected string $censorKey;
    protected ?string $locale;
    protected ?array $attributes;
    protected ?array $extraData;
    protected ?array $validations;

    public function __construct(CensorLoader $loader, string $censorKey, array $attributes, ?array $extraData = null, ?string $locale = null)
    {
        $this->censorKey = $censorKey;
        $this->attributes = $attributes;
        $this->extraData = $extraData;
        $this->locale = $locale;
        $this->loader = $loader;
    }

    public function build(): static {
        if (!empty($this->validations)) {
            return $this;
        }

        $validations = $this->loader->get($this->censorKey, $this->attributes, $this->locale);
        $this->attributes = array_keys($validations);
        $this->validations = $validations;

        foreach($validations as $validation) {
            $validation->parse($this->input(), $this->extraData());
        }

        return $this;
    }

    public function output(): array
    {
        return Arr::only($this->parseInput($this->input()), $this->attributes);
    }

    public function input(?array $input = null): static|array|null
    {
        if (func_num_args() == 0)
            return $this->input;

        $this->input = $input;
        return $this;
    }

    public function extraData(?array $extraData = null): array|static|null
    {
        if (func_num_args() == 0)
            return $this->extraData;

        $this->extraData = $extraData;
        return $this;
    }


    protected function parseInput(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseInput($value);
            }

            // If the data key contains a dot, we will replace it with another character
            // sequence so it doesn't interfere with dot processing when working with
            // array based validation rules and array_dot later in the validations.
            if (Str::contains($key, '.')) {
                $result[str_replace('.', '->', $key)] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public function attributes(): ?array
    {
        return $this->attributes;
    }

    public function censorKey(): string
    {
        return $this->censorKey;
    }


    public function messagesWithDot(): array
    {
        return Arr::dot($this->messages());
    }

    public function messages(): array
    {
        return array_map(fn($validation) => $validation->messages(), array_filter($this->validations, fn($validation) => !empty($validation->messages())));
    }

    public function translatedMessages(): array
    {
        $validator = $this->validator();
        $messages = [];

        foreach($this->validations as $validation)
        {
            if (empty($validation->messages()))
                continue;

            foreach($validation->messages() as $rule => $text) {
                $messages[$validation->attribute()][$rule] = $validator->makeReplacements($text, $validation->name(), $rule, $validation->ruleParameters($rule) ?? []);
            }
        }

        return $messages;
    }

    public function names(): array
    {
        return array_map(fn($validation) => $validation->name() ?: $validation->attribute(), $this->validations);
    }

    public function originalRules(): array
    {
        return array_map(fn($validation) => $validation->originalRules(), $this->validations);
    }

    public function computedRules(): array
    {
        return array_map(fn($validation) => $validation->computedRules(), $this->validations);
    }

    public function jsRules(): array
    {
        $rules = [];

        foreach($this->validations as $validation) {
            $rules = array_merge_recursive($rules, $validation->jsRules());
        }

        return $rules;
    }

    public function validator(): ValidatorEx
    {
        return $this->getValidationFactory()->make($this->input() ?? [], $this->originalRules(), $this->messagesWithDot(), $this->names());
    }

    public function js(): array
    {
        return [
            'rules' => $this->jsRules(),
            'messages' => $this->translatedMessages(),
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
