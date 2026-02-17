<?php

declare(strict_types=1);

namespace Gateway\Validation;

use Gateway\Validation\Exception\ValidationException;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;

final class EstornoValidator implements EstornoValidatorInterface
{
    public function __construct(
        private ValidatorFactoryInterface $validator,
    ) {}

    public function validate(?string $transacaoId): void
    {
        $data = [
            'transacao_id' => $transacaoId,
        ];

        $validator = $this->validator->make($data, $this->rules(), $this->messages());

        if ($validator->fails()) {
            throw ValidationException::fromErrors($validator->errors()->all());
        }
    }

    private function rules(): array
    {
        return [
            'transacao_id' => 'required|string|uuid',
        ];
    }

    private function messages(): array
    {
        return [
            'transacao_id.required' => 'transacao_id é obrigatório.',
            'transacao_id.string' => 'transacao_id deve ser uma string.',
            'transacao_id.uuid' => 'transacao_id deve ser um UUID válido.',
        ];
    }
}
