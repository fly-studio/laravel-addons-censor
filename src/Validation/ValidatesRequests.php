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

    public function censorScripts($censorKey, $attributes, ?array $replacement = null)
    {
        $censor = $this->getCensorFactory()->make($censorKey, $attributes, $replacement);

        return $censor->js();
    }

    /**
     * censor a
     *
     * @param  Request $request
     * @param  string  $censorKey
     * @param  array  $attributes
     * @param  Model|null $model
     * @return array|\Throwable
     */
    public function censor($request, string $censorKey, array $attributes, ?array $replacement = null): ?array {
        $data = null;

        if ($request instanceof Request) {
            $data = $request->all();
        } else if ($request instanceof Arrayable) {
            $data = $request->toArray();
        } else if (is_array($request)) {
            $data = $request;
        } else {
            throw new RuntimeException('The parameter#0 must be Array or Request.');
        }

        $censor = $this->getCensorFactory()->make($censorKey, $attributes, $replacement)->data($data);
        $validator = $censor->validator();

        return $validator->fails() ? $this->throwValidationException($data, $validator) : $censor->validData();
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
