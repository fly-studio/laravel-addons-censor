<?php

namespace Addons\Censor\Validation;

use RuntimeException;
use Addons\Censor\Factory;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Arrayable;
use Addons\Censor\Exceptions\CensorException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests as BaseValidatesRequests;

trait ValidatesRequests
{
    use BaseValidatesRequests;

    public function censorScripts($censorKey, $attributes, ?array $extraData = null)
    {
        $censor = $this->getCensorFactory()->make($censorKey, $attributes)
            ->extraData($extraData)
            ->build();

        return $censor->js();
    }

    /**
     * censor a
     *
     * @param  Request $request
     * @param  string  $censorKey
     * @param  array  $attributes
     * @param  ?array $extraData
     * @return array|\Throwable
     */
    public function censor($request, string $censorKey, array $attributes, ?array $extraData = null): ?array {
        $input = null;

        if ($request instanceof Request) {
            $input = $request->all();
        } else if ($request instanceof Arrayable) {
            $input = $request->toArray();
        } else if (is_array($request)) {
            $input = $request;
        } else {
            throw new RuntimeException('The parameter#0 must be Array or Request.');
        }

        $censor = $this->getCensorFactory()->make($censorKey, $attributes)
            ->input($input)
            ->extraData($extraData)
            ->build();
        $validator = $censor->validator();

        return $validator->fails() ? $this->throwValidationException($input, $validator) : $censor->output();
    }

    /**
     * Throw the failed validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Addons\Censor\Exceptions\CensorException
     */
    protected function throwValidationException(array $data, $validator)
    {
        throw new CensorException($data, $validator);
    }

    /**
     * Get a censor factory instance.
     *
     * @return \Addons\Censor\Factory
     */
    protected function getCensorFactory(): Factory
    {
        return app(Factory::class);
    }

}
