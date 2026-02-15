<?php

declare(strict_types=1);

namespace Gateway\Validation;

use Gateway\Validation\Exception\ValidationException;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

final class TransacaoValidator implements TransacaoValidatorInterface
{
    public function __construct(
        private ValidatorFactoryInterface $validator,
    ) {}

    public function validate(?string $cartaoNumero, mixed $valor, ?string $comerciante): void
    {
        $data = [
            'cartao_numero' => $cartaoNumero,
            'valor' => $valor,
            'comerciante' => $comerciante,
        ];

        $validator = $this->validator->make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw ValidationException::fromErrors($validator->errors()->all());
        }
    }

    private function rules(): array
    {
        return [
            'cartao_numero' => 'required|string|regex:/^\d{16}$/',
            'valor' => 'required|integer|min:1',
            'comerciante' => 'required|string|min:1',
        ];
    }

    private function messages(): array
    {
        return [
            'cartao_numero.required' => 'cartao_numero é obrigatório.',
            'cartao_numero.string' => 'cartao_numero deve ser uma string.',
            'cartao_numero.regex' => 'cartao_numero deve ter 16 dígitos numéricos.',
            'valor.required' => 'valor é obrigatório.',
            'valor.integer' => 'valor deve ser um inteiro (centavos).',
            'valor.min' => 'valor deve ser positivo (centavos).',
            'comerciante.required' => 'comerciante é obrigatório.',
            'comerciante.string' => 'comerciante deve ser uma string.',
            'comerciante.min' => 'comerciante não pode ser vazio.',
        ];
    }
}
